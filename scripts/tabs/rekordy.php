<?php
// INIT
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";

$TABLE = "history_cron_padarovice";

// Přepínač: zobrazit horní trojici „rekordy pro dnešní datum"
const SHOW_HEADLINE = true;

// Kolik řádků mají žebříčky rekordů
const TOP_N = 10;

// Jedna DB connection
$conn = mysqli_connect($dbServer, $dbUzivatel, $dbHeslo, $dbDb);
if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }
mysqli_query($conn, "SET NAMES 'utf8mb4'");

/* ------------------------------------------------------------------
   Denní agregát – jediný průchod tabulkou. Všechny sekce níž (absolutní
   rekordy, rekordy dle dnů i měsíční přehledy) se počítají až v PHP nad
   tímhle polem, takže se historie nečte stokrát dokola.
   ------------------------------------------------------------------ */
$sql = "
  SELECT DATE(date_time)            AS d,
         MIN(temperature)           AS t_min,
         MAX(temperature)           AS t_max,
         MIN(temperature_apparent)  AS a_min,
         MAX(temperature_apparent)  AS a_max,
         MAX(dew_point)             AS dp_max,
         MIN(NULLIF(humidity,0))    AS h_min,
         MIN(NULLIF(pressure_QNH,0)) AS p_min,
         MAX(pressure_QNH)          AS p_max,
         AVG(wind_speed)            AS w_avg,
         MAX(wind_speed)            AS w_max,
         MAX(wind_gust)             AS g_max,
         MAX(exposure)              AS e_max,
         MAX(uvi)                   AS uvi_max,
         MAX(rain_rate)             AS rr_max,
         MAX(rain_event)            AS re_max
  FROM {$TABLE}
  GROUP BY DATE(date_time)";
$dny = [];
if ($res = mysqli_query($conn, $sql)) {
  while ($r = mysqli_fetch_assoc($res)) { $dny[] = $r; }
}

/* Srážkové úhrny se sčítají z přírůstků (viz denniUhrnySrazek v fce.php), ne
   z MAX(rain_daily/monthly/yearly) po obdobích — tam období převezme hodnotu
   toho předchozího, dokud ji nepřekoná. rain_event zůstává na MAX(), protože
   epizoda není vázaná na kalendářní hranici. */
$denniRain = denniUhrnySrazek($conn, $TABLE);
foreach ($dny as $i => $r) { $dny[$i]['rain'] = $denniRain[(string)$r['d']] ?? 0.0; }

// řady pro dlaždice rekordních týdnů / měsíců / roků
$tydny    = klouzaveTydenniUhrny($denniRain);
$mesRows  = [];
foreach (uhrnyPoMesicich($denniRain) as $ym => $mm) { $mesRows[] = ['d' => $ym . '-01', 'mm' => $mm]; }
$rokRows  = [];
foreach (uhrnyPoRocich($denniRain)   as $y  => $mm) { $rokRows[] = ['d' => $y . '-01-01', 'mm' => $mm]; }

/* Měsíční agregát – průměry se počítají ze všech měření v měsíci, ne
   z průměrů dní (dny mají různý počet vzorků). */
$sql = "
  SELECT DATE_FORMAT(date_time,'%Y-%m-01') AS ym,
         AVG(temperature)   AS t_avg
  FROM {$TABLE}
  GROUP BY YEAR(date_time), MONTH(date_time)";
$mesice = [];
if ($res = mysqli_query($conn, $sql)) {
  $mesicniRain = uhrnyPoMesicich($denniRain);
  while ($r = mysqli_fetch_assoc($res)) {
    $r['rain'] = $mesicniRain[substr((string)$r['ym'], 0, 7)] ?? 0.0;
    $mesice[] = $r;
  }
}

mysqli_close($conn);

if (!$dny) { echo "<p>" . e($lang['err_ajax_nodata']) . "</p>"; return; }

/* ================= pomocné funkce nad agregáty ================= */

/** TOP N řádků setříděných podle jednoho klíče; řádky bez hodnoty vynechá. */
function topN(array $rows, string $key, string $order = 'DESC', int $limit = TOP_N): array {
  $out = array_values(array_filter($rows, fn($r) => isset($r[$key]) && $r[$key] !== null && $r[$key] !== ''));
  usort($out, fn($x, $y) => $order === 'ASC'
    ? (float)$x[$key] <=> (float)$y[$key]
    : (float)$y[$key] <=> (float)$x[$key]);
  return array_slice($out, 0, $limit);
}

/** Jediný extrémní řádek podle klíče (nebo null, když data chybí). */
function extrem(array $rows, string $key, string $order = 'DESC'): ?array {
  return topN($rows, $key, $order, 1)[0] ?? null;
}

/** Řádky denního agregátu spadající do zadaného měsíce (1–12). */
function dnyMesice(array $dny, int $m): array {
  return array_values(array_filter($dny, fn($r) => (int)substr((string)$r['d'], 5, 2) === $m));
}

/** Řádky měsíčního agregátu pro zadaný měsíc (1–12) napříč roky. */
function mesiceMesice(array $mesice, int $m): array {
  return array_values(array_filter($mesice, fn($r) => (int)substr((string)$r['ym'], 5, 2) === $m));
}

/**
 * Nechá jen měsíce, které máme naměřené celé. Rozběhnutý měsíc a měsíc,
 * ve kterém měření teprve začalo, jinak zákonitě vyhrávají žebříčky
 * „nejsušší / min. úhrn" a kazí i průměrné teploty.
 *
 * Nekoukáme na počet dní (výpadek cronu uprostřed měsíce nevadí – měsíční
 * úhrn čteme z kumulativního počitadla rain_monthly), ale na to, jestli
 * máme měření na začátku i na konci měsíce.
 */
function uplneMesice(array $mesice, array $dny, int $okraj = 2): array {
  $pokryti = [];
  foreach ($dny as $r) {
    $pokryti[substr((string)$r['d'], 0, 7)][(int)substr((string)$r['d'], 8, 2)] = true;
  }

  $bezici = date('Y-m');

  return array_values(array_filter($mesice, function ($r) use ($pokryti, $bezici, $okraj) {
    $ym = substr((string)$r['ym'], 0, 7);
    if ($ym >= $bezici) return false;               // rozběhnutý (a teoreticky budoucí) měsíc

    $dnyMes = $pokryti[$ym] ?? [];
    if (!$dnyMes) return false;
    $pocetDnu = (int)date('t', strtotime((string)$r['ym']));

    $zacatek = false;
    for ($d = 1; $d <= $okraj; $d++) { if (isset($dnyMes[$d])) { $zacatek = true; break; } }

    $konec = false;
    for ($d = $pocetDnu; $d > $pocetDnu - $okraj; $d--) { if (isset($dnyMes[$d])) { $konec = true; break; } }

    return $zacatek && $konec;
  }));
}

// Žebříčky i měsíční přehledy počítáme jen z kompletních měsíců. Fallback na
// všechna data je tu proto, aby stránka nebyla prázdná hned po spuštění stanice.
$mesiceUplne = uplneMesice($mesice, $dny) ?: $mesice;

/* ================= formátovače hodnot ================= */

$fmtTeplota = fn($v) => jednotkaTeploty(round((float)$v, 1), $u, 1);
$fmtVitr    = fn($v) => jednotkaVitr((float)$v, $uv, true);
$fmtTlak    = fn($v) => jednotkaTlak((float)$v, $ut, true);
$fmtMm      = fn($v) => round((float)$v, 1) . " mm";
$fmtMmH     = fn($v) => round((float)$v, 1) . " mm/h";
$fmtProcent = fn($v) => round((float)$v) . " %";
$fmtOsvit   = fn($v) => round((float)$v) . " W/m<sup>2</sup>";
$fmtUvi     = fn($v) => (string)round((float)$v, 1);

/* ================= renderovací pomocníci ================= */

$nadpis = function (string $text) {
  echo "<table class='tabulkaDnes'>
          <tr><td class='radekDnes'>
            <span class='font25 zelena'>" . mb_strtoupper($text, 'UTF-8') . "</span>
          </td></tr>
        </table>";
};

/**
 * Jedna dlaždice absolutního rekordu (popis / hodnota / datum).
 * $napoveda = ['uvod' => string, 'body' => string[]] pověsí na popisek bublinu
 * u veličin, jejichž definice není z čísla patrná.
 */
$dlazdice = function (string $barva, string $popis, string $hodnota, string $datum, ?array $napoveda = null) {
  $label = rtrim($popis, ':');
  if ($napoveda) {
    $odrazky = '';
    foreach ($napoveda['body'] as $radek) { $odrazky .= "<li>" . e($radek) . "</li>"; }
    $label = "<div class='tooltip'>{$label}<span class='tooltiptext siroky'>"
           . e($napoveda['uvod']) . "<ul>{$odrazky}</ul></span></div>";
  }
  echo "<div class='dlazdice{$barva}'>
          <div class='dl-popis'>{$label}</div>
          <div class='dl-hodnota'>{$hodnota}</div>
          <div class='dl-datum'>{$datum}</div>
        </div>";
};

/** Dvousloupcová tabulka žebříčku. */
$tabulka = function (string $title, string $headLeft, string $headRight, array $rows, callable $left, callable $right) {
  echo "<table>
          <tr class='zelenyRadek'><td colspan='2' class='radek'>{$title}</td></tr>
          <tr class='modryRadek'>
            <td class='radek'>{$headLeft}</td>
            <td class='radek'>{$headRight}</td>
          </tr>";
  foreach ($rows as $r) {
    echo "<tr><td>" . $left($r) . "</td><td>" . $right($r) . "</td></tr>";
  }
  echo "</table>";
};

/** Žebříček nad denním agregátem. */
$tabDny = function (string $title, string $key, string $order, string $head, callable $fmt)
  use ($dny, $tabulka, $lang) {
    $tabulka($title, $lang['den'], $head, topN($dny, $key, $order, TOP_N),
      fn($r) => formatDnu($r['d']),
      fn($r) => $fmt($r[$key]));
  };

/** Žebříček nad měsíčním agregátem (jen kompletní měsíce). */
$tabMesice = function (string $title, string $key, string $order, string $head, callable $fmt)
  use ($mesiceUplne, $tabulka, $lang) {
    $tabulka($title, $lang['mesic'], $head, topN($mesiceUplne, $key, $order, TOP_N),
      fn($r) => substr((string)$r['ym'], 0, 7),
      fn($r) => $fmt($r[$key]));
  };

/* ============== HLAVIČKA: REKORDY PRO DNEŠNÍ DATUM (napříč roky) ============== */
if (SHOW_HEADLINE) {
  $dnesniDen = date('n-j');
  $dnesek = array_values(array_filter($dny, function ($r) use ($dnesniDen) {
    $ts = strtotime((string)$r['d']);
    return $ts !== false && date('n-j', $ts) === $dnesniDen;
  }));

  if ($dnesek) {
    $nadpis($lang['rekordydatum']);
    echo "<div class='rekordy-tridlazdice'>";

    foreach ([
      ['t_max', 'DESC', $lang['nejvyssiteplota'], $fmtTeplota, 'barvaRameckuTeploty'],
      ['t_min', 'ASC',  $lang['nejnizsiteplota'], $fmtTeplota, 'barvaRameckuTeploty'],
      ['rain',  'DESC', $lang['nejvyssiuhrn'],    $fmtMm,      'barvaRameckuSrazky'],
    ] as [$key, $order, $popis, $fmt, $barva]) {
      $r = extrem($dnesek, $key, $order);
      if (!$r) continue;
      $v = (float)$r[$key];
      $dlazdice($barva($v), $popis, $fmt($v), date('Y', strtotime((string)$r['d'])));
    }

    echo "</div>";
  }
}

/* ============== ABSOLUTNÍ REKORDY (20 dlaždic = 5 / 4 / 2 ve sloupci) ============== */
$nadpis($lang['absolutnirekordy']);
echo "<div class='rekordy-dlazdice'>";

/* [zdrojová řada, klíč, řazení, popisek, formátovač hodnoty, barvicí funkce, formát data]
   Formát data: 'den' = j. n. Y, 'tyden' = rozsah okna, 'mesic' = n. Y, 'rok' = Y. */
$absRekordy = [
  [$dny, 't_max',   'DESC', $lang['nejvyssiteplota'],       $fmtTeplota, 'barvaRameckuTeploty',     'den'],
  [$dny, 't_min',   'ASC',  $lang['nejnizsiteplota'],       $fmtTeplota, 'barvaRameckuTeploty',     'den'],
  [$dny, 't_min',   'DESC', $lang['nejvyssidennimin'],      $fmtTeplota, 'barvaRameckuTeploty',     'den'],
  [$dny, 't_max',   'ASC',  $lang['nejnizsidennimax'],      $fmtTeplota, 'barvaRameckuTeploty',     'den'],
  [$dny, 'a_max',   'DESC', $lang['nejvyssipocteplota'],    $fmtTeplota, 'barvaRameckuTeploty',     'den'],

  [$dny, 'a_min',   'ASC',  $lang['nejnizsipocteplota'],    $fmtTeplota, 'barvaRameckuTeploty',     'den'],
  [$dny, 'dp_max',  'DESC', $lang['nejvyssirosnybod'],      $fmtTeplota, 'barvaRameckuTeploty',     'den'],
  [$dny, 'h_min',   'ASC',  $lang['nejnizsivlhkost'],       $fmtProcent, 'barvaRameckuVlhkost',     'den'],
  [$dny, 'p_max',   'DESC', $lang['nejvyssitlak'],          $fmtTlak,    'barvaRameckuTlak',        'den'],
  [$dny, 'p_min',   'ASC',  $lang['nejnizsitlak'],          $fmtTlak,    'barvaRameckuTlak',        'den'],

  [$dny, 'w_max',   'DESC', $lang['nejrychlejsivitr'],      $fmtVitr,    'barvaRameckuVitr',        'den'],
  [$dny, 'g_max',   'DESC', $lang['nejprudsinaraz'],        $fmtVitr,    'barvaRameckuVitr',        'den'],
  [$dny, 'e_max',   'DESC', $lang['maxosvit'],              $fmtOsvit,   'barvaRameckuOsvit',       'den'],
  [$dny, 'uvi_max', 'DESC', $lang['maxuvi'],                $fmtUvi,     'barvaRameckuUV',          'den'],
  [$dny, 'rr_max',  'DESC', $lang['maxintenzitasrazek'],    $fmtMmH,     'barvaRameckuSrazky',      'den'],

  [$dny,     'rain',   'DESC', $lang['nejvyssiuhrn'],        $fmtMm, 'barvaRameckuSrazky',      'den'],
  [$dny,     're_max', 'DESC', $lang['nejvyssiuhrnepizody'], $fmtMm, 'barvaRameckuSrazky',      'den'],
  [$tydny,   'mm',     'DESC', $lang['nejvyssityuhrn'],      $fmtMm, 'barvaRameckuSrazkyMesic', 'tyden'],
  [$mesRows, 'mm',     'DESC', $lang['nejvyssimuhrn'],       $fmtMm, 'barvaRameckuSrazkyMesic', 'mesic'],
  [$rokRows, 'mm',     'DESC', $lang['nejvyssiruhrn'],       $fmtMm, 'barvaRameckuSrazkyRok',   'rok'],
];

/* Bubliny jen u veličin, které se nedají odvodit z čísla. Klíčem je sloupec —
   rr_max i re_max jsou v $absRekordy právě jednou, takže je to jednoznačné.
   Zdroj obou definic je manuál GoGEN ME 3900, str. CZ-13. */
$napovedy = [
  'rr_max' => ['uvod' => $lang['intenzita_co'],
               'body' => [$lang['intenzita_prumer'], $lang['intenzita_spicka']]],
  're_max' => ['uvod' => $lang['epizoda_co'],
               'body' => [$lang['epizoda_zacatek'], $lang['epizoda_konec'], $lang['epizoda_presah']]],
];

foreach ($absRekordy as [$rada, $key, $order, $popis, $fmt, $barva, $format]) {
  $r = extrem($rada, $key, $order);
  if (!$r) { echo "<div class='dlazdice'><div class='dl-popis'>" . rtrim($popis, ':') . "</div><div class='dl-hodnota'>&mdash;</div><div class='dl-datum'>&nbsp;</div></div>"; continue; }

  $v  = (float)$r[$key];
  $ts = strtotime((string)$r['d']);
  switch ($format) {
    case 'rok':   $datum = date('Y', $ts); break;
    case 'mesic': $datum = date('n. Y', $ts); break;
    // u týdne ukazujeme celé okno, ať je zřejmé, kterých sedm dní se sečetlo
    case 'tyden': $datum = date('j. n.', $ts - 6 * 86400) . ' – ' . formatDnu($r['d']); break;
    default:      $datum = formatDnu($r['d']);
  }

  $dlazdice($barva($v), $popis, $fmt($v), $datum, $napovedy[$key] ?? null);
}

echo "</div>";

/* ============== REKORDY DLE DNŮ (8 tabulek = 4 / 2 / 1 ve sloupci) ============== */
$nadpis($lang['rekordydlednu']);
echo "<div class='rekordy-grid'>";

$tabDny($lang['nejteplejsidny'],        't_max', 'DESC', $lang['teplota'],     $fmtTeplota);
$tabDny($lang['nejchladnejsidny'],      't_min', 'ASC',  $lang['teplota'],     $fmtTeplota);
$tabDny($lang['nejnizsimaxima'],        't_max', 'ASC',  $lang['teplota'],     $fmtTeplota);
$tabDny($lang['nejvyssiminima'],        't_min', 'DESC', $lang['teplota'],     $fmtTeplota);
$tabDny($lang['pocnejteplejsidny'],     'a_max', 'DESC', $lang['teplota'],     $fmtTeplota);
$tabDny($lang['pocnejchladnejsidny'],   'a_min', 'ASC',  $lang['teplota'],     $fmtTeplota);
$tabDny($lang['nejdestivejsidny'],      'rain',  'DESC', $lang['srazky'],      $fmtMm);
$tabDny($lang['nejvetrnejsidny'],       'w_avg', 'DESC', $lang['prumvitr'],    $fmtVitr);

echo "</div>";

/* ============== REKORDY DLE MĚSÍCŮ (4 tabulky = 4 / 2 / 1 ve sloupci) ==============
   Nejvyšší minimum / nejnižší maximum tady schválně nejsou: napříč všemi
   měsíci v roce by v nich byl vždycky jen červenec, resp. leden. Smysl
   dávají až v rámci jednoho kalendářního měsíce, takže jsou jako řádky
   v měsíčních přehledech níž. */
$nadpis($lang['rekordydlemesicu']);
echo "<p class='poznamka'>" . e($lang['jenuplnemesice']) . "</p>";
echo "<div class='rekordy-grid'>";

$tabMesice($lang['nejteplejsimesice'],       't_avg', 'DESC', $lang['prumteplota'], $fmtTeplota);
$tabMesice($lang['nejchladnejsimesice'],     't_avg', 'ASC',  $lang['prumteplota'], $fmtTeplota);
$tabMesice($lang['nejdestivejsimesice'],     'rain',  'DESC', $lang['srazky'],      $fmtMm);
$tabMesice($lang['nejsussimesice'],          'rain',  'ASC',  $lang['srazky'],      $fmtMm);

echo "</div>";

/* ============== MĚSÍČNÍ PŘEHLEDY (1–12, 3 / 2 / 1 ve sloupci) ============== */
echo "<div class='rekordy-grid rekordy-grid--tretiny'>";

for ($i = 1; $i <= 12; $i++) {
  $dnyM = dnyMesice($dny, $i);
  $mesM = mesiceMesice($mesiceUplne, $i);
  $nazevMesice = $lang["mesic{$i}"] ?? (string)$i;

  echo "<table>
          <tr class='zelenyRadek'><td colspan='2' class='radek'>{$nazevMesice}</td></tr>
          <tr class='modryRadek'>
            <td class='radek'>{$lang['velicina']}</td>
            <td class='radek'>{$lang['datum']}</td>
          </tr>";

  // rekordy odvozené z jednotlivých dní – ukazujeme konkrétní datum
  foreach ([
    ['t_max',  'DESC', $lang['maxteplota'],           $fmtTeplota],
    ['t_min',  'ASC',  $lang['minteplota'],           $fmtTeplota],
    ['t_min',  'DESC', $lang['nejvyssiminimum'],      $fmtTeplota],
    ['t_max',  'ASC',  $lang['nejnizsimaximum'],      $fmtTeplota],
    ['rain',   'DESC', $lang['nejvyssidennisrazky'],  $fmtMm],
  ] as [$key, $order, $popis, $fmt]) {
    $r = extrem($dnyM, $key, $order);
    if (!$r) continue;
    echo "<tr><td>{$popis}</td><td>" . $fmt($r[$key]) . " (" . formatDnu($r['d']) . ")</td></tr>";
  }

  // rekordy odvozené z celého měsíce – ukazujeme rok
  foreach ([
    ['t_avg', 'DESC', $lang['nejvyssiprumteplota'],   $fmtTeplota],
    ['t_avg', 'ASC',  $lang['nejnizsiprumteplota'],   $fmtTeplota],
    ['rain',  'DESC', $lang['nejvyssimesicnisrazky'], $fmtMm],
    ['rain',  'ASC',  $lang['nejnizsimesicnisrazky'], $fmtMm],
  ] as [$key, $order, $popis, $fmt]) {
    $r = extrem($mesM, $key, $order);
    if (!$r) continue;
    echo "<tr><td>{$popis}</td><td>" . $fmt($r[$key]) . " (" . substr((string)$r['ym'], 0, 4) . ")</td></tr>";
  }

  echo "</table>";
}

echo "</div>";

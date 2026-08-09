<?php
// INIT
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";

$TABLE = "history_cron_padarovice";

// �� MIN/MAX aktu�ln� m�s�c ���������������������������������������������
$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { exit("Nejaky problem s DB: " . mysqli_connect_error()); }

// Srazkove uhrny se scitaji z prirustku, ne z MAX(rain_monthly)/MAX(rain_yearly) —
// tam by rozjety mesic (resp. rok) prebral hodnotu toho predchoziho, dokud ji
// neprekona. Viz denniUhrnySrazek v fce.php.
$denniRain   = denniUhrnySrazek($conn, $TABLE);
$srazkymesic = uhrnyPoMesicich($denniRain)[date('Y-m')] ?? 0.0;
$srazkyrok   = uhrnyPoRocich($denniRain)[date('Y')]     ?? 0.0;

$sqlM = "
  SELECT MAX(temperature) AS maxteplotamesic,
         MIN(temperature) AS minteplotamesic
  FROM {$TABLE}
  WHERE YEAR(date_time) = YEAR(CURDATE())
    AND MONTH(date_time) = MONTH(CURDATE())";
$resM = mysqli_query($conn, $sqlM);
list($maxteplotamesic,$minteplotamesic) = [null,null];
if ($resM && mysqli_num_rows($resM) > 0) {
  $row = mysqli_fetch_assoc($resM);
  $maxteplotamesic = (float)$row['maxteplotamesic'];
  $minteplotamesic = (float)$row['minteplotamesic'];
}

// �� MIN/MAX aktu�ln� rok �����������������������������������������������
$sqlR = "
  SELECT MAX(temperature) AS maxteplotarok,
         MIN(temperature) AS minteplotarok
  FROM {$TABLE}
  WHERE YEAR(date_time) = YEAR(CURDATE())";
$resR = mysqli_query($conn, $sqlR);
list($maxteplotarok,$minteplotarok) = [null,null];
if ($resR && mysqli_num_rows($resR) > 0) {
  $row = mysqli_fetch_assoc($resR);
  $maxteplotarok = (float)$row['maxteplotarok'];
  $minteplotarok = (float)$row['minteplotarok'];
}

// Srazkove normaly (WMO 1991-2020) z DB:
// mesicni = normal aktualniho mesice (month = 1..12), rocni = radek se souhrnem (month = 0)
$normMesic = null; $normRok = null;
$sqlN = "
  SELECT
    (SELECT normal19912020 FROM precipitation_normals WHERE month = MONTH(CURDATE())) AS norm_mesic,
    (SELECT normal19912020 FROM precipitation_normals WHERE month = 0)                AS norm_rok";
$resN = mysqli_query($conn, $sqlN);
if ($resN && ($row = mysqli_fetch_assoc($resN))) {
  $normMesic = is_null($row['norm_mesic']) ? null : (int)round((float)$row['norm_mesic']);
  $normRok   = is_null($row['norm_rok'])   ? null : (int)round((float)$row['norm_rok']);
}
mysqli_close($conn);

// --- 30 dn�: teploty + sr�ky ---
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['graf30dniteplota'],'UTF-8')."</span>
      </td></tr></table>";

echo "<div class='graf' id='graf-30-dni'>";
require __DIR__ . '/../grafy/dlouhodoby-vyvoj/30-dni.php';
echo "</div>";

if ($minteplotamesic !== null && $maxteplotamesic !== null) {
  echo "
  <div class='karty-wrap karty-wrap--tri'>
    <div class='kartapodgrafy ". barvaRameckuTeploty($minteplotamesic) ."'>
      <div class='popis'>{$lang['minmesic']}</div>
      <div class='aktuamens'>". jednotkaTeploty($minteplotamesic, $u, 1) ."</div>
    </div>
    <div class='kartapodgrafy ". barvaRameckuTeploty($maxteplotamesic) ."'>
      <div class='popis'>{$lang['maxmesic']}</div>
      <div class='aktuamens'>". jednotkaTeploty($maxteplotamesic, $u, 1) ."</div>
    </div>
    <div class='kartapodgrafy ". barvaRameckuSrazkyMesic($srazkymesic) ."'>
      <div class='popis'>{$lang['uhrnsrazekmesic']}</div>
      <div class='aktuamens'>". $srazkymesic ." mm" . ($normMesic !== null ? " <span class='normal'>({$lang['normalzkr']} {$normMesic} mm)</span>" : "") . "</div>
    </div>
  </div>";
}

// --- 3 roky: m?s�?n� hodnoty ---
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['graf3rokyteplota'],'UTF-8')."</span>
      </td></tr></table>";

echo "<div class='graf' id='graf-3-roky'>";
require __DIR__ . '/../grafy/dlouhodoby-vyvoj/3-roky.php';
echo "</div>";

if ($minteplotarok !== null && $maxteplotarok !== null) {
  echo "
  <div class='karty-wrap karty-wrap--tri'>
    <div class='kartapodgrafy ". barvaRameckuTeploty($minteplotarok) ."'>
      <div class='popis'>{$lang['minrok']}</div>
      <div class='aktuamens'>". jednotkaTeploty($minteplotarok, $u, 1) ."</div>
    </div>
    <div class='kartapodgrafy ". barvaRameckuTeploty($maxteplotarok) ."'>
      <div class='popis'>{$lang['maxrok']}</div>
      <div class='aktuamens'>". jednotkaTeploty($maxteplotarok, $u, 1) ."</div>
    </div>
    <div class='kartapodgrafy ". barvaRameckuSrazkyRok($srazkyrok) ."'>
      <div class='popis'>{$lang['uhrnsrazekrok']}</div>
      <div class='aktuamens'>". $srazkyrok ." mm" . ($normRok !== null ? " <span class='normal'>({$lang['normalzkr']} {$normRok} mm/{$lang['rokzkr']})</span>" : "") . "</div>
    </div>
  </div>";
}

// --- roky: ro?n� hodnoty ---
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['grafrokyhodnoty'],'UTF-8')."</span>
      </td></tr></table>";

echo "<div class='graf' id='graf-roky'>";
require __DIR__ . '/../grafy/dlouhodoby-vyvoj/roky.php';
echo "</div>";

<?php
// INIT
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";

$TABLE = "history_cron_padarovice";

// ——— Pøepínaè: zobrazit horní trojici „rekordy dne“ (pokud už je máš jinde, nastav false)
const SHOW_HEADLINE = true;

// Jedna DB connection
$conn = mysqli_connect($dbServer, $dbUzivatel, $dbHeslo, $dbDb);
if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }

// ============== HLAVIÈKA: REKORDY PRO DNEŠNÍ DATUM (napøíè roky) ==============
if (SHOW_HEADLINE) {
  // nejnižší teplota v tento den (napøíè roky)
  $sql = "
    SELECT temperature, YEAR(date_time) AS rok
    FROM {$TABLE}
    WHERE DAY(date_time) = ".date("j")." AND MONTH(date_time) = ".date("n")."
    ORDER BY temperature ASC
    LIMIT 1";
  $res = mysqli_query($conn, $sql);
  if ($res && mysqli_num_rows($res) > 0) {
    $t = mysqli_fetch_assoc($res);
    $minteplota = (float)$t['temperature'];
    $rokminteplota = (int)$t['rok'];
  } else { $minteplota = null; $rokminteplota = null; }

  // nejvyšší teplota v tento den (napøíè roky)
  $sql = "
    SELECT temperature, YEAR(date_time) AS rok
    FROM {$TABLE}
    WHERE DAY(date_time) = ".date("j")." AND MONTH(date_time) = ".date("n")."
    ORDER BY temperature DESC
    LIMIT 1";
  $res = mysqli_query($conn, $sql);
  if ($res && mysqli_num_rows($res) > 0) {
    $t = mysqli_fetch_assoc($res);
    $maxteplota = (float)$t['temperature'];
    $rokmaxteplota = (int)$t['rok'];
  } else { $maxteplota = null; $rokmaxteplota = null; }

  // nejvyšší denní srážky v tento den (napøíè roky) — z kumulativy bereme MAX(rain_daily) po dni
  $sql = "
    SELECT YEAR(d) AS rok, MAX(max_rain_daily) AS srazky
    FROM (
      SELECT DATE(date_time) AS d, MAX(rain_daily) AS max_rain_daily
      FROM {$TABLE}
      WHERE DAY(date_time) = ".date("j")." AND MONTH(date_time) = ".date("n")."
      GROUP BY DATE(date_time)
    ) x
    GROUP BY YEAR(d)
    ORDER BY srazky DESC
    LIMIT 1";
  $res = mysqli_query($conn, $sql);
  if ($res && mysqli_num_rows($res) > 0) {
    $t = mysqli_fetch_assoc($res);
    $maxsrazky = round((float)$t['srazky'], 1);
    $rokmaxsrazky = (int)$t['rok'];
  } else { $maxsrazky = null; $rokmaxsrazky = null; }

  echo "<table class='tabulkaDnes'>
          <tr><td class='radekDnes'>
            <span class='font25 zelena'>".mb_strtoupper($lang['rekordydatum'],'UTF-8')."</span>
          </td></tr>
        </table>

        <div class='container'><div class='row' style='width:98%;'>";

  // boxy (render jen když máme data)
  if ($maxteplota !== null) {
    echo "<div class='col-md-4 trisloupce'>
      <div class='aktualnetretinka".barvaRameckuTeploty($maxteplota)."'>
        <div class='aktualneOdskok'>
          {$lang['nejvyssiteplota']}<br>
          <font class='aktuamens'>".jednotkaTeploty($maxteplota,$u,1)." ({$rokmaxteplota})</font>
        </div>
      </div>
    </div>";
  }

  if ($minteplota !== null) {
    echo "<div class='col-md-4 trisloupce'>
      <div class='aktualnetretinka".barvaRameckuTeploty($minteplota)."'>
        <div class='aktualneOdskok'>
          {$lang['nejnizsiteplota']}<br>
          <font class='aktuamens'>".jednotkaTeploty($minteplota,$u,1)." ({$rokminteplota})</font>
        </div>
      </div>
    </div>";
  }

  if ($maxsrazky !== null) {
    echo "<div class='col-md-4 trisloupce'>
      <div class='aktualnetretinka".barvaRameckuSrazky($maxsrazky)."'>
        <div class='aktualneOdskok'>
          {$lang['nejvyssiuhrn']}<br>
          <span class='aktuamens'>{$maxsrazky} mm ({$rokmaxsrazky})</span>
        </div>
      </div>
    </div>";
  }

  echo "</div></div>";
}

// ============== REKORDY DLE DNÙ ==============
echo "<table class='tabulkaDnes'>
        <tr><td class='radekDnes'>
          <span class='font25 zelena'><br>".mb_strtoupper($lang['rekordydlednu'],'UTF-8')."</span>
        </td></tr>
      </table>";

/**
 * Helper pro tabulky rekordù (dvousloupcová tabulka s datem a hodnotou)
 */
function renderRekordTable($title, $headRight, $rows, $valueFormatter) {
  echo "<table class='rekordyctvrtina'>
          <tr class='zelenyRadek'><td colspan='2' class='radek'>{$title}</td></tr>
          <tr class='modryRadek'>
            <td class='radek'>".e($GLOBALS['lang']['den'])."</td>
            <td class='radek'>".$headRight."</td>
          </tr>";
  foreach ($rows as $r) {
    echo "<tr><td>".formatDnu($r['d'])."</td><td>".$valueFormatter($r['v'])."</td></tr>";
  }
  echo "</table>";
}

// 1) Nej-teplejší dny (MAX teploty po dnech, TOP 10)
$sql = "
  SELECT d, MAX_t AS v FROM (
    SELECT DATE(date_time) AS d, MAX(temperature) AS MAX_t
    FROM {$TABLE}
    GROUP BY DATE(date_time)
  ) z
  ORDER BY v DESC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[]=['d'=>$r['d'],'v'=>(float)$r['v']]; }
renderRekordTable($lang['nejteplejsidny'], $lang['teplota'], $rows, fn($v)=>jednotkaTeploty($v,$GLOBALS['u'],1));

// 2) Nej-chladnìjší dny (MIN teploty po dnech, TOP 10)
$sql = "
  SELECT d, MIN_t AS v FROM (
    SELECT DATE(date_time) AS d, MIN(temperature) AS MIN_t
    FROM {$TABLE}
    GROUP BY DATE(date_time)
  ) z
  ORDER BY v ASC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[]=['d'=>$r['d'],'v'=>(float)$r['v']]; }
renderRekordTable($lang['nejchladnejsidny'], $lang['teplota'], $rows, fn($v)=>jednotkaTeploty($v,$GLOBALS['u'],1));

// 3) Nejnižší denní maxima (MAX teploty po dnech, setøídìno vzestupnì)
$sql = "
  SELECT d, MAX_t AS v FROM (
    SELECT DATE(date_time) AS d, MAX(temperature) AS MAX_t
    FROM {$TABLE}
    GROUP BY DATE(date_time)
  ) z
  ORDER BY v ASC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[]=['d'=>$r['d'],'v'=>(float)$r['v']]; }
renderRekordTable($lang['nejnizsimaxima'], $lang['teplota'], $rows, fn($v)=>jednotkaTeploty($v,$GLOBALS['u'],1));

// 4) Nejvyšší denní minima (MIN teploty po dnech, setøídìno sestupnì)
$sql = "
  SELECT d, MIN_t AS v FROM (
    SELECT DATE(date_time) AS d, MIN(temperature) AS MIN_t
    FROM {$TABLE}
    GROUP BY DATE(date_time)
  ) z
  ORDER BY v DESC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[]=['d'=>$r['d'],'v'=>(float)$r['v']]; }
renderRekordTable($lang['nejvyssiminima'], $lang['teplota'], $rows, fn($v)=>jednotkaTeploty($v,$GLOBALS['u'],1));

// 5) Pocitovì nej-teplejší dny (MAX apparent po dnech)
$sql = "
  SELECT d, v FROM (
    SELECT DATE(date_time) AS d, MAX(temperature_apparent) AS v
    FROM {$TABLE}
    GROUP BY DATE(date_time)
  ) z
  ORDER BY v DESC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[]=['d'=>$r['d'],'v'=>(float)$r['v']]; }
renderRekordTable($lang['pocnejteplejsidny'], $lang['teplota'], $rows, fn($v)=>jednotkaTeploty($v,$GLOBALS['u'],1));

// 6) Pocitovì nej-chladnìjší dny (MIN apparent po dnech)
$sql = "
  SELECT d, v FROM (
    SELECT DATE(date_time) AS d, MIN(temperature_apparent) AS v
    FROM {$TABLE}
    GROUP BY DATE(date_time)
  ) z
  ORDER BY v ASC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[]=['d'=>$r['d'],'v'=>(float)$r['v']]; }
renderRekordTable($lang['pocnejchladnejsidny'], $lang['teplota'], $rows, fn($v)=>jednotkaTeploty($v,$GLOBALS['u'],1));

// 7) Nejdeštivìjší dny — z kumulativy denního úhrnu (MAX(rain_daily) po dnech)
$sql = "
  SELECT d, v FROM (
    SELECT DATE(date_time) AS d, MAX(rain_daily) AS v
    FROM {$TABLE}
    GROUP BY DATE(date_time)
  ) z
  ORDER BY v DESC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[]=['d'=>$r['d'],'v'=>round((float)$r['v'],1)]; }
renderRekordTable($lang['nejdestivejsidny'], $lang['srazky'], $rows, fn($v)=>$v." mm");

// 8) Nejvìtrnìjší dny — prùmìrná rychlost vìtru po dnech
$sql = "
  SELECT d, v FROM (
    SELECT DATE(date_time) AS d, AVG(wind_speed) AS v
    FROM {$TABLE}
    GROUP BY DATE(date_time)
  ) z
  ORDER BY v DESC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[]=['d'=>$r['d'],'v'=>round((float)$r['v'],1)]; }
renderRekordTable($lang['nejvetrnejsidny'], $lang['prumvitr'], $rows, fn($v)=>$v." m/s");

// ============== REKORDY DLE MÌSÍCÙ ==============
echo "<table class='tabulkaDnes'>
        <tr><td class='radekDnes'>
          <span class='font25 zelena'><br>".mb_strtoupper($lang['rekordydlemesicu'],'UTF-8')."</span>
        </td></tr>
      </table>";

function renderRekordTableMesic($title, $headRight, $rows) {
  echo "<table class='rekordyctvrtina'>
          <tr class='zelenyRadek'><td colspan='2' class='radek'>{$title}</td></tr>
          <tr class='modryRadek'>
            <td class='radek'>".e($GLOBALS['lang']['mesic'])."</td>
            <td class='radek'>{$headRight}</td>
          </tr>";
  foreach ($rows as $r) {
    echo "<tr><td>".substr($r['ym'],0,7)."</td><td>{$r['val']}</td></tr>";
  }
  echo "</table>";
}

// Nej-teplejší mìsíce (AVG teploty po mìsících, top 10)
$sql = "
  SELECT ym, ROUND(avg_t,1) AS v FROM (
    SELECT DATE_FORMAT(date_time,'%Y-%m-01') AS ym, AVG(temperature) AS avg_t
    FROM {$TABLE}
    GROUP BY YEAR(date_time), MONTH(date_time)
  ) z
  ORDER BY v DESC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) {
  $rows[] = ['ym'=>$r['v']? $r['ym']:$r['ym'], 'val'=>jednotkaTeploty((float)$r['v'],$u,1)];
}
renderRekordTableMesic($lang['nejteplejsimesice'], $lang['prumteplota'], $rows);

// Nej-chladnìjší mìsíce
$sql = "
  SELECT ym, ROUND(avg_t,1) AS v FROM (
    SELECT DATE_FORMAT(date_time,'%Y-%m-01') AS ym, AVG(temperature) AS avg_t
    FROM {$TABLE}
    GROUP BY YEAR(date_time), MONTH(date_time)
  ) z
  ORDER BY v ASC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) {
  $rows[] = ['ym'=>$r['ym'], 'val'=>jednotkaTeploty((float)$r['v'],$u,1)];
}
renderRekordTableMesic($lang['nejchladnejsimesice'], $lang['prumteplota'], $rows);

// Nejdeštivìjší mìsíce — z kumulativy mìsíèního úhrnu (MAX(rain_monthly) po mìsících)
$sql = "
  SELECT ym, v FROM (
    SELECT DATE_FORMAT(date_time,'%Y-%m-01') AS ym, MAX(rain_monthly) AS v
    FROM {$TABLE}
    GROUP BY YEAR(date_time), MONTH(date_time)
  ) z
  ORDER BY v DESC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[] = ['ym'=>$r['ym'], 'val'=>round((float)$r['v'],1)." mm"]; }
renderRekordTableMesic($lang['nejdestivejsimesice'], $lang['srazky'], $rows);

// Nejsušší mìsíce
$sql = "
  SELECT ym, v FROM (
    SELECT DATE_FORMAT(date_time,'%Y-%m-01') AS ym, MAX(rain_monthly) AS v
    FROM {$TABLE}
    GROUP BY YEAR(date_time), MONTH(date_time)
  ) z
  ORDER BY v ASC
  LIMIT 10";
$res = mysqli_query($conn,$sql);
$rows = [];
while($res && $r=mysqli_fetch_assoc($res)) { $rows[] = ['ym'=>$r['ym'], 'val'=>round((float)$r['v'],1)." mm"]; }
renderRekordTableMesic($lang['nejsussimesice'], $lang['srazky'], $rows);

// ============== MÌSÍÈNÍ PØEHLEDY (1–12) ==============
$i = 1;
while ($i <= 12) {
  $aktmesic = "mesic".$i;

  echo "<table class='rekordytretina'>
          <tr class='zelenyRadek'><td colspan='2' class='radek'>{$lang[$aktmesic]}</td></tr>
          <tr class='modryRadek'>
            <td class='radek'>{$lang['velicina']}</td>
            <td class='radek'>{$lang['datum']}</td>
          </tr>";

  // Nejvyšší teplota v mìsíci (po dnech)
  $sql = "
    SELECT d, v FROM (
      SELECT DATE(date_time) AS d, MAX(temperature) AS v
      FROM {$TABLE}
      WHERE MONTH(date_time) = {$i}
      GROUP BY DATE(date_time)
    ) z
    ORDER BY v DESC
    LIMIT 1";
  $res = mysqli_query($conn,$sql);
  if ($res && $r=mysqli_fetch_assoc($res)) {
    echo "<tr><td>{$lang['maxteplota']}</td>
          <td>".jednotkaTeploty((float)$r['v'],$u,1)." (".formatDnu($r['d']).")</td></tr>";
  }

  // Nejnižší teplota v mìsíci (po dnech)
  $sql = "
    SELECT d, v FROM (
      SELECT DATE(date_time) AS d, MIN(temperature) AS v
      FROM {$TABLE}
      WHERE MONTH(date_time) = {$i}
      GROUP BY DATE(date_time)
    ) z
    ORDER BY v ASC
    LIMIT 1";
  $res = mysqli_query($conn,$sql);
  if ($res && $r=mysqli_fetch_assoc($res)) {
    echo "<tr><td>{$lang['minteplota']}</td>
          <td>".jednotkaTeploty((float)$r['v'],$u,1)." (".formatDnu($r['d']).")</td></tr>";
  }

  // Nejvyšší denní srážky v mìsíci — MAX(rain_daily) po dnech daného mìsíce
  $sql = "
    SELECT d, v FROM (
      SELECT DATE(date_time) AS d, MAX(rain_daily) AS v
      FROM {$TABLE}
      WHERE MONTH(date_time) = {$i}
      GROUP BY DATE(date_time)
    ) z
    ORDER BY v DESC
    LIMIT 1";
  $res = mysqli_query($conn,$sql);
  if ($res && $r=mysqli_fetch_assoc($res)) {
    echo "<tr><td>{$lang['nejvyssidennisrazky']}</td>
          <td>".round((float)$r['v'],1)." mm (".formatDnu($r['d']).")</td></tr>";
  }

  // Nejvyšší prùmìrná teplota v mìsíci (AVG po mìsíci)
  $sql = "
    SELECT YEAR(date_time) AS rok, AVG(temperature) AS v
    FROM {$TABLE}
    WHERE MONTH(date_time) = {$i}
    GROUP BY YEAR(date_time), MONTH(date_time)
    ORDER BY v DESC
    LIMIT 1";
  $res = mysqli_query($conn,$sql);
  if ($res && $r=mysqli_fetch_assoc($res)) {
    echo "<tr><td>{$lang['nejvyssiprumteplota']}</td>
          <td>".jednotkaTeploty(round((float)$r['v'],1),$u,1)." (".$r['rok'].")</td></tr>";
  }

  // Nejnižší prùmìrná teplota v mìsíci
  $sql = "
    SELECT YEAR(date_time) AS rok, AVG(temperature) AS v
    FROM {$TABLE}
    WHERE MONTH(date_time) = {$i}
    GROUP BY YEAR(date_time), MONTH(date_time)
    ORDER BY v ASC
    LIMIT 1";
  $res = mysqli_query($conn,$sql);
  if ($res && $r=mysqli_fetch_assoc($res)) {
    echo "<tr><td>{$lang['nejnizsiprumteplota']}</td>
          <td>".jednotkaTeploty(round((float)$r['v'],1),$u,1)." (".$r['rok'].")</td></tr>";
  }

  // Nejvyšší mìsíèní srážky — MAX(rain_monthly) v daném mìsíci
  $sql = "
    SELECT YEAR(date_time) AS rok, MAX(rain_monthly) AS v
    FROM {$TABLE}
    WHERE MONTH(date_time) = {$i}
    GROUP BY YEAR(date_time), MONTH(date_time)
    ORDER BY v DESC
    LIMIT 1";
  $res = mysqli_query($conn,$sql);
  if ($res && $r=mysqli_fetch_assoc($res)) {
    echo "<tr><td>{$lang['nejvyssimesicnisrazky']}</td>
          <td>".round((float)$r['v'],1)." mm (".$r['rok'].")</td></tr>";
  }

  // Nejnižší mìsíèní srážky — MAX(rain_monthly) ASC
  $sql = "
    SELECT YEAR(date_time) AS rok, MAX(rain_monthly) AS v
    FROM {$TABLE}
    WHERE MONTH(date_time) = {$i}
    GROUP BY YEAR(date_time), MONTH(date_time)
    ORDER BY v ASC
    LIMIT 1";
  $res = mysqli_query($conn,$sql);
  if ($res && $r=mysqli_fetch_assoc($res)) {
    echo "<tr><td>{$lang['nejnizsimesicnisrazky']}</td>
          <td>".round((float)$r['v'],1)." mm (".$r['rok'].")</td></tr>";
  }

  echo "</table>";
  $i++;
}

// konec
mysqli_close($conn);

// pøípadné absolutní rekordy tu nechávám komentované, jak jsi mìl.
// echo "...";

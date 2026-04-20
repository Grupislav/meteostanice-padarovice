<?php
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";
require_once __DIR__ . "/../../config.php";

$jednotka = jednotkaSymbol($u);

// pùipojenù
$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { echo "Problùm s DB."; return; }
mysqli_query($conn, "SET NAMES 'utf8mb4'");

// MIN/MAX DNES
$sqlToday = "
  SELECT MIN(temperature) AS min_t, MAX(temperature) AS max_t
  FROM history_cron_padarovice
  WHERE date_time >= CURDATE()
";
$today = mysqli_query($conn, $sqlToday);
$minToday = $maxToday = null;
if ($today && mysqli_num_rows($today) > 0) {
  $row = mysqli_fetch_assoc($today);
  $minToday = is_null($row['min_t']) ? null : (float)$row['min_t'];
  $maxToday = is_null($row['max_t']) ? null : (float)$row['max_t'];
}

// MIN/MAX + SRùùKY ZA POSLEDNùCH 7 DNù
$sqlWeek = "
  SELECT
    MIN(temperature) AS min_t,
    MAX(temperature) AS max_t
  FROM history_cron_padarovice
  WHERE date_time >= NOW() - INTERVAL 7 DAY
";
$week = mysqli_query($conn, $sqlWeek);
$minWeek = $maxWeek = null;
$rainWeek = 0.0;
if ($week && mysqli_num_rows($week) > 0) {
  $row = mysqli_fetch_assoc($week);
  $minWeek = is_null($row['min_t']) ? null : (float)$row['min_t'];
  $maxWeek = is_null($row['max_t']) ? null : (float)$row['max_t'];
}

$sqlWeekRain = "
  SELECT date_time, rain_daily
  FROM history_cron_padarovice
  WHERE date_time >= NOW() - INTERVAL 8 DAY
  ORDER BY date_time ASC
";
$weekRain = mysqli_query($conn, $sqlWeekRain);
if ($weekRain && mysqli_num_rows($weekRain) > 0) {
  $cutoffTs = time() - (7 * 24 * 60 * 60);
  $prevRain = null;

  while ($row = mysqli_fetch_assoc($weekRain)) {
    $rowTs = strtotime((string)$row['date_time']);
    $rainDaily = is_null($row['rain_daily']) ? null : (float)$row['rain_daily'];

    if ($rainDaily === null || $rowTs === false) {
      continue;
    }

    if ($rowTs < $cutoffTs) {
      $prevRain = $rainDaily;
      continue;
    }

    if ($prevRain !== null) {
      $rainWeek += $rainDaily >= $prevRain ? ($rainDaily - $prevRain) : $rainDaily;
    }

    $prevRain = $rainDaily;
  }
}

$rainWeek = round($rainWeek, 1);
mysqli_close($conn);

// HLAVIùKA 24 HOD
echo "<table class='tabulkaDnes'>
        <tr>
          <td class='radekDnes'><span class='font25 zelena'>" . mb_strtoupper($lang['graf24hodin'],'UTF-8') . "</span></td>
        </tr>
      </table>";

echo "<div class='graf' id='graf-24-hodin'>";
require __DIR__ . "/../grafy/aktualne/24-hodin.php";
echo "</div>";

// MIN/MAX DNES
echo "
<div class='karty-wrap'>
  <div class='kartapodgrafy ". barvaRameckuTeploty($minToday) ."'>
    <div class='popis'>".$lang['mindnes']."</div>
    <div class='aktuamens'>". jednotkaTeploty($minToday, $u, 1) ."</div>
  </div>

  <div class='kartapodgrafy ". barvaRameckuTeploty($maxToday) ."'>
    <div class='popis'>".$lang['maxdnes']."</div>
    <div class='aktuamens'>". jednotkaTeploty($maxToday, $u, 1) ."</div>
  </div>
</div>";

// HLAVIùKA 5 DNù
echo "<table class='tabulkaDnes'>
        <tr>
          <td class='radekDnes'><span class='font25 zelena'>" . mb_strtoupper($lang['graf5dni'],'UTF-8') . "</span></td>
        </tr>
      </table>";

echo "<div class='graf' id='graf-5-dni'>";
require __DIR__ . "/../grafy/aktualne/5-dni.php";
echo "</div>";

// MIN/MAX TùDEN
echo "
<div class='karty-wrap karty-wrap--tri'>
  <div class='kartapodgrafy ". barvaRameckuTeploty($minWeek) ."'>
    <div class='popis'>".rtrim($lang['nejnizsiteplota'], ':')."</div>
    <div class='aktuamens'>". jednotkaTeploty($minWeek, $u, 1) ."</div>
  </div>

  <div class='kartapodgrafy ". barvaRameckuTeploty($maxWeek) ."'>
    <div class='popis'>".rtrim($lang['nejvyssiteplota'], ':')."</div>
    <div class='aktuamens'>". jednotkaTeploty($maxWeek, $u, 1) ."</div>
  </div>

  <div class='kartapodgrafy ". barvaRameckuSrazky($rainWeek) ."'>
    <div class='popis'>".($lang['uhrnsrazek'] ?? '⁄hrn sr·ûek')."</div>
    <div class='aktuamens'>". $rainWeek ." mm</div>
  </div>
</div>";

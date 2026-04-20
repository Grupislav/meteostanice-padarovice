<?php
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";
require_once __DIR__ . "/../../config.php";

$jednotka = jednotkaSymbol($u);

// pøipojení
$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { echo "Problém s DB."; return; }
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

// MIN/MAX POSLEDNÍ TÝDEN
$sqlWeek = "
  SELECT MIN(temperature) AS min_t, MAX(temperature) AS max_t
  FROM history_cron_padarovice
  WHERE date_time >= NOW() - INTERVAL 7 DAY
";
$week = mysqli_query($conn, $sqlWeek);
$minWeek = $maxWeek = null;
if ($week && mysqli_num_rows($week) > 0) {
  $row = mysqli_fetch_assoc($week);
  $minWeek = is_null($row['min_t']) ? null : (float)$row['min_t'];
  $maxWeek = is_null($row['max_t']) ? null : (float)$row['max_t'];
}
mysqli_close($conn);

// HLAVIÈKA 24 HOD
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

// HLAVIÈKA 5 DNÍ
echo "<table class='tabulkaDnes'>
        <tr>
          <td class='radekDnes'><span class='font25 zelena'>" . mb_strtoupper($lang['graf5dni'],'UTF-8') . "</span></td>
        </tr>
      </table>";

echo "<div class='graf' id='graf-5-dni'>";
require __DIR__ . "/../grafy/aktualne/5-dni.php";
echo "</div>";

// MIN/MAX TÝDEN
echo "
<div class='karty-wrap'>
  <div class='kartapodgrafy ". barvaRameckuTeploty($minWeek) ."'>
    <div class='popis'>".rtrim($lang['nejnizsiteplota'], ':')."</div>
    <div class='aktuamens'>". jednotkaTeploty($minWeek, $u, 1) ."</div>
  </div>

  <div class='kartapodgrafy ". barvaRameckuTeploty($maxWeek) ."'>
    <div class='popis'>".rtrim($lang['nejvyssiteplota'], ':')."</div>
    <div class='aktuamens'>". jednotkaTeploty($maxWeek, $u, 1) ."</div>
  </div>
</div>";

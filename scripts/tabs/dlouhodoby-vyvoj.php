<?php
// INIT
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";

$TABLE = "history_cron_padarovice";

// ùù MIN/MAX aktuùlnù mùsùc ùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùù
$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { exit("Nejaky problem s DB: " . mysqli_connect_error()); }

$sqlM = "
  SELECT MAX(temperature) AS maxteplotamesic,
         MIN(temperature) AS minteplotamesic,
         MAX(rain_monthly) AS srazkymesic
  FROM {$TABLE}
  WHERE YEAR(date_time) = YEAR(CURDATE())
    AND MONTH(date_time) = MONTH(CURDATE())";
$resM = mysqli_query($conn, $sqlM);
list($maxteplotamesic,$minteplotamesic,$srazkymesic) = [null,null,0.0];
if ($resM && mysqli_num_rows($resM) > 0) {
  $row = mysqli_fetch_assoc($resM);
  $maxteplotamesic = (float)$row['maxteplotamesic'];
  $minteplotamesic = (float)$row['minteplotamesic'];
  $srazkymesic = is_null($row['srazkymesic']) ? 0.0 : round((float)$row['srazkymesic'], 1);
}

// ùù MIN/MAX aktuùlnù rok ùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùùù
$sqlR = "
  SELECT MAX(temperature) AS maxteplotarok,
         MIN(temperature) AS minteplotarok,
         MAX(rain_yearly) AS srazkyrok
  FROM {$TABLE}
  WHERE YEAR(date_time) = YEAR(CURDATE())";
$resR = mysqli_query($conn, $sqlR);
list($maxteplotarok,$minteplotarok,$srazkyrok) = [null,null,0.0];
if ($resR && mysqli_num_rows($resR) > 0) {
  $row = mysqli_fetch_assoc($resR);
  $maxteplotarok = (float)$row['maxteplotarok'];
  $minteplotarok = (float)$row['minteplotarok'];
  $srazkyrok = is_null($row['srazkyrok']) ? 0.0 : round((float)$row['srazkyrok'], 1);
}
mysqli_close($conn);

// --- 30 dnù: teploty + srùky ---
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
    <div class='kartapodgrafy ". barvaRameckuSrazky($srazkymesic) ."'>
      <div class='popis'>{$lang['uhrnsrazekmesic']}</div>
      <div class='aktuamens'>". $srazkymesic ." mm</div>
    </div>
  </div>";
}

// --- 3 roky: m?sù?nù hodnoty ---
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
    <div class='kartapodgrafy ". barvaRameckuSrazky($srazkyrok) ."'>
      <div class='popis'>{$lang['uhrnsrazekrok']}</div>
      <div class='aktuamens'>". $srazkyrok ." mm</div>
    </div>
  </div>";
}

// --- roky: ro?nù hodnoty ---
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['grafrokyhodnoty'],'UTF-8')."</span>
      </td></tr></table>";

echo "<div class='graf' id='graf-roky'>";
require __DIR__ . '/../grafy/dlouhodoby-vyvoj/roky.php';
echo "</div>";

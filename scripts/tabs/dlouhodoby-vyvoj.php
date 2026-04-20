<?php
// INIT
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";

$TABLE = "history_cron_padarovice";

// ¦¦ MIN/MAX aktuální mìsíc ¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦
$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { exit("Nejaky problem s DB: " . mysqli_connect_error()); }

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

// ¦¦ MIN/MAX aktuální rok ¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦
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
mysqli_close($conn);

// --- 30 dní: teploty + srážky ---
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['graf30dniteplota'],'UTF-8')."</span>
      </td></tr></table>";

echo "<div class='graf' id='graf-30-dni'>";
require __DIR__ . '/../grafy/dlouhodoby-vyvoj/30-dni.php';
echo "</div>";

if ($minteplotamesic !== null && $maxteplotamesic !== null) {
  echo "
  <div class='karty-wrap'>
    <div class='kartapodgrafy ". barvaRameckuTeploty($minteplotamesic) ."'>
      <div class='popis'>{$lang['minmesic']}</div>
      <div class='aktuamens'>". jednotkaTeploty($minteplotamesic, $u, 1) ."</div>
    </div>
    <div class='kartapodgrafy ". barvaRameckuTeploty($maxteplotamesic) ."'>
      <div class='popis'>{$lang['maxmesic']}</div>
      <div class='aktuamens'>". jednotkaTeploty($maxteplotamesic, $u, 1) ."</div>
    </div>
  </div>";
}

// --- 3 roky: m?sí?ní hodnoty ---
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['graf3rokyteplota'],'UTF-8')."</span>
      </td></tr></table>";

echo "<div class='graf' id='graf-3-roky'>";
require __DIR__ . '/../grafy/dlouhodoby-vyvoj/3-roky.php';
echo "</div>";

if ($minteplotarok !== null && $maxteplotarok !== null) {
  echo "
  <div class='karty-wrap'>
    <div class='kartapodgrafy ". barvaRameckuTeploty($minteplotarok) ."'>
      <div class='popis'>{$lang['minrok']}</div>
      <div class='aktuamens'>". jednotkaTeploty($minteplotarok, $u, 1) ."</div>
    </div>
    <div class='kartapodgrafy ". barvaRameckuTeploty($maxteplotarok) ."'>
      <div class='popis'>{$lang['maxrok']}</div>
      <div class='aktuamens'>". jednotkaTeploty($maxteplotarok, $u, 1) ."</div>
    </div>
  </div>";
}

// --- roky: ro?ní hodnoty ---
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['grafrokyhodnoty'],'UTF-8')."</span>
      </td></tr></table>";

echo "<div class='graf' id='graf-roky'>";
require __DIR__ . '/../grafy/dlouhodoby-vyvoj/roky.php';
echo "</div>";

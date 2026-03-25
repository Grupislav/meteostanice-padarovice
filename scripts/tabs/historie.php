<?php
// INIT
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";

// Bezpeèné ètení GET
$ja = isset($_GET['ja']) ? $_GET['ja'] : $l;
$je = isset($_GET['je']) ? $_GET['je'] : $u;

// Den ve formátu YYYY-MM-DD (fallback: vèerejšek)
$den = isset($_GET['den']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['den'])
  ? $_GET['den']
  : date('Y-m-d', strtotime('-1 day'));

// Formuláø
echo "<form method='GET' action='#historie'>
  <fieldset>
    <legend>{$lang['zobrazitden']}</legend>
    <input type='hidden' name='ja' value='".htmlspecialchars($ja, ENT_QUOTES)."'>
    <input type='hidden' name='je' value='".htmlspecialchars($je, ENT_QUOTES)."'>
    <input type='hidden' name='typ' value='0'>
    <p>
      <label for='den'>{$lang['den']}:</label>
      <input type='text' name='den' id='den' value='".htmlspecialchars($den, ENT_QUOTES)."' autocomplete='off'>
      <input type='submit' class='submit' name='odeslani' value='{$lang['zobrazit']}'>
    </p>
  </fieldset>
</form>";

// Zobrazení dne (typ=0)
if (isset($_GET['typ']) && $_GET['typ'] == '0') {
  echo "<table class='tabulkaVHlavicce'><tr>
          <td class='radekDnes'><span class='font25 zelena'>".formatDnu($den)."</span></td>
        </tr></table>";

  // Graf (pøedám den pøes GET stejnì jako døív)
  echo "<div class='graf' id='graf-historie'>";
  require __DIR__ . '/../grafy/historie.php';
  echo "</div>";

  // Min/Max teplota daného dne
  $TABLE = "history_cron_padarovice";
  $conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
  if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }

  $denEsc = mysqli_real_escape_string($conn, $den);
  $sql = "SELECT MAX(temperature) AS maxteplota, MIN(temperature) AS minteplota
          FROM {$TABLE}
          WHERE DATE(date_time) = '{$denEsc}'";
  $res = mysqli_query($conn, $sql);
  if ($res && mysqli_num_rows($res) > 0) {
    $t = mysqli_fetch_assoc($res);
    $minT = isset($t['minteplota']) ? (float)$t['minteplota'] : null;
    $maxT = isset($t['maxteplota']) ? (float)$t['maxteplota'] : null;

    if ($minT !== null) {
      echo "<div class='aktualneMensi".barvaRameckuTeploty($minT)."'>
              <div class='aktualneOdskok'>
                {$lang['nejnizsiteplota']}<br>
                <font class='aktuamens'>".jednotkaTeploty($minT, $u, 1)."</font>
              </div>
            </div>";
    }
    if ($maxT !== null) {
      echo "<div class='aktualneMensi vpravo".barvaRameckuTeploty($maxT)."'>
              <div class='aktualneOdskok'>
                {$lang['nejvyssiteplota']}<br>
                <font class='aktuamens'>".jednotkaTeploty($maxT, $u, 1)."</font>
              </div>
            </div>";
    }
  } else {
    echo "Nemame data!";
  }
  mysqli_close($conn);
}

<?php
// INIT
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";

// ── Srážky
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['grafstatistikysrazky'],'UTF-8')."</span>
      </td></tr></table>
      <div class='graf' id='graf-stat-srazky'>";
require __DIR__ . '/../grafy/statistiky/srazky.php';
echo "</div>";

// ── Teplota
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['grafstatistikyteplota'],'UTF-8')."</span>
      </td></tr></table>
      <div class='graf' id='graf-stat-teplota'>";
require __DIR__ . '/../grafy/statistiky/teplota.php';
echo "</div>";

$url = 'https://www.chmi.cz/historicka-data/pocasi/uzemni-teploty';
$e = fn($s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

echo '<div style="text-align:left">'
   . $e($lang['statistikyzdroj_prefix']) . ' '
   . '<a href="'.$e($url).'" target="_blank" rel="noopener" style="color:#00a186;text-decoration:underline">'.$e($lang['statistikyzdroj_label']).'</a>'
   . '</div>';

// ── Charakteristické dny
echo "<table class='tabulkaDnes'><tr><td class='radekDnes'>
        <span class='font25 zelena'>".mb_strtoupper($lang['grafcharakteristickedny'],'UTF-8')."</span>
      </td></tr></table>
      <div class='graf' id='graf-stat-chardny'>";
require __DIR__ . '/../grafy/statistiky/chardny.php';
echo "</div>";

$legend = [
  ['color' => '#ff6600', 'text' => $lang['legend_summer_day']],
  ['color' => '#ff3300', 'text' => $lang['legend_tropical_day']],
  ['color' => '#ff944d', 'text' => $lang['legend_tropical_night']],
  ['color' => '#83b2e3', 'text' => $lang['legend_frost_day']],
  ['color' => '#4c8aca', 'text' => $lang['legend_ice_day']],
  ['color' => '#3573b1', 'text' => $lang['legend_arctic_day']],
];

echo '<div style="text-align:left">';
echo '<strong>' . htmlspecialchars($lang['legend_title'], ENT_QUOTES, 'UTF-8') . '</strong>';
echo '<ul class="legend-list">';
foreach ($legend as $row) {
  $c = htmlspecialchars($row['color'], ENT_QUOTES, 'UTF-8');
  $t = htmlspecialchars($row['text'],  ENT_QUOTES, 'UTF-8');
  echo '<li class="legend-item">'
     . '<span class="legend-swatch" style="background-color:' . $c . '"></span>'
     . '<span>' . $t . '</span>'
     . '</li>';
}
echo '</ul></div>';

<?php
// helpery a překlady
require_once __DIR__ . '/fce.php';
require_once __DIR__ . '/variableCheck.php';

// --- načtení astronomie z IPGeolocation (fixní souřadnice stanice)
$apiUrl = 'https://api.ipgeolocation.io/astronomy?' . http_build_query([
  'apiKey' => $ipgeo['apiKey'],
  'lat'    => $ipgeo['lat'],
  'long'   => $ipgeo['long'],
], '', '&', PHP_QUERY_RFC3986);
$raw    = get_geolocation($apiUrl);
$astro  = $raw ? json_decode($raw, true) : null;

// helpery pro výstup
$val   = fn($k) => isset($astro[$k]) ? (string)$astro[$k] : '';
$fmtKM = function($meters) {
  if (!is_numeric($meters)) return '—';
  $thousands = round(((float)$meters) / 1000); // tisíce km
  return number_format($thousands, 0, ',', ' ') . ' tis. km';
};
$fmtLen = function($s) {
  // day_length může být "10:35:21" → chceme "10:35"
  if (!$s) return '—';
  $parts = explode(':', $s);
  if (count($parts) >= 2) return sprintf('%02d:%02d', (int)$parts[0], (int)$parts[1]);
  // zkusíme parser jako fallback
  $dt = date_create($s);
  return $dt ? $dt->format('H:i') : $s;
};
$moonKey = function($phase) {
  // z "Waning Crescent" → "WANING_CRESCENT"
  if (!$phase) return 'NEW_MOON';
  $k = strtoupper(str_replace(' ', '_', (string)$phase));
  return $k; // klíče v language/cz.php jsou ve stylu NEW_MOON, FULL_MOON, ...
};

// výstup
echo "<table class='tabulkaVHlavicce'>
  <tr class='radek zelenyRadek'>
    <td colspan='2'>{$lang['info']}</td>
  </tr>
  <tr>
    <td align='right'>{$lang['umisteni']}:</td>
    <td>{$lang['pilinkov']}</td>
  </tr>
  <tr>
    <td align='right'>{$lang['nadmvyska']}:</td>
    <td>300 m n.m.</td>
  </tr>
  <tr>
    <td align='right'>{$lang['merenood']}:</td>
    <td>13. 11. 2024</td>
  </tr>

  <tr class='radek zelenyRadekStredovy'>
    <td colspan='2'>{$lang['astronomie']}</td>
  </tr>
  <tr>
    <td align='right'>{$lang['vychodslunce']}:</td>
    <td>" . ($val('sunrise') ?: '—') . "</td>
  </tr>
  <tr>
    <td align='right'>{$lang['zapadslunce']}:</td>
    <td>" . ($val('sunset') ?: '—') . "</td>
  </tr>
  <tr>
    <td align='right'>{$lang['delkadne']}:</td>
    <td>" . $fmtLen($val('day_length')) . "</td>
  </tr>
  <tr>
    <td align='right'>{$lang['slunecnipoledne']}:</td>
    <td>" . ($val('solar_noon') ?: '—') . "</td>
  </tr>
  <tr>
    <td align='right'>{$lang['vzdalenostslunce']}:</td>
    <td><div class='tooltip'>" . $fmtKM($val('sun_distance')) . "<span class='tooltiptext'>{$lang['strednivzdalenostslunce']}</span></div></td>
  </tr>
  <tr>
    <td align='right'>{$lang['osvitmesice']}:</td>
    <td>" . (is_numeric($val('moon_illumination_percentage')) ? (float)$val('moon_illumination_percentage') . ' %' : '—') . "</td>
  </tr>
  <tr>
    <td align='right'>{$lang['fazemesice']}:</td>
    <td>" . ($lang[$moonKey($val('moon_phase'))] ?? ($val('moon_phase') ?: '—')) . "</td>
  </tr>
  <tr>
    <td align='right'>{$lang['vychodmesice']}:</td>
    <td>" . ($val('moonrise') ?: '—') . "</td>
  </tr>
  <tr>
    <td align='right'>{$lang['zapadmesice']}:</td>
    <td>" . ($val('moonset') ?: '—') . "</td>
  </tr>
  <tr>
    <td align='right'>{$lang['vzdalenostmesice']}:</td>
    <td><div class='tooltip'>" . $fmtKM($val('moon_distance')) . "<span class='tooltiptext'>{$lang['strednivzdalenostmesice']}</span></div></td>
  </tr>
</table>";

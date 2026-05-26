<?php
// Defaulty z configu (jazyk = $l, jednotka teploty = $u, jednotka vetru = $uv, jednotka tlaku = $ut)
if (!isset($uv)) { $uv = 'k'; } // fallback, kdyby chybelo v configu
if (!isset($ut)) { $ut = 'h'; } // fallback, kdyby chybelo v configu
if (!isset($_GET['ja'])) { $_GET['ja'] = $l; }  // JA = jazyk
if (!isset($_GET['je'])) { $_GET['je'] = $u; }  // JE = jednotka teploty
if (!isset($_GET['jv'])) { $_GET['jv'] = $uv; } // JV = jednotka vetru
if (!isset($_GET['jt'])) { $_GET['jt'] = $ut; } // JT = jednotka tlaku

// Jen CZ/EN
$jazyky = [
    'cz' => 'cz',
    'en' => 'en',
];

// Jednotky teploty (menu) - whitelist pro $u. Konverze v jednotkaTeploty() / jednotkaSymbol().
$jednotky = [
    'C' => 'Celsius',
    'F' => 'Fahrenheit'
];

// Jednotky rychlosti vetru (menu) - data v DB jsou v km/h (Ecowitt wind_speed_unitid=7).
// 'k' = km/h, 'm' = m/s. Konverze a popisky jsou v fce.php (jednotkaVitr/Symbol).
$jednotkyVitr = [
    'k' => 'km/h',
    'm' => 'm/s',
];

// Jednotky tlaku (menu) - data v DB jsou v hPa (Ecowitt pressure_unitid=3).
// 'h' = hPa, 'mm' = mmHg. Konverze a popisky jsou v fce.php (jednotkaTlak/Symbol).
$jednotkyTlak = [
    'h'  => 'hPa',
    'mm' => 'mmHg',
];

// Jazyk z URL (whitelist), jinak ponech default z configu
if (isset($_GET['ja'], $jazyky[$_GET['ja']])) {
    $l = $jazyky[$_GET['ja']];
} else {
    $_GET['ja'] = $l;
}

// Na?ti jazykov� soubor bezpe?n?; fallback na 'cz'
$langFile = __DIR__ . "/language/{$l}.php";
if (!is_file($langFile)) {
    $l = 'cz';
    $langFile = __DIR__ . "/language/cz.php";
}
require_once $langFile;

// Jednotka teploty z URL (whitelist), jinak ponech default z configu
if (isset($_GET['je'], $jednotky[$_GET['je']])) {
    $u = $_GET['je'];
} else {
    $_GET['je'] = $u;
}

// Jednotka vetru z URL (whitelist), jinak ponech default z configu
if (isset($_GET['jv'], $jednotkyVitr[$_GET['jv']])) {
    $uv = $_GET['jv'];
} else {
    $_GET['jv'] = $uv;
}

// Jednotka tlaku z URL (whitelist), jinak ponech default z configu
if (isset($_GET['jt'], $jednotkyTlak[$_GET['jt']])) {
    $ut = $_GET['jt'];
} else {
    $_GET['jt'] = $ut;
}

<?php
// Defaulty z configu (jazyk = $l, jednotka = $u)
if (!isset($_GET['ja'])) { $_GET['ja'] = $l; } // JA = jazyk
if (!isset($_GET['je'])) { $_GET['je'] = $u; } // JE = jednotka

// Jen CZ/EN
$jazyky = [
    'cz' => 'cz',
    'en' => 'en',
];

// Jednotky nechám všechny, klidnì zúži na C/F
$jednotky = [
    'C' => 'Celsius',
    'F' => 'Fahrenheit'
];

// Jazyk z URL (whitelist), jinak ponech default z configu
if (isset($_GET['ja'], $jazyky[$_GET['ja']])) {
    $l = $jazyky[$_GET['ja']];
} else {
    $_GET['ja'] = $l;
}

// Naèti jazykový soubor bezpeènì; fallback na 'cz'
$langFile = __DIR__ . "/language/{$l}.php";
if (!is_file($langFile)) {
    $l = 'cz';
    $langFile = __DIR__ . "/language/cz.php";
}
require_once $langFile;

// Jednotka z URL (whitelist), jinak ponech default z configu
if (isset($_GET['je'], $jednotky[$_GET['je']])) {
    $u = $_GET['je'];
} else {
    $_GET['je'] = $u;
}

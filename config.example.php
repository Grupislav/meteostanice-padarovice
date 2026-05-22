<?php

//////////////////////////////////////////
// ZAKLADNI NASTAVENI / BASIC SETTINGS
//////////////////////////////////////////

// Zkopíruj tento soubor jako config.php a doplň hodnoty.
// config.php se do Gitu necommituje.

$dbServer   = 'db.example.com';
$dbUzivatel = 'db_user';
$dbHeslo    = 'db_password';
$dbDb       = 'db_name';

/**
 * Veřejná cesta k aplikaci na serveru (bez koncového lomítka).
 * Prázdný řetězec = kořen domény.
 * Pro https://tomaskrupicka.cz/meteostanice-padarovice/ nastav:
 */
$appBasePath = '/meteostanice-padarovice';

// Jazyk a jednotky – výchozí (povolené hodnoty: scripts/variableCheck.php)
$l = 'cz'; // cz | en
$u = 'C';  // C | F

// Auto-refresh v sekundách (0 = vypnuto)
$obnoveniStranky = 360;

// Ajax refresh aktuálních hodnot (sekundy; 0 = vypnuto)
$ajaxRefreshSec = 60;

// Omezovací IP (pokud používáš pro zápis měření)
$ip = '';

// --- Ecowitt API ---
$ecowitt = [
  'application_key'   => 'YOUR_APPLICATION_KEY',
  'api_key'           => 'YOUR_API_KEY',
  'mac'               => 'AA:BB:CC:DD:EE:FF',
  'temp_unitid'       => 1,
  'pressure_unitid'   => 3,
  'wind_speed_unitid' => 7,
  'rainfall_unitid'   => 12,
];

// --- ipgeolocation.io (astronomie apod.) ---
// Souřadnice (lat/long) se používají i pro stav oblohy u „aktuálního počasí“ (Open-Meteo).
$ipgeo = [
  'apiKey' => 'YOUR_IPGEO_API_KEY',
  'lat'    => '50.0000000',
  'long'   => '15.0000000',
];

// Pozn.: stav oblohy u „aktuálního počasí“ se bere z Open-Meteo (bez API klíče, funguje i v noci)
// podle souřadnic v $ipgeo. Dřívější $meteoPocasiApiId (api.meteo-pocasi.cz) se už nepoužívá.

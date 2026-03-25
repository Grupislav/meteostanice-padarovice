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

// Jazyk a jednotky – výchozí
$l = 'cz'; // cz, sk, en, de, fr...
$u = 'C';  // C, F, K, R, D, N, Re, Ro

// Auto-refresh v sekundách (0 = vypnuto)
$obnoveniStranky = 360;

// Ajax refresh aktuálních hodnot (sekundy; 0 = vypnuto)
$ajaxRefreshSec = 60;

// Přesměrování na mobilní verzi? (0/1)
$presmerovavatMobily = 1;

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
$ipgeo = [
  'apiKey' => 'YOUR_IPGEO_API_KEY',
  'lat'    => '50.0000000',
  'long'   => '15.0000000',
];

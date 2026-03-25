<?php

//////////////////////////////////////////
// ZAKLADNI NASTAVENI / BASIC SETTINGS
//////////////////////////////////////////

$dbServer = "md27.wedos.net"; // CZ: server, kde bezi databaze
$dbUzivatel = "w199169_meteo";     // CZ: uzivatelske jmeno pro prihlaseni do databaze
$dbHeslo = "VqhU3r2W";    // CZ: heslo pro prihlaseni do databaze
$dbDb = "d199169_meteo";       // CZ: nazev databaze


// Jazyk a jednotky – výchozí
$l = "cz"; // cz, sk, en, de, fr...
$u = "C";  // C, F, K, R, D, N, Re, Ro

// Auto-refresh v sekundách (0 = vypnuto)
$obnoveniStranky = 360;

// Ajax refresh aktuálních hodnot (sekundy; 0 = vypnuto)
$ajaxRefreshSec = 60;

// Přesměrování na mobilní verzi? (0/1)
$presmerovavatMobily = 1;

// Omezovací IP (pokud používáš pro zápis měření)
$ip = ""; // nech prázdné, pokud nepoužíváš

// --- externí API (doplnit své hodnoty) ---
$ecowitt = [
  'application_key' => '6037BF44658C215422FE65C98491BA15',
  'api_key'         => 'e7e44938-c367-4f09-996f-81e2b9436469',
  'mac'             => '08:F9:E0:50:39:94',
  // jednotky: 1=°C, 3=hPa, 7=km/h, 12=mm
  'temp_unitid'     => 1,
  'pressure_unitid' => 3,
  'wind_speed_unitid' => 7,
  'rainfall_unitid'   => 12,
];

$ipgeo = [
  'apiKey' => '35fa55a9bef84a859ba97ed0f34b0f2f',
  'lat'    => '50.6058728',
  'long'   => '15.0468442',
];
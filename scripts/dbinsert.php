<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/fce.php';

// doporuèuju mít ji v configu globálnì
date_default_timezone_set('Europe/Prague');

// --- malý toggle na ladìní ---
$DEBUG = false;

// --- slo? Ecowitt URL buï z configu ($ecowitt), nebo pou?ij fallback ---
if (isset($ecowitt) && is_array($ecowitt)) {
    $ecoParams = [
        'application_key'    => $ecowitt['application_key'],
        'api_key'            => $ecowitt['api_key'],
        'mac'                => $ecowitt['mac'],
        'temp_unitid'        => $ecowitt['temp_unitid'] ?? 1,   // °C
        'pressure_unitid'    => $ecowitt['pressure_unitid'] ?? 3, // hPa
        'wind_speed_unitid'  => $ecowitt['wind_speed_unitid'] ?? 7, // km/h
        'rainfall_unitid'    => $ecowitt['rainfall_unitid'] ?? 12, // mm
    ];
    $ecoUrl = 'https://api.ecowitt.net/api/v3/device/real_time?' . http_build_query($ecoParams, '', '&', PHP_QUERY_RFC3986);
} else {
    if ($DEBUG) {
        error_log('[dbinsert] chybí pole $ecowitt v config.php');
    }
    exit;
}

// --- stáhni a dekóduj ---
$json = curl_get_file_contents($ecoUrl);
if (!$json) {
    if ($DEBUG) error_log('[dbinsert] Ecowitt: empty response');
    exit; // klidnì skonèi ? nechceme vkládat nesmysly
}
$data = json_decode($json);
if (!$data || empty($data->data)) {
    if ($DEBUG) error_log('[dbinsert] Ecowitt: invalid JSON');
    exit;
}

// --- helpery na typy/sanity ---
$F  = fn($x) => is_numeric((string)$x) ? (float)$x : null;
$I  = fn($x) => is_numeric((string)$x) ? (int)$x : null;
$clamp = fn($v,$min,$max) => ($v===null)?null:max($min, min($max, $v));

// --- vytáhni hodnoty ---
$epoch            = $I($data->time) ?? time();
$minuteAligned    = (int)($epoch - ($epoch % 60));                   // zarovnat na minutu
$dtLocal          = date('Y-m-d H:i:00', $minuteAligned);            // lokální èas (Europe/Prague)

$temperature      = $F($data->data->outdoor->temperature->value);
$humidity         = $clamp($F($data->data->outdoor->humidity->value), 0, 100);
$dew_point        = $F($data->data->outdoor->dew_point->value);
$temperature_app  = $F($data->data->outdoor->feels_like->value);

$pressure_qnh     = $F($data->data->pressure->relative->value);
$exposure         = max(0.0, (float)$F($data->data->solar_and_uvi->solar->value)); // W/m2, bez záporných
$uvi              = $clamp($F($data->data->solar_and_uvi->uvi->value), 0, 50);     // prakticky 0?11+

$wind_speed       = max(0.0, (float)$F($data->data->wind->wind_speed->value));
$wind_gust        = max(0.0, (float)$F($data->data->wind->wind_gust->value));
$wind_dir         = $F($data->data->wind->wind_direction->value);
$wind_dir         = $wind_dir !== null ? fmod($wind_dir + 360.0, 360.0) : null;    // 0?<360

$rain_daily       = max(0.0, (float)$F($data->data->rainfall->daily->value));
$rain_event       = max(0.0, (float)$F($data->data->rainfall->event->value));
$rain_rate        = max(0.0, (float)$F($data->data->rainfall->rain_rate->value));
$rain_hourly      = max(0.0, (float)$F($data->data->rainfall->hourly->value));
$rain_weekly      = max(0.0, (float)$F($data->data->rainfall->weekly->value));
$rain_monthly     = max(0.0, (float)$F($data->data->rainfall->monthly->value));
$rain_yearly      = max(0.0, (float)$F($data->data->rainfall->yearly->value));

// minimální validace ? kdy? chybí tlak/teplota, radìji nevkládej
if ($pressure_qnh === null && $temperature === null) {
    if ($DEBUG) error_log('[dbinsert] missing key values (pressure & temp)');
    exit;
}

// --- pøipojení k DB ---
$conn = mysqli_connect($dbServer, $dbUzivatel, $dbHeslo, $dbDb);
if (!$conn) {
    if ($DEBUG) error_log('[dbinsert] DB connect failed');
    exit;
}

// --- prepared statement s UPSERTem ---
$sql = "
INSERT INTO history_cron_padarovice
(date_time, humidity, pressure_QNH, exposure, uvi, temperature, wind_speed, wind_direction, dew_point, rain_rate, rain_event, rain_daily, rain_hourly, rain_weekly, rain_monthly, rain_yearly, temperature_apparent, wind_gust)
VALUES
(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  humidity=VALUES(humidity),
  pressure_QNH=VALUES(pressure_QNH),
  exposure=VALUES(exposure),
  uvi=VALUES(uvi),
  temperature=VALUES(temperature),
  wind_speed=VALUES(wind_speed),
  wind_direction=VALUES(wind_direction),
  dew_point=VALUES(dew_point),
  rain_rate=VALUES(rain_rate),
  rain_event=VALUES(rain_event),
  rain_daily=VALUES(rain_daily),
  rain_hourly=VALUES(rain_hourly),
  rain_weekly=VALUES(rain_weekly),
  rain_monthly=VALUES(rain_monthly),
  rain_yearly=VALUES(rain_yearly),
  temperature_apparent=VALUES(temperature_apparent),
  wind_gust=VALUES(wind_gust)
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    if ($DEBUG) error_log('[dbinsert] prepare failed: ' . mysqli_error($conn));
    mysqli_close($conn);
    exit;
}

// typy: s (string), i (int), d (double)
$hum_i   = $humidity !== null ? (int)round($humidity) : 0; // TINYINT (0?100)
$uvi_i   = $uvi      !== null ? (int)round($uvi)      : 0; // TINYINT
$wdir_i  = $wind_dir !== null ? (int)round($wind_dir) : 0; // SMALLINT

mysqli_stmt_bind_param(
    $stmt,
    // 1x s, pak 17 èísel: i d d i d d i d d d d d d d d d d
    "siddidididdddddddd",
    $dtLocal,           // s
    $hum_i,             // i
    $pressure_qnh,      // d
    $exposure,          // d
    $uvi_i,             // i
    $temperature,       // d
    $wind_speed,        // d
    $wdir_i,            // i (smallint, ale binduje se jako int)
    $dew_point,         // d
    $rain_rate,         // d
    $rain_event,        // d
    $rain_daily,        // d
    $rain_hourly,       // d
    $rain_weekly,       // d
    $rain_monthly,      // d
    $rain_yearly,       // d
    $temperature_app,   // d
    $wind_gust          // d
);

$ok = mysqli_stmt_execute($stmt);

if ($DEBUG) {
    if ($ok) {
        error_log('[dbinsert] upsert OK for ' . $dtLocal);
    } else {
        error_log('[dbinsert] upsert FAIL: ' . mysqli_stmt_error($stmt));
    }
}

// uklid
mysqli_stmt_close($stmt);
mysqli_close($conn);

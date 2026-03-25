<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../variableCheck.php';
require_once __DIR__ . '/../fce.php';

/* ────────── Helpery ────────── */
$toFloat = static function ($v): ?float {
    if ($v === null) return null;
    $s = (string)$v;
    return is_numeric($s) ? (float)$s : null;
};
$toInt = static function ($v): ?int {
    if ($v === null) return null;
    $s = (string)$v;
    return is_numeric($s) ? (int)$s : null;
};
$wxLabel = static function ($code) use ($lang): string {
    $key = Pocasi($code ?? 0);                  // 'jasno' | 'dest' | …
    return $lang[$key] ?? $key;                 // fallback, kdyby v $lang nebyl
};

/* ────────── Live data z Ecowitt + doplňkové XML ────────── */
$params = [
  'application_key'   => $ecowitt['application_key'],
  'api_key'           => $ecowitt['api_key'],
  'mac'               => $ecowitt['mac'],
  'temp_unitid'       => $ecowitt['temp_unitid'],
  'pressure_unitid'   => $ecowitt['pressure_unitid'],
  'wind_speed_unitid' => $ecowitt['wind_speed_unitid'],
  'rainfall_unitid'   => $ecowitt['rainfall_unitid'],
];
$ecoUrl = 'https://api.ecowitt.net/api/v3/device/real_time?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
$ecoJson = curl_get_file_contents($ecoUrl);
$data = $ecoJson ? json_decode($ecoJson) : null;

$xmlString = curl_get_file_contents("http://api.meteo-pocasi.cz/api.xml?action=get-meteo-data&client=xml&id=00004c8SfUq5hdYFumackwf6NBJ5JC0iPfTG0QifuZlcCJs75Sj");
$xml = $xmlString ? @simplexml_load_string($xmlString) : null;

/* ────────── Je k dispozici živá teplota? ────────── */
$hasLive = isset($data->data->outdoor->temperature->value)
           && is_numeric((string)$data->data->outdoor->temperature->value);

/* ────────── Funkce pro vykreslení boxů ────────── */
$render = function(array $v) use ($lang, $u) {
    // $v obsahuje klíče: teplota, vlhkost, rosny, pocitovka, tlak, osvit, uvi, vitr, naraz, smer, srazky, pocasi, aktualizovano
    echo "<div class='aktualne jen jen" . barvaRameckuTeploty($v['teplota']) . "'>
        <div class='aktualneOdskok'>
          {$lang['aktualnipocasi']}<br>
          <span class='aktua jen'>" . jednotkaTeploty($v['teplota'], $u, 1) . "</span><br>"
          . $v['pocasi'] .
        "</div>
      </div>

      <div class='aktualneMensi" . barvaRameckuTeploty($v['pocitovka']) . "'>
        <div class='aktualneOdskok'>
          {$lang['pocteplota']}<br>
          <span class='aktuamens'>" . jednotkaTeploty($v['pocitovka'], $u, 1) . "</span>
        </div>
      </div>

      <div class='aktualneMensi vpravo" . barvaRameckuTeploty($v['rosny']) . "'>
        <div class='aktualneOdskok'>
          {$lang['rosnybod']}<br>
          <span class='aktuamens'>" . jednotkaTeploty($v['rosny'], $u, 1) . "</span>
        </div>
      </div>

      <div class='aktualneMensi" . barvaRameckuVlhkost($v['vlhkost']) . "'>
        <div class='aktualneOdskok'>
          {$lang['vlhkost']}<br>
          <span class='aktuamens'>{$v['vlhkost']} %</span>
        </div>
      </div>

      <div class='aktualneMensi vpravo" . barvaRameckuSrazky($v['srazky']) . "'>
        <div class='aktualneOdskok'>
          {$lang['srazky']}<br>
          <span class='aktuamens'>{$v['srazky']} mm</span>
        </div>
      </div>

      <div class='aktualneMensi" . barvaRameckuVitr($v['vitr']) . "'>
        <div class='aktualneOdskok'>
          {$lang['vitr']}<br>
          <span class='aktuamens'>{$v['vitr']} km/h</span>
        </div>
      </div>

      <div class='aktualneMensi vpravo" . barvaRameckuVitr($v['naraz']) . "'>
        <div class='aktualneOdskok'>
          {$lang['narazy']}<br>
          <span class='aktuamens'>{$v['naraz']} km/h</span>
        </div>
      </div>

      <div class='aktualneMensi aktualneMensiVitr'>
        <div class='aktualneOdskok'>
          {$lang['smervetru']}<br>
          <span class='aktuamens'>".SmerVetru($v['smer'])."</span>
        </div>
      </div>

      <div class='aktualneMensi vpravo" . barvaRameckuTlak($v['tlak']) . "'>
        <div class='aktualneOdskok'>
          {$lang['tlak']}<br>
          <span class='aktuamens'>{$v['tlak']} hPa</span>
        </div>
      </div>

      <div class='aktualneMensi" . barvaRameckuUV($v['uvi']) . "'>
        <div class='aktualneOdskok'>
          {$lang['uvi']}<br>
          <span class='aktuamens" . (($v['uvi'] > 2 && $v['uvi'] <= 7) ? "cerna" : "") . "'>{$v['uvi']}</span>
        </div>
      </div>

      <div class='aktualneMensi vpravo" . barvaRameckuOsvit($v['osvit']) . "'>
        <div class='aktualneOdskok'>
          {$lang['osvit']}<br>
          <span class='aktuamens" . ($v['osvit'] < 250 ? "" : "cerna") . "'>{$v['osvit']} W</span>
        </div>
      </div>

      <div class='" . barvaRameckuAktualizovano($v['aktualizovano']) . "'>
        <span>{$lang['posledniaktualizace']} {$v['aktualizovano']} (" . textAktualizovano($v['aktualizovano']) . ")</span>
      </div>";
};

/* ────────── Větev 1: LIVE DATA ────────── */
if ($hasLive) {
    $akteplota     = $toFloat($data->data->outdoor->temperature->value);
    $aktvlhkost    = $toFloat($data->data->outdoor->humidity->value);
    $aktrosnybod   = $toFloat($data->data->outdoor->dew_point->value);
    $aktpocteplota = $toFloat($data->data->outdoor->feels_like->value);
    $akttlak       = $toFloat($data->data->pressure->relative->value);
    $aktosvit      = $toFloat($data->data->solar_and_uvi->solar->value);
    $aktuvi        = $toFloat($data->data->solar_and_uvi->uvi->value);
    $aktvitr       = $toFloat($data->data->wind->wind_speed->value);
    $aktnarazvetru = $toFloat($data->data->wind->wind_gust->value);
    $aktsmervetru  = $toFloat($data->data->wind->wind_direction->value);
    $aktsrazky     = $toFloat($data->data->rainfall->daily->value);
    $aktpocasi     = $toInt($xml->input->sensor[0]->value ?? null);
    $aktualizovano = date("d.m.Y G:i", (int)($data->time ?? time()));

    $render([
        'teplota'=>$akteplota, 'vlhkost'=>$aktvlhkost, 'rosny'=>$aktrosnybod, 'pocitovka'=>$aktpocteplota,
        'tlak'=>$akttlak, 'osvit'=>$aktosvit, 'uvi'=>$aktuvi, 'vitr'=>$aktvitr, 'naraz'=>$aktnarazvetru,
        'smer'=>$aktsmervetru, 'srazky'=>$aktsrazky, 'pocasi'=>$wxLabel($aktpocasi), 'aktualizovano'=>$aktualizovano,
    ]);
    return;
}

/* ────────── Větev 2: Fallback na DB ────────── */
$conn = mysqli_connect($dbServer, $dbUzivatel, $dbHeslo, $dbDb);
if (!$conn) {
    // klidný výstup místo fatální chyby
    echo "<div class='aktualne'><div class='aktualneOdskok'>DB je dočasně nedostupná.</div></div>";
    return;
}

$sql = "SELECT * FROM `history_cron_padarovice` ORDER BY `date_time` DESC LIMIT 1";
$result = mysqli_query($conn, $sql);
$t = $result ? mysqli_fetch_assoc($result) : null;
mysqli_close($conn);

if ($t) {
    $akteplota     = $toFloat($t['temperature']        ?? null);
    $aktvlhkost    = $toFloat($t['humidity']           ?? null);
    $aktrosnybod   = $toFloat($t['dew_point']          ?? null);
    $aktpocteplota = $toFloat($t['temperature_apparent'] ?? null);
    $akttlak       = $toFloat($t['pressure_QNH']       ?? null);
    $aktosvit      = $toFloat($t['exposure']           ?? null);
    $aktuvi        = $toFloat($t['uvi']                ?? null);
    $aktvitr       = $toFloat($t['wind_speed']         ?? null);
    $aktnarazvetru = $toFloat($t['wind_gust']          ?? null);
    $aktsmervetru  = $toFloat($t['wind_direction']     ?? null);
    $aktsrazky     = $toFloat($t['rain_daily']         ?? null);
    $aktpocasi     = $toInt($xml->input->sensor[0]->value ?? null);
    $aktualizovano = $t['date_time'] ? date("d.m.Y G:i", strtotime($t['date_time'])) : date("d.m.Y G:i");

    $render([
        'teplota'=>$akteplota, 'vlhkost'=>$aktvlhkost, 'rosny'=>$aktrosnybod, 'pocitovka'=>$aktpocteplota,
        'tlak'=>$akttlak, 'osvit'=>$aktosvit, 'uvi'=>$aktuvi, 'vitr'=>$aktvitr, 'naraz'=>$aktnarazvetru,
        'smer'=>$aktsmervetru, 'srazky'=>$aktsrazky, 'pocasi'=>$wxLabel($aktpocasi), 'aktualizovano'=>$aktualizovano,
    ]);
} else {
    echo "<div class='aktualne'><div class='aktualneOdskok'>Není k dispozici žádný záznam.</div></div>";
}

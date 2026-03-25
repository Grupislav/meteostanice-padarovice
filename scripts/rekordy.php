<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/fce.php';
require_once __DIR__ . '/variableCheck.php';

// DB connect
$conn = mysqli_connect($dbServer, $dbUzivatel, $dbHeslo, $dbDb);
if (!$conn) {
  echo "<table width='100%' class='tabulkaVHlavicce'><tr class='radek zelenyRadek'><td colspan='3'>{$lang['rekordy']}</td></tr></table>";
  echo "<div class='aktualne'><div class='aktualneOdskok'>DB je dočasně nedostupná.</div></div>";
  return;
}

// helper: vezmi záznam s extrémem v daném sloupci
$ext = function(string $col, string $order = 'DESC') use ($conn) {
  $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
  $sql = "SELECT `$col` AS v, `date_time` AS d FROM `history_cron_padarovice` ORDER BY `$col` $order LIMIT 1";
  $res = mysqli_query($conn, $sql);
  if (!$res) return [null, null];
  $row = mysqli_fetch_assoc($res);
  if (!$row) return [null, null];
  $val = is_numeric($row['v']) ? (float)$row['v'] : null;
  $dt  = $row['d'] ?? null;
  $ds  = $dt ? date_format(date_create($dt), "d. m. Y") : '—';
  return [$val, $ds];
};

// vytáhneme extrémy
[$maxteplota,     $maxteplotadat]     = $ext('temperature',           'DESC');
[$minteplota,     $minteplotadat]     = $ext('temperature',           'ASC');
[$maxpocteplota,  $maxpocteplotadat]  = $ext('temperature_apparent',  'DESC');
[$minpocteplota,  $minpocteplotadat]  = $ext('temperature_apparent',  'ASC');
[$maxrosnybod,    $maxrosnyboddat]    = $ext('dew_point',             'DESC');
[$minrosnybod,    $minrosnyboddat]    = $ext('dew_point',             'ASC');
[$minvlhkost,     $minvlhkostdat]     = $ext('humidity',              'ASC');
[$maxdenniuhrn,   $maxdenniuhrndat]   = $ext('rain_daily',            'DESC');
[$maxvitr,        $maxvitrdat]        = $ext('wind_speed',            'DESC');
[$maxnaraz,       $maxnarazdat]       = $ext('wind_gust',             'DESC');
[$maxosvit,       $maxosvitdat]       = $ext('exposure',              'DESC');
[$maxtlak,        $maxtlakdat]        = $ext('pressure_QNH',          'DESC');
[$mintlak,        $mintlakdat]        = $ext('pressure_QNH',          'ASC');

mysqli_close($conn);

// výstup hlavičky
echo "<table width='100%' class='tabulkaVHlavicce'>
  <tr class='radek zelenyRadek'>
    <td colspan='3'>{$lang['rekordy']}</td>
  </tr>
</table>";

// pomocná funkce na jednu „kartu“ s tooltipem
$box = function(string $cls, string $label, string $valueHtml) {
  echo "<div class='aktualneMensi{$cls}'>
    <div class='aktualneOdskok'>
      {$label}<br>
      {$valueHtml}
    </div>
  </div>";
};

// teploty používají zvolenou jednotku (C/F)
$box(barvaRameckuTeploty($maxteplota ?? 0), $lang['nejvyssiteplota'],
  "<div class='aktuamens tooltip'>" . jednotkaTeploty($maxteplota, $u, 1) . "<span class='tooltiptext'>{$maxteplotadat}</span></div>"
);
$box(' vpravo' . barvaRameckuTeploty($minteplota ?? 0), $lang['nejnizsiteplota'],
  "<div class='aktuamens tooltip'>" . jednotkaTeploty($minteplota, $u, 1) . "<span class='tooltiptext'>{$minteplotadat}</span></div>"
);

$box(barvaRameckuTeploty($maxpocteplota ?? 0), $lang['nejvyssipocteplota'],
  "<div class='aktuamens tooltip'>" . jednotkaTeploty($maxpocteplota, $u, 1) . "<span class='tooltiptext'>{$maxpocteplotadat}</span></div>"
);
$box(' vpravo' . barvaRameckuTeploty($minpocteplota ?? 0), $lang['nejnizsipocteplota'],
  "<div class='aktuamens tooltip'>" . jednotkaTeploty($minpocteplota, $u, 1) . "<span class='tooltiptext'>{$minpocteplotadat}</span></div>"
);

$box(barvaRameckuTeploty($maxrosnybod ?? 0), $lang['nejvyssirosnybod'],
  "<div class='aktuamens tooltip'>" . jednotkaTeploty($maxrosnybod, $u, 1) . "<span class='tooltiptext'>{$maxrosnyboddat}</span></div>"
);
$box(' vpravo' . barvaRameckuTeploty($minrosnybod ?? 0), $lang['nejnizsirosnybod'],
  "<div class='aktuamens tooltip'>" . jednotkaTeploty($minrosnybod, $u, 1) . "<span class='tooltiptext'>{$minrosnyboddat}</span></div>"
);

// tlak, vítr, srážky, vlhkost (jednotky zůstávají stejné)
$box(barvaRameckuTlak($maxtlak ?? 0), $lang['nejvyssitlak'],
  "<div class='aktuamens tooltip'>" . ($maxtlak !== null ? $maxtlak . " hPa" : "—") . "<span class='tooltiptext'>{$maxtlakdat}</span></div>"
);
$box(' vpravo' . barvaRameckuTlak($mintlak ?? 0), $lang['nejnizsitlak'],
  "<div class='aktuamens tooltip'>" . ($mintlak !== null ? $mintlak . " hPa" : "—") . "<span class='tooltiptext'>{$mintlakdat}</span></div>"
);

$box(barvaRameckuVitr($maxvitr ?? 0), $lang['nejrychlejsivitr'],
  "<div class='aktuamens tooltip'>" . ($maxvitr !== null ? $maxvitr . " km/h" : "—") . "<span class='tooltiptext'>{$maxvitrdat}</span></div>"
);
$box(' vpravo' . barvaRameckuVitr($maxnaraz ?? 0), $lang['nejprudsinaraz'],
  "<div class='aktuamens tooltip'>" . ($maxnaraz !== null ? $maxnaraz . " km/h" : "—") . "<span class='tooltiptext'>{$maxnarazdat}</span></div>"
);

$box(barvaRameckuVlhkost($minvlhkost ?? 0), $lang['nejnizsivlhkost'],
  "<div class='aktuamens tooltip'>" . ($minvlhkost !== null ? $minvlhkost . " %" : "—") . "<span class='tooltiptext'>{$minvlhkostdat}</span></div>"
);
$box(' vpravo' . barvaRameckuSrazky($maxdenniuhrn ?? 0), $lang['nejvyssiuhrn'],
  "<div class='aktuamens tooltip'>" . ($maxdenniuhrn !== null ? $maxdenniuhrn . " mm" : "—") . "<span class='tooltiptext'>{$maxdenniuhrndat}</span></div>"
);

/* Máš-li chuť, můžeš vrátit i osvit:
$box(barvaRameckuOsvit($maxosvit ?? 0), $lang['maxosvit'],
  "<div class='aktuamens tooltip'>" . ($maxosvit !== null ? $maxosvit . " W" : "—") . "<span class='tooltiptext'>{$maxosvitdat}</span></div>"
);
*/

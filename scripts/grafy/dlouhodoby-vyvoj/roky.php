<?php
require __DIR__ . "/../../init.php";
require_once __DIR__ . "/../../fce.php";

$TABLE = "history_cron_padarovice";

$conn = mysqli_connect($dbServer, $dbUzivatel, $dbHeslo, $dbDb);
if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }

$sql = "
  SELECT
    YEAR(date_time)             AS y,
    AVG(temperature)            AS t_avg,
    MIN(temperature)            AS t_min,
    MAX(temperature)            AS t_max,
    AVG(dew_point)              AS dew,
    AVG(humidity)               AS hum,
    AVG(pressure_QNH)           AS qnh,
    AVG(exposure)               AS exp,
    AVG(wind_speed)             AS wind
  FROM {$TABLE}
  GROUP BY YEAR(date_time)
  ORDER BY y ASC";
$res = mysqli_query($conn, $sql);
// Rocni uhrny z prirustku, ne z MAX(rain_yearly) — na prelomu roku by leden
// pretahl lonsky uhrn. Viz denniUhrnySrazek v fce.php.
$rocniRain = uhrnyPoRocich(denniUhrnySrazek($conn, $TABLE));
mysqli_close($conn);

if (!$res || mysqli_num_rows($res) <= 0) { echo "Nemame data!"; return; }

$labels = $yMax = $yAvg = $yMin = $yRain = $yDew = $yHum = $yQnh = $yExp = $yWind = [];
while ($t = mysqli_fetch_assoc($res)) {
  $year = (string)$t['y'];
  $labels[] = $year === '2024'
    ? $year . " (" . ($lang['rok2024poznamka'] ?? 'od 13. 11.') . ")"
    : $year;

  $yMax[]  = jednotkaTeploty((float)$t['t_max'], $u, 0);
  $yAvg[]  = jednotkaTeploty(round((float)$t['t_avg'], 1), $u, 0);
  $yMin[]  = jednotkaTeploty((float)$t['t_min'], $u, 0);
  $yRain[] = $rocniRain[$year] ?? 0;

  $yDew[]  = jednotkaTeploty(round((float)$t['dew'], 1), $u, 0);
  $yHum[]  = round((float)$t['hum'], 1);
  $yQnh[]  = round(((float)$t['qnh']) * ($ut === 'mm' ? 0.750062 : 1), 1); // hPa → ev. mmHg
  $yExp[]  = round((float)$t['exp'], 1);
  $yWind[] = round(((float)$t['wind']) * ($uv === 'm' ? 1/3.6 : 1), 1); // km/h → ev. m/s
}
$jednotkaVit = jednotkaVitrSymbol($uv);
$jednotkaTl  = jednotkaTlakSymbol($ut);
?>
<script>
jQuery(function($){
  var chart = new Highcharts.Chart({
    chart: { renderTo:'graf-roky', zoomType:'x', backgroundColor:'#fff', borderRadius:0 },
    credits:{ enabled:false },
    xAxis: { categories: <?= json_encode($labels) ?>, labels:{ rotation:-45, align:'right' } },
    yAxis: [{
      labels:{ formatter:function(){ return this.value + ' <?= $jednotka ?>'; }, style:{ color:'#c4423f' } },
      title:{ text:null, style:{ color:'#c4423f' } }, opposite:false
    },{
      labels:{ formatter:function(){ return this.value + ' mm'; }, style:{ color:'#0066ff' } },
      title:{ text:null, style:{ color:'#0066ff' } }, opposite:true
    },{
      labels:{ formatter:function(){ return this.value + ' %'; }, style:{ color:'#33cccc' } },
      title:{ text:null, style:{ color:'#33cccc' } }, max:100, ceiling:100, opposite:true
    },{
      labels:{ formatter:function(){ return this.value + ' <?= $jednotkaTl ?>'; }, style:{ color:'#800000' } },
      title:{ text:null, style:{ color:'#800000' } }, opposite:true
    },{
      labels:{ formatter:function(){ return this.value + ' W'; }, style:{ color:'#999900' } },
      title:{ text:null, style:{ color:'#999900' } }, opposite:true
    },{
      labels:{ formatter:function(){ return this.value + ' <?= $jednotkaVit ?>'; }, style:{ color:'#3399ff' } },
      title:{ text:null, style:{ color:'#3399ff' } }, opposite:true
    }],
    tooltip:{ shared:true, crosshairs:true },
    legend:{ layout:'horizontal', align:'left', x:6, verticalAlign:'top', y:-5, floating:true, backgroundColor:'#fff' },
    series: [
      { name:'<?= $lang['avg'] ?>',  type:'spline', color:'#ebb91f', yAxis:0, data:<?= json_encode($yAvg) ?>, marker:{enabled:false} },
      { name:'<?= $lang['max'] ?>',  type:'spline', color:'#c01212', yAxis:0, data:<?= json_encode($yMax) ?>, marker:{enabled:false} },
      { name:'<?= $lang['min'] ?>',  type:'spline', color:'#1260c0', yAxis:0, data:<?= json_encode($yMin) ?>, marker:{enabled:false} },
      { name:'<?= $lang['srazky'] ?>', type:'column', color:'#0066ff', yAxis:1, data:<?= json_encode($yRain) ?>, marker:{enabled:false} },

      { name:'<?= $lang['rosnybod'] ?>',  type:'spline', color:'#009933', yAxis:0, data:<?= json_encode($yDew) ?>, marker:{enabled:false}, visible:false },
      { name:'<?= $lang['vlhkost'] ?>',   type:'spline', color:'#33cccc', yAxis:2, data:<?= json_encode($yHum) ?>, marker:{enabled:false}, visible:false },
      { name:'<?= $lang['tlak'] ?>',      type:'spline', color:'#800000', yAxis:3, data:<?= json_encode($yQnh) ?>, marker:{enabled:false}, visible:false },
      { name:'<?= $lang['osvit'] ?>',     type:'spline', color:'#e6e600', yAxis:4, data:<?= json_encode($yExp) ?>, marker:{enabled:false}, visible:false },
      { name:'<?= $lang['vitr'] ?>',      type:'spline', color:'#3399ff', yAxis:5, data:<?= json_encode($yWind) ?>, marker:{enabled:false}, visible:false }
    ]
  });

  $(".tabs > li").on('click', function(){ chart.reflow(); });
});
</script>

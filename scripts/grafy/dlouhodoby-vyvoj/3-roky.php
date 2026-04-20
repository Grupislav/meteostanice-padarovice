<?php
require __DIR__ . "/../../init.php";
require_once __DIR__ . "/../../fce.php";

$TABLE = "history_cron_padarovice";

$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }

$sql = "
  SELECT
    DATE_FORMAT(date_time, '%Y-%m-01') AS m,
    AVG(temperature)          AS t_avg,
    MIN(temperature)          AS t_min,
    MAX(temperature)          AS t_max,
    MAX(rain_monthly)         AS rain_m,      -- mìsíèní úhrn
    AVG(dew_point)            AS dew,
    AVG(humidity)             AS hum,
    AVG(pressure_QNH)         AS qnh,
    AVG(exposure)             AS exp,
    AVG(wind_speed)           AS wind
  FROM {$TABLE}
  GROUP BY YEAR(date_time), MONTH(date_time)
  ORDER BY m DESC
  LIMIT 36";
$res = mysqli_query($conn, $sql);
mysqli_close($conn);

if (!$res || mysqli_num_rows($res) <= 0) { echo "Nemame data!"; return; }

$labels=$yMax=$yAvg=$yMin=$yRain=$yDew=$yHum=$yQnh=$yExp=$yWind=[];
while($t = mysqli_fetch_assoc($res)){
  $labels[] = substr($t['m'],0,7);
  $yMax[]   = jednotkaTeploty((float)$t['t_max'], $u, 0);
  $yAvg[]   = jednotkaTeploty(round((float)$t['t_avg'],1), $u, 0);
  $yMin[]   = jednotkaTeploty((float)$t['t_min'], $u, 0);
  $yRain[]  = round((float)$t['rain_m'], 1);

  $yDew[]   = jednotkaTeploty(round((float)$t['dew'],1),   $u, 0);
  $yHum[]   = round((float)$t['hum'],1);
  $yQnh[]   = round((float)$t['qnh'],1);
  $yExp[]   = round((float)$t['exp'],1);
  $yWind[]  = round((float)$t['wind'],1);
}
// chronologicky vzestupnì
$labels = array_reverse($labels);
$yMax   = array_reverse($yMax);
$yAvg   = array_reverse($yAvg);
$yMin   = array_reverse($yMin);
$yRain  = array_reverse($yRain);
$yDew   = array_reverse($yDew);
$yHum   = array_reverse($yHum);
$yQnh   = array_reverse($yQnh);
$yExp   = array_reverse($yExp);
$yWind  = array_reverse($yWind);
?>
<script>
jQuery(function($){
  var chart = new Highcharts.Chart({
    chart: { renderTo:'graf-3-roky', zoomType:'x', backgroundColor:'#fff', borderRadius:0 },
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
      labels:{ formatter:function(){ return this.value + ' hPa'; }, style:{ color:'#800000' } },
      title:{ text:null, style:{ color:'#800000' } }, opposite:true
    },{
      labels:{ formatter:function(){ return this.value + ' W'; }, style:{ color:'#999900' } },
      title:{ text:null, style:{ color:'#999900' } }, opposite:true
    },{
      labels:{ formatter:function(){ return this.value + ' m/s'; }, style:{ color:'#3399ff' } },
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

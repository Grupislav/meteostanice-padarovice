<?php
require_once __DIR__ . "/../../../config.php";
require_once __DIR__ . "/../../fce.php";
require_once __DIR__ . "/../../variableCheck.php";

$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { echo "Nem·me data!"; return; }
mysqli_query($conn, "SET NAMES 'utf8mb4'");

$sql = "
  SELECT 
    FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(date_time)/300)*300) AS bucket,
    AVG(temperature)           AS temperature,
    AVG(temperature_apparent)  AS temperature_apparent,
    AVG(humidity)              AS humidity,
    AVG(dew_point)             AS dew_point,
    AVG(rain_rate)             AS rain_rate,   -- mm/h (intenzita)
    MAX(rain_daily)            AS rain_daily,  -- mm (kumulativ za den)
    AVG(pressure_QNH)          AS pressure_QNH,
    AVG(exposure)              AS exposure,
    AVG(wind_speed)            AS wind_speed
  FROM history_cron_padarovice
  WHERE date_time >= NOW() - INTERVAL 1 DAY
  GROUP BY bucket
  ORDER BY bucket ASC";
$res = mysqli_query($conn, $sql);
mysqli_close($conn);

if (!$res || mysqli_num_rows($res) <= 0) { echo "Nem·me data!"; return; }

$labels=[]; $yTemp=[]; $yApp=[]; $yHum=[]; $yDew=[];
$yRate=[]; $yCum=[]; $yPres=[]; $yExp=[]; $yWind=[];
while ($r = mysqli_fetch_assoc($res)) {
  $labels[] = $r['bucket'];
  $yTemp[]  = round(jednotkaTeploty((float)$r['temperature'], $u, 0), 1);
  $yApp[]   = round(jednotkaTeploty((float)$r['temperature_apparent'], $u, 0), 1);
  $yHum[]   = round((float)$r['humidity'], 1);
  $yDew[]   = round(jednotkaTeploty((float)$r['dew_point'], $u, 0), 1);
  $yRate[]  = is_null($r['rain_rate'])  ? 0 : (float)$r['rain_rate'];   // mm/h
  $yCum[]   = is_null($r['rain_daily']) ? 0 : round((float)$r['rain_daily'], 1); // mm
  $yPres[]  = round((float)$r['pressure_QNH'], 1);
  $yExp[]   = round((float)$r['exposure'], 1);
  $yWind[]  = round((float)$r['wind_speed'], 1);
}

// X osa + svislÈ Ë·ry mezi dny
$plotLines = []; $labelsOut = []; $prevDay = null;
foreach ($labels as $i => $ts) {
  $day = substr($ts, 0, 10);
  if ($prevDay !== null && $day !== $prevDay) { $plotLines[] = $i; }
  $labelsOut[] = substr($ts, 11, 5);
  $prevDay = $day;
}
$plotLinesOut = implode(',', array_map(fn($v)=>"{ color:'lightgrey', dashStyle:'solid', value:$v, width:1 }",$plotLines));
$jednotka = jednotkaSymbol($u);
?>
<script>
jQuery(function($){
  var chart = new Highcharts.Chart({
    chart: { renderTo:'graf-24-hodin', zoomType:'x', backgroundColor:'#ffffff', borderRadius:0 },
    credits: { enabled:false },
    xAxis: {
      categories: <?= json_encode($labelsOut) ?>,
      labels: { rotation:-45, align:'right', step:10 },
      plotLines: [<?= $plotLinesOut ?>]
    },
    yAxis: [{
      // 0 ó teploty
      labels: { formatter:function(){ return this.value + ' <?= $jednotka ?>'; }, style:{color:'#c4423f'} },
      title: { text:null, style:{color:'#c4423f'} },
      opposite:false
    },{
      // 1 ó vlhkost
      title:{ text:null, style:{color:'#33cccc'} },
      labels:{ formatter:function(){ return this.value + ' %'; }, style:{color:'#33cccc'} },
      opposite:true, max:100, ceiling:100
    },{
      // 2 ó sr·ûky intenzita (mm/h)
      title:{ text:null, style:{color:'#0066ff'} },
      labels:{ formatter:function(){ return this.value + ' mm/h'; }, style:{color:'#0066ff'} },
      opposite:true
    },{
      // 3 ó tlak
      title:{ text:null, style:{color:'#800000'} },
      labels:{ formatter:function(){ return this.value + ' hPa'; }, style:{color:'#800000'} },
      opposite:true
    },{
      // 4 ó osvit
      title:{ text:null, style:{color:'#999900'} },
      labels:{ formatter:function(){ return this.value + ' W'; }, style:{color:'#999900'} },
      opposite:true
    },{
      // 5 ó vÌtr
      title:{ text:null, style:{color:'#3399ff'} },
      labels:{ formatter:function(){ return this.value + ' m/s'; }, style:{color:'#3399ff'} },
      opposite:true
    },{
      // 6 ó sr·ûky kumulativ (mm)
      title:{ text:null, style:{color:'#0047b3'} },
      labels:{ formatter:function(){ return this.value + ' mm'; }, style:{color:'#0047b3'} },
      opposite:true
    }],
    tooltip: {
      shared:false, // aù se spr·vnÏ zobrazÌ jednotky pro r˘znÈ osy
      formatter: function () {
        var unit = {
          '<?= $lang['srazky'] ?>'         : ' mm/h',
          '<?= $lang['kumsrazkyden'] ?>'   : ' mm',
          '<?= $lang['teplota'] ?>'        : ' <?= $jednotka ?>',
          '<?= $lang['pocteplotazkratka'] ?>' : ' <?= $jednotka ?>',
          '<?= $lang['vlhkost'] ?>'        : ' %',
          '<?= $lang['rosnybod'] ?>'       : ' <?= $jednotka ?>',
          '<?= $lang['tlak'] ?>'           : ' hPa',
          '<?= $lang['osvit'] ?>'          : ' W',
          '<?= $lang['vitr'] ?>'           : ' m/s'
        }[this.series.name] || '';
        return '<b>' + this.x + '</b><br /><span style="color:'+this.series.color+'">\u25CF</span> ' +
               this.series.name + ': <b>' + this.y + unit + '</b>';
      },
      crosshairs: true
    },
    legend: { layout:'horizontal', align:'left', x:6, verticalAlign:'top', y:-5, floating:true, backgroundColor:'#FFFFFF' },

    // óóó po¯adÌ sÈriÌ: sr·ûky (sloupec + kumulativ) õ teploty õ ostatnÌ óóó
    series: [{
      name:'<?= $lang['teplota'] ?>',
      type:'spline', color:'#c4423f', yAxis:0,
      data: <?= json_encode($yTemp) ?>, marker:{enabled:false}
    },{
      name:'<?= $lang['pocteplotazkratka'] ?>',
      type:'spline', color:'#990099', yAxis:0,
      data: <?= json_encode($yApp) ?>, marker:{enabled:false}, visible:false
    },{
      name:'<?= $lang['srazky'] ?>',
      type:'column', color:'#0066ff', yAxis:2,
      data: <?= json_encode($yRate) ?>, marker:{enabled:false}
    },{
      name:'<?= $lang['kumsrazkyden'] ?>',
      type:'spline', color:'#0047b3', yAxis:6,
      data: <?= json_encode($yCum) ?>, marker:{enabled:false}, visible:false
    },{
      name:'<?= $lang['vlhkost'] ?>',
      type:'spline', color:'#33cccc', yAxis:1,
      data: <?= json_encode($yHum) ?>, marker:{enabled:false}, visible:false
    },{
      name:'<?= $lang['rosnybod'] ?>',
      type:'spline', color:'#009933', yAxis:0,
      data: <?= json_encode($yDew) ?>, marker:{enabled:false}, visible:false
    },{
      name:'<?= $lang['tlak'] ?>',
      type:'spline', color:'#800000', yAxis:3,
      data: <?= json_encode($yPres) ?>, marker:{enabled:false}, visible:false
    },{
      name:'<?= $lang['osvit'] ?>',
      type:'spline', color:'#e6e600', yAxis:4,
      data: <?= json_encode($yExp) ?>, marker:{enabled:false}, visible:false
    },{
      name:'<?= $lang['vitr'] ?>',
      type:'spline', color:'#3399ff', yAxis:5,
      data: <?= json_encode($yWind) ?>, marker:{enabled:false}, visible:false
    }]
  });

  $(".tabs > li").on('click', function(){ chart.reflow(); });
});
</script>

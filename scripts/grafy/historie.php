<?php
// INIT
require __DIR__ . "/../init.php";
require_once __DIR__ . "/../fce.php";
require_once __DIR__ . "/../variableCheck.php";

$TABLE = "history_cron_padarovice";

// Den z GET (ovìøíme znovu)
$den = isset($_GET['den']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['den'])
  ? $_GET['den']
  : date('Y-m-d', strtotime('-1 day'));

// Jedna connection
$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }
$denEsc = mysqli_real_escape_string($conn, $den);

/**
 * Agregace do 10min bucketù:
 *  - bucket_time = 00:00, 00:10, 00:20, ...
 *  - teplota/pocteplota/… = AVG
 *  - srážky = MAX(rain_daily) - MIN(rain_daily) v bucketu (pøírùstek)
 * Pozn.: pokud nemáš rain_daily, bude potøeba pøepnout na SUM(precipitation) a odstranit delta.
 */
$sql = "
SELECT
  SEC_TO_TIME(FLOOR(TIME_TO_SEC(TIME(date_time))/600)*600) AS bucket_time,
  AVG(temperature)            AS avg_temp,
  AVG(temperature_apparent)   AS avg_app,
  AVG(humidity)               AS avg_hum,
  AVG(dew_point)              AS avg_dew,
  (MAX(rain_daily) - MIN(rain_daily)) AS rain_inc,
  AVG(pressure_QNH)           AS avg_press,
  AVG(exposure)               AS avg_expo,
  AVG(wind_speed)             AS avg_wind
FROM {$TABLE}
WHERE DATE(date_time) = '{$denEsc}'
GROUP BY bucket_time
ORDER BY bucket_time ASC";

$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) <= 0) {
  mysqli_close($conn);
  echo "Nemame data!";
  return;
}

// Pøipravíme pole pro Highcharts
$labels = $yT = $yApp = $yHum = $yDew = $yRain = $yPress = $yExpo = $yWind = [];

while ($r = mysqli_fetch_assoc($res)) {
  $labels[] = substr($r['bucket_time'], 0, 5);

  $yT[]    = round(jednotkaTeploty((float)$r['avg_temp'], $u, 0), 1);
  $yApp[]  = round(jednotkaTeploty((float)$r['avg_app'], $u, 0), 1);
  $yHum[]  = round((float)$r['avg_hum'], 1);
  $yDew[]  = round(jednotkaTeploty((float)$r['avg_dew'], $u, 0), 1);
  $yRain[] = round(max(0, (float)$r['rain_inc']), 2); // jistota že delta nebude záporná
  $yPress[]= round((float)$r['avg_press'], 1);
  $yExpo[] = round((float)$r['avg_expo'], 1);
  $yWind[] = round((float)$r['avg_wind'], 1);
}
mysqli_close($conn);
?>
<script type="text/javascript">
  $(function () {
    var chart;
    $(document).ready(function () {
      chart = new Highcharts.Chart({
        chart: {renderTo: 'graf-historie', zoomType: 'x', backgroundColor: '#ffffff', borderRadius: 0},
        credits: {enabled: 0},
        xAxis: {
          categories: ['<?php echo implode("','", $labels); ?>'],
          labels: {rotation: -45, align: 'right', step: 3}
        },
        yAxis: [{
          labels: { formatter: function(){ return this.value + ' <?php echo "$jednotka"; ?>'; }, style: {color: '#c4423f'} },
          title: { text: null, style: {color: '#c4423f'} },
          opposite: false
        }, {
          title: { text: null, style: {color: '#33cccc'} },
          labels: { formatter: function(){ return this.value + ' %'; }, style: {color: '#33cccc'} },
          opposite: true, max: 100, ceiling: 100
        }, {
          title: { text: null, style: {color: '#0066ff'} },
          labels: { formatter: function(){ return this.value + ' mm'; }, style: {color: '#0066ff'} },
          opposite: true
        }, {
          title: { text: null, style: {color: '#800000'} },
          labels: { formatter: function(){ return this.value + ' hPa'; }, style: {color: '#800000'} },
          opposite: true
        }, {
          title: { text: null, style: {color: '#999900'} },
          labels: { formatter: function(){ return this.value + ' W'; }, style: {color: '#999900'} },
          opposite: true
        }, {
          title: { text: null, style: {color: '#3399ff'} },
          labels: { formatter: function(){ return this.value + ' m/s'; }, style: {color: '#3399ff'} },
          opposite: true
        }],
        tooltip: {
          formatter: function () {
            var unit = {
              '<?php echo $lang['teplota'] ?>': '<?php echo "$jednotka"; ?>',
              '<?php echo $lang['pocteplota'] ?>': '<?php echo "$jednotka"; ?>',
              '<?php echo $lang['vlhkost'] ?>': '%',
              '<?php echo $lang['rosnybod'] ?>': '<?php echo "$jednotka"; ?>',
              '<?php echo $lang['srazky'] ?>': ' mm',
              '<?php echo $lang['tlak'] ?>': ' hPa',
              '<?php echo $lang['osvit'] ?>': ' W',
              '<?php echo $lang['vitr'] ?>': ' m/s'
            }[this.series.name];
            return '<b>' + this.x + '</b><br /><b>' + this.y + ' ' + unit + '</b>';
          },
          crosshairs: true
        },
        legend: {
          layout: 'horizontal', align: 'left', x: 6,
          verticalAlign: 'top', y: -5, floating: true, backgroundColor: '#FFFFFF'
        },
        series: [{
          name: '<?php echo $lang['teplota'] ?>',
          type: 'spline', color: '#c4423f', yAxis: 0,
          data: [<?php echo implode(", ", $yT); ?>],
          marker: {enabled: false}
        }, {
          name: '<?php echo $lang['pocteplota'] ?>',
          type: 'spline', color: '#990099', yAxis: 0,
          data: [<?php echo implode(", ", $yApp); ?>],
          marker: {enabled: false}, visible: false
        }, {
          name: '<?php echo $lang['vlhkost'] ?>',
          type: 'spline', color: '#33cccc', yAxis: 1,
          data: [<?php echo implode(", ", $yHum); ?>],
          marker: {enabled: false}, visible: false
        }, {
          name: '<?php echo $lang['rosnybod'] ?>',
          type: 'spline', color: '#009933', yAxis: 0,
          data: [<?php echo implode(", ", $yDew); ?>],
          marker: {enabled: false}, visible: false
        }, {
          name: '<?php echo $lang['srazky'] ?>',
          type: 'column', color: '#0066ff', yAxis: 2,
          data: [<?php echo implode(", ", $yRain); ?>],
          marker: {enabled: false}<?php /* nechávám defaultnì viditelné = mùžeš dát visible:false */ ?>
        }, {
          name: '<?php echo $lang['tlak'] ?>',
          type: 'spline', color: '#800000', yAxis: 3,
          data: [<?php echo implode(", ", $yPress); ?>],
          marker: {enabled: false}, visible: false
        }, {
          name: '<?php echo $lang['osvit'] ?>',
          type: 'spline', color: '#e6e600', yAxis: 4,
          data: [<?php echo implode(", ", $yExpo); ?>],
          marker: {enabled: false}, visible: false
        }, {
          name: '<?php echo $lang['vitr'] ?>',
          type: 'spline', color: '#3399ff', yAxis: 5,
          data: [<?php echo implode(", ", $yWind); ?>],
          marker: {enabled: false}, visible: false
        }]
      });

      $(".tabs > li").on('click', function(){ chart.reflow(); });
    });
  });
</script>

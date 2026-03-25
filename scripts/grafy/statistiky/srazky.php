<?php
// INIT
require __DIR__ . "/../../init.php";
require_once __DIR__ . "/../../fce.php";

$TABLE = "history_cron_padarovice";

$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }
mysqli_query($conn, "SET NAMES 'utf8mb4'");

$sql = "
  SELECT
    DATE_FORMAT(h.date_time, '%Y-%m-01') AS ym,
    MAX(h.rain_monthly)                  AS srazky,
    MONTH(h.date_time)                   AS mesic,
    pn.normal19812010,
    pn.normal19611990,
    pn.normal19912020
  FROM {$TABLE} h
  JOIN precipitation_normals pn ON pn.month = MONTH(h.date_time)
  GROUP BY YEAR(h.date_time), MONTH(h.date_time)
  ORDER BY ym DESC
  LIMIT 36";
$res = mysqli_query($conn, $sql);
mysqli_close($conn);

if (!$res || mysqli_num_rows($res) <= 0) { echo "Nemame data!"; return; }

$labels = $ydata = $ydata2 = $ydata3 = $ydata4 = [];
while ($t = mysqli_fetch_assoc($res)) {
  $labels[] = substr($t['ym'],0,7);
  $ydata[]  = round((float)$t['srazky'], 1);
  $ydata2[] = (float)$t['normal19812010'];
  $ydata3[] = (float)$t['normal19611990'];
  $ydata4[] = (float)$t['normal19912020'];
}
$labels = array_reverse($labels);
$ydata  = array_reverse($ydata);
$ydata2 = array_reverse($ydata2);
$ydata3 = array_reverse($ydata3);
$ydata4 = array_reverse($ydata4);
?>
<script>
jQuery(function($){
  var chart = new Highcharts.Chart({
    chart:{ renderTo:'graf-stat-srazky', zoomType:'x', backgroundColor:'#fff', borderRadius:0 },
    title: {text: '<?php echo "."; ?>'},
    credits:{ enabled:false },
    xAxis:{ categories: <?= json_encode($labels) ?>, labels:{ rotation:-45, align:'right' } },
    yAxis:[{
      title:{ text:null, style:{ color:'#0066ff' } },
      labels:{ formatter:function(){ return this.value + ' mm'; }, style:{ color:'#0066ff' } },
      opposite:false
    }],
    tooltip:{ shared:true, crosshairs:true },
    legend:{ layout:'horizontal', align:'left', x:6, verticalAlign:'top', y:-5, floating:true, backgroundColor:'#fff' },
    series:[
      { name:'<?= $lang['skutecnost'] ?? 'Skutečnost' ?>',      type:'column', color:'#c01212', yAxis:0, data: <?= json_encode($ydata) ?>,  marker:{enabled:false} },
      { name:'<?= $lang['normal19912020'] ?? 'Normál 1991–2020' ?>',  type:'column', color:'#2f9e44', yAxis:0, data: <?= json_encode($ydata4) ?>, marker:{enabled:false} },
      { name:'<?= $lang['normal19812010'] ?? 'Normál 1981–2010' ?>',  type:'column', color:'#ebb91f', yAxis:0, data: <?= json_encode($ydata2) ?>, marker:{enabled:false} },
      { name:'<?= $lang['normal19611990'] ?? 'Normál 1961–1990' ?>',  type:'column', color:'#1260c0', yAxis:0, data: <?= json_encode($ydata3) ?>, marker:{enabled:false} }
    ]
  });
  $(".tabs > li").on('click', function(){ chart.reflow(); });
});
</script>

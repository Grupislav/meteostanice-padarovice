<?php
// INIT
require __DIR__ . "/../../init.php";
require_once __DIR__ . "/../../fce.php";

$TABLE = "history_cron_padarovice";

$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }
mysqli_query($conn, "SET NAMES 'utf8mb4'");

// Mesicni uhrny se scitaji z prirustku (viz denniUhrnySrazek v fce.php) — MAX(rain_monthly)
// pres kalendarni mesic sebere hodnotu prechoziho mesice, kdyz je ten novy sussi.
$mesicni = uhrnyPoMesicich(denniUhrnySrazek($conn, $TABLE));

$normaly = [];
if ($res = mysqli_query($conn, "SELECT month, normal19812010, normal19611990, normal19912020 FROM precipitation_normals")) {
  while ($n = mysqli_fetch_assoc($res)) { $normaly[(int)$n['month']] = $n; }
}
mysqli_close($conn);

if (!$mesicni) { echo "Nemame data!"; return; }

ksort($mesicni);
$mesicni = array_slice($mesicni, -36, null, true); // poslednich 36 mesicu, chronologicky

$labels = $ydata = $ydata2 = $ydata3 = $ydata4 = [];
foreach ($mesicni as $ym => $mm) {
  $m = (int)substr($ym, 5, 2);
  $labels[] = $ym;
  $ydata[]  = $mm;
  $ydata2[] = isset($normaly[$m]) ? (float)$normaly[$m]['normal19812010'] : null;
  $ydata3[] = isset($normaly[$m]) ? (float)$normaly[$m]['normal19611990'] : null;
  $ydata4[] = isset($normaly[$m]) ? (float)$normaly[$m]['normal19912020'] : null;
}
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

<?php
// INIT
require __DIR__ . "/../../init.php";
require_once __DIR__ . "/../../fce.php";

$TABLE = "history_cron_padarovice";

$conn = mysqli_connect($dbServer,$dbUzivatel,$dbHeslo,$dbDb);
if (!$conn) { echo "Nejaky problem s DB: " . mysqli_connect_error(); return; }

$sql = "
  SELECT
    DATE(h.date_time)  AS d,
    MONTH(h.date_time) AS mesic,
    MAX(h.temperature) AS maximum,
    MIN(h.temperature) AS minimum
  FROM {$TABLE} h
  GROUP BY YEAR(h.date_time), MONTH(h.date_time), DAY(h.date_time)
  ORDER BY d ASC";
$res = mysqli_query($conn, $sql);
mysqli_close($conn);

if (!$res || mysqli_num_rows($res) <= 0) { echo 'Nemame data!'; return; }

$labels=[]; $y1=$y2=$y3=$y4=$y5=$y6=[];
$letni=$tropicke=$tropnoci=$mrazove=$ledove=$arkticke=0;
$mesicpredtim=null; $datumpredtim=null;

while($t = mysqli_fetch_assoc($res)){
  $m = (int)$t['mesic'];
  if ($mesicpredtim !== null && $m !== $mesicpredtim) {
    $labels[] = substr($datumpredtim,0,7);
    $y1[]=$letni; $y2[]=$tropicke; $y3[]=$tropnoci; $y4[]=$mrazove; $y5[]=$ledove; $y6[]=$arkticke;
    $letni=$tropicke=$tropnoci=$mrazove=$ledove=$arkticke=0;
  }
  if ((float)$t['maximum'] >= 25) $letni++;
  if ((float)$t['maximum'] >= 30) $tropicke++;
  if ((float)$t['minimum'] >= 20) $tropnoci++;
  if ((float)$t['minimum'] <   0) $mrazove++;
  if ((float)$t['maximum'] <   0) $ledove++;
  if ((float)$t['maximum'] < -10) $arkticke++;

  $mesicpredtim = $m;
  $datumpredtim = $t['d'];
}
// poslední mìsíc
if ($datumpredtim){
  $labels[] = substr($datumpredtim,0,7);
  $y1[]=$letni; $y2[]=$tropicke; $y3[]=$tropnoci; $y4[]=$mrazove; $y5[]=$ledove; $y6[]=$arkticke;
}
?>
<script>
jQuery(function($){
  var chart = new Highcharts.Chart({
    chart:{ renderTo:'graf-stat-chardny', zoomType:'x', backgroundColor:'#fff', borderRadius:0 },
    credits:{ enabled:false },
    xAxis:{ categories: <?= json_encode($labels) ?>, labels:{ rotation:-45, align:'right' } },
    yAxis:[{
      title:{ text:null, style:{ color:'#c4423f' } },
      labels:{ formatter:function(){ return this.value; }, style:{ color:'#c4423f' } },
      opposite:false
    }],
    tooltip:{ shared:true, crosshairs:true },
    legend:{ layout:'horizontal', align:'left', x:6, verticalAlign:'top', y:-5, floating:true, backgroundColor:'#fff' },
    series:[
      { name:'<?= $lang['letnidny'] ?>',    type:'column', color:'#ff6600', yAxis:0, data: <?= json_encode($y1) ?>, marker:{enabled:false} },
      { name:'<?= $lang['tropickedny'] ?>', type:'column', color:'#ff3300', yAxis:0, data: <?= json_encode($y2) ?>, marker:{enabled:false} },
      { name:'<?= $lang['tropickenoci'] ?>',type:'column', color:'#ff944d', yAxis:0, data: <?= json_encode($y3) ?>, marker:{enabled:false}, visible:false },
      { name:'<?= $lang['mrazovedny'] ?>',  type:'column', color:'#83b2e3', yAxis:0, data: <?= json_encode($y4) ?>, marker:{enabled:false} },
      { name:'<?= $lang['ledovedny'] ?>',   type:'column', color:'#4c8aca', yAxis:0, data: <?= json_encode($y5) ?>, marker:{enabled:false} },
      { name:'<?= $lang['arktickedny'] ?>', type:'column', color:'#3573b1', yAxis:0, data: <?= json_encode($y6) ?>, marker:{enabled:false} }
    ]
  });
  $(".tabs > li").on('click', function(){ chart.reflow(); });
});
</script>

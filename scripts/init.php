<?php

// Je lepší volat před každým zpracováním grafu

// INIT
//if(isset($od))     { unset($od);  }    $od     = Array();
//if(isset($do))     { unset($do);  }    $do     = Array();
if(isset($ydata))  { unset($ydata);  } $ydata  = Array();
if(isset($ydata2)) { unset($ydata2); } $ydata2 = Array();
if(isset($ydata3)) { unset($ydata3); } $ydata3 = Array();
if(isset($ydata4)) { unset($ydata4); } $ydata4 = Array();
if(isset($ydata5)) { unset($ydata5); } $ydata5 = Array();
if(isset($ydata6)) { unset($ydata6); } $ydata6 = Array();
if(isset($ydata7)) { unset($ydata7); } $ydata7 = Array();
if(isset($ydata8)) { unset($ydata8); } $ydata8 = Array();
if(isset($labels)) { unset($labels); } $labels = Array();
$minteplota = ""; $maxteplota = ""; $teplota = ""; $vlhkost = ""; $rosnyBod = ""; $srazky = ""; $tlak = ""; $osvit = ""; $vitr = ""; $count = 0;

// zjistime jednotku teploty
$jednotkap = explode(" ", jednotkaTeploty(1, $u, 1));
$jednotka = str_replace("&deg;", "°", $jednotkap[1]);
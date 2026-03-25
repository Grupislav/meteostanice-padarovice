<?php

if (!function_exists('e')) {
    function e(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('urlWithParams')) {
    function urlWithParams(array $params): string {
        // Vezme aktuální URL bez query a přidá/aktualizuje parametry
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path   = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // bez query
        // Sloučí existující GET s novými parametry
        $merged = array_merge($_GET, $params);
        return $scheme . '://' . $host . $path . '?' . http_build_query($merged, '', '&', PHP_QUERY_RFC3986);
    }
}

function FazeMesice($cislo) {
    $i = (int)$cislo; // <- DŮLEŽITÉ
    $map = [
        1=>'nov', 2=>'dorusta', 3=>'prvnictvrt', 4=>'dorustamesic',
        5=>'uplnek', 6=>'couva', 7=>'poslednictvrt', 8=>'ubyva'
    ];
    return $map[$i] ?? 'chyba';
}

// směr větru (stupně mohou přijít jako SimpleXMLElement/string)
function SmerVetru($deg) {
    $d = (float)$deg;                       // <- DŮLEŽITÉ
    if (!is_finite($d)) return 'chyba';
    $d = fmod($d + 360.0, 360.0);

    if ($d >= 337.5 || $d < 22.5)  return "S &#8595;";
    if ($d >= 22.5  && $d < 67.5)  return "SV &#8601;";
    if ($d >= 67.5  && $d < 112.5) return "V &#8592;";
    if ($d >= 112.5 && $d < 157.5) return "JV &#8598;";
    if ($d >= 157.5 && $d < 202.5) return "J &#8593;";
    if ($d >= 202.5 && $d < 247.5) return "JZ &#8599;";
    if ($d >= 247.5 && $d < 292.5) return "Z &#8594;";
    if ($d >= 292.5 && $d < 337.5) return "SZ &#8600;";
    return "chyba";
}

// typ počasí
function Pocasi($cislo) {
    $i = (int)$cislo; // <- DŮLEŽITÉ
    $map = [1=>'jasno',2=>'skorojasno',3=>'polojasno',4=>'zatazeno',5=>'prehanky',6=>'dest'];
    return $map[$i] ?? 'chyba';
}

/**
 * formatData() vrací datum a čas
 * @param $datum
 * @return string
 */

function formatData($datum) {
    $dt = date_create((string)$datum); // <- přetypovat na string
    return $dt ? $dt->format('j.n.Y H:i') : (string)$datum;
}

function formatDnu($datum) {
    $dt = date_create($datum);
    return $dt ? $dt->format('j. n. Y') : (string)$datum;
}

/**
 * fahrenheit();
 * @param $teplota
 * @return float
 */
function fahrenheit($teplota)
{
    return round((1.8 * $teplota) + 32, 1);
}

/**
 * kelvin();
 * @param $teplota
 * @return float
 */
function kelvin($teplota)
{
    return round($teplota + 273.15, 1);
}

/**
 * rankine();
 * @param $teplota
 * @return float
 */
function rankine($teplota)
{
    return round(($teplota + 273.15) * (9 / 5), 1);
}

/**
 * delisle();
 * @param $teplota
 * @return float
 */
function delisle($teplota)
{
    return round((100 - $teplota) * (3 / 2), 1);
}

/**
 * newton();
 * @param $teplota
 * @return float
 */
function newton($teplota)
{
    return round($teplota * (33 / 100), 1);
}

/**
 * reaumur();
 * @param $teplota
 * @return float
 */
function reaumur($teplota)
{
    return round($teplota * (4 / 5), 1);
}

/**
 * romer();
 * @param $teplota
 * @return float
 */
function romer($teplota)
{
    return round($teplota * (21 / 40) + 7.5, 1);
}

function jednotkaTeploty($teplota = "", $jednotka = "C", $znak = 0) {
    $fmt = function($v, $s='') use($znak){ return $znak ? ($v . " $s") : $v; };
    if ($teplota === "" && $teplota !== 0) { return "-"; }

    switch ($jednotka) {
        case 'C': return $fmt($teplota, '&deg;C');
        case 'F': return $fmt(round(1.8*$teplota+32,1), '&deg;F');
        case 'K': return $fmt(round($teplota+273.15,1), '&deg;K');
        case 'R': return $fmt(round(($teplota+273.15)*(9/5),1), '&deg;R');
        case 'D': return $fmt(round((100-$teplota)*(3/2),1), '&deg;De');
        case 'N': return $fmt(round($teplota*(33/100),1), '&deg;N');
        case 'Re':return $fmt(round($teplota*(4/5),1), '&deg;Ré');
        case 'Ro':return $fmt(round($teplota*(21/40)+7.5,1), '&deg;Ro');
        default:  return "-";
    }
}

if (!function_exists('jednotkaSymbol')) {
  function jednotkaSymbol(string $u): string {
    switch ($u) {
      case 'F':  return '&deg;F';
      case 'K':  return '&deg;K';
      case 'R':  return '&deg;R';
      case 'D':  return '&deg;De';
      case 'N':  return '&deg;N';
      case 'Re': return '&deg;Ré';
      case 'Ro': return '&deg;Ro';
      default:   return '&deg;C';
    }
  }
}

/**
 * jeVikend() - podle date urci typ dne
 * @param date $datum
 * @return int
 */

function jeVikend($datum)
{
    $denVTydnu = date("N", mktime(0, 0, 0, substr($datum, 5, 2), substr($datum, 8, 2), substr($datum, 0, 4)));
    if($denVTydnu == 6 OR $denVTydnu == 7)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

/**
 * rosnyBod();
 * @param float $teplota
 * @param float $vlhkost
 * @return float
 */

/*function rosnyBod($teplota, $vlhkost)
{
    // Temperature    Range      Tn (°C)         m
    // Above water    0 – 50°C    243.12     17.62
    // Above ice     -40 – 0°C    272.62     22.46

    if(is_numeric($teplota) AND is_numeric($vlhkost) AND $teplota != 0 AND $vlhkost != 0)
    {

        if($teplota > 0)
        {
            return round(243.12 * ((log($vlhkost / 100) + ((17.62 * $teplota) / (243.12 + $teplota))) / (17.62 - log($vlhkost / 100) - ((17.62 * $teplota) / (243.12 + $teplota)))), 1);
        }
        else
        {
            return round(272.62 * ((log($vlhkost / 100) + ((22.46 * $teplota) / (272.62 + $teplota))) / (22.46 - log($vlhkost / 100) - ((22.46 * $teplota) / (272.62 + $teplota)))), 1);
        }

    }
    else
    {
        return "null";
    }
}*/

/**
 * Funkce vrátí datetime z MySQL naformátované do tvaru,
 * který je v vystup-XML.php
 *
 * @param datetime $datetime
 * @return string
 */

function barvaRameckuTeploty($teplota)
{
    $trida = " teplota-30";

    $skoky = [-30, -25, -20, -15, -10, -5, 0, 5, 10, 15, 20, 25, 30, 35];

    foreach($skoky as $skok)
    {
        if($teplota >= $skok)
        {
            $trida = " teplota" . (string)$skok;
        }
    }

    return $trida;
}

function barvaRameckuOsvit($osvit)
{
    $trida = " osvitneni";

    $skoky = [0, 100, 250, 500];

    foreach($skoky as $skok)
    {
        if($osvit > $skok)
        {
            $trida = " osvit" . (string)$skok;
        }
    }

    return $trida;
}

function barvaRameckuVlhkost($vlhkost)
{
    $trida = " vlhkost0";

    $skoky = [20, 30, 40, 50, 60, 70, 80, 90];

    foreach($skoky as $skok)
    {
        if($vlhkost > $skok)
        {
            $trida = " vlhkost" . (string)$skok;
        }
    }

    return $trida;
}

function barvaRameckuSrazky($srazky)
{
    $trida = " srazkynejsou";

    $skoky = [0, 3, 6, 10, 15, 20, 25, 30, 35, 40, 45];

    foreach($skoky as $skok)
    {
        if($srazky > $skok)
        {
            $trida = " srazky" . (string)$skok;
        }
    }

    return $trida;
}

function barvaRameckuTlak($tlak)
{
    $trida = " tlak-990";

    $skoky = [990, 1000, 1010, 1020, 1030];

    foreach($skoky as $skok)
    {
        if($tlak > $skok)
        {
            $trida = " tlak" . (string)$skok;
        }
    }

    return $trida;
}

function barvaRameckuVitr($vitr)
{
    $trida = " vitr0";

    $skoky = [2, 4, 8, 12, 16, 22];

    foreach($skoky as $skok)
    {
        if($vitr > $skok)
        {
            $trida = " vitr" . (string)$skok;
        }
    }

    return $trida;
}

function barvaRameckuUV($uvi)
{
    if ($uvi <= 2) {
        return " uvi0";
    } elseif ($uvi <= 5) {
        return " uvi3";
    } elseif ($uvi <= 7) {
        return " uvi6";
    } elseif ($uvi <= 10) {
        return " uvi8";
    } else {
        return " uvi11";
    }
}

function barvaRameckuAktualizovano($akt)
{
if (time()-strtotime($akt) < 3000) return("aktualneAktualizovano");
else return("aktualneNeaktualizovano");
}

function textAktualizovano($akt)
{
if (time()-strtotime($akt) < 3000) return("online");
else return("offline");
}

function curl_get_file_contents(string $url, int $timeout = 5) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'MeteostanicePadarovice/1.0 (+https://tomaskrupicka.cz)',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => ['Accept: application/json, */*;q=0.1'],
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($res === false || $code >= 400) {
        return null; // místo FALSE/Warning prostě null
    }
    return $res;
}
 
function get_geolocation($url) 
{
        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_HTTPGET, true);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        return curl_exec($cURL);
}

/**
 * Vytvoří URL aktuální stránky s přepsanými query parametry (zachová ostatní).
 */
function url_with_params(array $override): string {
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path  = $parts['path'] ?? '/';
    parse_str($parts['query'] ?? '', $q);
    $q = array_merge($q, $override);
    // odstraň prázdné/null hodnoty
    foreach ($q as $k => $v) { if ($v === null || $v === '') unset($q[$k]); }
    $query = http_build_query($q, '', '&', PHP_QUERY_RFC3986);
    return $path . ($query ? ('?' . $query) : '');
}

/**
 * Vyrenderuje položku menu pro jazyky (cz/en).
 */
function renderMenuJazyky(string $vybranyJazyk, array $jazyky, array $lang): string {
    $html = "<li><a href=\"#\" aria-haspopup=\"true\">" . strtoupper($vybranyJazyk) . "</a>";
    $html .= "<ul class=\"jazyk\">";
    foreach ($jazyky as $jazyk) {
        if ($jazyk !== $vybranyJazyk) {
            $url = htmlspecialchars(url_with_params(['ja' => $jazyk]), ENT_QUOTES, 'UTF-8');
            $html .= "<li><a href=\"$url\" hreflang=\"$jazyk\">" . strtoupper($jazyk) . "</a></li>";
        }
    }
    $html .= "</ul></li>";
    return $html;
}

/**
 * Vyrenderuje položku menu pro jednotky (C/F).
 */
function renderMenuJednotky(string $vybranaJednotka, array $jednotky): string {
    $currentLabel = $jednotky[$vybranaJednotka] ?? $vybranaJednotka;
    $html = "<li><a href=\"#\" aria-haspopup=\"true\" title=\"" . htmlspecialchars($currentLabel, ENT_QUOTES, 'UTF-8') . "\">"
          . htmlspecialchars($currentLabel, ENT_QUOTES, 'UTF-8') . "</a>";
    $html .= "<ul class=\"teplota\">";
    foreach ($jednotky as $index => $label) {
        if ($index !== $vybranaJednotka) {
            $url = htmlspecialchars(url_with_params(['je' => $index]), ENT_QUOTES, 'UTF-8');
            $html .= "<li><a href=\"$url\" title=\"" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "\">"
                  . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</a></li>";
        }
    }
    $html .= "</ul></li>";
    return $html;
}
<?php declare(strict_types=1);

// DEV režim: zapnout jen lokálně
// ini_set('display_errors', '1'); error_reporting(E_ALL);

// Bezpečnější require
require_once __DIR__ . '/config.php';                 // nastavení
require_once __DIR__ . '/scripts/fce.php';            // pomocné funkce
require_once __DIR__ . '/scripts/variableCheck.php';  // jazyk/jednotka

// ── Pomocná funkce pro escapování výstupu ──────────────────────────────
if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

// Pojistka proti rozbitému configu — whitelist je definovaný v scripts/variableCheck.php ($jazyky, $jednotky)
$l = in_array($l, array_keys($jazyky), true) ? $l : 'cz';
$u = in_array($u, array_keys($jednotky), true) ? $u : 'C';

// Refresh: celé číslo ≥ 0
$obnoveniStranky = isset($obnoveniStranky) && is_numeric($obnoveniStranky) && (int)$obnoveniStranky >= 0 ? (int)$obnoveniStranky : 0;

// Query string pro ajax/tabs
$q = http_build_query(['ja' => $l, 'je' => $u], '', '&', PHP_QUERY_RFC3986);

// Pomocník na stavění URL s udržením parametrů
function keep_params(array $extra = []): string {
  $params = ['ja' => $_GET['ja'] ?? null, 'je' => $_GET['je'] ?? null];
  foreach ($extra as $k => $v) $params[$k] = $v;
  return '?' . http_build_query(array_filter($params, fn($v)=>$v!==null), '', '&', PHP_QUERY_RFC3986);
}

$__appBase = isset($appBasePath) ? rtrim((string)$appBasePath, '/') : '';
$faviconHref = $__appBase === '' ? '/favicon.png' : $__appBase . '/favicon.png';

$__host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$__req  = $_SERVER['REQUEST_URI'] ?? '';
$canonicalUrl = 'https://' . $__host . ($__req !== '' ? preg_replace('/\?.*/', '', $__req) : ($__appBase === '' ? '/' : $__appBase . '/'));
$htmlLang = $l === 'cz' ? 'cs' : ($l === 'en' ? 'en' : 'cs');
?>
<!doctype html>
<html lang="<?= e($htmlLang) ?>">
<head>
  <meta charset="utf-8">
  <title><?= e($lang['titulekstranky'] ?? 'Meteostanice') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <meta name="description" content="<?= e($lang['popisstranky'] ?? '') ?>">
  <meta name="author" content="Tomáš Krupička, Michal Ševčík">
  <link rel="icon" href="<?= e($faviconHref) ?>" type="image/png">
  <?php if ($obnoveniStranky > 0): ?>
    <meta http-equiv="refresh" content="<?= $obnoveniStranky ?>">
  <?php endif; ?>

  <!-- CSS -->
  <link rel="stylesheet" href="css/css.css">
  <link rel="stylesheet"
      href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css"
      integrity="sha256-DcjZoj+4EdXndbnrXsdWkiAgx9N0PiUYY0cPl2ni7vg="
      crossorigin="anonymous">

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
          integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
          crossorigin="anonymous"></script>

  <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"
          crossorigin="anonymous"></script>

<!-- Highcharts -->
<script src="https://code.highcharts.com/11.4.7/highcharts.js"></script>
<script src="https://code.highcharts.com/11.4.7/highcharts-more.js"></script>
<script src="https://code.highcharts.com/11.4.7/modules/exporting.js"></script>
<script src="https://code.highcharts.com/11.4.7/modules/accessibility.js"></script>

<script>
  // Lazy načítání obsahu do tabů
  var loadingImage = '<p><img src="./images/loading.gif" alt="Načítání…"></p>';
  var TAB_LOAD_ERR = <?= json_encode($lang['err_tab_load'] ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;

  function loadTab(tabId) {
    var $el = $('#' + tabId);
    if (!$el.length || $el.data('loaded')) return;

    $el.html(loadingImage).load('scripts/tabs/' + tabId + '.php?<?= $q ?>', function (resp, status) {
      if (status === 'success') {
        $el.data('loaded', true);
        // reflow všech grafů po načtení
        if (window.Highcharts) setTimeout(function(){
          Highcharts.charts.forEach(function(c){ if(c && c.reflow) c.reflow(); });
        }, 0);
        // pokud jsme právě načetli historii, inicializuj datepicker
        if (tabId === 'historie') setTimeout(initHistorieWidgets, 0);
      } else {
        $el.html('<p>' + TAB_LOAD_ERR + '</p>');
      }
    });
  }

  function initHistorieWidgets() {
    if (!window.jQuery || !jQuery.fn.datepicker) return;
    var $den = jQuery('#den');
    if (!$den.length || $den.data('dp-init')) return;
    $den.datepicker({
      dateFormat: "yy-mm-dd",
      maxDate: -1,
      minDate: new Date(2019, 5, 1),
      changeMonth: true,
      changeYear: true
    });
    $den.data('dp-init', 1);
  }

  function showTab(hash) {

    // zvýrazni záložku
    $('.tabs a').removeClass('current');
    $('.tabs a[href="'+hash+'"]').addClass('current');

    // přepni panely
    $('.panely > div').hide();
    var id = hash.replace('#','');
    $('#'+id).show();

    // lazy-load jen u těchto tabů
    var lazyIds = ['dlouhodoby-vyvoj','statistiky','rekordy'];
    if (lazyIds.includes(id) && !$('#'+id).data('loaded')) {
      loadTab(id);
    }

    // historie: ujisti se, že datepicker je inicializovaný
    if (id === 'historie') setTimeout(initHistorieWidgets, 0);

    // reflow grafů
    if (window.Highcharts) {
      setTimeout(function(){ Highcharts.charts.forEach(function(c){ if(c && c.reflow) c.reflow(); }); }, 50);
    }

    // udrž hash v URL
    if (location.hash !== hash) history.replaceState(null, '', hash);
  }

  document.addEventListener('DOMContentLoaded', function () {
    // click na záložky → vše přes showTab()
    $('.tabs a').on('click', function (e) {
      e.preventDefault();
      showTab(this.getAttribute('href'));
    });

    // otevři podle hash, jinak #aktualne
    showTab(location.hash || '#aktualne');

    // Auto-refresh ajax části (interval z configu)
    var ajaxRefreshMs = <?= (int)($ajaxRefreshSec ?? 0) ?> * 1000;
    if (window.jQuery && ajaxRefreshMs > 0) {
      setInterval(function () {
        var $refresh = jQuery('.ajaxrefresh');
        $refresh.addClass('ajax-loading');
        jQuery.get('scripts/ajax/aktualne.php?<?= $q ?>', function (data) {
          $refresh.html(data).removeClass('ajax-loading');
        }).fail(function () {
          $refresh.removeClass('ajax-loading');
        });
      }, ajaxRefreshMs);
    }
  });
</script>

</head>
<body>

  <header class="roztahovak-modry">
    <div class="hlavicka container">
      <div id="nadpis"><h1><?= $lang['hlavninadpis'] ?? 'Meteostanice Padařovice' ?> | <a href="https://tomaskrupicka.cz">ZPĚT NA BLOG</a></h1></div>
      <nav id="menu">
        <ul>
          <?php
            // $jazyky a $jednotky přijdou z variableCheck.php
            echo renderMenuJazyky($l, array_keys($jazyky), $lang);
            echo renderMenuJednotky($u, $jednotky);
          ?>
        </ul>
      </nav>
    </div>
  </header>

  <?php require_once __DIR__ . "/scripts/head.php"; ?>

  <!-- ───────────────────────────── Tabs ───────────────────────────── -->
  <div id="hlavni" class="container">
    <div id="oblastzalozek">
      <ul class="tabs">
        <li><a href="#aktualne"><?= e($lang['aktualne'] ?? 'Aktuálně') ?></a></li>
        <li><a href="#dlouhodoby-vyvoj"><?= e($lang['dlouhodobyvyvoj'] ?? 'Dlouhodobý vývoj') ?></a></li>
        <li><a href="#statistiky"><?= e($lang['statistikytab'] ?? 'Statistiky') ?></a></li>
        <li><a href="#rekordy"><?= e($lang['rekordytab'] ?? 'Rekordy') ?></a></li>
        <li><a href="#historie"><?= e($lang['historie'] ?? 'Historie') ?></a></li>
      </ul>

      <div class="panely">
        <!-- 1) Aktuálně – načítáme rovnou -->
        <div id="aktualne">
          <?php require __DIR__ . "/scripts/tabs/aktualne.php"; ?>
        </div>

        <!-- 2) Dlouhodobý vývoj – lazy -->
        <div id="dlouhodoby-vyvoj"></div>

        <!-- 3) Statistiky – lazy -->
        <div id="statistiky"></div>

        <!-- 4) Rekordy – lazy -->
        <div id="rekordy"></div>

        <!-- 5) Historie – načítáme rovnou (pokud chceš, může být i lazy) -->
        <div id="historie">
          <?php require __DIR__ . "/scripts/tabs/historie.php"; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- ─────────────────────────────────────────────────────────────── -->

  <footer class="roztahovak-modry">
    <div class="paticka container">
      <p><?= e($lang['paticka'] ?? '') ?><a href="http://multi.tricker.cz" target="_blank">multi.tricker.cz</a>.</p>
    </div>
  </footer>

</body>
</html>

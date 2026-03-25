<?php
echo "<div class='roztahovak-vrsek'>
  <div id='tri' class='row'>
    <div class='container'>";

// Aktualnì
echo "<div class='col-md-4'>
        <div class='sloupekAktualne'>
          <div class='ajaxrefresh'>";
require_once __DIR__ . "/ajax/aktualne.php";
echo "    </div>
        </div>
      </div>";

// Info a astro
echo "<div class='col-md-4'>
        <div class='drivetoutodobouted'>";
require_once __DIR__ . "/info_a_astro.php";
echo "    </div>
      </div>";

// Rekordy
echo "<div class='col-md-3'>";
require_once __DIR__ . "/rekordy.php";
echo "  </div>
    </div>
  </div>
</div>";

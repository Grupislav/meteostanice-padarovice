# Meteostanice Padařovice

Webová aplikace pro **přehledné zobrazení měření** z meteostanice v Padařovicích: aktuální hodnoty, grafy, dlouhodobý vývoj, statistiky, rekordy a historie. Data vycházejí z **Ecowitt API** a z **vlastní databáze** (historické záznamy).

**Živá verze:** [tomaskrupicka.cz/meteostanice-padarovice](https://tomaskrupicka.cz/meteostanice-padarovice)

## Co aplikace umí

- záložky: aktuální stav, dlouhodobý vývoj, statistiky, rekordy, historie  
- **Highcharts** pro grafy, **jQuery UI** pro ovládání (např. výběr data)  
- jazyky **cz / en** a jednotky teploty **°C / °F** (whitelist v `scripts/variableCheck.php`)  
- pravidelné obnovení stránky a ajaxový refresh aktuálních hodnot (nastavitelné v konfiguraci)  
- skript `scripts/dbinsert.php` pro zápis měření do databáze (cron na hostingu)

## Technologie

PHP (mysqli), HTML/CSS, JavaScript (jQuery, Highcharts). Statické assety a PHP šablony bez frameworku.

## Pro vývojáře

Repozitář obsahuje zdrojáky webu; **citlivé údaje nejsou v Gitu** — lokálně nebo na serveru se používá `config.php` vytvořený ze šablony.

### Požadavky

- PHP s rozšířením **mysqli**  
- MySQL / MariaDB pro historická data (schéma podle vašeho nasazení)  
- vlastní **Ecowitt** a případně **ipgeolocation.io** klíče v konfiguraci

### Lokální spuštění

1. Zkopíruj `config.example.php` → `config.php` a doplň údaje k DB a API (**Ecowitt**, případně **ipgeolocation.io**).  
2. Volitelně `$meteoPocasiApiId` – ID pro api.meteo-pocasi.cz (stav oblohy u aktuálního počasí); bez něj se toto API nevolá.  
3. Nastav `$appBasePath`: prázdný řetězec pro kořen webu, nebo cestu k podadresáři (např. `/meteostanice-padarovice`).  
4. V kořeni projektu např. `php -S localhost:8080` a otevři prohlížeč.

Soubor `config.php` je v `.gitignore` — do commitů patří jen `config.example.php`.

### Nasazení a CI (pro správce)

Po pushi na větev `main` může běžet GitHub Action [`.github/workflows/deploy-ftp.yml`](.github/workflows/deploy-ftp.yml), která nahraje soubory na FTP. V repozitáři je potřeba nastavit secrets `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_SERVER_DIR` (cílová složka na hostingu včetně koncového `/`). `config.php` workflow neposílá — na produkci musí zůstat váš vlastní soubor.

---

V zápatí a v kódu je uvedeno autorství **Tomáš Krupička**, **Michal Ševčík**; původní koncept vychází z prostředí [multi.tricker.cz](http://multi.tricker.cz). Tento repozitář slouží k provozu stanice v **Padařovicích**.

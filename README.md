# Meteostanice Padařovice

PHP aplikace pro zobrazení dat meteostanice (Ecowitt, vlastní databáze).

## Lokální běh

1. Zkopíruj `config.example.php` na `config.php`.
2. Doplň přihlašovací údaje k databázi, Ecowitt a případně ipgeolocation.
3. Nastav `$appBasePath`: prázdné pro kořen webu, nebo např. `/meteostanice-padarovice` pro nasazení v podadresáři.
4. Spusť na PHP s rozšířením `mysqli` (např. vestavěný server: `php -S localhost:8080` v kořeni projektu).

## GitHub

- V repozitáři je pouze `config.example.php`; soubor `config.php` je v `.gitignore` a nesmí se commitovat.
- Pokud byl `config.php` s reálnými hesly někdy v historii commitnutý, **změň hesla k DB a API klíče** (Git historie je dál čitelná).

## Automatické nasazení (FTP)

Workflow [`.github/workflows/deploy-ftp.yml`](.github/workflows/deploy-ftp.yml) po každém pushi na větev `main` nahraje soubory na FTP.

### Secrets v GitHubu

V **Settings → Secrets and variables → Actions** přidej:

| Secret           | Význam |
|------------------|--------|
| `FTP_SERVER`     | FTP host (u Wedosu např. `wXXXX.wedos.ws` – viz FTP údaje v administraci) |
| `FTP_USERNAME`   | FTP uživatel |
| `FTP_PASSWORD`   | FTP heslo |
| `FTP_SERVER_DIR` | Cílová složka **na serveru** včetně koncového `/` |

`FTP_SERVER_DIR` musí odpovídat struktuře u poskytovatele. U Wedosu je často něco jako `www/nazev-domenoveho-adresare/meteostanice-padarovice/` – ověř v **Správce souborů** nebo v nápovědě k FTP, kam patří obsah pro `tomaskrupicka.cz/meteostanice-padarovice`.

### První nasazení

Na hostingu musí už existovat `config.php` s produkčními hodnotami (FTP workflow ho z repozitáře neposílá). Jednorázově ho nahraj ručně nebo vytvoř na serveru z `config.example.php`.

### SFTP / jiný hosting

Tento workflow používá čisté FTP. Pokud máš jen SFTP, použij např. akci založenou na `lftp`/`rsync` přes SSH, nebo nasazení z panelu hostingu.

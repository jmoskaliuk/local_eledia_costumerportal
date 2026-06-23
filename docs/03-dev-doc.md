# local_customerportal - Dev Doc

## Architektur

Die Lite-Version arbeitet ausschliesslich mit lokalen Moodle-Daten.

### Wichtige Dateien

- `classes/local/installation_service.php`: lokale Installations- und Plugin-Daten.
- `classes/local/site_info_service.php`: lokale Site-Statistiken.
- `classes/local/request_service.php`: lokale Request-Persistenz.
- `index.php`, `installation.php`, `myplugins.php`, `requests.php`: Portal-Seiten.
- `templates/shell_header.mustache` und `templates/nav_tabs.mustache`: LernHive-kompatible Shell.

## Entfernte Remote-Pfade

Nicht mehr Teil der Lite-Version:

- Remote-API-Client
- Remote-Katalog
- Snapshot- oder Plugin-Sync-Tasks
- Installationsregistrierung bei externen Diensten
- Remote-IDs in Request- oder Installationsdaten

## UI-Konvention

Die Seiten laden `/local/lernhive/styles.css`, setzen `lh-plugin-shell-page` als Body-Class und verwenden LernHive-Klassen fuer Shell, Header, Section-Nav, Cards, Grids und Buttons.

Das Portal nutzt diese Klassen direkt im Mustache-Markup, damit keine PHP-Abhaengigkeit auf LernHive-Output-Klassen entsteht.

## Requests

`request_service::create()` schreibt lokal in `local_customerportal_request`. Jeder erfolgreich gespeicherte Request bekommt den Status `local`; nur technische Speicherfehler werden als Moodle-Exception gemeldet.

# local_customerportal - Dev Doc

## Architektur

Die Lite-Version arbeitet ausschliesslich mit lokalen Moodle-Daten.

## Devflow

Das fuehrende Entwicklungsrepo ist:

```text
https://github.com/jmoskaliuk/local_eledia_costumerportal
```

Deploys nach `demo.eledia.ai` laufen ueber den Spiegel im Repo:

```text
https://github.com/jmoskaliuk/eledia.ai
```

Der Zielpfad im Deploy-Repo ist:

```text
custom-plugins/local/customerportal
```

Standardablauf:

1. Aenderungen im Plugin-Repo `local_eledia_costumerportal` entwickeln.
2. Lokale Checks ausfuehren: PHP-Lint, `git diff --check`, Template-String-Abgleich.
3. Commit nach `local_eledia_costumerportal/main` pushen.
4. Den getrackten Commit-Inhalt nach `eledia.ai/custom-plugins/local/customerportal` spiegeln.
5. Commit nach `eledia.ai/main` pushen; dieser Stand triggert den Deploy.

Beim Spiegeln nur getrackte Plugin-Dateien verwenden, zum Beispiel per `git archive HEAD`. Lokale Hilfsdateien wie `.DS_Store`, lokale Deploy-Scripts oder temporaere Dateien duerfen nicht in das Deploy-Repo wandern.

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

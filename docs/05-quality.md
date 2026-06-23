# local_customerportal - Quality

## Abgedeckte Logiktests

`tests/request_service_test.php` prueft die lokale Request-Erstellung und Fehlerbehandlung.

Weitere bestehende Tests decken lokale Health- und Site-Info-Services ab.

## Durchgefuehrte Verifikation

In der aktuellen Session wurden ausgefuehrt:

- PHP-Syntaxpruefung fuer alle PHP-Dateien
- `git diff --check`
- Suche nach verbliebenen Remote-/Sync-/Katalog-Codebegriffen
- Template-String-Abgleich fuer EN und DE

## Noch offen

- Kein kompletter Moodle-PHPUnit-Lauf in dieser Session
- Kein Behat-End-to-End-Lauf in dieser Session
- Kein manueller Browser-Smoke-Test gegen eine laufende Moodle-Instanz in dieser Session

## Empfohlener Smoke-Test

1. Portal in einer Moodle-Instanz mit installiertem `local_lernhive` oeffnen.
2. Pruefen, dass `Customer Portal` in der Moodle-Topbar nur einmal erscheint.
3. Dashboard, `Plugins`, `Upgrade`, `AI` und `AI Setup` pruefen.
4. `Requests` pruefen: Der Link muss zu `https://eledia.de/kontakt` fuehren.
5. `AI Setup` pruefen: Provider-, Add-Provider- und Action-Settings-Links muessen erreichbar sein.
6. Pruefen, dass kein externer Request fuer Katalog, Sync oder Registrierung ausgefuehrt wird.

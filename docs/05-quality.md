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
2. Dashboard, `My Installation`, `My Plugins` und `Requests` pruefen.
3. Neue Anfrage anlegen und sicherstellen, dass sie lokal mit Status `Local` erscheint.
4. Pruefen, dass kein externer Request fuer Katalog, Sync oder Registrierung ausgefuehrt wird.

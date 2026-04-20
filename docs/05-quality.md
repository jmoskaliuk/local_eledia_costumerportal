# local_customerportal — Quality

## Abgedeckte Logiktests

`tests/sync_service_test.php` deckt den neuen Registrierungsflow ab:

- Registrierung ohne vorhandene ID
- Registrierung mit vorhandener ID
- ungueltige Response-ID wird verworfen
- fehlende Konfiguration stoppt vor HTTP-Call
- `401` wird als Fehlerfall erkannt

## Durchgefuehrte Verifikation

Syntaxpruefung erfolgreich fuer:

- `register_installation.php`
- `installation.php`
- `classes/local/installation_service.php`
- `classes/local/sync_service.php`
- `tests/sync_service_test.php`
- `lang/en/local_customerportal.php`
- `lang/de/local_customerportal.php`

## Noch offen

- Kein kompletter Moodle-PHPUnit-Lauf in dieser Session
- Kein Behat-End-to-End-Lauf in dieser Session
- Kein manueller Browser-Smoke-Test gegen eine laufende Moodle-Instanz in dieser Session

## Empfohlener Smoke-Test

1. Plugin konfigurieren mit `directus_url` und `directus_token`
2. `My Installation` als Site-Admin oeffnen
3. `Installation registrieren` klicken
4. Pruefen, dass eine UUID gespeichert und im UI angezeigt wird
5. Geplante Tasks fuer Snapshot und Plugin-Sync ausfuehren
6. Pruefen, dass der Sync anschliessend mit der gespeicherten ID arbeitet

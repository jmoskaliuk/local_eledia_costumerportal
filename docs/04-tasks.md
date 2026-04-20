# local_customerportal — Tasks

## Abgeschlossen

- [x] Manuellen Admin-Registrierungsflow fuer Directus implementiert
- [x] Erst-Registrierung ohne lokale `installation_id` ermoeglicht
- [x] Re-Registrierung mit bestehender `installation_id` unterstuetzt
- [x] Rueckgelieferte UUID validiert und lokal gespeichert
- [x] Erfolgsmarker fuer Cron-Flow gesetzt
- [x] POST-only-Action mit `sesskey` und Admin-Check hinzugefuegt
- [x] Status- und Button-UI in `My Installation` integriert
- [x] EN/DE-Sprachstrings ergaenzt
- [x] PHPUnit-Logiktests fuer Registrierungsfaelle erweitert

## Offene Folgeaufgaben

- [ ] PHPUnit/Behat in einer echten Moodle-Testumgebung ausfuehren
- [ ] Optional: Settings-Seite ebenfalls um einen Admin-Button erweitern
- [ ] Optional: HTTP-Fehler im `api_client` weiter typisieren statt nur per Message-Auswertung
- [ ] Optional: eigener Capability-Scope fuer Admin-Registrierung statt reinem `require_admin()`

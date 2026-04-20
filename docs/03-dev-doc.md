# local_customerportal — Dev Doc

## Architektur des Registrierungsflows

### Betroffene Dateien

- `classes/local/installation_service.php`
- `classes/local/sync_service.php`
- `installation.php`
- `templates/installation_view.mustache`
- `register_installation.php`
- `lang/en/local_customerportal.php`
- `lang/de/local_customerportal.php`
- `tests/sync_service_test.php`

## Service-Layer

### `installation_service`

Neue Helper:

- `get_optional_installation_id()`
- `has_installation_id()`
- `set_installation_id()`

Zweck:

- UI und Service koennen den Registrierungszustand pruefen, ohne Exceptions fuer den "noch nicht registriert"-Fall zu missbrauchen
- Nach erfolgreicher Registrierung kann die neue ID sofort in dieselbe Request-Lifecycle-Instanz uebernommen werden

### `sync_service`

Die Registrierungslogik ist zentral in `register_installation()` gekapselt.

Wichtige Punkte:

- gemeinsamer Base-Payload fuer Snapshot und Registrierung
- Erst-Registrierung ohne `id`
- Re-Registrierung mit `id`
- UUID-Validierung auf `data.id`
- Persistenz von:
  - `installation_id`
  - `installation_registered`
  - `last_registration_at`

`ensure_installation_registered()` nutzt dieselbe Logik wie der manuelle Admin-Button, damit kein zweiter, abweichender Codepfad entsteht.

## HTTP- und Sicherheitslogik

### Route

`register_installation.php`

- nur `POST`
- `require_login()`
- `require_sesskey()`
- `require_admin()`

### Fehlerklassifikation

- fehlende lokale Konfiguration vor HTTP-Call
- `400` -> Konfigurations-/Payload-Fehler
- `401/403` -> Authentifizierungsfehler
- `409` mit bestehender lokaler ID -> idempotentes Update
- ungueltige Response-ID -> Abbruch ohne Persistenz

## UI

`installation.php` liefert zusaetzlich:

- Registrierungsstatus
- Sync-Status
- aktuelle Installations-ID
- Zeitstempel der letzten Registrierung
- Button-Text nach Erst-/Re-Registrierungsfall

`installation_view.mustache` rendert daraus einen eigenen Block `Registrierung und Sync`.

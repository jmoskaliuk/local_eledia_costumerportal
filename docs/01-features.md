# local_customerportal — Features

## Ziel

`local_customerportal` stellt Kundinnen und Kunden ein Moodle-internes Portal fuer Installationsstatus, Plugins und Requests bereit.

## Feature: Admin-Registrierung der Installation

Site-Admins koennen die Moodle-Instanz direkt aus dem Plugin bei Directus registrieren oder eine bestehende Registrierung aktualisieren.

### Nutzerwert

- Keine manuelle Vorbelegung der `installation_id` mehr notwendig
- Snapshot- und Plugin-Sync koennen nach einem erfolgreichen Klick sofort weiterarbeiten
- Konfigurationsfehler werden im Admin-UI sichtbar statt nur indirekt ueber Cron-Logs

### Verhalten

- Wenn keine `installation_id` vorhanden ist, sendet das Plugin eine Erst-Registrierung ohne `id`
- Wenn bereits eine `installation_id` vorhanden ist, sendet das Plugin eine idempotente Aktualisierung mit `id`
- Eine gueltige, vom Backend zurueckgelieferte UUID wird lokal gespeichert
- Erfolgreiche Registrierungen setzen zusaetzlich lokale Erfolgsmarker fuer den Cron-Flow

### Fehlerfaelle

- Fehlende `directus_url` oder `directus_token` blockieren den Call vor dem HTTP-Request
- `401` oder `403` werden als Konfigurations-/Authentifizierungsfehler kommuniziert
- `400` wird als ungueltige Server-/Payload-Konfiguration kommuniziert
- `409` wird bei bestehender lokaler ID als idempotente Aktualisierung behandelt
- Ungueltige Response-IDs werden nicht gespeichert

# local_customerportal — User Doc

## Admin-Flow: Installation registrieren

### Voraussetzung

- Ein Site-Admin ist in Moodle eingeloggt
- In den Plugin-Einstellungen sind `Directus base URL` und `Directus API token` gesetzt

### Ablauf

1. `My Installation` oeffnen
2. Im Block `Registrierung und Sync` auf `Installation registrieren` klicken
3. Nach erfolgreichem Call zeigt das Plugin:
   - Registrierungsstatus
   - gespeicherte Installations-ID
   - Zeitpunkt der letzten Registrierung

### Folge bei bestehender ID

Wenn bereits eine Installations-ID vorhanden ist, lautet die Aktion `Installation erneut registrieren / aktualisieren`.

### Sichtbarkeit

- Der Button ist nur fuer Site-Admins sichtbar
- Normale Portal-Nutzer sehen nur den Status, aber keine Schreibaktion

### Fehlermeldungen

- Fehlende Konfiguration: Hinweis auf fehlende Plugin-Settings
- Authentifizierungsfehler: Hinweis auf URL/Token
- Netzwerkfehler: Hinweis auf spaeteren Retry
- Server-Antwort ohne gueltige UUID: Registrierung wird nicht lokal gespeichert

# local_customerportal - User Doc

## Dashboard

Das Dashboard zeigt eine lokale Uebersicht der Moodle-Installation:

- registrierte Nutzer/innen
- aktive Nutzer/innen der letzten 30 Tage
- Kursanzahl
- Moodle-Version
- Anzahl installierter Add-ons
- Einstieg zu Kontakt, AI und AI Setup

## Meine Installation

`My Installation` zeigt das lokale Installationsprofil und technische Site-Informationen. Der Cron-Status wird direkt aus Moodle gelesen.

## Meine Plugins

`My Plugins` listet lokal installierte Add-ons. Standard-Moodle-Plugins werden ausgeblendet, damit die Ansicht auf kundenspezifische Erweiterungen fokussiert bleibt.

## Anfragen

`Requests` fuehrt direkt zum eLeDia-Kontaktformular:

```text
https://eledia.de/kontakt
```

Damit gibt es im Portal keinen lokalen Anfrage-Posteingang mehr.

## AI Setup

`AI Setup` fuehrt Admins zum lokalen Moodle-AI-Setup. Der User bringt den Key mit; das Portal verlinkt auf die Moodle-AI-Provider-Einstellungen, wo der Key gespeichert wird.

`AI` oeffnet die LernHive AI Suite, wenn `local_lernhive_ai` installiert ist.

## Kein Remote-Flow

Diese Lite-Version hat bewusst keinen Remote-Katalog, keinen Sync und keine externe Registrierung.

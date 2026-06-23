# local_customerportal - User Doc

## Dashboard

Das Dashboard zeigt eine lokale Uebersicht der Moodle-Installation:

- registrierte Nutzer/innen
- aktive Nutzer/innen der letzten 30 Tage
- Kursanzahl
- Moodle-Version
- Anzahl installierter Add-ons
- Anzahl lokal gespeicherter Requests

## Meine Installation

`My Installation` zeigt das lokale Installationsprofil und technische Site-Informationen. Der Cron-Status wird direkt aus Moodle gelesen.

## Meine Plugins

`My Plugins` listet lokal installierte Add-ons. Standard-Moodle-Plugins werden ausgeblendet, damit die Ansicht auf kundenspezifische Erweiterungen fokussiert bleibt.

## Anfragen

Portal-Nutzer mit der Capability `local/customerportal:createrequest` koennen neue Anfragen anlegen. Die Anfrage bleibt lokal in Moodle gespeichert und wird nicht automatisch uebertragen.

## Kein Remote-Flow

Diese Lite-Version hat bewusst keinen Remote-Katalog, keinen Sync und keine externe Registrierung.

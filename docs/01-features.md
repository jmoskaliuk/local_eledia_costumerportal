# local_customerportal - Features

## Ziel

`local_customerportal` stellt ein Moodle-internes Lite-Portal fuer Installation, Plugins und lokale Requests bereit.

## Lokale Installation

- Zeigt lokale Moodle-Basisdaten wie Release, Nutzerzahlen, Kurse und Cron-Status.
- Nutzt lokale Plugin-Settings fuer Profilwerte wie Site-Label, Hosting-Profil, SLA und Release-Kanal.
- Sendet keine Installationsdaten an externe Dienste.

## Meine Plugins

- Liest installierte Add-ons direkt aus Moodle.
- Gruppiert Plugins nach Plugintyp und zeigt Version sowie lokalen Status.
- Verwendet keinen Remote-Katalog und keine Overlay-Daten.

## Requests

- Requests werden lokal in der Moodle-Datenbank gespeichert.
- Es gibt keinen Sync, keine Registrierung und keine ausgehende API-Uebertragung.
- Der Status `Local` zeigt an, dass die Anfrage im Portal erfasst wurde.

## LernHive UI

- Die Portal-Seiten verwenden die LernHive Plugin-Shell, Section-Navigation, Cards und Buttons.
- Das UI bleibt dadurch konsistent mit den bestehenden LernHive-Plugins, ohne eigene Remote-Abhaengigkeiten einzufuehren.

# local_customerportal - Features

## Ziel

`local_customerportal` stellt ein Moodle-internes Lite-Portal fuer Installation, Plugins, AI-Einrichtung und Kontaktwege bereit.

## Lokale Installation

- Zeigt lokale Moodle-Basisdaten wie Release, Nutzerzahlen, Kurse und Cron-Status.
- Nutzt lokale Plugin-Settings fuer Profilwerte wie Site-Label, Hosting-Profil, SLA und Release-Kanal.
- Sendet keine Installationsdaten an externe Dienste.

## Meine Plugins

- Liest installierte Add-ons direkt aus Moodle.
- Gruppiert Plugins nach Plugintyp und zeigt Version sowie lokalen Status.
- Verwendet keinen Remote-Katalog und keine Overlay-Daten.

## Requests und Kontakt

- Der Portal-Einstieg `Requests` fuehrt direkt zu `https://eledia.de/kontakt`.
- Es gibt keinen lokalen Request-Workflow mehr im aktiven UI.
- Es gibt keinen Sync, keine Registrierung und keine ausgehende API-Uebertragung aus dem Portal.

## AI und AI Setup

- `AI` fuehrt zur vorhandenen LernHive AI Suite (`local_lernhive_ai`).
- `AI Setup` fuehrt durch die lokale Moodle-AI-Einrichtung und verlinkt auf AI Provider, Provider-Erstellung und Action-Konfiguration.
- API-Keys werden nicht im Customer Portal gespeichert, sondern in den Moodle-AI-Provider-Einstellungen.

## LernHive UI

- Die Portal-Seiten verwenden die LernHive Plugin-Shell, Section-Navigation, Quicklinks, Cards und Buttons.
- Das Customer Portal registriert keinen eigenen Moodle-Primary-Navigation-Eintrag mehr; die zentrale LernHive-Navigation kann den Topbar-Einstieg bereitstellen.
- Das UI bleibt dadurch konsistent mit den bestehenden LernHive-Plugins, ohne eigene Remote-Abhaengigkeiten einzufuehren.

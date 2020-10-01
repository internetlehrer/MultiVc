# About

This [ILIAS](https://www.ilias.de) Plugin allows to interact with different WebRTC-based virtual classroom platforms.
Currently we have implemented the ability to use the following VCs:
- Bigbluebutton
- Spreed
- Openmeetings


# Prerequisites

Zunächst benötigen Sie eine funktionsfähige Installation der gewünschten WebRTC Plattform.

## BigBlueButton
Zur Nutzung mit Bigbluebutton empfehlen wir die Installation auf einer hoch performanten Hardware.
Ausführliche Installationsanweisungen finden Sie auf https://docs.bigbluebutton.org/

## Openmeetings
Zu Testzwecken und gegebenenfalls auch zum Einsatz in Produktivsystemen können die jeweiligen Server-Dienste über deren Installation in einer Docker Umgebung avisiert werden.
Entsprechende Docker Images stehen auf https://hub.docker.com/ zur Verfügung.

Hier ein Beispiel zum Ausführen eines Docker Containers mit Openmeetings:
`docker run --name openmeetings -i --expose 5080 --expose 5443 --expose 8888 -p 5080:5080 -p 5443:5443 -p 8888:8888 --rm apache/openmeetings:5.0.0-M4` 

## Spreed
Installieren Sie Spreed gemäß https://github.com/strukturag/spreed-webrtc .
Kopieren Sie die mit dem Plugin gelieferte main.html in das html-Verzeichnis von Spreed. Wenn Sie möchten, dass nur ILIAS-Benutzer Spreed verwenden können, editieren Sie die Datei main.html und fügen Sie die ILIAS-NIC bei checkInstIds hinzu.


# ILIAS MultiVC-Plugin

## Software
 
Wir empfehlen die Nutzung des MultiVC-Plugin mit ILIAS Release 6 oder Release 5.4. Die Mindestvoraussetzungen, mit denen das Plugin getestet wurde, finden Sie hier im Überblick:
- ILIAS 6.x, 5.4.x
- PHP 7.2
- MySQL 5.7

## Installation

- Kopieren Sie den Inhalt dieses Ordners oder Klonen Sie das Git Repository in folgendes Verzeichnis auf Ihrem Webserver: `<ILIAS_directory>/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVC`
    - Wechsle auf dem Filesystem deines Webservers ins ILIAS-Verzeichnis, dann
    - mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject
    - cd Customizing/global/plugins/Services/Repository/RepositoryObject
    - git clone https://github.com/Uwe-Kohnle/MultiVc


- Melden Sie sich auf Ihrer ILIAS-Installation als Administrator an und wählen Sie im Menü `Administration / Plugins`. In der Plugin-Übersicht finden Sie den Eintrag MultiVC. Führen Sie über dessen Dropdown-Menü folgende Aktionen aus:
    - Installieren
    - Aktivieren
    - Konfigurieren
- Füllen Sie alle mit * gekennzeichneten Pflichtfelder und ggf. die übrigen optionalen Felder aus und bestätigen Sie Ihre Eingaben mit einem Klick auf Speichern

## Virtuellen Meetingraum anlegen

Wir empfehlen virtuelle Meetingräume in Kursen oder Gruppen anzulegen. Die Zugriffsrechte auf das Objekt können somit für Benutzer über deren zugewiesene Benutzerrolle eingestellt werden. Wir empfehlen folgende Rollenvorlagen anzupassen:
- Gruppenadministrator
- Gruppenmitglied
- Kursadministrator
- Kurstutor
- Kursmitglied

## Gastlink

Zum Einladen von Gästen über einen Link, gehen Sie so vor:
- In der Plugin-Konfiguration die folgende Optionen nach Bedarf setzen:
    - Gastlink auswählbar
    - Gastlink Voreinstellung
- In den Objekt-Einstellungen ggf. "Gastlink verfügbar" auswählen

Moderatoren und Administratoren sehen nun im Inhalt unter dem Reiter "Meeting" im Abschnitt "Gastlink" eine Url.
Diese kann den einzuladenden Gästen mitgeteilt werden.

Der Gastlink steht grundsätzlich nur in moderierten Räumen zur Verfügung. Gäste erhalten immer die Rolle "Teilnehmer".

### Kurze Gastlink-Url

Die zunächst sehr lange Url kann auf folgendes Schema verkürzt werden:
https://domain/m/client/id

Zum Kürzen der Url gehen Sie so vor:
- fügen Sie Ihrer Webserver-Konfiguration eine Rewrite-Rule hinzu
    - Bsp. für Apache .htaccess:
        `RewriteRule ^m/([A-Za-z0-9]+)/([0-9]+)$ ./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/index.php?ref_id=$2&client=$1 [L]`
    - Bsp. für nginx .conf:
        `location /m/  {
                rewrite ^/m/([A-Za-z0-9]+)/([0-9]+)$ /Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/index.php?ref_id=$2&client=$1 last;
                return 403;
        }`
- in der plugin.ini setzen Sie den Wert "guest_link_shortener = 1"


#Icon
Icon: https://pixabay.com/de/vectors/orange-stil-treffen-pfeile-punkt-41015/



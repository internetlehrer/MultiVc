# About

This [ILIAS](https://www.ilias.de) Plugin allows to interact with different WebRTC-based virtual classroom platforms.
Currently we have implemented the ability to use the following VCs:
- Bigbluebutton
- Webex
- Edudip (Webinar)
- Spreed
- Openmeetings


# Prerequisites

Zunächst benötigen Sie eine funktionsfähige Installation der gewünschten WebRTC Plattform, bzw ein Kundenkonto beim WebRTC Provider.

## BigBlueButton
Zur Nutzung mit Bigbluebutton empfehlen wir die Installation auf einer hoch performanten Hardware.
Ausführliche Installationsanweisungen finden Sie auf https://docs.bigbluebutton.org/

## Webex
In der Plugin-Administration stehen für den Meeting-Typ Webex zwei Varianten für die Authorisierung zum Anlegen und Starten von Webex-Meetings bereit.
- User Scope
    Bei MultiVc-Objekten mit UserScope, müssen ILIAS-Benutzer (Objekt-Eigentümer), die Authorisierung für das jeweilige
    Objekt selbst in einem OAuth-Prozess durchführen. Für die Authorisierung werden bei Webex nur Scopes abgerufen,
    wie sie z. B. auch im Lizenzumfang eines kostenlosen Webex-Kontos verfügbar sind. Die Optionen zum Authorisieren und Widerrufen,
    stehen ausschließlich dem Objekt-Eigentümer zur Verfügung. Ändert sich der Eigentümer, oder die erste, in den Benutzereinstellungen des entsprechenden Benutzers angegebene, E-Mailadresse,
    wird die Authorisierung beim nächsten Aufruf des Objekts aufgehoben.
- Admin Scope
    Für Webex-Meeting-Typ-Konfigurationen mit Admin Scope erfolgt die Authorisierung durch einen Webex-Meeting-Administrator
    global für alle Objekte der Meeting-Typ-Konfiguration. Objekt-Eigentümer können ohne Weiteres Meetings anlegen und starten.

Zur Wahrung der Eigentumsrechte an bereits angelegten Meetings können diese ausschließlich von ILIAS-Benutzern verwaltet
werden, deren UserId mit der, zum angelegten Meeting, hinterlegten übereinstimmt und die, zum angelegten Meeting, hinterlegte E-Mailadresse
mit der des Eigentümers übereinstimmt. Ändert sich der Eigentümer, oder die erste, in den Benutzereinstellungen des entsprechenden Benutzers angegebene, E-Mailadresse,
stehen die dem Objekt zugewiesenen Meetings zum Abruf nicht weiter zur Verfügung.

### Integration
Mit Authorisierung einer Webex-Integration wird die Relation vom MultiVc-Objekt zum Webex-Account hergestellt. Erzeugt
werden kann eine Integration durch einen Benutzer der Site: developer.webex.com (siehe https://developer.webex.com/docs/integrations)
Die Parameter, der Meeting-Typ-Konfiguration, "Client-ID der Webex Integration" und "Client-Secret der Webex Integration", entnehmen Sie den Integration-Settings.
Des Weiteren muss der Wert des Meeting-Typ-Konfigurations-Parameters "Redirect URI" in den Settings der Integration angegeben werden.

### Authorisation
- Die Authorisation für Webex-Meeting-Typ-Konfigurationen mit Admin Scope, erfolgt in der Übersicht durch einen Klick auf den Link "Authorisieren" zur gewünschten Meeting-Typ-Konfiguration.
- Die Authorisation für Webex-Meeting-Typ-Konfigurationen mit User Scope, erfolgt in den Objekt-Einstellungen durch Klick auf den Button "Authorisieren".
Nach erfolgter Authorisierung ist hier der Button "Authorisierung aufheben" zu sehen.

### Meeting / Geplante Meetings





## Edudip
Nachdem Sie in der Plugin-Administration einen Meeting-Typ mit Edudip als WebRTC-Plattform angelegt haben,
können Sie ILIAS-Benutzer authorisieren, Webinare anzulegen und zu starten.
### BenutzerInnen Authorisation
Öffnen Sie mit einem Klick auf "Bearbeiten"
die Konfiguration des Meeting-Typs. Im unteren Bereich des Formulars finden Sie die Felder:
- Registrierte ModeratorInnen
    bereits Authorisierte sind hier an der E-Mailadresse zu erkennen. Mit einem Klick auf "X",
    kann die jeweilige Authorisierung aufgehoben werden. Sind (noch) keine Authorisierungen vorhanden,
    signalisiert dies "keine Einträge vorhanden".
- Neue ModeratorIn / Token
    hier können Sie durch Texteingaabe die E-Mailadresse der zu authorisierenden ILIAS-BenutzerIn definieren.
    Das Textfeld bietet auch die Option ILIAS-BenutzerInnen durch Eingabe von Vor- / Nachname oder Benutzername auszuwählen.
    Eine Auswahlliste erscheint ab Eingabe von drei Zeichen, weitere Zeichen verfeinern das Suchergebnis. Mit Klick
    auf einen Eintrag aus der Auswahlliste, wird die entsprechende E-Mailadresse im Textfeld hinterlegt.
    Zum Authorisieren für die Nutzung von Edudip-Webinaren als ModeratorIn, muss ein Token hinterlegt werden.
    Ein Klick auf den Button "Token hinzufügen", öffnet ein Eingabefenster (Modal) mit dem Titel "Token hinzufügen". In das Textfeld
    im Modal tragen Sie den Token ein und bestätigen Ihre Eingabe mit einem Klick auf "Speichern".
    Nach Speichern, gelangen Sie zurück auf die Oberfläche der Meeting-Typ Konfiguration. Bei erfolgreichem Speichern,
    erscheint oberhalb des Formulars der Hinweis "Token gespeichert" und im Feld "Registrierte ModeratorInnen" ist die
    neu authorisierte E-Mailadresse zu sehen. Weiteres zur Authorisation finden Sie unter dem Punkt "Hinweise zur Edudip Authorisation".

### Hinweise zur Authorisation
- zu Authorisierende müssen bereits als Benutzer bei ILIAS registriert sein.
- beim Anlegen und Starten von Webinaren wird die erste, in den Bentzerdaten angegebene, E-Mailadresse
zusammen mit dem hinterlegten Token zur Authentifizierung bei Edudip heran gezogen.
- hinterlegte Tokens sind grundsätzlich nicht über die Weboberfläche einsehbar und können nicht geändert werden.
- authorisierte E-Mailadressen können gelöscht werden, womit auch der jeweils hinterlegte Token gelöscht wird.
- Gründe, weshalb in der Meeting-Typ Konfiguration der Hinweis "Token nicht gespeichert" erscheint:
    -- angegebene E-Mailadresse ist bereits authorisiert
    -- angegebene E-Mailadresse ist bei ILIAS nicht bekannt
    -- Token hat nicht die Mindestlänge
- Die Verwaltung der Tokens für zu authorisierende E-Mailadressen erfolgt über die Weboberfläche bei Edudip.
- Die von Edudip erzeugten Tokens können in der Plugin Konfiguration grundsätzlich nur eingegeben werden.

### Webinar / Geplante Webinare
- Eigentümer können
    -- Webinare anlegen, löschen und zuordnen
    -- ausschließlich Webinare starten
- Kurstutoren und -Administratoren können
    -- Webinaren als Co-Moderatoren beitreten
    --






## Openmeetings
Zu Testzwecken und gegebenenfalls auch zum Einsatz in Produktivsystemen können die jeweiligen Server-Dienste über deren Installation in einer Docker Umgebung avisiert werden.
Entsprechende Docker Images stehen auf https://hub.docker.com/ zur Verfügung.

Hier ein Beispiel zum Ausführen eines Docker Containers mit Openmeetings:
`docker run --name openmeetings -i --expose 5080 --expose 5443 --expose 8888 -p 5080:5080 -p 5443:5443 -p 8888:8888 --rm apache/openmeetings:5.0.0-M4` 

## Spreed
Installieren Sie Spreed gemäß https://github.com/strukturag/spreed-webrtc .
Kopieren Sie die mit dem Plugin gelieferte main.html in das html-Verzeichnis von Spreed. Wenn Sie möchten, dass nur ILIAS-Benutzer Spreed verwenden können, editieren Sie die Datei main.html und fügen Sie die ILIAS-NIC bei checkInstIds hinzu.


# ILIAS MultiVc-Plugin

## Software
 
Wir empfehlen die Nutzung des MultiVc-Plugin mit ILIAS Release 6 oder Release 5.4. Die Mindestvoraussetzungen, mit denen das Plugin getestet wurde, finden Sie hier im Überblick:
- ILIAS 6.x, 5.4.x
- PHP 7.2
- MySQL 5.7

## Installation

- Kopieren Sie den Inhalt dieses Ordners oder Klonen Sie das Git Repository in folgendes Verzeichnis auf Ihrem Webserver: `<ILIAS_directory>/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc`
    - Wechsle auf dem Filesystem deines Webservers ins ILIAS-Verzeichnis, dann
    - mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject
    - cd Customizing/global/plugins/Services/Repository/RepositoryObject
    - git clone https://github.com/internetlehrer/MultiVc


- Melden Sie sich auf Ihrer ILIAS-Installation als Administrator an und wählen Sie im Menü `Administration / Plugins`. In der Plugin-Übersicht finden Sie den Eintrag MultiVc. Führen Sie über dessen Dropdown-Menü folgende Aktionen aus:
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



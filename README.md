# ILIAS MultiVc-Plugin



## Über

Dieses [ILIAS](https://www.ilias.de) Plugin ermöglicht die Verwendung verschiedener WebRTC-basierter Plattformen für virtuelle Klassenzimmer und Konferenzsysteme.

Für die ILIAS-Version 6 und 7 nutzen Sie bitte den branch 'release7'. S. https://github.com/internetlehrer/MultiVc/tree/release7

## Features

- bestimmen Sie, welche WebRTC-basierten Systeme in der Plugin-Kofiguration zur Verfügung stehen
- erstellen Sie multiple WebRTC-Plattform-Konfigurationen, die dann in MultiVc-Objekten zur Auswahl stehen 
- weisen Sie den WebRTC-Plattform-Konfigurationen globale und lokale Benutzerrollen zu und steuern Sie, wer welche Plattform-Konfigurationen nutzen darf
- bestimmen Sie, welche Nutzer WebRTC-Sitzungen in Kursen und Gruppen starten dürfen (Benutzerrollen-basierte Moderatorfunktion) 

Viele weitere Plattformabhängige Features stehen zur Verfügung, wie beispielsweise Aufzeichnungen und Terminplanung.

## Inhaltsverzeichnis

[TOC]



# Voraussetzungen

Wir empfehlen die Nutzung des MultiVc-Plugin mit ILIAS Release 8. Die Mindestvoraussetzungen, mit denen das Plugin getestet wurde, finden Sie hier im Überblick:

- ILIAS 8.x
- PHP 7.4
- MySQL 5.7 oder MariaDB 10.2

Des Weiteren benötigen Sie eine funktionsfähige Installation der gewünschten WebRTC Plattform bzw. ein Kundenkonto beim WebRTC Provider.



# Installation

- Kopieren Sie den Inhalt dieses Ordners oder Klonen Sie das Git Repository in folgendes Verzeichnis auf Ihrem Webserver: `<ILIAS_directory>/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc`
  - Wechseln Sie auf dem Filesystem Ihres Webservers ins ILIAS-Verzeichnis, dann
  - `mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject`
  - `cd Customizing/global/plugins/Services/Repository/RepositoryObject`
  - `git clone https://github.com/internetlehrer/MultiVc`


- Melden Sie sich auf Ihrer ILIAS-Installation als Administrator an und wählen Sie im Menü `Administration / Plugins`. In der Plugin-Übersicht finden Sie den Eintrag MultiVc. Führen Sie über dessen Dropdown-Menü folgende Aktionen aus:
  - Installieren
  - Aktivieren
  - Konfigurieren
- Mit einem Klick auf den Button "Neuen Meeting-Typ definieren", können Sie Ihre gewünschten WebRTC-Plattform-Konfigurationen anlegen.



# Unterstützte WebRTC-Platformen
Aktuell werden folgende WebRTC-Platformen unterstützt:
- BigBlueButton
- edudip (Webinar)
- Openmeetings
- Webex
- Teams






## BigBlueButton
Zur Nutzung mit Bigbluebutton empfehlen wir die Installation auf einer hoch performanten Hardware.
Ausführliche Installationsanweisungen finden Sie auf https://docs.bigbluebutton.org/


### Aufzeichnungen (mp4)

Aufzeichnungen von Meetings werden nach Beenden eines Meetings tabellarisch unter dem Meeting-Tab aufgeführt. Folgende Voraussetzungen zum Aufzeichnen müssen erfüllt sein:

- Zum Aufzeichnen von Meetings müssen in der Plugin-Konfiguration und in den Objekt-Eigenschaften die entsprechenden Optionen vor Beginn des Meetings aktiviert werden. 
- Die Formate `.webm` und `.mp4`, können in der BigBlueButton Konfiguration in der Datei `/usr/local/bigbluebutton/core/scripts/presentation.yml` unter dem Block `video_formats` festgelegt werden. Detaillierte Informationen dazu finden Sie hier: [BigBlueButton : Customize](https://docs.bigbluebutton.org/admin/customize#enable-playback-of-recordings-on-ios)

### Anzahl maximal gleichzeitiger Nutzer definieren

Sie können die Anzahl maximal gleichzeitiger Nutzer definieren und die Nutzung in einer tabellarischen Übersicht nach Datum und Uhrzeit auswerten. Damit Sie dieses Feature nutzen können, gehen Sie wie folgt vor:

- Wechseln Sie auf dem Filesystem Ihres Webservers ins ILIAS-Plugin-Verzeichnis
  `cd [*documentroot*]/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc`
- öffnen Sie mit Schreibrechten die Datei "plugin.ini"
  `sudo nano ./plugin.ini`
- entfernen Sie das Zeichen "#" in der Zeile, beginnend mit "max_concurrent_users"
- setzten Sie Ihren gewünschten Wert am Ende der selben Zeile, so dass diese z. B. wie folgt aussieht:
  `max_concurrent_users = 50`
- speichern und schließen Sie die Datei
  `STRG + o`, `STRG + c`
#### Multiple .ini-Konfigurationen

Sie können weitere Konfigurationen hirarchisch in drei Prioritäten anlegen. Dabei hat die Datei "plugin.ini" die niedrigste Priorität. Darauf folgen Dateien, die mit der Domain Ihrer ILIAS-Installation beginnen. Die höchste Priorität haben Dateien, die mit der Domain Ihrer BigBlueButton Installation beginnen. Datei-Beispiel mit niedrigster (1) bis höchster Priorität (3):

1. `plugin.ini`
2. `lms.example.com.ini`
3. `bbb.example.com.ini`



## edudip (Webinar)
Nachdem Sie in der Plugin-Administration einen Meeting-Typ mit edudip als WebRTC-Plattform angelegt haben,
können Sie ILIAS-Benutzer authorisieren, Webinare anzulegen und zu starten.

Bitte beachten Sie, dass Webinare jeweils nur als Einzeltermin angelegt werden.

### BenutzerInnen authorisieren
Öffnen Sie in der Plugin-Konfiguration mit einem Klick auf "Bearbeiten"
die Konfiguration des Meeting-Typs. Im unteren Bereich des Formulars finden Sie die Felder:

- Registrierte ModeratorInnen
    bereits Authorisierte sind hier an der E-Mailadresse zu erkennen. Mit einem Klick auf "X",
    kann die jeweilige Authorisierung aufgehoben werden. Sind (noch) keine Authorisierungen vorhanden,
    signalisiert dies "keine Einträge vorhanden".
- Neue ModeratorIn / Token
    hier können Sie durch Texteingabe die E-Mailadresse der zu authorisierenden ILIAS-BenutzerIn definieren.
    Das Textfeld bietet auch die Option ILIAS-BenutzerInnen durch Eingabe von Vor- / Nachname oder Benutzername auszuwählen.
    Eine Auswahlliste erscheint ab Eingabe von drei Zeichen, weitere Zeichen verfeinern das Suchergebnis. Mit Klick
    auf einen Eintrag aus der Auswahlliste, wird die entsprechende E-Mailadresse im Textfeld hinterlegt.
    Zum Authorisieren für die Nutzung von edudip-Webinaren als ModeratorIn, muss ein Token hinterlegt werden.
    Ein Klick auf den Button "Token hinzufügen", öffnet ein Eingabefenster (Modal) mit dem Titel "Token hinzufügen". In das Textfeld
    im Modal tragen Sie den Token ein und bestätigen Ihre Eingabe mit einem Klick auf "Speichern".
    Nach Speichern, gelangen Sie zurück auf die Oberfläche der Meeting-Typ Konfiguration. Bei erfolgreichem Speichern,
    erscheint oberhalb des Formulars der Hinweis "Token gespeichert" und im Feld "Registrierte ModeratorInnen" ist die
    neu authorisierte E-Mailadresse zu sehen. Weiteres zur Authorisation finden Sie unter dem Punkt "Hinweise zur edudip Authorisation".

#### Hinweise zur Authorisierung
- zu Authorisierende müssen bereits als Benutzer bei ILIAS registriert sein.
- beim Anlegen und Starten von Webinaren wird die erste, in den Bentzerdaten angegebene, E-Mailadresse
zusammen mit dem hinterlegten Token zur Authentifizierung bei edudip heran gezogen.
- hinterlegte Tokens sind grundsätzlich nicht über die Weboberfläche einsehbar und können nicht geändert werden.
- authorisierte E-Mailadressen können gelöscht werden, womit auch der jeweils hinterlegte Token gelöscht wird.
- Gründe, weshalb in der Meeting-Typ Konfiguration der Hinweis "Token nicht gespeichert" erscheint:
    -- angegebene E-Mailadresse ist bereits authorisiert
    -- angegebene E-Mailadresse ist bei ILIAS nicht bekannt
    -- Token hat nicht die Mindestlänge
- Die Verwaltung der Tokens für zu authorisierende E-Mailadressen erfolgt über die Weboberfläche bei edudip.
- Die von Edudip erzeugten Tokens können in der Plugin Konfiguration grundsätzlich nur eingegeben werden.

### Webinar / Geplante Webinare
- Eigentümer können Webinare
    - anlegen
    - löschen
    - zuordnen
    - starten
- Gruppen- und Kurs-Administratoren sowie Kurstutoren können Webinare 
    - starten
    - als Co-Moderator beitreten
### E-Mail Benachrichtigung

Nach Anlegen oder Löschen eines Webinars, werden Benachrichtigungen an Gruppenadministratoren sowie Kursadministratoren und -Tutoren gesendet. Bitte beachten Sie, dass nur zum Zeitpunkt des Events bereits definierte Admins und Tutoren benachrichtigt werden.

Betreff und Inhalt der Benachrichtigung kann über Sprachvariablen und in den Variablewerten enthaltene Platzhalter konfiguriert werden. Hierfür stehen folgende Sprachvariablen und Platzhalter zur Verfügung:

| Variable                     | Text / Platzhalter                                           | Beschreibung                                                 |
| ---------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| webinar_notification_subject | {EVENT}: {SUBJECT}                                           | **Betreff der Benachrichtigung.** <br />`{EVENT}` wird ersetzt durch den Text der Variable `webinar_event_created` oder `webinar_event_deleted`<br />`{SUBJECT}` wird durch den Titel des Webinars ersetzt. |
| webinar_notification_body    | Hallo {NAME},{NL}{NL}es wurde folgendes {EVENT}:{NL}{NL}{SUBJECT}{NL}{DATERANGE}{NL}{LINK}{NL}{NL}Mit freudlichen Grüßen{NL}{NL}{FROM} | **Inhalt der Benachrichtigung.**<br />`{NL}` wird ersetzt durch Zeilenumbruch `\n\l`.<br />`{NAME}` wird ersetzt durch Titel Vorname Nachname des Empfängers.<br />`{DATERANGE}` wird durch Start- / Enddatum  des Webinars ersetzt.<br />{LINK} wird durch Url zur ILIAS Objekt-Seite ersetzt.<br />`{FROM}` wird ersetzt durch den Text der Variable `webinar_notification_from` |
| webinar_event_created        | Webinar angelegt                                             | Inhalt des Platzhalters `{EVENT}`                            |
| webinar_event_deleted        | Webinar gelöscht                                             | Inhalt des Platzhalters `{EVENT}`                            |
| webinar_notification_from    | ILIAS                                                        | Inhalt des Platzhalters `{FROM}`                             |

**Beispiel:**

Nach Anlegen eines Webinars mit den Werten:
Titel: Mein Test-Webinar
Von - Bis: 10.01.2021 08:00 - 10.01.2021 09:00

werden Benachrichtigungen gesendet mit dem Inhalt:

`webinar_notification_subject`: 
Webinar angelegt: Mein Test-Webinar

`webinar_notification_body`: 
Hallo Prof. Dr. Müller,

es wurde folgendes Webinar angelegt:

Mein Test-Webinar
10.01.2021 08:00 - 10.01.2021 09:00
https://DOMAIN.TLD/PFAD_ZU_ILIAS/ilias.php?target=xmvc_123&client_id=inno

Mit freudlichen Grüßen

ILIAS






## Openmeetings

Zu Testzwecken und gegebenenfalls auch zum Einsatz in Produktivsystemen können die jeweiligen Server-Dienste über deren Installation in einer Docker Umgebung avisiert werden.
Entsprechende Docker Images stehen auf https://hub.docker.com/ zur Verfügung.

Hier ein Beispiel zum Ausführen eines Docker Containers mit Openmeetings:
`docker run --name openmeetings -i --expose 5080 --expose 5443 --expose 8888 -p 5080:5080 -p 5443:5443 -p 8888:8888 --rm apache/openmeetings:5.0.0-M4` 

### Tips zur Openmeetings Konfiguration

Legen Sie in Openmeetings einen User für den SOAP-Zugriff an. Wichtig ist, dem SoapUser folgende Rechte zu geben:

- SOAP
- LOGIN
- ROOM

### Hinweis
Openmeetings wird mit MultiVc ab ILIAS 9 nicht mehr unterstützt.





## Webex

In der Plugin-Administration stehen für den Meeting-Typ Webex zwei Varianten für die Authorisierung zum Anlegen und Starten von Webex-Meetings bereit.
- User Scope
    Bei MultiVc-Objekten mit UserScope, müssen ILIAS-Benutzer (Objekt-Eigentümer), die Authorisierung für das jeweilige
    Objekt selbst in einem OAuth-Prozess durchführen. Für die Authorisierung werden bei Webex nur Scopes abgerufen,
    wie sie z. B. auch im Lizenzumfang eines kostenlosen Webex-Kontos verfügbar sind. Die Optionen zum Authorisieren und Widerrufen
    stehen ausschließlich dem Objekt-Eigentümer zur Verfügung. Ändert sich der Eigentümer, oder die erste, in den Benutzereinstellungen des entsprechenden Benutzers angegebene, E-Mailadresse,
    wird die Authorisierung beim nächsten Aufruf des Objekts aufgehoben.
- Admin Scope
    Für Webex-Meeting-Typ-Konfigurationen mit Admin Scope erfolgt die Authorisierung durch einen Webex-Meeting-Administrator
    global für alle Objekte der Meeting-Typ-Konfiguration. Objekt-Eigentümer können ohne Weiteres Meetings anlegen und starten.

Zur Wahrung der Eigentumsrechte an bereits angelegten Meetings können diese ausschließlich von ILIAS-Benutzern verwaltet
werden, deren UserId mit der, zum angelegten Meeting, hinterlegten übereinstimmt und die, zum angelegten Meeting, hinterlegte E-Mailadresse mit der des Eigentümers übereinstimmt. Ändert sich der Eigentümer, oder die erste, in den Benutzereinstellungen des entsprechenden Benutzers angegebene, E-Mailadresse,
stehen die dem Objekt zugewiesenen Meetings zum Abruf nicht weiter zur Verfügung.

### Integration

Mit Authorisierung einer Webex-Integration wird die Relation vom MultiVc-Objekt zum Webex-Account hergestellt. Erzeugt
werden kann eine Integration durch einen Benutzer der Site: developer.webex.com (siehe https://developer.webex.com/docs/integrations)
Die Parameter, der Meeting-Typ-Konfiguration, "Client-ID der Webex Integration" und "Client-Secret der Webex Integration", entnehmen Sie den Integration-Settings.
Des Weiteren muss der Wert des Meeting-Typ-Konfigurations-Parameters "Redirect URI" in den Settings der Integration angegeben werden.

### Authorisierung
- Die Authorisierung für Webex-Meeting-Typ-Konfigurationen mit Admin Scope, erfolgt in der Übersicht durch einen Klick auf den Link "Authorisieren" zur gewünschten Meeting-Typ-Konfiguration.
- Die Authorisierung für Webex-Meeting-Typ-Konfigurationen mit User Scope, erfolgt in den Objekt-Einstellungen durch Klick auf den Button "Authorisieren".
Nach erfolgter Authorisierung ist hier der Button "Authorisierung aufheben" zu sehen.

### Meeting / Geplante Meetings

- nur Eigentümer können Meetings
  - anlegen
  - löschen
  - zuordnen
- Gruppen- und Kursadministratoren (Eigentümer u. A.) sowie Kurstutoren können Meetings
  - starten 
  - als Co-Moderator beitreten 

  Insofern bei Webex eingestellt, wird beim Beitritt automatisch eine E-Mail mit den Zugangsdaten versendet. Dabei erhalten auch Eigentümer eine E-Mail-Benachrichtigung. 

## Teams

### Integration
#### App registrieren
Sie müssen im Microsoft Entra Admin Center über Anwendungen für das MultiVc-Plugin für Ihre Organistation eine App registrieren.
Folgende API-Berechtigungen sollten als Anwendungsberechtigungen erteilt werden:
- Calendars.ReadWrite
- OnlineMeetingArtifact.Read.All
- OnlineMeetingRecording.Read.All
- OnlineMeetings.ReadWrite.All
- User.Read.All

Nach Auswahl klicken Sie auf 'Berechtigung hinzufügen' und danach auf 'Administratorzustimmung (...) erteilen'. 
Anschließend Klicken Sie auf 'Zertifikate & Geheimnisse' und erstellen Sie einen neuen geheimen Clientschlüssel.
Notieren Sie sogleich den nur nach der Erstellung angezeigten 'Wert'.
Für die Einrichtung des MultiVc-Plugins benötigen Sie neben diesem Client-Secret noch die Tenant-ID (Verzeichnis-ID, Mandant) und die Client-ID (Anwendungs-ID). 
Diese Angaben finden Sie bei der registrierten App unter 'Übersicht'. 
Stellen Sie sicher, dass Benutzer auch OnlineMeetings erstellen dürfen.

#### ApplicationAccessPolicy erstellen
Für die Absicherung des Zugriffs benötigen Sie noch eine ApplicationAccessPolicy. Gehen Sie wie folgt vor:  
Öffnen Sie die Windows-PowerShell und geben Sie ein: Connect-MicrosoftTeams

[Hinweis: Sollte das Ausführen von Connect-MicrosoftTeams nicht möglich sein, müssen Sie diese zuerst installieren:
Öffnen Sie hierzu PowerShell mit Administratorrechten und geben Sie dann ein:

Install-Module -Name MicrosoftTeams]

Ein Login-Fenster wird nun angezeigt. Ein Account mit weitgehenden Rechten wird benötigt (hier: Obermotz). 

Geben Sie nun über die PowerShell die folgenden beiden Zeilen ein:

New-CsApplicationAccessPolicy -Identity _Name der ApplicationAccessPolicy_ -AppIds "_Clent-ID bzw. Anwendungs-ID_" -Description "MultiVc2 Policy"

Grant-CsApplicationAccessPolicy -PolicyName _Vergebener Name der ApplicationAccessPolicy_ -Identity "_UPN oder ObjectID des Benutzers Obermotz_"

### Lernfortschritt
Mit dem erstmaligen Einrichten von Teams im MultiVc-Plugin werden die Rechte 'Lernfortschrittseinstellungen bearbeiten' und 'Lernfortschritt anderer Benutzer einsehen' hinzugefügt. Passen Sie ggfs. Objekte und insbesondere die Rollenvorlagen an. 

Für den Fall, dass Sie den Lernfortschritt nutzen möchten, wird empfohlen, dass in der Konfiguration 'Benutzerübersicht verstecken' deaktiviert ist.
Somit können Sie für abgelaufene Meetings durch Klick auf 'Anwesenheitszeiten' unter 'Meeting' den Lernfortschritt aktualisieren. Ansonsten wird der Lernfortschritt durch den täglich laufenden Cronjob 'MultiVc-Cronjob zur Ermittlung des Lernfortschritts' berechnet.
Beachten Sie, dass der Lernfortschritt nur für diejenigen berechnet werden kann, die sich über die Teams-App angemeldet haben. Ansonsten kann eine eindeutige Nutzerzuordnung nicht erfolgen und die Teilnehmenden werden mit der Rolle 'Gast' angezeigt. Für Gäste wird kein Lernfortschritt ermittelt.

Grundlage für die Ermittlung des Lernfortschritts ist die Anwesenheitszeit. In der 'Benutzerübersicht' sehen Sie die einzelne Teilnahmezeiten, gekennzeichnet mit 'Teilnahme ab' und 'Teilnahme bis'. 
Unter dem Link 'Anwesenheitszeiten' sehen Sie die kumulierten Anwesenheitszeiten je Meeting und einen Prozentwert. 
Dieser Prozentwert berücksichtigt die Dauer des Meetings ohne Überziehungen. Wurde beispielsweise ein Meeting von 13:00 bis 14:00 angesetzt, so werden Zeiten nach 14:00 nicht berücksichtigt. 
Wurde das Meeting von einem (co-) Organisator vorzeitig beendet, so wird die verbleibende Zeit bis zum ursprünglich vorgesehenen Ende nicht für die Berechnung des Prozentwerts herangezogen.

Anwesenheitszeiten bei vor der vorgesehener Startzeit beendeten Sitzungen werden ebenso wenig berücksichtigt, wie Anwesenheitszeiten für Sitzungen, die erst nach dem vorgesehenen Ende gestartet wurden.
Innerhalb der vorgesehenen Startzeit und Endzeit darf nur eine Sitzung vorhanden sein. 
Sollte zwischendurch das Meeting beendet und neu gestartet werden, so wird nur die erste Sitzung innerhalb eines Meetings berücksichtigt. 

Über den Reiter 'Lernfortschritt' und den Link 'Einstellungen' können Sie bei grundsätzlich aktiviertem Lernfortschritt einen Schwellwert für den Status 'Bearbeitet' bestimmen. Die Default-Einstellung ist 70. Das bedeutet, dass mindestens 70% der möglichen Anwesenheitszeit erreicht werden muss, um den Status 'Bearbeitet' (grün) zu erhalten.

Hinweis: Der CronJob sollte aus Performanzgründen in jedem Fall aktiviert sein. 

### Moderierte Räume
Moderierte Räume sind dadurch gekennzeichnet, dass Teilnehmende nicht eigenständig in Meetings gelangen, sondern durch (Co-)Organisatoren hineingelassen werden müssen. Außerdem kann in moderierten Räumen nicht jeder eigenständig Präsentator-Rechte wahrnehmen. 

### Co-Organisatoren (Mitorganisatoren)
Damit Kurs- bzw. Gruppenadministratoren bzw. Kurstutoren erweiterte Rechte als Co-Organisatoren haben, müssen sie _vor_ einem angesetzten Meeting mit ihrer Rolle im Kurs bzw. in der Gruppe eingetragen sein. Danach sind einfache Teilnehmende. 
Nur diejenigen können Co-Organisatoren sein, die ein Konto im selben Tenant wie das Konto des Organisators verwenden.

### Kalender-Einträge
Teilnehmende werden auch dann benachrichtigt, wenn Meetings bereits vor ihrem Kurs- bzw. Gruppenbeitritt angesetzt wurden.

### Sprache der Benachrichtigung
Grundsätzlich werden die Standard-Teams-Benachrichtigungen unter Berücksichtigung der Benutzersprache und der gewählten Zeitzone genutzt. Da die Teilnehmenden außer dem Direktlink zu Teams auch den Link im ILIAS-Objekt nutzen können und ggfs. ein Hinweis zu anstehenden Aufzeichnungen übermittelt werden soll, gilt Folgendes: Möchten Sie als Organisator die ergänzenden Texte z.B. in Englisch anzeigen lassen, so wechseln Sie in ILIAS zur englischen Sprache und legen Sie dann ein Meeting an. Auch alle Folge-Benachrichtigungen etwa beim Kursbeitritt nutzen dann die zum Zeitpunkt des Anlegens eines Meetings genutzte Sprache.   




# Verwendung



## Virtuellen Meetingraum anlegen

Virtuelle Meetingräume sollten in Kursen oder Gruppen angelegt werden. Die Zugriffsrechte auf das Objekt können somit für Benutzer über deren zugewiesene Benutzerrolle eingestellt werden. Wir empfehlen folgende Rollenvorlagen anzupassen:

- Gruppenadministrator
- Gruppenmitglied
- Kursadministrator
- Kurstutor
- Kursmitglied



## Globale / lokale Rollen zuweisen

Wählen Sie in der Plugin-Konfiguration aus der Übersicht die gewünschte WebRTC-Plattform-Konfiguration mit einem Klick auf Bearbeiten aus. Im Formular finden Sie ein Multi-Selektfeld mit der Bezeichnung "Zugewiesene Rollen". Setzen Sie bei den gewünschten Rollen einen Haken und bestätigen Sie Ihre Eingaben mit einem Klick auf Speichern.

Melden Sie sich bei ILIAS als Benutzer mit einer der zugewiesenen Rollen an. Navigieren Sie im Magazin an eine gewünschte Stelle und fügen Sie ein neues Objekt "Virtueller Meetingraum" hinzu. Im nächsten Schritt werden Sie aufgefordert einen Titel anzugeben und eine WebRTC-Plattform auszuwählen (* Pflichtfelder) - in der Liste stehen (nur) die Verbindungen zur Verfügung., denen eine der Rollen ihres aktuellen Benutzers zugewiesen wurde.

### Nach einem Upgrade stehen zunächst alle Verbindungen im Objekt bereit

Wenn Sie das Plugin von einer früheren Version (< v4) auf die aktuelle Version upgraden, bleibt die Konfiguration bereits angelegter Verbindungen unberührt.

### Rollenzuweisung ausschalten

Auf Lernplattformen mit sehr vielen Kursen kann es in der Meetingtyp-Konfiguration zu langen Ladezeiten kommen. In der Konfigurationsdatei `plugin.ini` können Sie durch Setzen des folgenden Parameters das Formularfeld ausschließen: `non_role_based_vc = 1`

Bitte achten Sie darauf, dass bevor Sie diesen Parameter setzen keine Rollen bereits zugewiesen sind.



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
    `RewriteRule ^m/([A-Za-z0-9]+)/([0-9]+)$ ./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/index.php?ref_id=$2&client_id=$1 [L]`
  - Bsp. für nginx .conf:
    `location /m/  {
            rewrite ^/m/([A-Za-z0-9]+)/([0-9]+)$ /Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/index.php?ref_id=$2&client_id=$1 last;
            return 403;
    }`
- in der plugin.ini setzen Sie den Wert "guest_link_shortener = 1"



## Gruppe im Kurs

Wird eine Gruppe in einem Kurs von Nicht-Administratoren angelegt, wird der Kursadministrator zunächst als Nicht-Moderator angesehen. Soll der Kurs-Administrator Moderationsrechte in der Gruppe haben, kann dies auf zwei Wegen realisiert werden:

1. Kursadministrator wird als Gruppenadministrator der Gruppe hinzugefügt
2. Kursadministrator erhält das Bearbeitungsrecht am MultiVc-Objekt in der Gruppe

### E-Mail Benachrichtigung (edudip)

Sollen Kurstuten über Webinare in Gruppen informiert werden, müssen diese als Gruppenadministrator der jeweiligen Gruppe hinzugefügt werden. 






# Icon

Icon: https://pixabay.com/de/vectors/orange-stil-treffen-pfeile-punkt-41015/

## Anpassen

Das Icon ist hinterlegt im Plugin-Ordner: `templates/images/icon_xmvc.svg`

Der Inhalt der .svg-Datei kann nach eigenem Ermessen angepasst werden.
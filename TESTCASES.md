# MultiVc-Plugin Testing

**Inhaltsverzeichnis**

[TOC]
# Plugin Administration

Für diesen Abschnitt benötigen Sie einen ILIAS-Benutzer mit der globalen Rolle "Administrator". Dieser wird nachfolgend als "SYSADMIN" bezeichnet. Zusätzlich benötigen Sie drei weitere ILIAS-Benutzer jeweils mit der globalen Rolle "User". 

Navigieren Sie zunächst in der Administration zur Benutzerverwaltung und legen Sie drei neue Benutzer an, mit 
Benutzernamen: TC001 USER, TC001 TUTOR, TC001 ADMIN
Standardrolle: User

## Konfigurationen anlegen und ändern

### BigBlueButton

Für diesen Abschnitt benötigen Sie die Zugangsdaten zu einem BigBlueButton-Server ab Version 2.3.

#### Vorbereitung

- [ ] Navigieren Sie in der Administration zu den Plugins und konfigurieren Sie das MultiVc-Plugin durch einen entpsrechenden Klick im zugehörigen Dropdown-Menü.
- [ ] Klicken Sie auf den Button "Neuen Meeting-Typ definieren".
- [ ] Wählen Sie als WebRTC Plattform aus der Dropdown-Liste "BigBlueButton" und bestätigen Sie Ihre Eingabe mit Klick auf den Button "Konfiguration anlegen".
- [ ] Geben Sie in der Konfiguration folgende Werte an:
  - [ ] Titel: TC001 BBB CONN1
  - [ ] Verfügbarkeit: neue erzeugbar
  - [ ] Zugewiesene Rollen: Administrator
  - [ ] Max. Teilnehmer: 2
  - [ ] Maximale Dauer:
    Stunden: 0
    Minuten: 2
  - [ ] moderierter Raum:
    Auswahlmöglichkeit: ja
    Voreinstellung: nein
  - [ ] Gastlink:
    Auswahlmöglichkeit: ja
    Voreinstellung: nein
- [ ] Vervollständigen Sie die übrigen, mit * gekennzeichneten, Pflichtfelder nach eigenem Ermessen.
- [ ] Bestätigen Sie Ihre Eingaben mit einem Klick auf den Button "Erstellen"

#### Test-Case 1.1

- [ ] Navigieren Sie im Magazin zur Einstiegsseite

- [ ] Fügen Sie einen Kurs hinzu, mit dem Titel "TC001 CRS" und bestätigen Sie Ihre Eingaben mit Klick auf "Kurs anlegen"

- [ ] In den Kurs-Einstellungen: setzen Sie einen Haken bei "Online" und bestätigen Sie mit "Speichern"

- [ ] Rufen Sie den Tab "Mitglieder" auf und fügen Sie dem Kurs folgende Benutzer hinzu:
  TC001 ADMIN als Kursadministrator
  TC001 TUTOR als Kurstutor
  TC001 USER als Kursmitglied

  

- [ ] Melden Sie sich in einer weiteren Browser-Session (z. B. alternative Browseranwendung) mit dem Benutzer "TC001 ADMIN" bei ILIAS an

- [ ] Rufen Sie den Tab "Inhalt" auf

- [ ] Fügen Sie ein neues Objekt hinzu vom Typ "Virtueller Meetingraum" mit den Werten:
  Titel: TC001 BBB ROOM1
  Meeting-Typ: TC001 BBB CONN1
  
- [ ] Bestätigen Sie Ihre Eingaben mit Klick auf "virtuellen Meetingraum hinzufügen"

##### Ergebnis 1.1

- [ ] Der Meeting-Typ "TC001 BBB CONN1" steht nicht zur Verfügung
- [ ] Es steht kein Meeting-Typ zur Verfügung
- [ ] Objekt kann nicht angelegt werden

#### Test-Case 1.2

- [ ] Als SYSADMIN: öffnen Sie die MultiVc-Konfiguration für den Meeting-Typ "TC001 BBB CONN1"

- [ ] Selektieren Sie im Feld "Zugewiesene Rollen" den Eintrag mit der Rolle für Kursadministratoren des Kurses "TC001 CRS"
  (schauen Sie nach einem Eintrag in der Form `il_crs_admin_[ref_id]`, wobei sich [ref_id] auf die RefId des Objekts bezieht, also auf den Kurs "TC001 CRS")
  
- [ ] Speichern Sie Ihre Eingaben und wechseln Sie zur Session des Users "TC001 ADMIN"

  

- [ ] Als TC001 ADMIN: Aktualisieren Sie Formularseite oder rufen Sie den Tab "Inhalt" auf und  fügen ein neues Objekt hinzu vom Typ "Virtueller Meetingraum"
- [ ] Geben Sie folgende Werte an:
  Titel: TC001 BBB ROOM1
  Meeting-Typ: TC001 BBB CONN1
- [ ] Bestätigen Sie Ihre Eingaben mit Klick auf "virtuellen Meetingraum hinzufügen"

##### Ergebnis 1.2
- [ ] Ein virtueller Meetingraum wurde erfolgreich angelegt und der Tab "Eigenschaften" ist aktiv
- [ ] Der angelegte virtuelle Meetingraum hat die Werte:
  Titel: TC001 BBB ROOM1
  Meeting-Typ "TC001 BBB CONN1" steht nicht zur Verfügung
  Moderiert: nein
  Gastlink: nein

#### Test-Case 1.3
- [ ] Setzen Sie einen Haken bei "Online" und bestätigen Sie mit "Speichern"
- [ ] Wechseln Sie zum Tab "Meeting"

##### Ergebnis 1.3
- [ ] Sie sehen den Button "Meeting beitreten"

#### Test-Case 1.4
- [ ] Melden Sie sich in einer eigenen Browser-Session als User "TC001 USER" bei ILIAS an. Dabei muss der User "TC001 ADMIN" angemeldet bleiben.
- [ ] Navigieren Sie zum Kurs "TC001 CRS" und dort zum virtuellen Meetingraum "TC001 BBB ROOM1"

##### Ergebnis 1.4
- [ ] Der Tab "Meeting" ist aktiv 
- [ ] Sie sehen den Button "Meeting beitreten"

#### Test-Case 1.5
- [ ] Als User "TC001 USER": Klicken Sie auf den Button "Meeting beitreten"
- [ ] Als User "TC001 ADMIN": Klicken Sie auf den Button "Meeting beitreten"

##### Ergebnis 1.5
- [ ] Es  öffnet sich jeweils im neuen Tab ein gemeinsames BigBlueButton-Meeting
- [ ] Sie sehen in den Tabs beider Browser-Sessions beide Teilnehmer als Moderatoren
- [ ] Sie sehen in beiden Browser-Fenstern oberhalb des Whiteboards einen laufenden Countdown, der bei 2 Minuten begonnen hat.
- [ ] Der Countdown endet bei 0 und eine Meldung erscheint, dass das Meeting in kürze beendet wird.
- [ ] Das Meeting wird automatisch beendet und Sie werden zur ILIAS-MultiVc-Seite weitergeleitet, mit dem Hinweis, dieses Fenster nun schließen zu können.
#### Test-Case 1.6

- [ ] Schließen Sie in beiden Browsern den jeweiligen Browser-Tab, mit dem Hinweis, dieses Fenster nun schließen zu können.
- [ ] Als SYSADMIN: 
  Öffnen Sie in der Plugin-Administration die Meeting-Typ-Konfiguration: TC001 BBB CONN1
- [ ] Konfigurieren Sie den Parameter "Maximale Dauer" wie folgt:
  Stunden: 0
  Minuten: 0
- [ ] Speichern Sie Ihre Konfiguration
- [ ] Als User "TC001 ADMIN":
  Wechseln Sie zum Tab "Eigenschaften"
- [ ] konfigurieren Sie folgende Parameter:
  moderierter Raum: ja
  Gastlink: ja
  Passwort für Gäste: geheim
  Ablaufdatum: *morgiges Datum*
- [ ] Speichern Sie Ihre Einstellungen.
- [ ] Wechseln Sie zum Tab "Meeting"
- [ ] Als User "TC001 USER": aktualisieren Sie den Tab "Meeting"

##### Ergebnis 1.6
- [ ] Als User "TC001 ADMIN": Sie sehen den Button "Meeting starten"

- [ ] Als User "TC001 USER": Sie sehen einen Hinweis, dass noch kein Moderator im Raum ist, ein Button ist nicht zu sehen.

#### Test-Case 1.7
- [ ] Als User "TC001 ADMIN": Klicken Sie auf den Button "Meeting starten"
- [ ] Als User "TC001 USER": Aktualisieren Sie die Seite. 

##### Ergebnis 1.7
- [ ] Als User "TC001 ADMIN": befinden Sie sich als Moderator im BigBlueButton-Meeting
- [ ] Als User "TC001 USER": sehen Sie den Button "Meeting beitreten"

#### Test-Case 1.8
- [ ] Als User "TC001 USER": klicken Sie den Button "Meeting beitreten"

##### Ergebnis 1.8
- [ ] Als User "TC001 USER": befinden Sie sich als Nicht-Moderator im BigBlueButton-Meeting.
- [ ] In beiden Browserfenstern sehen Sie beide Teilnehmer im BigBlueButton-Meeting.
  
#### Test-Case 1.9
- [ ] Als User "TC001 ADMIN": Wechseln Sie in den Browser-Tab mit der MultiVc-Seite "Meeting"
- [ ] kopieren Sie den Gastlink in die Zwischenablage
- [ ] Als User "TC001 USER": 
  Schließen Sie den Tab mit dem laufenden Meeting
  Rufen Sie in einem neuen Tab den Link aus der Zwischenablage auf
- [ ] Als User "TC001 ADMIN": kopieren Sie das Passwort in die Zwischenablage
- [ ] Als User "TC001 USER": bestätigen Sie die Nutzungsbedingungen und melden Sie sich mit dem Passwort aus der Zwischenablage als Gast an.
- [ ] Geben Sie einen Namen ein (z. B. Test Gast) und treten Sie dem Meeting als Gast bei.

##### Ergebnis 1.9
- [ ] Als User "TC001 USER": befinden Sie sich als Nicht-Moderator mit dem angegebenen Namen (z. B. Test Gast) im BigBlueButton-Meeting zusammen mit "TC001 ADMIN"

  



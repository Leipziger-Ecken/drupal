## Handbuch Leipziger Ecken 2.0
*Stand: v0.3, 12.12.2020*

Die folgenden Menüpunkte und Features stehen eingeloggten Administrator*innen zur Verfügung:

**1. Inhaltsverwaltung** (Pfad: */admin/content*)

Übersicht über alle in Drupal angelegten Inhalte:
* Nutzer-generierte Inhalte, d.h. **Akteure** und **Events**
* Administrative Inhalte, d.h. **Inhaltsseiten** ("Impressum" / "Über uns") und (Journal-)**Artikel**
* Drittanbieter Inhalte, d.h. FwAL- und depot.social-Angebote (kann außen vorgelassen werden)

Zum schnelleren Einstieg bietet die Inhaltsverwaltung ein **Filtermenü**, in welchem nach Schlagwort ("Title"), Inhaltstyp ("Content Type") und Veröffentlichungsstatus ("Published type") gefiltert werden kann.
Diese Informationen werden weiter unten, in der tabellarischen Darstellung der Inhalte, ebenso ausgegeben und können durch Klick auf die entsprechende Spalte auf- und absteigend sortiert werden (bspw. nach Erstell- oder Änderungsdatum "updated").

Im Falle vom Inhaltstyp Events wird der verknüpfte Akteur in der Spalte "Akteur" mit eingeblendet. Dies ist bei Events mit "privatem Veranstalter" nicht der Fall.

Durch klick auf "Bearbeiten" oder den Pfeil-nach-unten-Button, ganz rechts in jeder Zeile, können Inhalte ohne Umweg über das Inhaltsformular direkt editiert oder gelöscht werden. 

*Schnellaktionen ("bulk actions"):* Es können aus der Inhaltsverwaltung heraus auch mehrere Inhalte bearbeitet werden, ohne jeden einzeln zu öffnen (bspw. zum (Un-)Veröffentlichen oder Löschen mehrere Inhalte). Dazu am betroffenen Inhalt ganz links in der Tabelle ein Häckchen setzen und in der Auswahlliste "Aktion" (unter dem Filtermenü) eine Zielaktion auswählen. Dann klick auf "Auf ausgewählte Elemente anwenden" und ggf. Bestätigung.

**1.1. Inhalte anlegen** (*/node/add*)

Klick auf "+ Inhalt hinzufügen" von der Seite "Inhaltsverwaltung" aus. In der folgenden Liste wird zunächst der Ziel-Inhaltstyp ausgewählt, bevor zum entpsrechenden Formular weitergeleitet wird; für administrative Zwecke wird dies meist der Inhaltstyp "Artikel" oder "Einfache Seite" (d.h. Inhaltsseite) sein, da alle anderen Inhalte entweder von Nutzer*innen oder Drupal-Modulen angelegt werden.

Bitte beachten, dass Administrator*innen möglicherweise nur einige der Listenpunkte zu sehen bekommen, wenn sie bspw. der Rolle "Redakteur" zugeordnet sind.

Auf dem Formular zum Anlegen eines Inhalts befindet sich rechts immer ein "Akkordeon"-Menü zur Verwaltung sog. Meta-Daten, etwa der/dem asoziierten **Autorin** sowie **URL-Alias** und **Menüeinstellung**.
* **URL-Alias** beschreibt den in der Browserleiste angezeigten Pfad zu einem Inhalt. Dieser wird bei Akteuren, Events und Journal-Artikeln auto-generiert, muss also nicht weiter beachtet werden. Bei Inhaltsseiten ist es wichtig, dass hier ein logischer, sog. generischer Pfad, händisch eingetragen wird. Dies dient der besseren Lesbarkeit und Auffindbarkeit in Suchmaschinen. Lautet die Seite "Was wir so tun", wäre der Pfad logischerweise "/was-wir-so-tun", **ohne etwaige Umlaute**.
* **Menüeinstellungen**: Durch Anklicken der Checkbox "Menüpunkt erstellen", kann die neu anzulegende Seite im Haupt- oder Footermenü verlinkt werden. Dies sollte nur selten nötig sein.

Zuletzt kann ganz am Ende des Formulars die Checkbox **Veröffentlichungsstatus** ausgewählt weren, um den Inhalt tatsächlich auch öffentlich darzustellen. Dies wäre bspw. bei Journalartikeln in Vorbereitung oder Inhalten mit mehreren Autor*innen nicht gewünscht. Über den Schalter "Vorschau" kann zudem eine Vorschau auf den neu angelegten Inhalt dargestellt werden.

**2. Nutzerverwaltung** (*/admin/people*)

Übersicht über alle in Drupal angelegten Nutzer*innen; Darstellung analog **Inhaltsverwaltung** (d.h. filter- und sortierbare Nutzer, "Nutzer hinzufügen"-Button, Schnellaktionen, Button "Bearbeiten" in jeder Zeile).

* **Löschen von Nutzerinnen:** Hierzu die/den betroffenen Nutzer*in suchen, "Bearbeiten" und ganz am Ende des Nutzer-bearbeiten-Formulars den i.d.R. roten Button "Benutzerkonto löschen" anklicken. Hier kann in einem Zwischenschritt die Art der Löschung konfiguriert werden. Standardmäßig zu empfehlen sei (etwa bei Konten die offensichtlich ein Spamprofil darstellen) die Option *Das Benutzerkonto und dessen erstellten Inhalt löschen.*.

* **Ändern von Rollen:** Im Nutzer-bearbeiten-Formular kann eine*r Nutzer*in eine oder mehrere Rollen vergeben werden. Im Regelfall, bei bestätigten selbst-registrierten Nutzer*innen, ist dies alleine die Rolle "Angemeldeter Benutzer". Es kann hier aber auch die Rolle "Redakteur" und (Achtung, dies nicht achtlos tun) "Administrator" ausgewählt werden. Die/Der Nutzer*in hat im Anschluss Zugriff auf die Drupal-Verwaltungsoberfläche und kann bspw. Journal-Artikel anlegen.

* **Einsicht von verknüpften Akteuren/Events:** Nutzer*in auswählen und "Bearbeiten". Ganz oben sind die Reiter "Akteure" und "Events" ersichtlich. Bei klick ergiebt sich jeweils eine Listenansicht der verknüpften Inhalte.

**3. Taxonomieverwaltung** (*/admin/structure/taxonomy, Navigationspunkt: "Struktur" > "Taxonomie"*)

"Taxonomien" sind jegliche einem Akteur/Event/Artikel zuordbaren, i.d.R. aus nur einem Wort bestehenden, Begriffe. Hierunter fallen:
* Bezirke ("Stadtteile")
* ("Stadt-")Regionen
* Tags (**Achtung:** "Tags" für Artikel-Tags, "Kategorie: Tags" für Akteur-/Event-Tags!)
* Kategorien (Name in Liste: "Kategorie: Angebotstyp")
* Zielgruppe (Name in Liste: "Kategorie: Zielgruppe")
* Akteurstyp

Die an jeder Taxonomie zugeordneten Begriffe können durch Anklicken des grauen, an jeder Zeile ganz rechts befindlichen "Begriffe auflisten" Buttons eingesehen, editiert und natürlich erweitert werden. Hier gemachte Änderungen stehen entsprechend sofort in den jeweiligen verknüpften Formularen zur Verfügung. Das Feld "URL-Alias" sollte in jedem Fall **leer** gelassen werden, da sonst unerwünschte Effekte auf der Seite auftreten können.

* **Bezirk hinzufügen/editieren**: Klick auf Zeile "Bezirk" > "Begriffe auflisten". Auswahl und klick auf "Bearbeiten" eines aufgelisteten Bezirks oder klick auf "+ Begriff hinzufügen". **Wichtig:** Im Bezirksformular muss jedem Bezirk eine "Region" zugeordnet werden. Bezirk "Althen-Kleinpösna (Ost)" kriegt entsprechend die Region "Osten" zugeteilt.
* **Region hinzufügen/editieren**: Das hinzufügen von Regionen sollte nicht nötig sein und i.d.R. durch eine*n Entwickler*in durchgeführt werden.
* **Tags hinzufügen/editieren**: Das hinzufügen von Tags sollte nicht nötig sein, da dies am Artikelformular geschieht (komma-separierte Eingabe von Tags, sowohl vorhandenen als auch neuen).
* **Kategorie: Tags hinzufügen/editieren**: Das hinzufügen von Kategorie: Tags sollte nicht nötig sein, da dies am jeweiligen Akteurs- oder Eventformular geschieht (auch hier komma-separariert).
* **Kategorie: Angebotstyp hinzufügen/editieren**: Klick auf Zeile "Kategorie: Angebotstyp" > "Begriffe auflisten". Auswahl und klick auf "Bearbeiten" einer jeweiligen Kategorie oder klick auf "+ Begriff hinzufügen". Getreu des vom QM-Ost entwickelten Kategorien-Models kann an Formularen **immer eine Haupt- und mehrere dazugehörige Unterkategorien** gewählt werden. Dazu ist eine Verschachtelung/Asoziation notwendig. Wird also ein Unterkategorien-Punkt angelegt, muss dieser seiner überliegende Kategorie im Reiter "Beziehungen" > in der Auswahlliste "Übergeordnete Begriffe" zugeordnet sein. Dies kann auch in der Kategorien-Begriffs-Übersichtsliste durch via "Drag & Drop" einrück- und sortierbare Begriffe vorgenommen werden.
* **Kategorie: Zielgruppe hinzufügen/editieren**: Das hinzufügen oder bearbeiten von Zielgruppen sollte nicht nötig sein, da initial bereits konfiguriert. Ansonsten analog wie oben beschrieben.
* **Akteurstyp hinzufügen/editieren**: Akteurstypen sind im Akteur-anlegen-Formular auswählbar und haben bisher (Stand: Dezember 2020) noch keine Bedeutung. Klick auf Zeile "Akteurstyp" > "Begriffe auflisten". Auswahl und klick auf "Bearbeiten" eines jeweiligen Akteurstyp oder klick auf "+ Begriff hinzufügen". Hier muss ein kleiner Beschreibungstext und Icon (idealerweise im .svg-Format) benannt werden, welche dann im Akteursformular dargestellt werden.

**Sonstiges**

* **Ändern des Theme-Logos:** Navigieren zu "Design" > "Leipziger Ecken (Standard-Theme): Einstellungen" > Reiter "Logo" (ganz unten), Checkbox "Das vom Theme bereitgestellte Logo verwenden" deselektieren und neues Logo (idealerweise im .png-Format) hochladen. Formular speichern.
* **Ändern der an Nutzerinnen versandten E-Mail-Texte:** Navigieren zu "Konfiguration" > "Kontoeinstellungen", Editieren des entsprechenden zu bearbeitbaren Mail-Textes in den Reitern unter "E-Mails" (ganz unten). Hierzu können optional Platzhalter verwendet werden. Formular Speichern.


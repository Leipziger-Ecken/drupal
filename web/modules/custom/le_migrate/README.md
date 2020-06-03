## Leipziger Ecken Migrate

Unofficial one-time script to import raw SQL Drupal 7 Akteur-data (incl. images) into native Drupal 8 Content-types with cool URI-slug.

Requires aee_data_*-tables to be present in the default database (can be removed afterwards).

### Howto (German)

*[] = Kann übersprungen werden wenn es sich um eine frisch installierte Drupal-Instanz handeln sollte (empfohlen; s. README im Projekt-root).*

[1.] Stelle im Drupal-Backend (/admin/people) sicher, dass alle evtl. bisher angelegten Nutzer (Ausnahme: Administrator, erkennbar an der ID "1") und alle Inhalte (/admin/content) gelöscht sind.

[2.] Stelle sicher, dass Du den aktuellsten Stand des Repositories auf Deinem Rechner hast (Befehl: "git pull"). Vermutlich musst Du die neu dazugekommenen Abhängigkeiten über Composer nach-installieren (Befehl: "composer install").

3. Importiere den heruntergeladenen SQL-dump in Deine Drupal-Datenbank, z.B. über "phpMyAdmin". Dass der Import erfolgreich war, erkennst Du an den neu dazugekommenen Tabellen (aae_data_*).

4. Du solltest jetzt in der Modul-Verwaltung des Drupal-Backends (/admin/modules) unter "Leipziger Ecken" folgende Module sehen können: *Akteure, Bezirk, Event, Rollen, Leipziger Ecken API, Leipziger Ecken Migrate*. Setze bei allen diesen Modulen ein Häckchen **bis auf *Leipziger Ecken Migrate*** und klicke unten auf den Button "Installieren"!

4.1. Wenn Du es bis hierhin ohne Fehlermeldungen geschafft hast, solltest Du jetzt theoretisch unter "Inhalt hinzufügen" (/node/add) "Akteure" und "Events" hinzufügen und als Link auf der Hauptseite sehen können - hurra (warte aber mit dem manuellen Hinzufügen noch bis Punkt 5 beendet wurde)! 

4.2. Es kann aber auch sein, dass sich Drupal bei der Installation der LE-Module beschwert, da diese einige Abhängigkeiten zueinander und zu anderen, ggf. noch nicht installierten Modulen haben. In diesem Fall versuche bitte, jedes der "Leipziger Ecken"-Module (...bis auf "LE Migrate") einzeln anzuhaken und zu installieren, ggf. geht dies nur über eine gewisse Reihenfolge (erst Rollen installieren, dann Bezirke, dann Akteure und zuletzt Events). Sollte etwas gar nicht klappen, bitte Mail mit Screenshot der Fehlermeldung (wenn vorhanden) oder der letzten protokollierten Fehler (/admin/reports/dblog) an Felix.

5. Die eigentliche Migration kann nun beginnen: Setze ein Häckchen bei "Leipziger Ecken Migrate" und klicke auf "Installieren" - dieser Prozess kann einige Minuten dauern, da u.a. Fotomaterial auf den lokalen Rechner kopiert und Geodaten errechnet werden. Dass alles geklappt hat, siehst Du an der Erfolgsmeldung (z.B. "101 Akteure erfolgreich importiert.") und an den neuen Inhalten (/admin/content). Sollte etwas gar nicht klappen, melde Dich bitte wie unter 4.2. angegeben bei Felix.

Viel Glück!

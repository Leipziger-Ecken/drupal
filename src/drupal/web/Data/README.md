# Erzeugung von RDF-Daten aus der Datenbank "Leipziger Ecken"

## Installation

* Kopie eines DB-Dumps von leipziger-ecken.de in eine Datenbank laden, 
* die Datei *inc_sample.php* nach *inc.php* kopieren und dort die
  DB-Credentials dieser Datenbank eintragen,
* ggf. einen PHP-fähigen Webserver auf localhost: starten 
* und die Seite *index.php* aufrufen. 

Anmerkung: In *inc.php* ist die Funktion db_query($query) definiert.  Eine
Funktion gleichen Namens greift in Drupal auf die Datenbank zu, so dass es
einfach sein sollte, diese Installation in ein Drupal-Modul zu verwandeln.

## Anmerkungen

HGG 2017-11-12: "composer" ist aktuell nicht mehr erforderlich, da die
Anhängigkeiten von EasyRDF eliminiert wurden. Damit wird allerdings bei der
Ausgabe die jeweilige Information auch nicht mehr mittels EasyRDF im
Turtle-Format normalisiert. Das müsste dann auf dem Weg der Weiterverarbeitung
geschehen.

## Grundsätzliche Struktur des Verzeichnisses

In diesem Verzeichnis sind verschiedene php Transformationsroutinen
zusammengestellt, die direkt auf die Datenbank zugreifen und die Instanzen der
Klassen *le:Akteur*, *org:Membership*, *le:Ort*, *le:Adresse*, *le:Event* und
*le:Sparte* in verschiedenen RDF-Graphen erzeugen.

Die entsprechenden Transformationen werden von den Scripts `adressen.php`,
`akteure.php`, `events.php` und `sparten.php` ausgeführt, die durch die
gemeinsame Datei `helper.php` unterstützt werden, in der vor allem
verschiedenen Routinen zum Adjustieren von Strings sowie zum Erstellen von
Einträgen in einer Turtle-Datei zusammengefasst sind, die immer wieder benötigt
werden.

Generelles Vorgehen: über Select-Anfragen an die Datenbank werden die
relevanten Information ausgelesen und dann datensatzweise über eine oder
mehrere Methoden in das RDF-Zielformat transformiert.  Fremdschlüssel werden
dabei in URIs der entsprechenden Klassen verwandelt und so dieselbe Verbindung
über RDF-Mittel hergestellt.

Die Dateien `main.php` und `index.php` können verwendet werden, um die
Transformationen auszuführen, wobei `index.php` das Ergebnis auf einer Webseite
anzeigt, `main.php` dagegen die Transformationen als Turtle-Dateien in das
Unterverzeichnis `Daten` schreibt (Aufruf `php main.php` von der Kommandozeile
aus).  `getdata.php` stellt diese Funktionalität als einfachen Webservice zur
Verfügung der etwa als `getdata.php?show=akteure` aufgerufen werden kann.

## Namensschemata für lokale URIs

Lokale URIs werden direkt aus den Primärschlüsseln (der Id) des entsprechenden
Datensatzes erzeugt. Diese haben grundsätzlich die Struktur
`<Präfix>/<Typ>/X<Id>`, wobei X aus technischen Gründen ein Buchstabe ist.

Dabei werden die Präfixe

-  le: <http://leipziger-ecken.de/Data/Model#>
-  ld: <http://leipzig-data.de/Data/Model/> 

für Datenstrukturen verwendet, die entweder spezifisch für `leipziger-ecken.de`
oder für `leipzig-data.de` sind, sowie weitere verbreitete Ontologien wie

-  dct: <http://purl.org/dc/terms/>
-  foaf: <http://xmlns.com/foaf/0.1/> 
-  gsp: <http://www.opengis.net/ont/geosparql#> 
-  ical: <http://www.w3.org/2002/12/cal/ical#>
-  org: <http://www.w3.org/ns/org#>

eingesetzt.

## Datenmodell und dessen Transformation

### Allgemeines

Verweise auf Personen in Feldern wie *creator* oder Tabellen wie
aae_data_akteur_hat_user werden z.B. als
``` 
org:hasMember <http://leipziger-ecken.de/Data/Person/P13> .
``` 
dargestellt, können aber nicht weiter aufgelöst werden, da die Schlüssel in
eine User-Tabelle verweisen, die nicht mit im Daten-Dump enthalten ist.  Hier
wäre es sinnvoll, diese Angaben als foaf:Person zu extrahieren und damit die
exportierten RDF-Daten zu ergänzen.

### le:Adresse 

Besteht aus plz, strasse, nr, adresszusatz, bezirk, gps

Entspricht damit nicht ganz dem Konzept *ld:Adresse*; dort charakterisiert der
Adresszusatz die Lage eines *ld:Ort* innerhalb des Gebäudekomplexes, der unter
der gegebenen Adresse zu finden ist. gps=(long,lat) ist ein Aggregat, das
einfach in ein *gsp:asWKT* transformiert werden kann, allerdings steht in der
Spalte auch noch viel Schrott (Stand 02/2016), der aber durch ein Matching nach
"," ausgefiltert werden kann.

**Transformation:** *bezirk* und *adresszusatz* werden nicht extrahiert, der
Rest gegen *ld:Adresse* normalisiert. Es werden überhaupt nur Adressen
extrahiert, die bei Akteuren oder Events verwendet werden. Dazu wird aus den
Einzelteilen eine *le:proposedAddress* generiert, die in LD-Anbindung.ttl als
*ld:hasAddress* übernommen und kuratiert wird.

`gsp:asWKT "Point(long lat)"` wird gegenüber der getrennten Verwendung von
`lat` und `long` der Vorzug gegeben, um Geokoordinaten als Datenaggregat zu
behandeln und damit Geodaten aus verschiedenen Quellen für dieselbe Adresse
vergleichen zu können.

### le:Akteur 

Mischung aus *ld:Ort* und *org:Organization* (als Oberklasse verschiedener
Arten juristischer Personen in LD), auch noch zwei Felder *ansprechpartner* und
*funktion*, die zusammen ein *org:Membership* beschreiben.

**Transformation:** Aus jedem Eintrag werden Einträge *le:Akteur* (juristische
Person), *le:Ort* (entspricht *ld:Ort*) und *org:Membership* extrahiert.
Zuordnungen erfolgen nach diesem Muster:

* le:Ort ld:hasSupplier le:Akteur 
* org:Membership org:organization le:Akteur

*le:Akteur* als juristische Person und Träger einer Einrichtung ist in
LeipzigData als Unterklassen von *org:Organization* modelliert und inzwischen
auf mehrere RDF-Graphen aufgeteilt:

* Buergervereine.ttl 
* Hochschulen.ttl
* KirchlicheEinrichtungen.ttl
* OeffentlicheEinrichtungen.ttl
* Stadtverwaltung.ttl
* Unternehmen.ttl
* Vereine.ttl

### le:Event

Die Modellierung folgt der von *ld:Event*. In der neuen LE-Version sind für
Events nur noch Start- und Endzeit gegeben, die komplexeren Möglichkeiten von
regelmäßig stattfindenden Events wird aktuell -- wie in ld:Event -- nicht
unterstützt.  Filtere die Events mit ersteller=0 raus. 

Unterschiede zu ld:Event:

* *ical:location* verweist nicht auf einen *ld:Ort*, sondern auf eine
  *le:Adresse*.
* *ical:creator* verweist wieder auf eine Person in der nicht zugänglichen
  Personentabelle.
* Über *le:hatAkteur* ist einem Event teilweise ein Akteur zugeordnet.
* Über *le:zurSparte* sind einem Event Schlagworte zugeordnet.

### le:Sparte 

Nicht konsolidierte Menge von 95 (Stand 02/2016) Schlüsselwörtern, die Akteuren
oder Event zugeordnet werden können.  In *Sparten.ttl* sind die URIs aus der
Tabelle aae_data_sparte erzeugt. 

Die Einträge in den Tabellen aae_data_akteur_hat_sparte und
aae_data_event_hat_sparte sind dem jeweiligen *le:Ort* bzw. *le:Event*
zugeordnet.

Die aktuelle Liste der Sparten ist sehr redundant, das müsste aufgeräumt
werden.

### le:Bezirk 

71 Einträge, entsprechen *ld:Ortsteil*, in Klammern dazu jeweils
*ld:Stadtbezirk*, die über *ld:Adresse* rekonstruiert werden können.

## Alignment mit Leipzig Data

### Adressen

Über eine CONSTRUCT-Query einem lokalen RDF-Store wird über
*le:proposedAddress* ein RDF-Graph 
```
le:Adresse ld:hasAddress ld:Adresse
``` 
erzeugt, die ld:Adresse manuell korrigiert und das Ergebnis gegen LeipzigData
geprüft (lokales Transformationsskript *getLDAdressen.php*, hier nicht
enthalten).

In *Alignment.ttl* sind die geprüften Zuordnungen aufgeführt, in
*LD-Adressen.ttl* ein aus LeipzigData extrahierter Datenbestand mit Adressen
und GeoDaten zusammengestellt.

## Anmerkungen: Analyse des dumps ledump-20171022.sql

--------------------------------------------------------------
Finde die Adressen, die wirklich verwendet 

```
SELECT * FROM aae_data_adresse where exists (select * from
aae_data_akteur where adresse=ADID) or exists (select * from
aae_data_event where ort=ADID); 
```

Adressen: 467
Adressen ohne strasse: 53
Wirklich verwendete Adressen: 377

--------------------------------------------------------------
Finde die Adressen, die wirklich verwendet werden, aber keinen Straßennamen haben:

```
SELECT ADID,strasse,AID,name FROM aae_data_adresse, aae_data_akteur where
adresse=ADID and strasse='';
```
+------+---------+-----+------------------------------------------------------------+
| ADID | strasse | AID | name                                                       |
+------+---------+-----+------------------------------------------------------------+
|   62 |         |  37 | KollektivArtesMobiles                                      |
|   77 |         |  46 | Die Wunderfinder - Bildungspatenprojekt im Leipziger Osten |
|   79 |         |  48 | Bürgerverein Anger-Crottendorf                             |
|   87 |         |  53 | Kunstverein gegenwart e.V.                                 |
|  137 |         |  71 | Arbeitskreis Flüchtlingshilfe Region Alte Messe            |
|  207 |         |  92 | Lichtstrahl                                                |
|  373 |         | 105 | FREIRAUM FESTIVAL                                          |
|  384 |         | 106 | Ortsgruppe Ost                                             |
+------+---------+-----+------------------------------------------------------------+
8 rows in set (0,00 sec)

```
SELECT ADID,strasse,EID,name FROM aae_data_adresse, aae_data_event where
ort=ADID and strasse='';
```+------+---------+-----+------------------------------------------------+
| ADID | strasse | EID | name                                           |
+------+---------+-----+------------------------------------------------+
|   79 |         |  30 | Frühjahrsputz                                  |
|   48 |         |  94 | Lenes Nachbarn - die andere Seite von Reudnitz |
|   62 |         | 124 | Nachbarschafts-Sommerschule Eisenbahnstraße    |
|  197 |         | 406 | Flohmarktfest von Hof zu Hof                   |
|  137 |         | 461 | Speedgaming                                    |
|  137 |         | 467 | Recycling Basteln - Fahrradschlauch            |
|  137 |         | 468 | Recycling Basteln - Papierschöpfen             |
|  137 |         | 469 | Internationaler Kochabend                      |
|  137 |         | 470 | Film - Bekas                                   |
|  137 |         | 471 | Tandem Café - deutsche Konversation            |
|  137 |         | 477 | Speed Gaming - Learn German with Games         |
|  137 |         | 478 | Recycling Basteln                              |
|  207 |         | 486 | Lichtstrahl-Kinderfest                         |
|  207 |         | 487 | Lichtstrahl-Kinderfest                         |
|  298 |         | 724 | Frühjahrsputz                                  |
|  314 |         | 750 | Test                                           |
|  332 |         | 783 | Fahrradtour | Tag der Städtebauförderung       |
|  372 |         | 845 | FREIRAUM FESTIVAL 2017                         |
+------+---------+-----+------------------------------------------------+
18 rows in set (0,00 sec)

--------------------------------------------------------------

Finde Events, die wirklich verwendet werden 

SELECT EID,name,ersteller FROM aae_data_event where name is NULL and ersteller<>0;

SELECT * FROM aae_data_event where ersteller=0;


## Analyse der Geodaten -- Code im privaten Repo ld-workbench

Es wurden die verfügbaren GeoDaten der jeweiligen ld:Adresse zugeordnet und
verglichen (lokales Skript *checkDistance.php*).

Ergebnis dieser Geodatenanalyse:
```
http://leipzig-data.de/Data/04317.Leipzig.Gabelsbergerstrasse.30 hat 1 Geodaten-Einträge.

http://leipzig-data.de/Data/04315.Leipzig.Eisenbahnstrasse.147 hat 2 Geodaten-Einträge.
Dies sind Point(9.402638 48.719173), Point(12.4189593 51.3449446).
Der Abstand beträgt 363209.79928452 Meter.

http://leipzig-data.de/Data/04315.Leipzig.Eisenbahnstrasse.54 hat 1 Geodaten-Einträge.

http://leipzig-data.de/Data/04315.Leipzig.Hedwigstrasse.7 hat 2 Geodaten-Einträge.
Dies sind Point(12.375 51.342), Point(12.4031474 51.3467999).
Der Abstand beträgt 2082.2377660994 Meter.

http://leipzig-data.de/Data/04277.Leipzig.Kochstrasse.132 hat 1 Geodaten-Einträge.

http://leipzig-data.de/Data/04315.Leipzig.Kohlgartenstrasse.51 hat 2 Geodaten-Einträge.
Dies sind Point(12.3992969 51.3423112), Point(12.4021349 51.3398044).
Der Abstand beträgt 344.99293566063 Meter.

http://leipzig-data.de/Data/04317.Leipzig.DresdnerStrasse.84 hat 2 Geodaten-Einträge.
Dies sind Point(12.3988189 51.3391931), Point(12.4061516728362 51.3379169390566).
Der Abstand beträgt 543.1922753523 Meter.

http://leipzig-data.de/Data/04315.Leipzig.Eisenbahnstrasse.49 hat 2 Geodaten-Einträge.
Dies sind Point(12.375 51.342), Point(12.4023604 51.3458157).
Der Abstand beträgt 2001.8357154269 Meter.

http://leipzig-data.de/Data/04315.Leipzig.TorgauerPlatz.2 hat 1 Geodaten-Einträge.

http://leipzig-data.de/Data/04315.Leipzig.Eisenbahnstrasse.157 hat 2 Geodaten-Einträge.
Dies sind Point(12.399316 51.345989), Point(12.4207268 51.3448569).
Der Abstand beträgt 1536.0489512708 Meter.

http://leipzig-data.de/Data/04315.Leipzig.Hedwigstrasse.20 hat 2 Geodaten-Einträge.
Dies sind Point(12.403096 51.346596), Point(12.4032093 51.3464121).
Der Abstand beträgt 22.012888961027 Meter.

http://leipzig-data.de/Data/04315.Leipzig.Hildegardstrasse.49 hat 2 Geodaten-Einträge.
Dies sind Point(8.0490356 52.161542), Point(12.4072117 51.3445224).
Der Abstand beträgt 324606.80960965 Meter.

http://leipzig-data.de/Data/04315.Leipzig.Hildegardstrasse.51 hat 2 Geodaten-Einträge.
Dies sind Point(12.4070104 51.3431029), Point(12.4072117 51.3445224).
Der Abstand beträgt 158.64459556813 Meter.

http://leipzig-data.de/Data/04315.Leipzig.Konradstrasse.27 hat 1 Geodaten-Einträge.

http://leipzig-data.de/Data/04317.Leipzig.DresdnerStrasse.59 hat 2 Geodaten-Einträge.
Dies sind Point(12.402913 51.338952), Point(12.4029673 51.3389024).
Der Abstand beträgt 6.7490086256735 Meter.
```
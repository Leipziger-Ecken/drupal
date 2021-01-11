<?php

/* Copy inc_sample.php to inc.php and fill in your credentials */

include_once("inc.php");
include_once("helper.php");

function getEvents() { 
  $res = db_query("SELECT * FROM aae_data_event where ersteller<>0");
  $out='';
  foreach ($res as $row) {
    $out.=createEvent($row);
  }

  $res = db_query("SELECT * FROM aae_data_akteur_hat_event where EID>0");
  foreach ($res as $row) {
    $out.='<http://leipziger-ecken.de/Data/Event/E'. $row['EID'] .'> '
      .'le:hatAkteur '
      .'<http://leipziger-ecken.de/Data/Akteur/A'. $row['AID'] ."> . \n\n" ;
  }

  $res = db_query("SELECT * FROM aae_data_event_hat_sparte where hat_EID>0");
  foreach ($res as $row) {
    $out.='<http://leipziger-ecken.de/Data/Event/E'. $row['hat_EID'] .'> '
      .'le:zurSparte '
      .'<http://leipziger-ecken.de/Data/Sparte/S'. $row['hat_KID'] ."> . \n\n" ;
  }

  return TurtlePrefix().'
<http://leipziger-ecken.de/Data/Events/> a owl:Ontology ;
    rdfs:comment "Dump aus der Datenbank";
    rdfs:label "Leipziger Ecken - Events" .

'.$out;
}

function createEvent($row) {
  $id=$row['EID'];
  $a=array();
  $a[]=' a ld:Event ';
  $a=addLiteral($a,'le:hasEID', $id);
  $a=addLiteral($a,'rdfs:label', $row['name']);
  $a=addMLiteral($a,'ical:summary', $row['kurzbeschreibung']);
  $a=addLiteral($a,'foaf:Image', fixImageString($row['bild']));
  $a=addResource($a,'ical:location', "http://leipziger-ecken.de/Data/Adresse/A",$row['ort']);
  $a=addResource($a,'ical:url', "", fixURL($row['url']));
  $a=addResource($a,'ical:creator', "http://leipziger-ecken.de/Data/Person/P",$row['ersteller']);
  $a=addLiteral($a,'ical:dtstart', str_replace(" ", "T", $row['start_ts']));
  $a=addLiteral($a,'ical:dtend', str_replace(" ", "T", $row['ende_ts']));
  $a=addLiteral($a,'dct:created', str_replace(" ", "T", $row['created']));
  $a=addLiteral($a,'dct:lastModified', str_replace(" ", "T", $row['modified']));
  return '<http://leipziger-ecken.de/Data/Event/E'. $id .'>'. join(" ;\n  ",$a) . " . \n\n" ;
}

// zum Testen
// echo getEvents();

?>

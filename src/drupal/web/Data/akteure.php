<?php

/* Copy inc_sample.php to inc.php and fill in your credentials */

include_once("inc.php");
include_once("helper.php");

function getAkteure() {
  $query='SELECT * FROM aae_data_akteur, aae_data_adresse where adresse=ADID';
  $res = db_query($query);
  $out='';
  foreach ($res as $row) {
    $out.=createAkteur($row);
    $out.=createOrt($row);
    if(!empty($row['ansprechpartner'])) { $out.=createMembership($row); }
  }

  $res = db_query("SELECT * FROM aae_data_akteur_hat_user");
  foreach ($res as $row) {
    $out.='<http://leipziger-ecken.de/Data/Akteur/A'. $row['hat_AID'] .'> '
      .'org:hasMember '
      .'<http://leipziger-ecken.de/Data/Person/P'. $row['hat_UID'] ."> . \n\n" ;
  }

  // Platzhalter für die Extraktion von foaf:Person Einträgen aus der User-Tabelle 
  $res = db_query("SELECT * FROM aae_data_akteur where ersteller>0");
  foreach ($res as $row) {
    $out.='<http://leipziger-ecken.de/Data/Person/P'. $row['ersteller'] .'> '
      .'a foaf:Person; foaf:name "to be added" ' ." . \n\n" ;
  }

  $res = db_query("SELECT * FROM aae_data_akteur_hat_sparte");
  foreach ($res as $row) {
    $out.='<http://leipziger-ecken.de/Data/Ort/O'. $row['hat_AID'] .'> '
      .'le:zurSparte '
      .'<http://leipziger-ecken.de/Data/Sparte/S'. $row['hat_KID'] ."> . \n\n" ;
  }
  
  return TurtlePrefix().'
<http://leipziger-ecken.de/Data/Akteure/> a owl:Ontology ;
    rdfs:comment "Dump aus der Datenbank";
    rdfs:label "Leipziger Ecken - Akteure" .

'.$out;

}

function createAkteur($row) {
  $id=$row['AID'];
  $a=array();
  $a[]=' a le:Akteur, org:Organization ';
  $a=addLiteral($a,'le:hasID', $row['AID']);
  $a=addLiteral($a,'foaf:name', $row['name']);
  $a=addLiteral($a,'foaf:mbox', $row['email']);
  $a=addLiteral($a,'foaf:phone', fixPhone($row['telefon']));
  $a=addResource($a,'foaf:homepage', "", fixURL($row['url']));
  $a=addResource($a,'le:hatAdresse', "http://leipziger-ecken.de/Data/Adresse/A",$row['adresse']);
  return '<http://leipziger-ecken.de/Data/Akteur/A'. $id .'>'. join(" ;\n  ",$a) . " . \n\n" ;
}

function createOrt($row) {
  $id=$row['AID'];
  $a=array();
  $a[]=' a le:Ort ';
  $a=addLiteral($a,'le:hasAID', $row['AID']);
  $a=addResource($a,'ld:hasSupplier', 'http://leipziger-ecken.de/Data/Akteur/A', $row['AID']);
  $a=addLiteral($a,'foaf:name', $row['name']);
  $a=addLiteral($a,'foaf:mbox', $row['email']);
  $a=addLiteral($a,'foaf:phone', fixPhone($row['telefon']));
  $a=addResource($a,'foaf:homepage', "", fixURL($row['url']));
  $a=addLiteral($a,'foaf:Image', str_replace("/sites/default/files/", "",$row['bild']));
  $a=addMLiteral($a,'foaf:description', $row['beschreibung']);
  $a=addMLiteral($a,'le:hatOeffungszeiten', $row['oeffnungszeiten']);
  $a=addResource($a,'le:hatAdresse', "http://leipziger-ecken.de/Data/Adresse/A",$row['adresse']);
  $a=addLiteral($a,'le:hatAdresszusatz', $row['adresszusatz']); // obsolet
  $a=addLiteral($a,'le:barrierefrei', $row['barrierefrei']); 
  $a=addResource($a,'dct:creator',"http://leipziger-ecken.de/Data/Person/P", $row['ersteller']);
  $a=addLiteral($a,'dct:created', str_replace(" ", "T", $row['created']));
  $a=addLiteral($a,'dct:lastModified', str_replace(" ", "T", $row['modified']));
  return '<http://leipziger-ecken.de/Data/Ort/O'. $id .'>'. join(" ;\n  ",$a) . " . \n\n" ;
}

function createMembership($row) {
  $id=$row['AID'];
  $a=array();
  $a[]=' a org:Membership ';
  $a=addLiteral($a,'org:member', $row['ansprechpartner']);
  $a=addResource($a,'org:organization', "http://leipziger-ecken.de/Data/Akteur/A",$id);
  $a=addLiteral($a,'org:role', $row['funktion']);
  return '<http://leipziger-ecken.de/Data/Membership/M'. $id .'>'. join(" ;\n  ",$a) . " . \n\n" ;
}


// zum Testen
// echo getAkteure();

?>

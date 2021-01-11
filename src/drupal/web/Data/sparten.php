<?php

/* Copy inc_sample.php to inc.php and fill in your credentials */

include_once("inc.php");
include_once("helper.php");

function getSparten() {
  $res = db_query("SELECT * FROM aae_data_sparte");
  $out='';
  foreach ($res as $row) {
    $out.=createSparte($row);
  }

  return TurtlePrefix().'
<http://leipziger-ecken.de/Data/Sparten/> a owl:Ontology ;
    rdfs:comment "Dump aus der Datenbank";
    rdfs:label "Leipziger Ecken - Sparten" .

'.$out;
}

function createSparte($row) {
  $id=$row['KID'];
  $a=array();
  $a[]=' a le:Sparte ';
  $a=addLiteral($a,'le:hasKID', $id);
  $a=addLiteral($a,'rdfs:label', $row['kategorie']);
  return '<http://leipziger-ecken.de/Data/Sparte/S'. $id .'>'. join(" ;\n  ",$a) . " . \n\n" ;
}

// zum Testen
// echo getSparten();

?>

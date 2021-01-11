<?php

/* Copy inc_sample.php to inc.php and fill in your credentials */

include_once("inc.php");
include_once("helper.php");

function getAdressen() {
  $query='
SELECT * FROM aae_data_adresse where 
exists (select * from aae_data_akteur where adresse=ADID) or
exists (select * from aae_data_event where ort=ADID) 
';
  $res = db_query($query);
  $out='';
  foreach ($res as $row) {
    $out.=createAdresse($row);
  }
  return TurtlePrefix().'
<http://leipziger-ecken.de/Data/Adressen/> a owl:Ontology ;
    rdfs:comment "Dump aus der Datenbank";
    rdfs:label "Leipziger Ecken - Adressen" .

'.$out;
}

function createAdresse($row) {
  $id=$row['ADID'];
  $strasse=$row['strasse'];
  if (empty($strasse)) { return ; }
  $nr=$row['nr'];
  $plz=$row['plz'];
  $gps_lat=$row['gps_lat']; 
  $gps_long=$row['gps_long'];
  // if (!empty($gps) and strstr($gps,",")) { $gps=geo($gps); } else {$gps='';}
  $leipzigDataURI=fixURI($plz.'.Leipzig.'.$strasse.'.'.$nr);
  $a=array();
  $a[]=' a le:Adresse ';
  $a=addResource($a,'le:proposedAddress', "http://leipzig-data.de/Data/", $leipzigDataURI);
  $a=addLiteral($a,'rdfs:label', "$strasse $nr, $plz Leipzig");
  if (!empty($gps_lat)) { 
      $a=addLiteral($a,'gsp:asWKT', "Point($gps_long $gps_lat)"); 
  }
  return '<http://leipziger-ecken.de/Data/Adresse/A'. $id .'>'. join(" ;\n  ",$a) . " . \n\n" ;
}

function geo($s) {
  $a=preg_split('/\s*,\s*/',$s);
  return "Point($a[1] $a[0])";
}

// zum Testen
// echo getAdressen();
// echo OldtoRDFString(getAdressen()); // works only with easyrdf 0.9.1 

?>

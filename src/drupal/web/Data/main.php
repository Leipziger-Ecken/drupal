<?php
/**
 * User: Hans-Gert Gräbe
 * Date: 2016-04-20
 */

include_once("helper.php");
include_once("akteure.php");
include_once("adressen.php");
include_once("events.php");
include_once("sparten.php");

main();

function main() {
  file_put_contents ("../Daten/Akteure.ttl",toRDFString(getAkteure())); 
  echo "<p>Ausgabe ../Daten/Akteure.ttl erzeugt</p> \n";
  file_put_contents ("../Daten/Adressen.ttl",toRDFString(getAdressen())); 
  echo "<p>Ausgabe ../Daten/Adressen.ttl erzeugt</p> \n";
  file_put_contents ("../Daten/Events.ttl",toRDFString(getEvents()));  
  echo "<p>Ausgabe ../Daten/Events.ttl erzeugt</p> \n";
  file_put_contents ("../Daten/Sparten.ttl",toRDFString(getSparten())); 
  echo "<p>Ausgabe ../Daten/Sparten.ttl erzeugt</p> \n";


}


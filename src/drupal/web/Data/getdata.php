<?php
/**
 * User: Hans-Gert Gräbe
 * Date: 2016-04-20
 */

include_once("akteure.php");
include_once("adressen.php");
include_once("events.php");
include_once("sparten.php");

function main() {
    $what=$_GET['show'];
    $out='';
    if ($what=='akteure') { $out=getAkteure(); }
    else if ($what=='adressen') { $out=getAdressen(); }
    else if ($what=='events') { $out=getEvents(); }
    else if ($what=='sparten') { $out=getSparten(); }
    else $out="Aufruf getdata.php?show=akteure";
    if (defined($_GET['embedded'])) { $out=htmlwrap($out); }
    return $out;
}

function htmlwrap($out) {
    return '
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="description" content="LD Leipziger Ecken Standalone Info Page"/>
    <meta name="author" content="Leipzig Data Project"/>
'. asPlainText($out). '
  </body>
</html>';
}    

echo main();

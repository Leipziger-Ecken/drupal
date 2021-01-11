<?php

  /* Kopiere diese Datei nach inc.php und adjustiere die Zugangsdaten zu den
     Datenbanken. */

function getConnection() {
    try {
        $dbh = new PDO('mysql:host=localhost;dbname=leipziger_ecken_db;charset=utf8', "leipziger_usr", "Pv6ya2!2$");
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    } 
    return $dbh;
}

function db_query($query) {
    $dbh = getConnection();
    return $dbh->query($query);
}

?>

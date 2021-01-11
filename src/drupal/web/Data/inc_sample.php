<?php

  /* Kopiere diese Datei nach inc.php und adjustiere die Zugangsdaten zu den
     Datenbanken. */

function getConnection() {
    try {
        $dbh = new PDO('mysql:host=localhost;dbname=??;charset=utf8', "dbuser", "dbpass");
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

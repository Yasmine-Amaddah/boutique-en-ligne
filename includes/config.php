<?php
session_start();
    $bdd = new PDO("mysql:host=localhost;dbname=boutique", 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>

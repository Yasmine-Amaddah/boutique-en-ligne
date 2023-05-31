<?php

require("../includes/config.php"); 


if (isset($_GET['id'])) {
$id=$_GET['id'];
$requete = $bdd->prepare('SELECT articles.*, souscategorie.*, categorie.* FROM articles 
INNER JOIN categart ON articles.idArt = categart.id_article
INNER JOIN souscategorie  ON souscategorie.id = categart.id_sousCat
INNER JOIN categorie ON categorie.idCat=souscategorie.id_parent WHERE articles.idArt=:id' );
$requete->execute(array('id' => $id));
$result = $requete->fetch(PDO::FETCH_ASSOC);
echo json_encode($result);
}

if(isset($_GET["index"])){
    if($_GET["index"]==1){
$requete = $bdd->prepare('SELECT articles.*, souscategorie.*, categorie.* FROM articles INNER JOIN categart ON articles.idArt = categart.id_article INNER JOIN souscategorie  ON souscategorie.id = categart.id_sousCat INNER JOIN categorie ON categorie.idCat=souscategorie.id_parent' );
$requete->execute();
$result = $requete->fetchAll(PDO::FETCH_ASSOC); // IMPORTANT fetchAll pour afficher plusieurs trucs, si fetch ça n'affiche que le premier resultat
echo json_encode($result);}
}
if(isset($_GET["panelAdmin"])){
    if($_GET["panelAdmin"]==1){
$requete = $bdd->prepare('SELECT * FROM categorie');
$requete->execute();
$result = $requete->fetchAll(PDO::FETCH_ASSOC); // IMPORTANT fetchAll pour afficher plusieurs trucs, si fetch ça n'affiche que le premier resultat
echo json_encode($result);}
}
?>

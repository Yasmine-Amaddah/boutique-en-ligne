<?php
//require_once('header.php');
require_once('../classes/User.php');
require_once('../classes/Adresse.php');
require_once('../includes/config.php');

require_once('../stripe/init.php');
require_once('../includes/keyStripe.php');

\Stripe\Stripe::setApiKey($stripeSecretKey);

ob_start();

$somme = 0;
$livraison = 4.99;
$prixTotal = 0;
$countArt = 0;
$request = $bdd->prepare('SELECT * FROM `panier` WHERE `id_user` = ?');
$request->execute([$_SESSION['user']['id']]);
$result = $request->fetchAll(PDO::FETCH_ASSOC);

$products = [];

if ($result) {
    foreach ($result as $productInCart) {
        $product_id = $productInCart['id_article'];

        // Récupérer les informations de l'article depuis la base de données
        $productRequest = $bdd->prepare('SELECT * FROM `articles` WHERE `idArt` = ?');
        $productRequest->execute([$product_id]);
        $product = $productRequest->fetch(PDO::FETCH_ASSOC);
        $somme += $product['prix'];

        $products[] = $product;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier</title>
    <link rel="stylesheet" type="text/css" href="../css/style.css">
    <link rel="stylesheet" type="text/css" href="../css/header.css">
    <link rel="stylesheet" type="text/css" href="../css/checkout.css">
    <link rel="stylesheet" type="text/css" href="../css/panier.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/e1a1b68f9b.js" crossorigin="anonymous"></script>
    <script src="../js/autocompletion.js" defer></script>
    <script src="../js/fonction.js" defer></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="../js/checkout.js" defer></script>
    <script>
        const products = <?= json_encode($products) ?>;
        const email = "<?= $_SESSION['user']['email'] ?>";
    </script>
</head>

<body>
    <?php require_once('../includes/header2.php'); ?>
    <main>
        <h1>Mon Panier</h1>
        <div id="panier">
            <?php
            if (count($products) > 0) {
                // Afficher les produits du panier
                foreach ($products as $product) {
                    echo "<a href='detail.php?article_id=" . $product['idArt'] . "'><img src='" . $product['imgArt'] . " '></a><span>" . $product['titreArt'] . " - Prix : " . $product['prix'] . "€</span></br>";
                }
                // $somme c'est le prix AVEC TVA comprise
                $tva = (20 / 100); // on met la TVA toujours a 20% ici
                $prixTva = $somme / (1 + $tva);
                $valeurLimiteeTva = number_format($prixTva, 2); // pour limiter le calcul a 2 chiffres apres la virgule
                $prixTotal = $somme + $livraison;
                echo "<span>Sous total (hors taxes) : " . $valeurLimiteeTva . " €</span></br>
                <span>TVA : + 20% </span></br>
                <span>Frais de livraison : 4,99 € </span></br>
                <span>Total : " . ($prixTotal) . " €</span>";
            } else {
                echo "<p>Panier vide</p>";
            }
            ?>
        </div>
        <div><span>Adresse de livraison :</span>
            <?php $adresse = new Adresse($_SESSION['user']['id'], '', '', '', '', '', '');
            if ($adresse->itExist($bdd)) {
                $adresseCommande = $adresse->isExisting($bdd);
                echo $adresseCommande;
            } else {
                echo $adresse->isExisting($bdd);
            }
            ?>
        </div>

        <div><span>Numero de téléphone :</span>
            <?php $user = new User($_SESSION['user']['id'], '', '', '', '', '', '');
            if ($user->isPhoneExist($bdd)) {
                $phoneCommande = $user->selectPhoneNumber($bdd);
                echo $phoneCommande;
            } else { ?>
                <form method="POST">
                    <input type="tel" id="phone" name="phone" pattern="[0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}" placeholder="01 23 45 67 89" required>
                    <input type="submit" name="submitPhone" value="Ajouter">
                    <?php if (isset($_POST['submitPhone'])) {
                        $user->addPhone($bdd);
                        echo "enregistré";
                        header('Location:panier.php');
                    }
                    ?>
                </form>
            <?php }

            if (count($products) > 0) {
            ?>
                <div><span>Choisissez un moyen de payement :</span></div>
                <div style="margin-top: 10px; max-width: 200px;">
                    <div id="link-authentication-element">
                        <!--Stripe.js injects the Link Authentication Element-->
                    </div>
                    <div id="payment-element">
                        <!--Stripe.js injects the Payment Element-->
                    </div>
                    <button id="submit">
                        <div class="spinner hidden" id="spinner"></div>
                        <span id="button-text">Acheter</span>
                    </button>
                    <div id="payment-message" class="hidden"></div>
                </div>

                <!-- <form method="POST"><input type="submit" name="validerPanier" value="Valider la commande"></form> -->
            <?php
                if (isset($_POST['validerPanier'])) {
                    $dateActuelle = date('Y-m-d');
                    $request3 = $bdd->prepare('INSERT INTO `commande`(`adresse`, `id_user`, `phone`, `date`, `prixTotal`) VALUES (?,?,?,?,?)');
                    $request3->execute([$adresseCommande, $_SESSION['user']['id'], $phoneCommande, $dateActuelle, $prixTotal]);
                    $idcommande = $bdd->lastInsertId();

                    if ($result) {
                        // parcourir les produits du panier
                        foreach ($result as $produit) {
                            $articleIDPanier = $produit['id_article'];
                            $request5 = $bdd->prepare('INSERT INTO `commandpanier`(`id_commande`, `id_article`) VALUES (?,?)');
                            $request5->execute([$idcommande, $articleIDPanier]);
                        }
                    }
                    $request6 = $bdd->prepare('DELETE FROM `panier` WHERE `id_user` = (?)');
                    $request6->execute([$_SESSION['user']['id']]);
                    header('Location:panier.php');
                }
            }
            ?>
    </main>
</body>

</html>
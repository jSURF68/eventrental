<?php
require_once '../../main.inc.php';
require_once 'class/eventrental_product.class.php';

if (!$user->admin) {
    die("Erreur : Vous devez être administrateur");
}

echo "<h1>🧪 Test Classe EventRentalProduct</h1>";

try {
    // Test 1 : Instanciation
    $product = new EventRentalProduct($db);
    echo "<p style='color:green'>✅ Classe instanciée avec succès</p>";
    
    // Test 2 : Création d'un produit de test
    $product->ref_product = 'TEST001';
    $product->label = 'BeamZ Pro Wash IGNITE 740 LED 7x40W';
    $product->description = 'Projecteur LED wash 7x40W RGBW avec zoom 6-60°';
    $product->category_event = 'eclairage';
    $product->sub_category = 'Projecteurs LED';
    $product->prix_location_jour = 25.00;
    $product->prix_location_weekend = 30.00;
    $product->caution_unitaire = 150.00;
    $product->qty_total = 6;
    $product->qty_disponible = 6;
    $product->poids = 4.2;
    $product->dimensions = '290 x 320 x 145 mm';
    $product->puissance_electrique = 300;
    $product->delai_preparation = 1;
    $product->nb_techniciens_requis = 0;
    $product->compatible_exterieur = 1;
    
    $result = $product->create($user);
    
    if ($result > 0) {
        echo "<p style='color:green'>✅ Produit créé avec ID: $result</p>";
        echo "<p><strong>Référence:</strong> " . $product->ref_product . "</p>";
        echo "<p><strong>Libellé:</strong> " . $product->label . "</p>";
        echo "<p><strong>Catégorie:</strong> " . $product->category_event . "</p>";
        echo "<p><strong>Prix/jour:</strong> " . price($product->prix_location_jour) . "€</p>";
        
        // Test 3 : Lecture du produit
        $product2 = new EventRentalProduct($db);
        $fetch_result = $product2->fetch($result);
        
        if ($fetch_result > 0) {
            echo "<p style='color:green'>✅ Produit lu avec succès</p>";
            echo "<p><strong>ID lu:</strong> " . $product2->id . "</p>";
            echo "<p><strong>Libellé lu:</strong> " . $product2->label . "</p>";
        } else {
            echo "<p style='color:red'>❌ Erreur lecture produit</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ Erreur création produit: ";
        if (!empty($product->errors)) {
            echo implode(', ', $product->errors);
        } else {
            echo $product->error;
        }
        echo "</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color:red'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🏠 Retour Module</a></p>";
?>
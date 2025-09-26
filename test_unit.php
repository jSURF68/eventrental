<?php
require_once '../../main.inc.php';
require_once 'class/eventrental_product.class.php';
require_once 'class/eventrental_unit.class.php';

if (!$user->admin) {
    die("Erreur : Vous devez être administrateur");
}

echo "<h1>🧪 Test Classe EventRentalUnit</h1>";

try {
    // Récupération du produit BeamZ existant
    $product = new EventRentalProduct($db);
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."eventrental_product WHERE ref_product = 'TEST001' LIMIT 1";
    $resql = $db->query($sql);
    
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        $product->fetch($obj->rowid);
        
        echo "<p style='color:green'>✅ Produit BeamZ trouvé (ID: ".$product->id.")</p>";
        
        // Création de 3 unités pour le produit BeamZ
        for ($i = 1; $i <= 3; $i++) {
            $unit = new EventRentalUnit($db);
            $unit->fk_product = $product->id;
            $unit->numero_serie = 'BZ740-2024-00' . $i;
            $unit->qr_code = 'QR-BZ00' . $i;
            $unit->numero_interne = 'INT-' . $i;
            $unit->etiquette_physique = 'TAG-BEAMZ-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $unit->statut = ($i == 2) ? 'maintenance' : 'disponible';
            $unit->etat_physique = 'excellent';
            $unit->emplacement_actuel = 'Rack A-' . $i;
            $unit->zone_stockage = 'Zone Éclairage';
            $unit->date_achat = date('Y-m-d', strtotime('-1 year'));
            $unit->prix_achat = 280.00;
            $unit->valeur_actuelle = 200.00;
            $unit->observations = 'Unité ' . $i . ' - Projecteur LED wash en excellent état';
            
            $result = $unit->create($user);
            
            if ($result > 0) {
                echo "<p style='color:green'>✅ Unité $i créée (ID: $result)</p>";
                echo "<p><strong>N° Série:</strong> " . $unit->numero_serie . "</p>";
                echo "<p><strong>QR Code:</strong> " . $unit->qr_code . "</p>";
                echo "<p><strong>Statut:</strong> " . $unit->statut . "</p>";
                echo "<hr>";
            } else {
                echo "<p style='color:red'>❌ Erreur création unité $i: ";
                if (!empty($unit->errors)) {
                    echo implode(', ', $unit->errors);
                } else {
                    echo $unit->error;
                }
                echo "</p>";
            }
        }
        
        // Test lecture des unités
        echo "<h2>📋 Test Lecture des Unités</h2>";
        $sql2 = "SELECT rowid, numero_serie, qr_code, statut FROM ".MAIN_DB_PREFIX."eventrental_unit WHERE fk_product = ".$product->id;
        $resql2 = $db->query($sql2);
        
        if ($resql2) {
            while ($obj2 = $db->fetch_object($resql2)) {
                $unit_test = new EventRentalUnit($db);
                $unit_test->fetch($obj2->rowid);
                
                echo "<p>🔧 <strong>Unité ID ".$obj2->rowid.":</strong></p>";
                echo "<p>N° Série: " . $unit_test->numero_serie . "</p>";
                echo "<p>QR Code: " . $unit_test->qr_code . "</p>";
                echo "<p>Statut: " . $unit_test->getLibStatut(1) . "</p>";
                echo "<hr>";
            }
        }
        
        // Test mise à jour compteurs produit
        echo "<h2>🔢 Test Mise à Jour Compteurs</h2>";
        $product->updateQuantityCounters();
        $product->fetch($product->id); // Recharger les données
        
        echo "<p><strong>Quantités mises à jour:</strong></p>";
        echo "<p>Total: " . $product->qty_total . "</p>";
        echo "<p>Disponible: " . $product->qty_disponible . "</p>";
        echo "<p>Maintenance: " . $product->qty_maintenance . "</p>";
        
    } else {
        echo "<p style='color:red'>❌ Produit BeamZ (TEST001) non trouvé. Créez-le d'abord.</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color:red'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🏠 Retour Module</a></p>";
?>

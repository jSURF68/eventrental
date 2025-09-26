<?php
require_once '../../main.inc.php';

if (!$user->admin) {
    die("Erreur : Admin requis");
}

echo "<h1>🔧 Correction Statuts Unités</h1>";

// Libération des unités en "reserve" sans assignation
$sql = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit 
        SET statut = 'disponible' 
        WHERE statut = 'reserve' 
        AND rowid NOT IN (
            SELECT DISTINCT fk_unit 
            FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment 
            WHERE statut IN ('assigne', 'sorti', 'en_cours')
        )";

$result = $db->query($sql);

if ($result) {
    $affected = $db->affected_rows($result);
    echo "<p style='color:green'>✅ $affected unités libérées (passage de 'reserve' à 'disponible')</p>";
} else {
    echo "<p style='color:red'>❌ Erreur: " . $db->lasterror() . "</p>";
}

// Création d'unités pour les produits qui n'en ont pas
echo "<h2>🔨 Création Unités Manquantes</h2>";

// Recherche produits sans unités
$sql_products = "SELECT p.rowid, p.ref_product, p.label, p.qty_total,
                 COALESCE(u.nb_units, 0) as nb_units_existantes
                 FROM ".MAIN_DB_PREFIX."eventrental_product p
                 LEFT JOIN (
                     SELECT fk_product, COUNT(*) as nb_units 
                     FROM ".MAIN_DB_PREFIX."eventrental_unit 
                     GROUP BY fk_product
                 ) u ON u.fk_product = p.rowid
                 WHERE p.statut = 1
                 AND COALESCE(u.nb_units, 0) < p.qty_total";

$resql_products = $db->query($sql_products);

if ($resql_products) {
    while ($obj = $db->fetch_object($resql_products)) {
        $units_to_create = $obj->qty_total - $obj->nb_units_existantes;
        
        echo "<p><strong>".$obj->ref_product.":</strong> ".$obj->nb_units_existantes." unités existantes, ".$obj->qty_total." prévues → Créer $units_to_create unités</p>";
        
        for ($i = 1; $i <= $units_to_create; $i++) {
            $unit_number = str_pad(($obj->nb_units_existantes + $i), 3, '0', STR_PAD_LEFT);
            $serial = substr($obj->ref_product, 0, 6) . '-2025-' . $unit_number;
            $qr_code = 'QR-' . strtoupper(substr($obj->ref_product, 0, 3)) . '-' . $unit_number;
            
            $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."eventrental_unit (";
            $sql_insert .= "entity, fk_product, numero_serie, qr_code, statut, etat_physique, date_creation, fk_user_author";
            $sql_insert .= ") VALUES (";
            $sql_insert .= "1, ";
            $sql_insert .= $obj->rowid.", ";
            $sql_insert .= "'".$db->escape($serial)."', ";
            $sql_insert .= "'".$db->escape($qr_code)."', ";
            $sql_insert .= "'disponible', ";
            $sql_insert .= "'excellent', ";
            $sql_insert .= "'".$db->idate(dol_now())."', ";
            $sql_insert .= $user->id;
            $sql_insert .= ")";
            
            $result_insert = $db->query($sql_insert);
            
            if ($result_insert) {
                echo "<span style='color:green;'>✅ Créé: $serial</span><br>";
            } else {
                echo "<span style='color:red;'>❌ Erreur création $serial: ".$db->lasterror()."</span><br>";
            }
        }
    }
    
    // Mise à jour des compteurs produits
    echo "<p><strong>🔄 Mise à jour des compteurs...</strong></p>";
    $sql_update_counters = "UPDATE ".MAIN_DB_PREFIX."eventrental_product p SET 
                           qty_total = (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit u WHERE u.fk_product = p.rowid),
                           qty_disponible = (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit u WHERE u.fk_product = p.rowid AND u.statut = 'disponible')";
    
    if ($db->query($sql_update_counters)) {
        echo "<p style='color:green'>✅ Compteurs mis à jour</p>";
    }
}

echo "<p><a href='test_assignments.php?event_id=1' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔍 Re-tester Debug</a></p>";
echo "<p><a href='event/generate_sheet.php?event_id=1' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔧 Tester Assignation</a></p>";
?>

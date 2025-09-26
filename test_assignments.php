<?php
require_once '../../main.inc.php';

if (!$user->admin) {
    die("Erreur : Admin requis");
}

echo "<h1>🔍 Debug Assignations</h1>";

$event_id = GETPOST('event_id', 'int') ?: 1;

echo "<p><strong>Event ID testé:</strong> $event_id</p>";

// Test 1 : Vérification des lignes événement
echo "<h2>📋 Lignes de l'Événement</h2>";
$sql_lines = "SELECT l.rowid, l.fk_product, l.qty, p.ref_product, p.label FROM ".MAIN_DB_PREFIX."eventrental_event_line l";
$sql_lines .= " LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = l.fk_product";
$sql_lines .= " WHERE l.fk_event = $event_id";

$resql_lines = $db->query($sql_lines);
if ($resql_lines) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID Ligne</th><th>Produit</th><th>Qté demandée</th></tr>";
    
    while ($obj = $db->fetch_object($resql_lines)) {
        echo "<tr>";
        echo "<td>".$obj->rowid."</td>";
        echo "<td>".$obj->ref_product." - ".$obj->label."</td>";
        echo "<td>".$obj->qty."</td>";
        echo "</tr>";
        
        // Test 2 : Unités disponibles pour ce produit
        echo "<tr><td colspan='3'>";
        echo "<strong>Unités disponibles:</strong><br>";
        
        $sql_units = "SELECT rowid, numero_serie, statut FROM ".MAIN_DB_PREFIX."eventrental_unit WHERE fk_product = ".$obj->fk_product;
        $resql_units = $db->query($sql_units);
        
        if ($resql_units) {
            while ($obj_unit = $db->fetch_object($resql_units)) {
                $color = ($obj_unit->statut == 'disponible') ? 'green' : 'orange';
                echo "<span style='color:$color;'>• ".$obj_unit->numero_serie." (".$obj_unit->statut.")</span><br>";
            }
        } else {
            echo "<span style='color:red;'>Aucune unité trouvée</span>";
        }
        echo "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ Aucune ligne trouvée pour cet événement</p>";
    echo "<p>SQL: " . $sql_lines . "</p>";
    echo "<p>Erreur: " . $db->lasterror() . "</p>";
}

// Test 3 : Assignations existantes
echo "<h2>🔗 Assignations Existantes</h2>";
$sql_assign = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment WHERE fk_event = $event_id";
$resql_assign = $db->query($sql_assign);

if ($resql_assign) {
    $obj_assign = $db->fetch_object($resql_assign);
    echo "<p><strong>Nombre d'assignations:</strong> " . $obj_assign->nb . "</p>";
} else {
    echo "<p style='color:red;'>❌ Erreur table assignations: " . $db->lasterror() . "</p>";
}

echo "<p><a href='event/generate_sheet.php?event_id=$event_id' style='background:#007bff;color:white;padding:10px;text-decoration:none;'>🔧 Tester Assignation</a></p>";
?>

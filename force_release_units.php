<?php
require_once '../../main.inc.php';

if (!$user->admin) {
    die("Erreur : Admin requis");
}

echo "<h1>🔓 Forcer Libération Unités BeamZ</h1>";

// Libération forcée des unités BeamZ spécifiquement
$sql_force = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit 
              SET statut = 'disponible' 
              WHERE numero_serie LIKE 'BZ740-2024-%'
              AND statut = 'reserve'";

$result = $db->query($sql_force);

if ($result) {
    $affected = $db->affected_rows($result);
    echo "<p style='color:green'>✅ $affected unités BeamZ libérées (forcé)</p>";
    
    // Vérification
    $sql_check = "SELECT numero_serie, statut FROM ".MAIN_DB_PREFIX."eventrental_unit WHERE numero_serie LIKE 'BZ740-2024-%'";
    $resql_check = $db->query($sql_check);
    
    echo "<h3>📋 État des Unités BeamZ</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Numéro Série</th><th>Statut</th></tr>";
    
    if ($resql_check) {
        while ($obj = $db->fetch_object($resql_check)) {
            $color = ($obj->statut == 'disponible') ? 'green' : 'red';
            echo "<tr><td>".$obj->numero_serie."</td><td style='color:$color;'><strong>".$obj->statut."</strong></td></tr>";
        }
    }
    echo "</table>";
    
} else {
    echo "<p style='color:red'>❌ Erreur: " . $db->lasterror() . "</p>";
}

// Mise à jour des compteurs produit
echo "<h3>🔄 Mise à Jour Compteurs</h3>";

$sql_update_beamz = "UPDATE ".MAIN_DB_PREFIX."eventrental_product 
                     SET qty_disponible = (
                         SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit 
                         WHERE fk_product = (
                             SELECT rowid FROM ".MAIN_DB_PREFIX."eventrental_product 
                             WHERE ref_product = 'TEST001'
                         ) AND statut = 'disponible'
                     )
                     WHERE ref_product = 'TEST001'";

if ($db->query($sql_update_beamz)) {
    echo "<p style='color:green'>✅ Compteurs BeamZ mis à jour</p>";
}

echo "<p><a href='event/generate_sheet.php?event_id=1&action=auto_assign' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔄 Re-tester Assignation</a></p>";
?>

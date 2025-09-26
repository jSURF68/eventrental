<?php
require_once '../../../main.inc.php';

if (!$user->admin) {
    die("Erreur : Vous devez être administrateur");
}

echo "<h1>🗃️ Installation Tables EvenRental</h1>";

$tables_sql = array(
    'llx_eventrental_product.sql',
    'llx_eventrental_unit.sql', 
    'llx_eventrental_event.sql',
    'llx_eventrental_event_line.sql',
    'llx_eventrental_unit_assignment.sql'
);

$errors = 0;

foreach ($tables_sql as $table_file) {
    echo "<h3>Installation: $table_file</h3>";
    
    $sql_content = file_get_contents($table_file);
    if ($sql_content === false) {
        echo "<p style='color:red'>❌ Impossible de lire $table_file</p>";
        $errors++;
        continue;
    }
    
    // Remplacer les préfixes
    $sql_content = str_replace('llx_', MAIN_DB_PREFIX, $sql_content);
    
    // Exécution
    $result = $db->query($sql_content);
    
    if ($result) {
        echo "<p style='color:green'>✅ Table installée avec succès</p>";
    } else {
        echo "<p style='color:red'>❌ Erreur: " . $db->error() . "</p>";
        $errors++;
    }
}

if ($errors == 0) {
    echo "<h2 style='color:green'>🎉 Installation terminée avec succès !</h2>";
    echo "<p><a href='../index.php' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🚀 Retour au Module</a></p>";
} else {
    echo "<h2 style='color:red'>⚠️ Installation terminée avec $errors erreurs</h2>";
}
?>
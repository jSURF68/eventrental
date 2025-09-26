<?php
require_once '../../../main.inc.php';
require_once '../class/eventrental_event.class.php';

$event_id = GETPOST('event_id', 'int') ?: 9; // Remplacez par votre ID d'événement

echo "<h1>🔍 Debug Génération Devis - Événement $event_id</h1>";

// Chargement événement
$event = new EventRental($db);
$event->fetch($event_id);

echo "<h2>📋 Informations Événement</h2>";
echo "<p><strong>ID:</strong> ".$event->id."</p>";
echo "<p><strong>Nom:</strong> ".$event->nom_evenement."</p>";
echo "<p><strong>Client ID:</strong> ".$event->socid."</p>";

// Vérification client
if ($event->socid > 0) {
    $event->fetch_thirdparty();
    echo "<p><strong>Client:</strong> ".$event->thirdparty->name."</p>";
} else {
    echo "<p style='color:red;'>❌ Aucun client associé</p>";
}

// ÉTAPE 1: Vérification des lignes de matériel
echo "<h2>📦 Vérification Lignes Matériel</h2>";

$sql_lines = "SELECT l.*, p.ref_product, p.label, p.price_day, p.category_event
              FROM ".MAIN_DB_PREFIX."eventrental_event_line l
              LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = l.fk_product
              WHERE l.fk_event = $event_id";

echo "<p><strong>Requête:</strong> <code>$sql_lines</code></p>";

$resql_lines = $db->query($sql_lines);

if ($resql_lines) {
    $nb_lines = $db->num_rows($resql_lines);
    echo "<p><strong>Nombre de lignes trouvées:</strong> $nb_lines</p>";
    
    if ($nb_lines > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID Ligne</th><th>Produit</th><th>Label</th><th>Qté</th><th>Prix/jour</th><th>Catégorie</th></tr>";
        
        while ($obj_line = $db->fetch_object($resql_lines)) {
            echo "<tr>";
            echo "<td>".$obj_line->rowid."</td>";
            echo "<td>".$obj_line->ref_product."</td>";
            echo "<td>".$obj_line->label."</td>";
            echo "<td>".$obj_line->qty."</td>";
            echo "<td>".($obj_line->price_day ?: 'Non défini')."</td>";
            echo "<td>".$obj_line->category_event."</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p style='color:green;'>✅ Matériel trouvé, le devis devrait inclure ces lignes</p>";
    } else {
        echo "<p style='color:red;'>❌ Aucune ligne de matériel trouvée pour cet événement</p>";
        echo "<p><strong>Solution:</strong> Ajoutez du matériel à l'événement d'abord</p>";
        echo "<p><a href='event_card.php?id=$event_id'>Aller à l'événement</a></p>";
    }
} else {
    echo "<p style='color:red;'>❌ Erreur requête: ".$db->lasterror()."</p>";
}

// ÉTAPE 2: Test de génération si matériel présent
if ($resql_lines && $db->num_rows($resql_lines) > 0) {
    echo "<h2>🧪 Test Génération Devis</h2>";
    
    if (GETPOST('test_generate', 'int')) {
        echo "<p>⏳ Génération en cours...</p>";
        
        // Nettoyage préalable
        $sql_clean = "UPDATE ".MAIN_DB_PREFIX."eventrental_event SET fk_propal = NULL WHERE rowid = $event_id";
        $db->query($sql_clean);
        
        $result = $event->generatePropal($GLOBALS['user']);
        
        if ($result > 0) {
            echo "<p style='color:green;'>✅ Devis généré avec succès (ID: $result)</p>";
            echo "<p><a href='".DOL_URL_ROOT."/comm/propal/card.php?id=$result' target='_blank'>Voir le devis</a></p>";
        } else {
            echo "<p style='color:red;'>❌ Erreur génération: ".$event->error."</p>";
        }
    } else {
        echo "<p><a href='debug_devis.php?event_id=$event_id&test_generate=1' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🧪 Tester Génération Devis</a></p>";
    }
}

// ÉTAPE 3: Vérification table eventrental_event_line
echo "<h2>🔍 Vérification Structure Table</h2>";
$sql_structure = "DESCRIBE ".MAIN_DB_PREFIX."eventrental_event_line";
$resql_structure = $db->query($sql_structure);

if ($resql_structure) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    while ($obj = $db->fetch_object($resql_structure)) {
        echo "<tr>";
        echo "<td>".$obj->Field."</td>";
        echo "<td>".$obj->Type."</td>";
        echo "<td>".$obj->Null."</td>";
        echo "<td>".$obj->Key."</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<p><a href='event_card.php?id=$event_id'>🔙 Retour Événement</a></p>";
?>

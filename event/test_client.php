<?php
require_once '../../../main.inc.php';
require_once '../class/eventrental_event.class.php';

echo "<h1>🔍 Test Client Événement</h1>";

// Test création
$event = new EventRental($db);
$event->socid = 1;  // ID d'un client existant
$event->nom_evenement = "Test Client";
$event->ref_event = "TEST-".date('Y-m-d-H-i');
$event->date_debut = dol_now();
$event->date_fin = dol_time_plus_duree(dol_now(), 1, 'd');

echo "<p><strong>Création événement de test...</strong></p>";
$result = $event->create($GLOBALS['user']);

if ($result > 0) {
    echo "<p style='color:green;'>✅ Événement créé avec succès (ID: $result)</p>";
    
    // Test récupération
    $event2 = new EventRental($db);
    $event2->fetch($result);
    
    echo "<p><strong>Client associé:</strong> ".$event2->socid."</p>";
    
    if ($event2->socid > 0) {
        $event2->fetch_thirdparty();
        echo "<p><strong>Nom client:</strong> ".$event2->thirdparty->name."</p>";
        echo "<p style='color:green;'>✅ Client correctement récupéré</p>";
    }
    
    // Test génération devis
    echo "<p><strong>Test génération devis...</strong></p>";
    $propal_id = $event2->generatePropal($GLOBALS['user']);
    
    if ($propal_id > 0) {
        echo "<p style='color:green;'>✅ Devis généré avec succès (ID: $propal_id)</p>";
        echo "<p><a href='".DOL_URL_ROOT."/comm/propal/card.php?id=$propal_id'>Voir le devis</a></p>";
    } else {
        echo "<p style='color:red;'>❌ Erreur génération devis: ".$event2->error."</p>";
    }
    
} else {
    echo "<p style='color:red;'>❌ Erreur création: ".$event->error."</p>";
}

echo "<p><a href='event_card.php?action=create'>🔙 Créer Événement</a></p>";
?>

<?php
require_once '../../../main.inc.php';
require_once '../class/eventrental_event.class.php';

$event_id = GETPOST('event_id', 'int') ?: 1;

echo "<h1>🔍 Debug Client Événement</h1>";

// Vérification de l'événement
$event = new EventRental($db);
$event->fetch($event_id);

echo "<h2>Événement: ".$event->nom_evenement."</h2>";
echo "<p><strong>ID Événement:</strong> ".$event->id."</p>";
echo "<p><strong>Référence:</strong> ".$event->ref_event."</p>";
echo "<p><strong>Client ID (socid):</strong> ".($event->socid ?: '<span style="color:red;">AUCUN</span>')."</p>";

if ($event->socid > 0) {
    $event->fetch_thirdparty();
    echo "<p><strong>Client:</strong> ".$event->thirdparty->name."</p>";
    echo "<p style='color:green;'>✅ Client correctement associé</p>";
} else {
    echo "<p style='color:red;'>❌ Aucun client associé à cet événement</p>";
    
    // Liste des clients disponibles
    echo "<h3>Clients Disponibles :</h3>";
    $sql_clients = "SELECT rowid, nom as name FROM ".MAIN_DB_PREFIX."societe WHERE client IN (1, 3) ORDER BY nom";
    $resql_clients = $db->query($sql_clients);
    
    if ($resql_clients) {
        echo "<form method='post'>";
        echo "<select name='client_id'>";
        echo "<option value=''>-- Choisir un client --</option>";
        
        while ($client = $db->fetch_object($resql_clients)) {
            echo "<option value='".$client->rowid."'>".$client->name."</option>";
        }
        echo "</select>";
        echo "<input type='hidden' name='event_id' value='$event_id'>";
        echo "<input type='hidden' name='action' value='assign_client'>";
        echo "<input type='submit' value='Associer ce Client' style='background:#28a745;color:white;padding:5px;'>";
        echo "</form>";
    }
}

// Action d'assignation
if (GETPOST('action') == 'assign_client') {
    $client_id = GETPOST('client_id', 'int');
    $event_id = GETPOST('event_id', 'int');
    
    if ($client_id > 0 && $event_id > 0) {
        $sql_update = "UPDATE ".MAIN_DB_PREFIX."eventrental_event SET socid = $client_id WHERE rowid = $event_id";
        
        if ($db->query($sql_update)) {
            echo "<p style='color:green;'>✅ Client associé avec succès !</p>";
            echo "<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
        } else {
            echo "<p style='color:red;'>❌ Erreur: ".$db->lasterror()."</p>";
        }
    }
}

echo "<p><a href='event_card.php?id=$event_id'>🔙 Retour Événement</a></p>";
echo "<p><a href='propal.php?id=$event_id'>💰 Tenter Génération Devis</a></p>";
?>

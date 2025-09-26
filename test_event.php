<?php
require_once '../../main.inc.php';
require_once 'class/eventrental_event.class.php';

if (!$user->admin) {
    die("Erreur : Vous devez être administrateur");
}

echo "<h1>🧪 Test Classe EventRental</h1>";

try {
    // Test 1 : Instanciation
    $event = new EventRental($db);
    echo "<p style='color:green'>✅ Classe EventRental instanciée</p>";
    
    // Test 2 : Récupération d'un client existant
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client IN (1,3) LIMIT 1";
    $resql = $db->query($sql);
    
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        $client_id = $obj->rowid;
        
        echo "<p style='color:green'>✅ Client trouvé (ID: $client_id)</p>";
        
        // Test 3 : Création événement
        $event->nom_evenement = 'Mariage Sarah & Tom';
        $event->type_evenement = 'Mariage';
        $event->description = 'Célébration de mariage avec 150 invités au Château de Versailles';
        $event->fk_soc = $client_id;
        $event->fk_user_commercial = $user->id;
        $event->date_debut = strtotime('+15 days 18:00');
        $event->date_fin = strtotime('+16 days 02:00');
        $event->lieu_evenement = 'Château de Versailles';
        $event->adresse_evenement = 'Place d\'Armes, 78000 Versailles';
        $event->nb_invites = 150;
        $event->phase_actuelle = 'en_attente';
        $event->note_public = 'Événement de prestige avec décoration florale';
        
        echo "<p>🎊 Tentative création événement...</p>";
        echo "<p>Nom: " . $event->nom_evenement . "</p>";
        echo "<p>Type: " . $event->type_evenement . "</p>";
        echo "<p>Date début: " . date('d/m/Y H:i', $event->date_debut) . "</p>";
        echo "<p>Date fin: " . date('d/m/Y H:i', $event->date_fin) . "</p>";
        
        $result = $event->create($user);
        
        if ($result > 0) {
            echo "<p style='color:green'>✅ Événement créé avec ID: $result</p>";
            echo "<p><strong>Référence:</strong> " . $event->ref_event . "</p>";
            echo "<p><strong>Phase:</strong> " . $event->getLibStatut(1) . "</p>";
            
            // Test 4 : Lecture de l'événement
            $event_read = new EventRental($db);
            $fetch_result = $event_read->fetch($result);
            
            if ($fetch_result > 0) {
                echo "<p style='color:green'>✅ Événement lu avec succès</p>";
                echo "<p><strong>ID lu:</strong> " . $event_read->id . "</p>";
                echo "<p><strong>Nom lu:</strong> " . $event_read->nom_evenement . "</p>";
                echo "<p><strong>Phase lue:</strong> " . $event_read->phase_actuelle . "</p>";
            }
            
            // Test 5 : Changement de phase
            echo "<h2>🔄 Test Changement de Phase</h2>";
            $phase_result = $event_read->changePhase('valide', 'Test validation événement');
            
            if ($phase_result > 0) {
                echo "<p style='color:green'>✅ Phase changée en 'valide'</p>";
                echo "<p><strong>Badge phase:</strong> " . $event_read->getLibStatut(1) . "</p>";
                echo "<p><strong>Date validation:</strong> " . ($event_read->date_validation ? date('d/m/Y H:i', $event_read->date_validation) : 'Non définie') . "</p>";
            }
            
        } else {
            echo "<p style='color:red'>❌ Erreur création événement: ";
            if (!empty($event->errors)) {
                echo implode(', ', $event->errors);
            } else {
                echo $event->error;
            }
            echo "</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ Aucun client trouvé. Créez d'abord un client.</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color:red'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "<p>Trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<h2>📋 Événements Existants</h2>";
$sql_events = "SELECT e.rowid, e.ref_event, e.nom_evenement, e.phase_actuelle, e.date_debut, s.nom as client_name
               FROM ".MAIN_DB_PREFIX."eventrental_event e
               LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = e.fk_soc
               ORDER BY e.date_creation DESC
               LIMIT 10";

$resql_events = $db->query($sql_events);
if ($resql_events) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'>";
    echo "<th>Référence</th><th>Nom</th><th>Client</th><th>Phase</th><th>Date début</th>";
    echo "</tr>";
    
    while ($obj_event = $db->fetch_object($resql_events)) {
        echo "<tr>";
        echo "<td>" . $obj_event->ref_event . "</td>";
        echo "<td>" . $obj_event->nom_evenement . "</td>";
        echo "<td>" . $obj_event->client_name . "</td>";
        echo "<td>" . $obj_event->phase_actuelle . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($obj_event->date_debut)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Aucun événement trouvé.</p>";
}

echo "<p><a href='index.php' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🏠 Retour Module</a></p>";
?>

<?php
require_once '../../../main.inc.php';
require_once '../class/eventrental_event.class.php';

$event_id = GETPOST('event_id', 'int') ?: 1;

echo "<h1>🔍 Debug Détaillé Assignation</h1>";

// Récupération événement
$event = new EventRental($db);
$event->fetch($event_id);

echo "<h2>Événement: ".$event->nom_evenement."</h2>";
echo "<p>Dates: ".dol_print_date($event->date_debut, 'dayhour')." → ".dol_print_date($event->date_fin, 'dayhour')."</p>";

// 1. Matériel demandé
echo "<h3>📋 Matériel Demandé</h3>";
$sql_needed = "SELECT l.qty, p.ref_product, p.label FROM ".MAIN_DB_PREFIX."eventrental_event_line l
               LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = l.fk_product
               WHERE l.fk_event = $event_id";
$resql_needed = $db->query($sql_needed);

if ($resql_needed) {
    while ($obj = $db->fetch_object($resql_needed)) {
        echo "<p><strong>".$obj->ref_product.":</strong> ".$obj->qty." unités demandées</p>";
        
        // 2. Unités de ce produit
        echo "<h4>Unités disponibles pour ".$obj->ref_product."</h4>";
        $sql_units = "SELECT u.rowid, u.numero_serie, u.statut 
                      FROM ".MAIN_DB_PREFIX."eventrental_unit u
                      INNER JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = u.fk_product
                      WHERE p.ref_product = '".$obj->ref_product."'";
        
        $resql_units = $db->query($sql_units);
        if ($resql_units) {
            echo "<table border='1'>";
            echo "<tr><th>Unité</th><th>Statut</th><th>Assignation Actuelle</th></tr>";
            
            while ($unit = $db->fetch_object($resql_units)) {
                echo "<tr>";
                echo "<td>".$unit->numero_serie."</td>";
                echo "<td>".$unit->statut."</td>";
                
                // Vérification assignation
                $sql_assign = "SELECT e.ref_event, e.nom_evenement, e.phase_actuelle, e.date_debut, e.date_fin
                               FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment a
                               INNER JOIN ".MAIN_DB_PREFIX."eventrental_event e ON e.rowid = a.fk_event
                               WHERE a.fk_unit = ".$unit->rowid;
                
                $resql_assign = $db->query($sql_assign);
                if ($resql_assign && $db->num_rows($resql_assign) > 0) {
                    $assign = $db->fetch_object($resql_assign);
                    echo "<td>".$assign->ref_event." (".$assign->phase_actuelle.") - ".dol_print_date($db->jdate($assign->date_debut), 'day')." → ".dol_print_date($db->jdate($assign->date_fin), 'day')."</td>";
                } else {
                    echo "<td>Libre</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

echo "<p><a href='generate_sheet.php?event_id=$event_id'>🔙 Retour Fiche Sortie</a></p>";
?>

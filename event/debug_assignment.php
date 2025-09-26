<?php
require_once '../../../main.inc.php';
require_once '../class/eventrental_event.class.php';
require_once '../class/eventrental_assignment.class.php';

if (!$user->admin) {
    die("Erreur : Admin requis");
}

echo "<h1>🔍 Debug Assignation Matériel</h1>";

$event_id = GETPOST('event_id', 'int') ?: 1;

// Récupération de l'événement
$event = new EventRental($db);
$event->fetch($event_id);

echo "<h2>📅 Événement: ".$event->nom_evenement."</h2>";
echo "<p><strong>Dates:</strong> ".dol_print_date($event->date_debut, 'dayhour')." → ".dol_print_date($event->date_fin, 'dayhour')."</p>";

// Test 1 : Vérification des lignes de matériel
echo "<h3>📋 Matériel Demandé</h3>";
$sql_lines = "SELECT l.rowid, l.fk_product, l.qty, p.ref_product, p.label, p.qty_disponible
              FROM ".MAIN_DB_PREFIX."eventrental_event_line l
              LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = l.fk_product
              WHERE l.fk_event = $event_id";

$resql_lines = $db->query($sql_lines);
if ($resql_lines) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Produit</th><th>Qté Demandée</th><th>Stock Disponible</th><th>Unités Dispo</th></tr>";
    
    while ($obj_line = $db->fetch_object($resql_lines)) {
        echo "<tr>";
        echo "<td>".$obj_line->ref_product." - ".$obj_line->label."</td>";
        echo "<td>".$obj_line->qty."</td>";
        echo "<td>".$obj_line->qty_disponible."</td>";
        
        // Vérification détaillée des unités
        echo "<td>";
        $sql_units = "SELECT rowid, numero_serie, statut 
                      FROM ".MAIN_DB_PREFIX."eventrental_unit 
                      WHERE fk_product = ".$obj_line->fk_product."
                      ORDER BY statut, numero_serie";
        
        $resql_units = $db->query($sql_units);
        $units_dispo = 0;
        $units_details = [];
        
        if ($resql_units) {
            while ($obj_unit = $db->fetch_object($resql_units)) {
                $color = 'black';
                switch ($obj_unit->statut) {
                    case 'disponible': $color = 'green'; $units_dispo++; break;
                    case 'reserve': $color = 'orange'; break;
                    case 'loue': $color = 'red'; break;
                    case 'maintenance': $color = 'blue'; break;
                    case 'panne': $color = 'purple'; break;
                }
                $units_details[] = "<span style='color:$color;'>".$obj_unit->numero_serie." (".$obj_unit->statut.")</span>";
            }
        }
        
        echo "<strong>$units_dispo disponibles</strong><br>";
        echo implode('<br>', $units_details);
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 2 : Vérification des conflits de dates
echo "<h3>⚠️ Conflits Potentiels</h3>";

$sql_conflicts = "SELECT e.ref_event, e.nom_evenement, e.date_debut, e.date_fin
                  FROM ".MAIN_DB_PREFIX."eventrental_event e
                  WHERE e.rowid != $event_id
                  AND e.phase_actuelle NOT IN ('annule', 'archive')
                  AND (
                      (e.date_debut <= '".$db->idate($event->date_fin)."' AND e.date_fin >= '".$db->idate($event->date_debut)."')
                  )
                  ORDER BY e.date_debut";

$resql_conflicts = $db->query($sql_conflicts);
if ($resql_conflicts && $db->num_rows($resql_conflicts) > 0) {
    echo "<p style='color:orange;'>⚠️ Événements qui se chevauchent :</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Événement</th><th>Dates</th></tr>";
    
    while ($obj_conflict = $db->fetch_object($resql_conflicts)) {
        echo "<tr>";
        echo "<td>".$obj_conflict->ref_event." - ".$obj_conflict->nom_evenement."</td>";
        echo "<td>".dol_print_date($db->jdate($obj_conflict->date_debut), 'dayhour')." → ".dol_print_date($db->jdate($obj_conflict->date_fin), 'dayhour')."</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:green;'>✅ Aucun conflit de dates détecté</p>";
}

// Test 3 : Simulation assignation
echo "<h3>🧪 Simulation Assignation</h3>";

if (GETPOST('simulate', 'int')) {
    echo "<div style='background:#f0f8f0; padding:10px; border:1px solid #28a745;'>";
    
    $resql_lines = $db->query($sql_lines);
    if ($resql_lines) {
        while ($obj_line = $db->fetch_object($resql_lines)) {
            echo "<h4>Produit: ".$obj_line->ref_product." (besoin: ".$obj_line->qty.")</h4>";
            
            // Recherche unités disponibles SANS conflit de dates
            $sql_free_units = "SELECT u.rowid, u.numero_serie, u.statut
                               FROM ".MAIN_DB_PREFIX."eventrental_unit u
                               WHERE u.fk_product = ".$obj_line->fk_product."
                               AND u.statut = 'disponible'
                               AND u.rowid NOT IN (
                                   SELECT DISTINCT a.fk_unit 
                                   FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment a
                                   INNER JOIN ".MAIN_DB_PREFIX."eventrental_event e ON e.rowid = a.fk_event
                                   WHERE e.phase_actuelle NOT IN ('annule', 'archive')
                                   AND e.rowid != $event_id
                                   AND (
                                       (e.date_debut <= '".$db->idate($event->date_fin)."' AND e.date_fin >= '".$db->idate($event->date_debut)."')
                                   )
                               )
                               ORDER BY u.nb_locations ASC, u.rowid ASC
                               LIMIT ".$obj_line->qty;
            
            $resql_free = $db->query($sql_free_units);
            
            if ($resql_free) {
                $nb_found = $db->num_rows($resql_free);
                echo "<p><strong>Unités trouvées: $nb_found/".$obj_line->qty."</strong></p>";
                
                if ($nb_found > 0) {
                    echo "<ul>";
                    while ($obj_unit = $db->fetch_object($resql_free)) {
                        echo "<li style='color:green;'>✅ ".$obj_unit->numero_serie."</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p style='color:red;'>❌ Aucune unité libre trouvée pour ce produit</p>";
                }
            } else {
                echo "<p style='color:red;'>❌ Erreur SQL: ".$db->lasterror()."</p>";
            }
        }
    }
    echo "</div>";
}

echo "<p><a href='debug_assignment.php?event_id=$event_id&simulate=1' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🧪 Simuler Assignation</a></p>";

// Correction rapide des statuts
echo "<h3>🔧 Actions de Correction</h3>";

if (GETPOST('fix_status', 'int')) {
    // Libérer les unités "reserve" orphelines
    $sql_fix = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit 
                SET statut = 'disponible' 
                WHERE statut = 'reserve' 
                AND rowid NOT IN (
                    SELECT DISTINCT fk_unit 
                    FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment 
                    WHERE statut IN ('assigne', 'sorti', 'en_cours')
                )";
    
    if ($db->query($sql_fix)) {
        $affected = $db->affected_rows($sql_fix);
        echo "<p style='color:green;'>✅ $affected unités orphelines libérées</p>";
    }
    
    // Mise à jour compteurs produits
    $sql_update = "UPDATE ".MAIN_DB_PREFIX."eventrental_product p SET 
                   p.qty_disponible = (
                       SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit u 
                       WHERE u.fk_product = p.rowid AND u.statut = 'disponible'
                   )";
    if ($db->query($sql_update)) {
        echo "<p style='color:green;'>✅ Compteurs produits mis à jour</p>";
    }
}

echo "<p><a href='debug_assignment.php?event_id=$event_id&fix_status=1' style='background:#dc3545;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔧 Corriger Statuts</a></p>";

echo "<p><a href='generate_sheet.php?event_id=$event_id' style='background:#007bff;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🔙 Retour Fiche Sortie</a></p>";
?>

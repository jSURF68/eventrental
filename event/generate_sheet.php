<?php
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once '../class/eventrental_event.class.php';
require_once '../class/eventrental_product.class.php';
require_once '../class/eventrental_assignment.class.php';

$langs->loadLangs(array("eventrental@eventrental", "other"));

// Security check
if (!$user->rights->eventrental->event->read) {
    accessforbidden();
}

// Get parameters
$event_id = GETPOST('event_id', 'int');
$action = GETPOST('action', 'aZ09');

// Initialize objects
$event = new EventRental($db);
$form = new Form($db);

// Load event
if ($event_id > 0) {
    $result = $event->fetch($event_id);
    if ($result <= 0) {
        dol_print_error($db, $event->error);
        exit;
    }
    $event->fetch_thirdparty();
}

/*
 * Actions
 */
// Variables globales pour les conflits (accessibles dans View)
$blocking_events = array();
$warning_events = array();
$assignment_debug = "";
$has_blocking_conflicts = false;
$has_warning_conflicts = false;

if ($action == 'auto_assign' && $event_id > 0) {
    // Variables pour stocker les résultats
    $assignment_messages = array();
    $assignment_warnings = array();
    $assignment_errors = array();
    
    // Récupération des dates de l'événement
    $assignment_debug .= "<p><strong>Événement:</strong> ".$event->nom_evenement."</p>";
    $assignment_debug .= "<p><strong>Dates:</strong> ".dol_print_date($event->date_debut, 'dayhour')." → ".dol_print_date($event->date_fin, 'dayhour')."</p>";
    
    // ÉTAPE 1: Vérification des conflits de dates AVEC gestion des phases
    $assignment_debug .= "<h4>⚠️ Vérification Conflits de Dates par Phase</h4>";
    
    $sql_conflicts = "SELECT e.rowid, e.ref_event, e.nom_evenement, e.date_debut, e.date_fin, e.phase_actuelle,
                      COUNT(DISTINCT a.fk_unit) as units_assignees,
                      GROUP_CONCAT(DISTINCT p.ref_product) as produits_uses
                      FROM ".MAIN_DB_PREFIX."eventrental_event e
                      LEFT JOIN ".MAIN_DB_PREFIX."eventrental_unit_assignment a ON a.fk_event = e.rowid
                      LEFT JOIN ".MAIN_DB_PREFIX."eventrental_unit u ON u.rowid = a.fk_unit
                      LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = u.fk_product
                      WHERE e.rowid != $event_id
                      AND e.phase_actuelle NOT IN ('annule', 'archive')
                      AND (
                          -- Chevauchement de dates STRICT
                          (e.date_debut < '".$db->idate($event->date_fin)."' AND e.date_fin > '".$db->idate($event->date_debut)."')
                      )
                      GROUP BY e.rowid
                      ORDER BY 
                          CASE WHEN e.phase_actuelle = 'valide' THEN 1 ELSE 2 END,
                          e.date_debut";
    
    $resql_conflicts = $db->query($sql_conflicts);
    
    if ($resql_conflicts && $db->num_rows($resql_conflicts) > 0) {
        
        while ($conflict = $db->fetch_object($resql_conflicts)) {
            if ($conflict->phase_actuelle == 'valide') {
                $has_blocking_conflicts = true;
                $blocking_events[] = $conflict;
            } else {
                $has_warning_conflicts = true;
                $warning_events[] = $conflict;
            }
        }
        
        // Traitement des conflits BLOQUANTS
        if ($has_blocking_conflicts) {
            $blocking_message = "Événements validés en conflit: ";
            foreach ($blocking_events as $conflict) {
                $blocking_message .= $conflict->ref_event." (".$conflict->nom_evenement."), ";
            }
            $assignment_errors[] = rtrim($blocking_message, ', ');
            
            $assignment_debug .= "<div class='error'>🚨 CONFLITS BLOQUANTS détectés avec des événements validés</div>";
        }
        
        // Traitement des conflits d'AVERTISSEMENT
        if ($has_warning_conflicts) {
            $warning_message = "Événements non validés aux mêmes dates: ";
            foreach ($warning_events as $conflict) {
                $warning_message .= $conflict->ref_event." (".$conflict->nom_evenement." - ".$conflict->phase_actuelle."), ";
            }
            $assignment_warnings[] = rtrim($warning_message, ', ');
            
            $assignment_debug .= "<div class='warning'>⚠️ AVERTISSEMENTS détectés avec des événements non validés</div>";
        }
        
    } else {
        $assignment_debug .= "<div class='ok'>✅ Aucun conflit de dates détecté</div>";
    }
    
    // Gestion des confirmations
    $force_blocking = GETPOST('force_blocking', 'int');
    $confirm_warnings = GETPOST('confirm_warnings', 'int');
    
    // Demande de confirmation si conflits bloquants
    if ($has_blocking_conflicts && !$force_blocking) {
        $assignment_errors[] = "Assignation bloquée par des événements validés aux mêmes dates. Vérifiez le planning.";
        // On s'arrête ici sans faire l'assignation
    }
    // Demande de confirmation si conflits d'avertissement  
    else if ($has_warning_conflicts && !$confirm_warnings && !$has_blocking_conflicts) {
        $assignment_warnings[] = "Événements non validés détectés aux mêmes dates. Confirmez pour continuer.";
        // On s'arrête ici sans faire l'assignation
    }
    // Assignation autorisée
    else {
        // ÉTAPE 2: Suppression des anciennes assignations
        $sql_delete = "DELETE FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment WHERE fk_event = ".((int) $event_id);
        $db->query($sql_delete);
        $assignment_debug .= "<p>✅ Anciennes assignations supprimées</p>";
        
        // ÉTAPE 3: Libération intelligente
         $sql_smart_free = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit u
                          INNER JOIN ".MAIN_DB_PREFIX."eventrental_unit_assignment a ON a.fk_unit = u.rowid
                          INNER JOIN ".MAIN_DB_PREFIX."eventrental_event e ON e.rowid = a.fk_event
                          SET u.statut = 'disponible'
                          WHERE u.statut = 'reserve'
                          AND e.rowid != $event_id
                          AND (
                              -- Libérer si événement annulé/archivé
                              e.phase_actuelle IN ('annule', 'archive')
                              OR
                              -- Libérer si pas de conflit de dates (même pour événements non validés)
                              NOT (
                                  e.date_debut < '".$db->idate($event->date_fin)."' 
                                  AND e.date_fin > '".$db->idate($event->date_debut)."'
                              )
                          )";
        
        $result_smart = $db->query($sql_smart_free);
        if ($result_smart) {
            $freed = $db->affected_rows($result_smart);
            $assignment_debug .= "<p>✅ $freed unités intelligemment libérées (pas de conflit de dates)</p>";
            
            // Suppression des assignations correspondantes
            if ($freed > 0) {
                $sql_clean_assign = "DELETE FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment 
                                     WHERE fk_event IN (
                                         SELECT rowid FROM ".MAIN_DB_PREFIX."eventrental_event 
                                         WHERE rowid != $event_id
                                         AND (
                                             phase_actuelle IN ('annule', 'archive')
                                             OR
                                             NOT (
                                                 date_debut < '".$db->idate($event->date_fin)."' 
                                                 AND date_fin > '".$db->idate($event->date_debut)."'
                                             )
                                         )
                                     )";
                $db->query($sql_clean_assign);
                $assignment_debug .= "<p>✅ Assignations orphelines supprimées</p>";
            }
        }

        // ÉTAPE 4: Assignation avec exclusion UNIQUEMENT des événements validés
        $sql_lines = "SELECT l.rowid, l.fk_product, l.qty, p.ref_product, p.label 
                      FROM ".MAIN_DB_PREFIX."eventrental_event_line l
                      LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = l.fk_product
                      WHERE l.fk_event = ".((int) $event_id);
        $resql = $db->query($sql_lines);
        
        $total_assigned = 0;
        $total_needed = 0;
        
        if ($resql) {
            while ($obj_line = $db->fetch_object($resql)) {
                $units_needed = $obj_line->qty;
                $total_needed += $units_needed;
                
                $assignment_debug .= "<p><strong>".$obj_line->ref_product.":</strong> $units_needed unités demandées</p>";
                
                // Recherche des unités disponibles - EXCLUSION UNIQUEMENT des événements VALIDÉS
                $sql_units = "SELECT u.rowid, u.numero_serie, u.qr_code, u.statut 
                              FROM ".MAIN_DB_PREFIX."eventrental_unit u
                              WHERE u.fk_product = ".((int) $obj_line->fk_product)."
                              AND u.statut = 'disponible'
                              AND u.rowid NOT IN (
                                  SELECT DISTINCT a.fk_unit 
                                  FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment a
                                  INNER JOIN ".MAIN_DB_PREFIX."eventrental_event e ON e.rowid = a.fk_event
                                  WHERE e.rowid != $event_id
                                  AND a.statut IN ('assigne', 'sorti', 'en_cours')
                                  AND (
                                      (e.date_debut < '".$db->idate($event->date_fin)."' AND e.date_fin > '".$db->idate($event->date_debut)."')
                                  )
                                  AND e.phase_actuelle = 'valide'
                              )
                              ORDER BY u.nb_locations ASC, u.rowid ASC
                              LIMIT " . $units_needed;
                
                $assignment_debug .= "<p><em>SQL recherche unités:</em> ".substr($sql_units, 0, 100)."...</p>";
                
                $resql_units = $db->query($sql_units);
                $units_found = 0;
                
                if ($resql_units) {
                    $assignment_debug .= "<p>Unités trouvées: ".$db->num_rows($resql_units)."</p>";
                    
                    while ($obj_unit = $db->fetch_object($resql_units)) {
                        // Création de l'assignation
                        $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."eventrental_unit_assignment (";
                        $sql_insert .= "entity, fk_event, fk_unit, fk_event_line, statut, date_assignation, date_creation, fk_user_author";
                        $sql_insert .= ") VALUES (";
                        $sql_insert .= "1, ";
                        $sql_insert .= ((int) $event_id).", ";
                        $sql_insert .= ((int) $obj_unit->rowid).", ";
                        $sql_insert .= ((int) $obj_line->rowid).", ";
                        $sql_insert .= "'assigne', ";
                        $sql_insert .= "'".$db->idate(dol_now())."', ";
                        $sql_insert .= "'".$db->idate(dol_now())."', ";
                        $sql_insert .= ((int) $user->id);
                        $sql_insert .= ")";
                        
                        $result_insert = $db->query($sql_insert);
                        
                        if ($result_insert) {
                            // Mise à jour du statut de l'unité
                            $sql_update = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit SET statut = 'reserve' WHERE rowid = ".((int) $obj_unit->rowid);
                            $db->query($sql_update);
                            
                            $assignment_debug .= "<p style='color:green;'>✅ Assigné: ".$obj_unit->numero_serie."</p>";
                            $total_assigned++;
                            $units_found++;
                        } else {
                            $assignment_debug .= "<p style='color:red;'>❌ Erreur assignation: ".$db->lasterror()."</p>";
                        }
                    }
                } else {
                    $assignment_debug .= "<p style='color:red;'>❌ Erreur SQL recherche unités: ".$db->lasterror()."</p>";
                }
                
                if ($units_found < $units_needed) {
                    $units_blocked = $units_needed - $units_found;
                    $assignment_warnings[] = "$units_blocked unités de ".$obj_line->ref_product." non assignées (conflits ou indisponibles)";
                }
            }
        }
        
        // Messages de résultat
        if ($total_assigned > 0) {
            if ($total_assigned == $total_needed) {
                $assignment_messages[] = $total_assigned.' unités assignées avec succès';
            } else {
                $assignment_warnings[] = $total_assigned.'/'.$total_needed.' unités assignées. '.(($total_needed-$total_assigned)).' unités non assignées.';
            }
        } else {
            if ($has_warning_conflicts && !$confirm_warnings) {
                // Pas d'assignation à cause d'un avertissement non confirmé
                $assignment_warnings[] = 'Assignation suspendue - Confirmation requise pour les conflits d\'avertissement';
            } else {
                $assignment_errors[] = 'Aucune unité n\'a pu être assignée - Vérifiez la disponibilité du matériel';
            }
        }
    }
    
    // Application des messages Dolibarr
    if (!empty($assignment_messages)) {
        setEventMessages($assignment_messages, null, 'mesgs');
    }
    if (!empty($assignment_warnings)) {
        setEventMessages($assignment_warnings, null, 'warnings');  
    }
    if (!empty($assignment_errors)) {
        setEventMessages($assignment_errors, null, 'errors');
    }
}

/*
 * View
 */
$title = "Fiche de Sortie - " . $event->nom_evenement;
llxHeader('', $title);

if ($event_id <= 0) {
    print '<div class="error">Événement non spécifié</div>';
    llxFooter();
    exit;
}

// En-tête événement
$linkback = '<a href="event_card.php?id='.$event_id.'">← Retour à l\'événement</a>';
print load_fiche_titre($title, $linkback);

// Informations événement (résumé)
print '<div class="fichecenter">';
print '<table class="border centpercent">';
print '<tr>';
print '<td class="titlefield"><strong>Référence</strong></td><td>'.$event->ref_event.'</td>';
print '<td><strong>Client</strong></td><td>'.$event->thirdparty->getNomUrl(1).'</td>';
print '</tr>';
print '<tr>';
print '<td><strong>Date début</strong></td><td>'.dol_print_date($event->date_debut, 'dayhour').'</td>';
print '<td><strong>Phase</strong></td><td>'.$event->getLibStatut(1).'</td>';
print '</tr>';
print '<tr>';
print '<td><strong>Date fin</strong></td><td>'.dol_print_date($event->date_fin, 'dayhour').'</td>';
if (!empty($event->lieu_evenement)) {
    print '<td><strong>Lieu</strong></td><td>'.$event->lieu_evenement.'</td>';
} else {
    print '<td colspan="2"></td>';
}
print '</tr>';
print '</table>';
print '</div>';

print '<br>';

// Affichage des boutons de confirmation si nécessaire
if ($action == 'auto_assign') {
    $has_blocking_conflicts = !empty($blocking_events);
    $has_warning_conflicts = !empty($warning_events);
    
    // Boutons de confirmation pour conflits
    if ($has_blocking_conflicts && !GETPOST('force_blocking', 'int')) {
        print '<div class="error">';
        print '<h3>🚨 Assignation Bloquée</h3>';
        print '<p>Des événements validés utilisent le même matériel aux mêmes dates.</p>';
        print '<div class="center">';
        print '<a class="button button-cancel" href="generate_sheet.php?event_id='.$event_id.'">↩️ Annuler</a>&nbsp;';
        print '<a class="button butActionDelete" href="generate_sheet.php?event_id='.$event_id.'&action=auto_assign&force_blocking=1&token='.newToken().'" onclick="return confirm(\'DANGER: Cela créera des conflits matériels réels !\')">🚨 Forcer (Dangereux)</a>';
        print '</div>';
        print '</div>';
    }
    else if ($has_warning_conflicts && !GETPOST('confirm_warnings', 'int') && !$has_blocking_conflicts) {
        print '<div class="warning">';
        print '<h3>⚠️ Confirmation Requise</h3>';
        print '<p>Des événements non validés sont programmés aux mêmes dates. L\'assignation reste possible.</p>';
        print '<div class="center">';
        print '<a class="button button-cancel" href="generate_sheet.php?event_id='.$event_id.'">↩️ Annuler</a>&nbsp;';
        print '<a class="button" href="generate_sheet.php?event_id='.$event_id.'&action=auto_assign&confirm_warnings=1&token='.newToken().'">⚠️ Continuer Malgré l\'Avertissement</a>';
        print '</div>';
        print '</div>';
    }
}

// Debug info si admin
if ($user->admin && !empty($assignment_debug)) {
    print '<div class="info">';
    print '<details>';
    print '<summary>🔍 Détails de l\'assignation (Admin)</summary>';
    print $assignment_debug;
    print '</details>';
    print '</div>';
}

// Récupération des assignations
$assignments = EventRentalAssignment::getEventAssignments($event_id, $db);

if (empty($assignments)) {
    print '<div class="info">Aucune unité assignée à cet événement.</div>';
    
    print '<div class="tabsAction">';
    print '<a class="butAction" href="generate_sheet.php?event_id='.$event_id.'&action=auto_assign&token='.newToken().'">Assigner automatiquement les unités</a>';
    print '</div>';
} else {
    // Liste des unités assignées
    print '<h3>Matériel Assigné ('.count($assignments).' unités)</h3>';
    
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Catégorie</th>';
    print '<th>Produit</th>';
    print '<th>N° Série</th>';
    print '<th>QR Code</th>';
    print '<th>Statut</th>';
    print '</tr>';
    
    $current_category = '';
    foreach ($assignments as $assignment) {
        if ($assignment->category_event != $current_category) {
            $category_icons = array(
                'son' => '[SON]',
                'eclairage' => '[ECL]',
                'scene' => '[SCE]',
                'mobilier' => '[MOB]',
                'decoration' => '[DEC]',
                'technique' => '[TEC]'
            );
            $icon = isset($category_icons[$assignment->category_event]) ? $category_icons[$assignment->category_event] : '[AUT]';
            
            print '<tr class="oddeven" style="background: #f0f0f0;">';
            print '<td colspan="5"><strong>'.$icon.' '.strtoupper($assignment->category_event).'</strong></td>';
            print '</tr>';
            
            $current_category = $assignment->category_event;
        }
        
        print '<tr class="oddeven">';
        print '<td></td>';
        print '<td>'.$assignment->ref_product.' - '.$assignment->product_label.'</td>';
        print '<td><strong>'.$assignment->numero_serie.'</strong></td>';
        print '<td><code>'.$assignment->qr_code.'</code></td>';
        
        $statut_badges = array(
            'assigne' => 'badge-info',
            'sorti' => 'badge-warning',
            'en_cours' => 'badge-primary',
            'retourne' => 'badge-success',
            'incident' => 'badge-danger'
        );
        $badge_class = isset($statut_badges[$assignment->statut]) ? $statut_badges[$assignment->statut] : 'badge-secondary';
        
        print '<td><span class="badge '.$badge_class.'">'.ucfirst($assignment->statut).'</span></td>';
        print '</tr>';
    }
    
    print '</table>';
    
    // Actions
    print '<div class="tabsAction">';
    print '<a class="butAction" href="generate_simple_pdf.php?event_id='.$event_id.'">📄 Générer Fiche PDF</a>';
    print '<a class="butActionDelete" href="generate_sheet.php?event_id='.$event_id.'&action=auto_assign&token='.newToken().'">🔄 Réassigner les unités</a>';
    print '</div>';
}

/**
 * Met à jour les totaux de l'événement
 */
function updateEventTotals($event_id, $db) {
    $sql_total = "SELECT SUM(total_ht) as total FROM ".MAIN_DB_PREFIX."eventrental_event_line WHERE fk_event = ".((int) $event_id);
    $resql_total = $db->query($sql_total);
    
    if ($resql_total) {
        $obj_total = $db->fetch_object($resql_total);
        $total_ht = $obj_total->total ?: 0;
        $total_ttc = $total_ht * 1.20; // TVA 20% par défaut
        
        $sql_update = "UPDATE ".MAIN_DB_PREFIX."eventrental_event SET ";
        $sql_update .= "total_ht = ".$total_ht.", ";
        $sql_update .= "total_ttc = ".$total_ttc." ";
        $sql_update .= "WHERE rowid = ".((int) $event_id);
        
        $db->query($sql_update);
    }
}

llxFooter();
$db->close();
?>

<?php
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once '../class/eventrental_event.class.php';
require_once '../class/eventrental_product.class.php';

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
if ($action == 'add_equipment' && $event_id > 0) {
    $fk_product = GETPOST('fk_product', 'int');
    $qty = GETPOST('qty', 'int');
    $description = GETPOST('description', 'restricthtml');
    
    if ($fk_product > 0 && $qty > 0) {
        // Récupération infos produit
        $product = new EventRentalProduct($db);
        $product->fetch($fk_product);
        
        // Insertion ligne événement
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."eventrental_event_line (";
        $sql .= "fk_event, fk_product, qty, description, product_label, prix_unitaire, total_ht, rang, date_creation";
        $sql .= ") VALUES (";
        $sql .= $event_id.", ";
        $sql .= $fk_product.", ";
        $sql .= $qty.", ";
        $sql .= "'".$db->escape($description)."', ";
        $sql .= "'".$db->escape($product->label)."', ";
        $sql .= $product->prix_location_jour.", ";
        $sql .= ($product->prix_location_jour * $qty).", ";
        $sql .= "0, ";
        $sql .= "'".$db->idate(dol_now())."'";
        $sql .= ")";
        
        $result = $db->query($sql);
        
        if ($result) {
            setEventMessages('Équipement ajouté avec succès', null, 'mesgs');
            
            // Recalcul des totaux événement
            updateEventTotals($event_id, $db);
        } else {
            setEventMessages('Erreur ajout équipement: ' . $db->lasterror(), null, 'errors');
        }
    }
}

if ($action == 'delete_line') {
    $line_id = GETPOST('line_id', 'int');
    
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."eventrental_event_line WHERE rowid = ".((int) $line_id)." AND fk_event = ".((int) $event_id);
    $result = $db->query($sql);
    
    if ($result) {
        setEventMessages('Ligne supprimée', null, 'mesgs');
        updateEventTotals($event_id, $db);
    } else {
        setEventMessages('Erreur suppression: ' . $db->lasterror(), null, 'errors');
    }
}

/*
 * View
 */
$title = "Matériel - " . $event->nom_evenement;
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
print '</table>';
print '</div>';

print '<br>';

// Formulaire d'ajout d'équipement
if ($action == 'create') {
    print load_fiche_titre("Ajouter du Matériel");
    
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?event_id='.$event_id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add_equipment">';
    
    print '<table class="border centpercent">';
    
// Sélection produit avec Select2 natif Dolibarr
print '<tr><td class="titlefieldcreate fieldrequired">Produit</td>';
print '<td>';

// Préparation des données pour select2
$products_array = array();
$sql_products = "SELECT p.rowid, p.ref_product, p.label, p.category_event, p.prix_location_jour, p.qty_disponible";
$sql_products .= " FROM ".MAIN_DB_PREFIX."eventrental_product p";
$sql_products .= " WHERE p.statut = 1";
$sql_products .= " ORDER BY p.category_event, p.label";

$resql_products = $db->query($sql_products);

if ($resql_products) {
    while ($obj_product = $db->fetch_object($resql_products)) {
        $category_icons = array(
            'son' => '🎵',
            'eclairage' => '💡', 
            'scene' => '🎪',
            'mobilier' => '🪑',
            'decoration' => '🎨',
            'technique' => '🔧'
        );
        
        $icon = isset($category_icons[$obj_product->category_event]) ? $category_icons[$obj_product->category_event] : '📦';
        
        $availability_info = '';
        if ($obj_product->qty_disponible <= 0) {
            $availability_info = ' (Indisponible)';
        } else if ($obj_product->qty_disponible <= 2) {
            $availability_info = ' ('.$obj_product->qty_disponible.' dispo)';
        } else {
            $availability_info = ' ('.$obj_product->qty_disponible.' dispo)';
        }
        
        $display_text = $icon . ' ' . $obj_product->ref_product . ' - ' . $obj_product->label;
        $display_text .= ' - ' . price($obj_product->prix_location_jour) . '€/j' . $availability_info;
        
        $products_array[$obj_product->rowid] = $display_text;
    }
}

// Utilisation du select2 natif de Dolibarr
print $form->selectarray('fk_product', $products_array, '', 1, 0, 0, '', 0, 0, 0, '', 'minwidth400', 1);

print '</td></tr>';
    
    // Quantité
    print '<tr><td class="fieldrequired">Quantité</td>';
    print '<td><input type="number" name="qty" value="1" min="1" max="100" required></td></tr>';
    
    // Description
    print '<tr><td>Description/Notes</td>';
    print '<td><textarea name="description" rows="3" cols="50" placeholder="Notes spécifiques pour ce matériel..."></textarea></td></tr>';
    
    print '</table>';
    
    print '<div class="center">';
    print '<input type="submit" class="button" value="Ajouter">';
    print '&nbsp;<a href="equipment_lines.php?event_id='.$event_id.'" class="button button-cancel">Annuler</a>';
    print '</div>';
    
    print '</form>';
    
} else {
    // Liste du matériel de l'événement
    print '<div class="tabsAction">';
    if ($user->rights->eventrental->event->write) {
        print '<a class="butAction" href="equipment_lines.php?event_id='.$event_id.'&action=create">Ajouter matériel</a>';
    }
    print '</div>';

    // Récupération des lignes matériel
    $sql = "SELECT l.rowid, l.fk_product, l.qty, l.description, l.product_label, l.prix_unitaire, l.total_ht,";
    $sql .= " p.ref_product, p.category_event, p.qty_disponible";
    $sql .= " FROM ".MAIN_DB_PREFIX."eventrental_event_line l";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = l.fk_product";
    $sql .= " WHERE l.fk_event = ".((int) $event_id);
    $sql .= " ORDER BY l.rang, l.rowid";

    $resql = $db->query($sql);
    $total_event = 0;

    if ($resql) {
        $num = $db->num_rows($resql);
        
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th>Référence</th>';
        print '<th>Produit</th>';
        print '<th>Catégorie</th>';
        print '<th class="center">Qté</th>';
        print '<th class="right">Prix unit.</th>';
        print '<th class="right">Total</th>';
        print '<th class="center">Actions</th>';
        print '</tr>';
        
        if ($num > 0) {
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                $total_event += $obj->total_ht;
                
                print '<tr class="oddeven">';
                
                // Référence
                print '<td><strong>' . $obj->ref_product . '</strong></td>';
                
                // Produit
                print '<td>' . dol_escape_htmltag($obj->product_label) . '</td>';
                
                // Catégorie
                print '<td>';
                $category_icons = array(
                    'son' => '🎵',
                    'eclairage' => '💡',
                    'scene' => '🎪',
                    'mobilier' => '🪑',
                    'decoration' => '🎨',
                    'technique' => '🔧'
                );
                $icon = isset($category_icons[$obj->category_event]) ? $category_icons[$obj->category_event] : '';
                print $icon . ' ' . ucfirst($obj->category_event);
                print '</td>';
                
                // Quantité
                print '<td class="center"><strong>' . $obj->qty . '</strong></td>';
                
                // Prix unitaire
                print '<td class="right">' . price($obj->prix_unitaire) . ' €/j</td>';
                
                // Total
                print '<td class="right"><strong>' . price($obj->total_ht) . ' €</strong></td>';
                
                // Actions
                print '<td class="center">';
                if ($user->rights->eventrental->event->write) {
                    print '<a href="equipment_lines.php?event_id='.$event_id.'&action=delete_line&line_id='.$obj->rowid.'&token='.newToken().'" ';
                    print 'onclick="return confirm(\'Supprimer cette ligne ?\');" title="Supprimer">❌</a>';
                }
                print '</td>';
                
                print '</tr>';
                
                // Description si présente
                if (!empty($obj->description)) {
                    print '<tr><td colspan="7" class="opacitymedium" style="font-style:italic;padding-left:20px;">';
                    print '💬 ' . dol_escape_htmltag($obj->description);
                    print '</td></tr>';
                }
                
                $i++;
            }
            
            // Ligne total
            print '<tr class="liste_total">';
            print '<td colspan="5" class="right"><strong>Total HT :</strong></td>';
            print '<td class="right"><strong>' . price($total_event) . ' €</strong></td>';
            print '<td></td>';
            print '</tr>';
            
        } else {
            print '<tr><td colspan="7" class="opacitymedium center">Aucun matériel ajouté à cet événement</td></tr>';
        }
        
        print '</table>';
        
    } else {
        dol_print_error($db);
    }
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

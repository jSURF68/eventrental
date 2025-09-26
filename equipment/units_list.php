<?php
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once '../class/eventrental_product.class.php';
require_once '../class/eventrental_unit.class.php';

$langs->loadLangs(array("eventrental@eventrental", "other"));

// Security check
if (!$user->rights->eventrental->equipment->read) {
    accessforbidden();
}

// Get parameters
$product_id = GETPOST('product_id', 'int');
$action = GETPOST('action', 'aZ09');
$unit_id = GETPOST('unit_id', 'int');
$new_status = GETPOST('new_status', 'alpha');

// Initialize objects
$product = new EventRentalProduct($db);
$form = new Form($db);

// Load product
if ($product_id > 0) {
    $result = $product->fetch($product_id);
    if ($result <= 0) {
        dol_print_error($db, $product->error);
        exit;
    }
}

/*
 * Actions
 */
if ($action == 'change_status' && $unit_id > 0 && !empty($new_status)) {
    $unit = new EventRentalUnit($db);
    $unit->fetch($unit_id);
    
    $result = $unit->changeStatus($new_status, 'Changement manuel depuis interface');
    
    if ($result > 0) {
        setEventMessages('Statut modifié avec succès', null, 'mesgs');
    } else {
        setEventMessages('Erreur changement statut: ' . $unit->error, null, 'errors');
    }
    
    // Mise à jour compteurs produit
    $product->updateQuantityCounters();
    $product->fetch($product_id); // Recharger
}

$quantity = GETPOST('unit_quantity', 'int');
$quantity = ($quantity > 0) ? $quantity : 1;

if ($action == 'add_unit' && $product_id > 0) {
    $unit = new EventRentalUnit($db);
    $unit->fk_product = $product_id;
    $unit->numero_serie = GETPOST('numero_serie', 'alpha');
    $unit->qr_code = GETPOST('qr_code', 'alpha');
    $unit->numero_interne = GETPOST('numero_interne', 'alpha');
    $unit->emplacement_actuel = GETPOST('emplacement_actuel', 'alpha');
    $unit->etat_physique = GETPOST('etat_physique', 'alpha');
    $unit->observations = GETPOST('observations', 'restricthtml');
    
    // Génération QR code automatique si vide
    if (empty($unit->qr_code)) {
        $unit->qr_code = $unit->generateQRCode('QR-' . strtoupper(substr($product->ref_product, 0, 3)));
    }
    
    $result = $unit->create($user);
    
    if ($result > 0) {
        setEventMessages('Unité créée avec succès', null, 'mesgs');
        $product->updateQuantityCounters();
        $product->fetch($product_id); // Recharger
    } else {
        setEventMessages('Erreur création unité: ' . implode(', ', $unit->errors), null, 'errors');
    }
}

/*
 * View
 */
$title = "Unités - " . $product->label;
llxHeader('', $title);

if ($product_id <= 0) {
    print '<div class="error">Produit non spécifié</div>';
    llxFooter();
    exit;
}

// En-tête produit
$linkback = '<a href="list.php">← Retour à la liste des produits</a>';
print load_fiche_titre($title, $linkback);

// Informations produit
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">Référence</td><td><strong>' . $product->ref_product . '</strong></td></tr>';
print '<tr><td>Catégorie</td><td>' . $product->category_event . '</td></tr>';
print '<tr><td>Prix/jour</td><td>' . price($product->prix_location_jour) . ' €</td></tr>';
print '</table>';
print '</div>';

print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">Total unités</td><td><strong>' . $product->qty_total . '</strong></td></tr>';
print '<tr><td>Disponibles</td><td><span class="badge badge-status4">' . $product->qty_disponible . '</span></td></tr>';
print '<tr><td>Louées</td><td><span class="badge badge-status6">' . $product->qty_louee . '</span></td></tr>';
print '<tr><td>Maintenance</td><td><span class="badge badge-status3">' . $product->qty_maintenance . '</span></td></tr>';
print '<tr><td>En panne</td><td><span class="badge badge-status8">' . $product->qty_panne . '</span></td></tr>';
print '</table>';
print '</div>';
print '</div>';

print '<div class="clearboth"></div>';

// Formulaire ajout unité
if ($action == 'create') {
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?product_id='.$product_id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add_unit">';
    
    print load_fiche_titre("Nouvelle Unité");
    
    print '<table class="border centpercent">';
    
    print '<tr><td class="titlefieldcreate fieldrequired">N° de série</td>';
    print '<td><input type="text" name="numero_serie" size="30" required placeholder="Ex: BZ740-2024-004"></td></tr>';

    print'<tr><td class="titlefield">Quantité à ajouter</td>';
    print '<td><input type="number" name="unit_quantity" value="1" min="1" max="1000" class="flat" /></td></tr>';
    
    print '<tr><td>QR Code</td>';
    print '<td><input type="text" name="qr_code" size="30" placeholder="Laissez vide pour génération auto"></td></tr>';
    
    print '<tr><td>N° interne</td>';
    print '<td><input type="text" name="numero_interne" size="20" placeholder="Ex: INT-004"></td></tr>';
    
    print '<tr><td>Emplacement</td>';
    print '<td><input type="text" name="emplacement_actuel" size="40" placeholder="Ex: Rack A-4"></td></tr>';
    
    print '<tr><td>État physique</td>';
    print '<td>';
    $conditions = array(
        'neuf' => 'Neuf',
        'excellent' => 'Excellent', 
        'bon' => 'Bon',
        'moyen' => 'Moyen',
        'use' => 'Usé',
        'defaillant' => 'Défaillant'
    );
    print $form->selectarray('etat_physique', $conditions, 'excellent');
    print '</td></tr>';
    
    print '<tr><td>Observations</td>';
    print '<td><textarea name="observations" rows="3" cols="50" placeholder="Notes sur cette unité..."></textarea></td></tr>';
    
    print '</table>';
    
    print '<div class="center">';
    print '<input type="submit" class="button" value="Créer l\'unité">';
    print '&nbsp;<a href="units_list.php?product_id='.$product_id.'" class="button button-cancel">Annuler</a>';
    print '</div>';
    
    print '</form>';
} else {
    // Liste des unités
    print '<div class="tabsAction">';
    if ($user->rights->eventrental->equipment->manage) {
        print '<a class="butAction" href="units_list.php?product_id='.$product_id.'&action=create">Nouvelle unité</a>';
        print '<a class="butAction" href="bulk_management.php">Gestion en Lot</a>';

    }
    print '</div>';

    // Récupération des unités
    $sql = "SELECT rowid, numero_serie, qr_code, numero_interne, statut, etat_physique, emplacement_actuel, nb_locations, observations";
    $sql .= " FROM ".MAIN_DB_PREFIX."eventrental_unit";
    $sql .= " WHERE fk_product = ".((int) $product_id);
    $sql .= " ORDER BY numero_serie";

    $resql = $db->query($sql);

    if ($resql) {
        $num = $db->num_rows($resql);
        
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th>N° Série</th>';
        print '<th>QR Code</th>';
        print '<th>Statut</th>';
        print '<th>État</th>';
        print '<th>Emplacement</th>';
        print '<th>Locations</th>';
        print '<th class="center">Actions</th>';
        print '</tr>';
        
        if ($num > 0) {
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                
                print '<tr class="oddeven">';
                
                // N° Série
                print '<td><strong>' . $obj->numero_serie . '</strong>';
                if (!empty($obj->numero_interne)) {
                    print '<br><span class="opacitymedium">(' . $obj->numero_interne . ')</span>';
                }
                print '</td>';
                
                // QR Code
                print '<td>';
                print '<code style="background:#f0f0f0;padding:2px 4px;">' . $obj->qr_code . '</code>';
                print '</td>';
                
                // Statut avec changement rapide
                print '<td>';
                $unit_temp = new EventRentalUnit($db);
                $unit_temp->statut = $obj->statut;
                print $unit_temp->getLibStatut(1);
                
                if ($user->rights->eventrental->equipment->manage) {
                    print '<br><select onchange="changeStatus('.$obj->rowid.', this.value)" style="font-size:11px;">';
                    print '<option value="">Changer...</option>';
                    $statuts = array(
                        'disponible' => 'Disponible',
                        'maintenance' => 'Maintenance', 
                        'panne' => 'En panne',
                        'reforme' => 'Réformé'
                    );
                    foreach ($statuts as $key => $label) {
                        if ($key != $obj->statut) {
                            print '<option value="'.$key.'">'.$label.'</option>';
                        }
                    }
                    print '</select>';
                }
                print '</td>';
                
                // État physique
                print '<td>';
                $condition_icons = array(
                    'neuf' => '🆕',
                    'excellent' => '✨',
                    'bon' => '👍', 
                    'moyen' => '⚠️',
                    'use' => '🔧',
                    'defaillant' => '❌'
                );
                $icon = isset($condition_icons[$obj->etat_physique]) ? $condition_icons[$obj->etat_physique] : '';
                print $icon . ' ' . ucfirst($obj->etat_physique);
                print '</td>';
                
                // Emplacement
                print '<td>' . ($obj->emplacement_actuel ?: '<em>Non défini</em>') . '</td>';
                
                // Nb locations
                print '<td class="center">' . $obj->nb_locations . '</td>';
                
                // Actions
                print '<td class="center">';
                if ($user->rights->eventrental->equipment->manage) {
                    print '<a href="unit_card.php?id='.$obj->rowid.'" title="Voir détail">👁️</a> ';
                    print '<a href="unit_card.php?id='.$obj->rowid.'&action=edit" title="Modifier">✏️</a>';
                }
                print '</td>';
                
                print '</tr>';
                
                // Ligne observations si présentes
                if (!empty($obj->observations)) {
                    print '<tr><td colspan="7" class="opacitymedium" style="font-style:italic;padding-left:20px;">';
                    print '💬 ' . dol_escape_htmltag($obj->observations);
                    print '</td></tr>';
                }
                
                $i++;
            }
        } else {
            print '<tr><td colspan="7" class="opacitymedium center">Aucune unité créée pour ce produit</td></tr>';
        }
        
        print '</table>';
        
    } else {
        dol_print_error($db);
    }
}

// JavaScript pour changement de statut rapide
print '<script>
function changeStatus(unitId, newStatus) {
    if (newStatus && confirm("Confirmer le changement de statut ?")) {
        window.location.href = "units_list.php?product_id='.$product_id.'&action=change_status&unit_id=" + unitId + "&new_status=" + newStatus;
    }
}
</script>';

llxFooter();
$db->close();
?>

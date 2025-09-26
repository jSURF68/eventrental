<?php
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once '../class/eventrental_product.class.php';
require_once '../class/eventrental_unit.class.php';

$langs->loadLangs(array("eventrental@eventrental", "products"));

// Security check
if (!$user->rights->eventrental->equipment->read) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$form = new Form($db);

/*
 * Actions
 */
if ($action == 'add_bulk_stock') {
    $product_id = GETPOST('product_id', 'int');
    $type_ajout = GETPOST('type_ajout', 'alpha'); // 'cable', 'triplette', 'lot'
    $quantite_lots = GETPOST('quantite_lots', 'int');
    $lot_number = GETPOST('lot_number', 'alpha');
    
    if ($product_id > 0) {
        $errors = array();
        
        switch ($type_ajout) {
            case 'cable':
                $longueurs = GETPOST('longueurs', 'array'); // Différentes longueurs
                $quantites = GETPOST('quantites', 'array'); // Quantités par longueur
                
                foreach ($longueurs as $i => $longueur) {
                    $qty = $quantites[$i];
                    if ($longueur > 0 && $qty > 0) {
                        for ($j = 1; $j <= $qty; $j++) {
                            $numero_serie = "CABLE-{$lot_number}-{$longueur}M-".sprintf('%03d', $j);
                            
                            $sql = "INSERT INTO ".MAIN_DB_PREFIX."eventrental_unit (
                                entity, fk_product, numero_serie, type_unite, longueur_metre, 
                                lot_number, statut, date_creation, date_modification
                            ) VALUES (
                                1, $product_id, '$numero_serie', 'lot', $longueur, 
                                '$lot_number', 'disponible', NOW(), NOW()
                            )";
                            
                            if (!$db->query($sql)) {
                                $errors[] = "Erreur câble $longueur"."m #$j: ".$db->lasterror();
                            }
                        }
                    }
                }
                break;
                
            case 'triplette':
                $longueurs_tri = GETPOST('longueurs_tri', 'array');
                $quantites_tri = GETPOST('quantites_tri', 'array');
                
                foreach ($longueurs_tri as $i => $longueur) {
                    $qty_lots = $quantites_tri[$i]; // Nombre de triplettes
                    if ($longueur > 0 && $qty_lots > 0) {
                        for ($j = 1; $j <= $qty_lots; $j++) {
                            $numero_serie = "TRIP-{$lot_number}-{$longueur}M-".sprintf('%03d', $j);
                            
                            $sql = "INSERT INTO ".MAIN_DB_PREFIX."eventrental_unit (
                                entity, fk_product, numero_serie, type_unite, longueur_metre,
                                lot_number, quantite_lot, statut, date_creation, date_modification
                            ) VALUES (
                                1, $product_id, '$numero_serie', 'lot', $longueur,
                                '$lot_number', 3, 'disponible', NOW(), NOW()
                            )";
                            
                            if (!$db->query($sql)) {
                                $errors[] = "Erreur triplette $longueur"."m #$j: ".$db->lasterror();
                            }
                        }
                    }
                }
                break;
                
            case 'lot_generique':
                $quantite_unitaire = GETPOST('quantite_unitaire', 'int');
                
                for ($i = 1; $i <= $quantite_lots; $i++) {
                    $numero_serie = "LOT-{$lot_number}-".sprintf('%03d', $i);
                    
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."eventrental_unit (
                        entity, fk_product, numero_serie, type_unite, lot_number, 
                        quantite_lot, statut, date_creation, date_modification
                    ) VALUES (
                        1, $product_id, '$numero_serie', 'lot', '$lot_number', 
                        $quantite_unitaire, 'disponible', NOW(), NOW()
                    )";
                    
                    if (!$db->query($sql)) {
                        $errors[] = "Erreur lot #$i: ".$db->lasterror();
                    }
                }
                break;
        }
        
        if (empty($errors)) {
            setEventMessages('Stock ajouté avec succès', null, 'mesgs');
        } else {
            setEventMessages($errors, null, 'errors');
        }
    }
}

/*
 * View
 */
$title = "Gestion Matériel en Lot";
llxHeader('', $title);

print load_fiche_titre($title);

// JavaScript pour la gestion dynamique
?>
<script>
function showConfigFor(type) {
    document.querySelectorAll('.config-section').forEach(el => el.style.display = 'none');
    document.getElementById('config-' + type).style.display = 'block';
}

function addLongueurLine(containerId) {
    const container = document.getElementById(containerId);
    const div = document.createElement('div');
    div.innerHTML = `
        <label>Longueur: <input type="number" name="longueurs_${containerId.split('-')[1]}[]" min="1" max="100" style="width:60px;"> mètres</label>
        <label>Quantité: <input type="number" name="quantites_${containerId.split('-')[1]}[]" min="1" style="width:60px;"></label>
        <button type="button" onclick="this.parentElement.remove()">Supprimer</button>
    `;
    container.appendChild(div);
}
</script>
<?php

print '<div class="fichecenter">';
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="add_bulk_stock">';

// Sélection du produit
print '<table class="border centpercent">';
print '<tr class="liste_titre"><td colspan="2">Configuration du Stock en Lot</td></tr>';

print '<tr>';
print '<td class="titlefield">Produit</td>';
print '<td>';

// Récupération des produits non-unitaires ou nouveaux
$sql_products = "SELECT p.rowid, p.ref_product, p.label, p.category_event
                 FROM ".MAIN_DB_PREFIX."eventrental_product p 
                 WHERE p.category_event IN ('cable', 'accessoire', 'consommable')
                 OR p.ref_product LIKE '%CABLE%' 
                 OR p.ref_product LIKE '%TRIP%'
                 OR p.ref_product LIKE '%LOT%'
                 ORDER BY p.category_event, p.ref_product";

$resql_products = $db->query($sql_products);
$products_array = array();

if ($resql_products) {
    while ($obj_prod = $db->fetch_object($resql_products)) {
        $products_array[$obj_prod->rowid] = $obj_prod->ref_product.' - '.$obj_prod->label.' ['.$obj_prod->category_event.']';
    }
}

print $form->selectarray('product_id', $products_array, GETPOST('product_id'), 'Sélectionner un produit', 1);
print '</td></tr>';

// Type d'ajout
print '<tr>';
print '<td>Type de matériel</td>';
print '<td>';
$types_ajout = array(
    'cable' => '🔌 Câblage (par longueurs)',
    'triplette' => '🔌 Triplettes/Multipaires',
    'lot_generique' => '📦 Matériel en lots'
);
print $form->selectarray('type_ajout', $types_ajout, GETPOST('type_ajout'), 'Choisir le type', 1, 0, '', 0, 0, 0, '', 'onchange="showConfigFor(this.value)"');
print '</td></tr>';

// Numéro de lot général
print '<tr>';
print '<td>Numéro de lot</td>';
print '<td><input type="text" name="lot_number" value="'.date('Y-m-d').'" required></td>';
print '</tr>';
print '</table>';

// Configurations spécifiques par type
?>

<!-- Configuration Câblage -->
<div id="config-cable" class="config-section" style="display:none;">
    <h3>🔌 Configuration Câblage</h3>
    <div id="longueurs-cable">
        <div>
            <label>Longueur: <input type="number" name="longueurs[]" min="1" max="100" style="width:60px;"> mètres</label>
            <label>Quantité: <input type="number" name="quantites[]" min="1" style="width:60px;"></label>
        </div>
    </div>
    <button type="button" onclick="addLongueurLine('longueurs-cable')">+ Ajouter une longueur</button>
    <p><em>Exemple: 10 câbles de 5m + 5 câbles de 10m</em></p>
</div>

<!-- Configuration Triplettes -->
<div id="config-triplette" class="config-section" style="display:none;">
    <h3>🔌 Configuration Triplettes</h3>
    <div id="longueurs-triplette">
        <div>
            <label>Longueur: <input type="number" name="longueurs_tri[]" min="1" max="100" style="width:60px;"> mètres</label>
            <label>Nb triplettes: <input type="number" name="quantites_tri[]" min="1" style="width:60px;"></label>
        </div>
    </div>
    <button type="button" onclick="addLongueurLine('longueurs-triplette')">+ Ajouter une longueur</button>
    <p><em>Une triplette = 3 prises. Exemple: 5 triplettes de 10m</em></p>
</div>

<!-- Configuration Lot Générique -->
<div id="config-lot_generique" class="config-section" style="display:none;">
    <h3>📦 Configuration Lots Génériques</h3>
    <label>Nombre de lots: <input type="number" name="quantite_lots" min="1" style="width:60px;"></label><br>
    <label>Quantité par lot: <input type="number" name="quantite_unitaire" min="1" style="width:60px;"> unités</label>
    <p><em>Exemple: 5 lots de 10 serre-joints chacun</em></p>
</div>

<?php
print '<div class="center">';
print '<input type="submit" class="button" value="Ajouter au Stock">';
print '</div>';
print '</form>';
print '</div>';

// Affichage du stock existant
print '<br><h3>📊 Stock Actuel par Type</h3>';

$sql_stock = "SELECT 
    p.ref_product, p.label, p.category_event,
    u.type_unite,
    CASE 
        WHEN u.type_unite = 'lot' AND u.longueur_metre IS NOT NULL THEN CONCAT(u.longueur_metre, 'm')
        WHEN u.type_unite = 'lot' AND u.quantite_lot > 1 THEN CONCAT(u.quantite_lot, ' unités/lot')
        ELSE 'Standard'
    END as specification,
    COUNT(u.rowid) as stock_disponible,
    SUM(CASE WHEN u.quantite_lot IS NOT NULL THEN u.quantite_lot ELSE 1 END) as total_unitaire
    FROM ".MAIN_DB_PREFIX."eventrental_product p
    LEFT JOIN ".MAIN_DB_PREFIX."eventrental_unit u ON u.fk_product = p.rowid AND u.statut = 'disponible'
    WHERE p.category_event IN ('cable', 'accessoire', 'consommable')
    AND u.rowid IS NOT NULL
    GROUP BY p.rowid, p.ref_product, u.type_unite, u.longueur_metre, u.quantite_lot
    ORDER BY p.category_event, p.ref_product, u.longueur_metre";

$resql_stock = $db->query($sql_stock);

if ($resql_stock && $db->num_rows($resql_stock) > 0) {
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Produit</th><th>Type</th><th>Spécification</th><th>Stock Lots</th><th>Total Unitaire</th>';
    print '</tr>';
    
    while ($obj = $db->fetch_object($resql_stock)) {
        print '<tr class="oddeven">';
        print '<td><strong>'.$obj->ref_product.'</strong><br><small>'.$obj->label.'</small></td>';
        print '<td>'.ucfirst($obj->category_event).'</td>';
        print '<td>'.$obj->specification.'</td>';
        print '<td class="center">'.$obj->stock_disponible.'</td>';
        print '<td class="center">'.$obj->total_unitaire.'</td>';
        print '</tr>';
    }
    print '</table>';
} else {
    print '<p class="opacitymedium center">Aucun stock en lot configuré</p>';
}

llxFooter();
?>

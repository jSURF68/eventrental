<?php
require_once '../../../main.inc.php';
require_once '../class/eventrental_event.class.php';

$event_id = GETPOST('event_id', 'int') ?: 1;

echo "<h1>🔍 Debug Prix Unitaire - Événement $event_id</h1>";

// Chargement événement
$event = new EventRental($db);
$event->fetch($event_id);

echo "<h2>📋 Informations Événement</h2>";
echo "<p><strong>Dates:</strong> ".dol_print_date($event->date_debut, 'dayhour')." → ".dol_print_date($event->date_fin, 'dayhour')."</p>";

// Calcul jours
$date_debut = is_string($event->date_debut) ? strtotime($event->date_debut) : $event->date_debut;
$date_fin = is_string($event->date_fin) ? strtotime($event->date_fin) : $event->date_fin;
$nb_jours = max(1, ceil(($date_fin - $date_debut) / 86400));

echo "<p><strong>Nombre de jours calculés:</strong> $nb_jours</p>";

// Vérification des lignes avec détail
echo "<h2>📦 Debug Lignes de Matériel</h2>";

$sql_debug = "SELECT l.rowid, l.fk_product, l.qty, l.prix_unitaire, l.product_label,
              p.ref_product, p.label as product_db_label
              FROM ".MAIN_DB_PREFIX."eventrental_event_line l
              LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = l.fk_product
              WHERE l.fk_event = $event_id";

$resql_debug = $db->query($sql_debug);

if ($resql_debug && $db->num_rows($resql_debug) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID Ligne</th><th>Produit</th><th>Qté</th><th>Prix Unitaire</th><th>Prix × Jours</th><th>Prix × Jours × Qté</th></tr>";
    
    while ($obj = $db->fetch_object($resql_debug)) {
        $prix_jour = $obj->prix_unitaire ?: 0;
        $prix_unitaire_devis = $prix_jour * $nb_jours;
        $prix_total = $prix_unitaire_devis * $obj->qty;
        
        $color = ($prix_jour > 0) ? '' : 'background:#ffebee;';
        
        echo "<tr style='$color'>";
        echo "<td>".$obj->rowid."</td>";
        echo "<td>".$obj->ref_product." - ".($obj->product_label ?: $obj->product_db_label)."</td>";
        echo "<td>".$obj->qty."</td>";
        echo "<td>".price($prix_jour)." €/jour</td>";
        echo "<td>".price($prix_unitaire_devis)." € (".$prix_jour." × ".$nb_jours.")</td>";
        echo "<td>".price($prix_total)." € (".$prix_unitaire_devis." × ".$obj->qty.")</td>";
        echo "</tr>";
        
        if ($prix_jour <= 0) {
            echo "<tr style='background:#ffebee;'><td colspan='6'>⚠️ PROBLÈME: Prix unitaire = 0 pour cette ligne</td></tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ Aucune ligne de matériel trouvée</p>";
}

// Test direct d'ajout ligne devis
if (GETPOST('test_direct', 'int')) {
    echo "<h2>🧪 Test Direct Ajout Ligne Devis</h2>";
    
    require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
    
    $propal = new Propal($db);
    $propal->socid = $event->socid;
    $propal->date = dol_now();
    
    $propal_id = $propal->create($GLOBALS['user']);
    
    if ($propal_id > 0) {
        echo "<p>✅ Devis test créé (ID: $propal_id)</p>";
        
        // Test ajout ligne avec prix fixe
        $result = $propal->addline(
            "TEST - Enceinte avec prix fixe\nTarif: 50€/jour x 2 jours = 100€/unité",
            100,  // Prix unitaire FIXE
            2,    // Quantité
            20    // TVA 20%
        );
        
        if ($result > 0) {
            echo "<p>✅ Ligne test ajoutée avec prix 100€</p>";
            echo "<p><a href='".DOL_URL_ROOT."/comm/propal/card.php?id=$propal_id' target='_blank'>Voir devis test</a></p>";
        } else {
            echo "<p style='color:red;'>❌ Erreur ajout ligne: ".$propal->error."</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Erreur création devis: ".$propal->error."</p>";
    }
}

echo "<p><a href='debug_prix.php?event_id=$event_id&test_direct=1' style='background:#28a745;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🧪 Test Direct</a></p>";
echo "<p><a href='event_card.php?id=$event_id'>🔙 Retour Événement</a></p>";
?>

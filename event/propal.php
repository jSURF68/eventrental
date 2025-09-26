<?php
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once '../class/eventrental_event.class.php';

$langs->loadLangs(array("eventrental@eventrental", "propal", "bills", "companies"));

// Security check
if (!$user->rights->eventrental->event->read) {
    accessforbidden();
}

// Get parameters
$event_id = GETPOST('id', 'int');
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
if ($action == 'generate_propal' && $event_id > 0) {
    $result = $event->generatePropal($user);
    
    if ($result > 0) {
        setEventMessages('Devis généré avec succès (ID: '.$result.')', null, 'mesgs');
        
        // Redirection vers le devis créé
        if (GETPOST('redirect_to_propal', 'int') == 1) {
            header('Location: '.DOL_URL_ROOT.'/comm/propal/card.php?id='.$result);
            exit;
        }
    } else {
        setEventMessages('Erreur génération devis: '.$event->error, null, 'errors');
    }
}

/*
 * View
 */
$title = "Devis/Facturation - " . $event->nom_evenement;
llxHeader('', $title);

if ($event_id <= 0) {
    print '<div class="error">Événement non spécifié</div>';
    llxFooter();
    exit;
}

// En-tête
$linkback = '<a href="event_card.php?id='.$event_id.'">← Retour à l\'événement</a>';
print load_fiche_titre($title, $linkback);

// Informations événement
print '<div class="fichecenter">';
print '<table class="border centpercent">';
print '<tr>';
print '<td class="titlefield"><strong>Événement</strong></td><td>'.$event->nom_evenement.'</td>';
print '<td><strong>Client</strong></td><td>'.$event->thirdparty->getNomUrl(1).'</td>';
print '</tr>';
print '<tr>';
print '<td><strong>Phase</strong></td><td>'.$event->getLibStatut(1).'</td>';
print '<td><strong>Dates</strong></td><td>'.dol_print_date($event->date_debut, 'day').' → '.dol_print_date($event->date_fin, 'day').'</td>';
print '</tr>';
print '</table>';
print '</div>';

print '<br>';

// Workflow de facturation
$propal = $event->getLinkedPropal();
$facture = $event->getLinkedInvoice();

if (!$propal) {
    // Étape 1 : Pas de devis
    print '<div class="info">';
    print '<h3>📋 Étape 1 : Génération du Devis</h3>';
    print '<p>Générez d\'abord un devis commercial pour cet événement.</p>';
    
    if (count($event->lines) > 0) {
        print '<div class="center">';
        print '<a class="butAction" href="propal.php?id='.$event_id.'&action=generate_propal&token='.newToken().'">📋 Générer le Devis</a>';
        print '<a class="butAction" href="propal.php?id='.$event_id.'&action=generate_propal&redirect_to_propal=1&token='.newToken().'">📋 Générer et Aller au Devis</a>';
        print '</div>';
    } else {
        print '<div class="warning">Ajoutez d\'abord du matériel à l\'événement pour générer un devis.</div>';
    }
    print '</div>';
    
} else {
    // Étape 2 : Devis existant
    print '<div class="ok">';
    print '<h3>✅ Étape 1 Terminée : Devis Généré</h3>';
    print '<table class="border centpercent">';
    print '<tr>';
    print '<td class="titlefield"><strong>Devis</strong></td><td>'.$propal->getNomUrl(1).'</td>';
    print '<td><strong>Statut</strong></td><td>'.$propal->getLibStatut(1).'</td>';
    print '</tr>';
    print '<tr>';
    print '<td><strong>Date</strong></td><td>'.dol_print_date($propal->date, 'day').'</td>';
    print '<td><strong>Montant TTC</strong></td><td>'.price($propal->total_ttc).'</td>';
    print '</tr>';
    print '</table>';
    
    print '<div class="center">';
    print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$propal->id.'">📋 Voir le Devis</a>';
    
    if ($propal->statut == Propal::STATUS_DRAFT) {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$propal->id.'&action=valid">✅ Valider le Devis</a>';
    } elseif ($propal->statut == Propal::STATUS_VALIDATED) {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$propal->id.'&action=close&token='.newToken().'">📝 Signer le Devis</a>';
    }
    print '</div>';
    print '</div>';
    
    if ($facture) {
        // Étape 3 : Facture générée
        print '<br><div class="ok">';
        print '<h3>💰 Étape 2 Terminée : Facture Générée</h3>';
        print '<table class="border centpercent">';
        print '<tr>';
        print '<td class="titlefield"><strong>Facture</strong></td><td>'.$facture->getNomUrl(1).'</td>';
        print '<td><strong>Statut</strong></td><td>'.$facture->getLibStatut(1).'</td>';
        print '</tr>';
        print '<tr>';
        print '<td><strong>Date</strong></td><td>'.dol_print_date($facture->date, 'day').'</td>';
        print '<td><strong>Montant TTC</strong></td><td>'.price($facture->total_ttc).'</td>';
        print '</tr>';
        print '</table>';
        
        print '<div class="center">';
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.$facture->id.'">💰 Voir la Facture</a>';
        if ($facture->statut == Facture::STATUS_DRAFT) {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.$facture->id.'&action=valid">✅ Valider la Facture</a>';
        }
        print '</div>';
        print '</div>';
        
    } elseif ($propal->statut == Propal::STATUS_SIGNED) {
        // Devis signé, prêt à facturer
        print '<br><div class="info">';
        print '<h3>💰 Étape 2 : Facturation</h3>';
        print '<p>Le devis est signé, vous pouvez maintenant le convertir en facture.</p>';
        print '<div class="center">';
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$propal->id.'&action=facture">💰 Convertir en Facture</a>';
        print '</div>';
        print '</div>';
    }
}

llxFooter();
$db->close();
?>

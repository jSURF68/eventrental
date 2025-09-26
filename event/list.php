<?php
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once '../class/eventrental_event.class.php';

$langs->loadLangs(array("eventrental@eventrental", "other"));

// Security check
if (!$user->rights->eventrental->event->read) {
    accessforbidden();
}

// Get parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_name = GETPOST('search_name', 'alpha');
$search_client = GETPOST('search_client', 'alpha');
$search_phase = GETPOST('search_phase', 'alpha');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

if (empty($page) || $page == -1) {
    $page = 0;
}
$offset = $limit * $page;

// Initialize objects
$object = new EventRental($db);
$form = new Form($db);

/*
 * Actions
 */
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
    $search_ref = '';
    $search_name = '';
    $search_client = '';
    $search_phase = '';
}

/*
 * View
 */
$title = $langs->trans('EventsList');
llxHeader('', $title);

// Build and execute select
$sql = "SELECT e.rowid, e.ref_event, e.nom_evenement, e.type_evenement, e.date_debut, e.date_fin, ";
$sql .= "e.lieu_evenement, e.phase_actuelle, e.total_ttc, ";
$sql .= "s.rowid as socid, s.nom as client_name, s.client";
$sql .= " FROM ".MAIN_DB_PREFIX."eventrental_event as e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.socid";  // ← CORRECTION ICI
$sql .= " WHERE e.entity IN (".getEntity('eventrental_event').")";

// Add search filters
if ($search_ref) {
    $sql .= natural_search('e.ref_event', $search_ref);
}
if ($search_name) {
    $sql .= natural_search('e.nom_evenement', $search_name);
}
if ($search_client) {
    $sql .= natural_search('s.nom', $search_client);
}
if ($search_phase && $search_phase != '-1') {
    $sql .= " AND e.phase_actuelle = '".$db->escape($search_phase)."'";
}

// Count total records
$sqlforcount = preg_replace('/^SELECT[^,]*,/', 'SELECT COUNT(*) as nbtotalofrecords,', $sql);
$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
$resqlcount = $db->query($sqlforcount);
$nbtotalofrecords = 0;
if ($resqlcount) {
    $objforcount = $db->fetch_object($resqlcount);
    $nbtotalofrecords = $objforcount->nbtotalofrecords;
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield ?: 'e.date_creation', $sortorder ?: 'DESC');
if ($limit) {
    $sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Output page
$arrayofselected = array();
$param = '';
if ($search_ref) $param .= '&search_ref='.urlencode($search_ref);
if ($search_name) $param .= '&search_name='.urlencode($search_name);
if ($search_client) $param .= '&search_client='.urlencode($search_client);
if ($search_phase && $search_phase != '-1') $param .= '&search_phase='.urlencode($search_phase);

$newcardbutton = '';
if ($user->rights->eventrental->event->write) {
    $newcardbutton = dolGetButtonTitle($langs->trans('NewEvent'), '', 'fa fa-plus-circle', 'event_card.php?action=create');
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'calendar', 0, $newcardbutton, '', $limit, 0, 0, 1);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">';

// Fields title search
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre left">';
print '<input type="text" class="flat maxwidth100imp" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
print '</td>';
print '<td class="liste_titre left">';
print '<input type="text" class="flat maxwidth150imp" name="search_name" value="'.dol_escape_htmltag($search_name).'">';
print '</td>';
print '<td class="liste_titre left">';
print '<input type="text" class="flat maxwidth100imp" name="search_client" value="'.dol_escape_htmltag($search_client).'">';
print '</td>';
print '<td class="liste_titre left">';
$phases_options = array(
    '' => $langs->trans("All"),
    'en_attente' => 'En attente',
    'valide' => 'Validé',
    'en_cours' => 'En cours',
    'retour' => 'Retour',
    'annule' => 'Annulé',
    'archive' => 'Archivé'
);
print $form->selectarray('search_phase', $phases_options, $search_phase, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
print '</td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre center maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

// Fields title label
print '<tr class="liste_titre">';
print_liste_field_titre('Référence', $_SERVER["PHP_SELF"], 'e.ref_event', '', $param, 'class="left"', $sortfield, $sortorder);
print_liste_field_titre('Nom événement', $_SERVER["PHP_SELF"], 'e.nom_evenement', '', $param, 'class="left"', $sortfield, $sortorder);
print_liste_field_titre('Client', $_SERVER["PHP_SELF"], 's.nom', '', $param, 'class="left"', $sortfield, $sortorder);
print_liste_field_titre('Phase', $_SERVER["PHP_SELF"], 'e.phase_actuelle', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('Date début', $_SERVER["PHP_SELF"], 'e.date_debut', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('Lieu', $_SERVER["PHP_SELF"], 'e.lieu_evenement', '', $param, 'class="left"', $sortfield, $sortorder);
print_liste_field_titre('Total TTC', $_SERVER["PHP_SELF"], 'e.total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print '</tr>';

// Loop on record
$i = 0;
$totalarray = array();
while ($i < ($limit ? min($num, $limit) : $num)) {
    $obj = $db->fetch_object($resql);
    if (empty($obj)) {
        break;
    }

    print '<tr class="oddeven">';

    // Ref
    print '<td class="nowraponall">';
    print '<a href="event_card.php?id='.$obj->rowid.'">';
    print '<strong>'.$obj->ref_event.'</strong>';
    print '</a>';
    if (!empty($obj->type_evenement)) {
        print '<br><span class="opacitymedium">('.$obj->type_evenement.')</span>';
    }
    print '</td>';

    // Name
    print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->nom_evenement).'">';
    print '<a href="event_card.php?id='.$obj->rowid.'">';
    print dol_escape_htmltag($obj->nom_evenement);
    print '</a>';
    print '</td>';

    // Client
    print '<td>';
    if ($obj->socid > 0) {
        $client = new Societe($db);
        $client->id = $obj->socid;
        $client->nom = $obj->client_name;
        $client->client = $obj->client;
        print $client->getNomUrl(1);
    }
    print '</td>';

    // Phase
    print '<td class="center">';
    $event_temp = new EventRental($db);
    $event_temp->phase_actuelle = $obj->phase_actuelle;
    print $event_temp->getLibStatut(1);
    print '</td>';

    // Date début
    print '<td class="center nowrap">';
    print dol_print_date($db->jdate($obj->date_debut), 'dayhour');
    if ($obj->date_fin) {
        print '<br><span class="opacitymedium">→ '.dol_print_date($db->jdate($obj->date_fin), 'dayhour').'</span>';
    }
    print '</td>';

    // Lieu
    print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->lieu_evenement).'">';
    print dol_escape_htmltag($obj->lieu_evenement);
    print '</td>';

    // Total TTC
    print '<td class="nowraponall right">';
    print price($obj->total_ttc);
    print '</td>';

    // Action column
    print '<td class="nowraponall center">';
    if ($user->rights->eventrental->event->write) {
        print '<a class="editfielda" href="event_card.php?id='.$obj->rowid.'&action=edit&token='.newToken().'" title="'.$langs->trans('Edit').'">'.img_edit().'</a>';
        print ' ';
    }
    if ($user->rights->eventrental->event->delete) {
        print '<a class="deletefielda" href="event_card.php?id='.$obj->rowid.'&action=delete&token='.newToken().'" title="'.$langs->trans('Delete').'">'.img_delete().'</a>';
    }
    print '</td>';

    print '</tr>';
    $i++;
}

// If no record found
if ($num == 0) {
    $colspan = 8;
    print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

$db->free($resql);

print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
?>

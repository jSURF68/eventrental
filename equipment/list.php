<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
    $res = @include "../../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once '../class/eventrental_product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Load translation files required by the page
$langs->loadLangs(array("eventrental@eventrental", "other"));

// Security check
if (!$user->rights->eventrental->equipment->read) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_category = GETPOST('search_category', 'alpha');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

if (empty($page) || $page == -1) {
    $page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new EventRentalProduct($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('eventrental_productlist'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    't.ref_product'=>'Ref',
    't.label'=>'Label',
);

$arrayfields = array();
$arrayfields['t.ref_product'] = array('label'=>$langs->trans("Ref"), 'checked'=>1);
$arrayfields['t.label'] = array('label'=>$langs->trans("Label"), 'checked'=>1);
$arrayfields['t.category_event'] = array('label'=>$langs->trans("CategoryEvent"), 'checked'=>1);
$arrayfields['t.prix_location_jour'] = array('label'=>$langs->trans("PricePerDay"), 'checked'=>1);
$arrayfields['t.qty_total'] = array('label'=>$langs->trans("QtyTotal"), 'checked'=>1);
$arrayfields['t.qty_disponible'] = array('label'=>$langs->trans("QtyAvailable"), 'checked'=>1);
$arrayfields['t.qty_louee'] = array('label'=>$langs->trans("QtyRented"), 'checked'=>1);
$arrayfields['t.statut'] = array('label'=>$langs->trans("Status"), 'checked'=>1);

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml');
$search = array();

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
    $action = 'list';
    $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
    $massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        $search_ref = '';
        $search_label = '';
        $search_category = '';
        $search_all = '';
        $search_array_options = array();
    }
}

/*
 * View
 */

$form = new Form($db);
// $formother = new FormOther($db); // Supprimé car non nécessaire pour l'instant

$title = $langs->trans('EquipmentList');
$help_url = '';

// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
$sql .= $object->getFieldList('t');
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
    foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
        $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
    }
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
}
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) {
    $sql .= " WHERE t.entity IN (".getEntity($object->element).")";
} else {
    $sql .= " WHERE 1 = 1";
}

// Add where from search criterias
if ($search_ref) {
    $sql .= natural_search('t.ref_product', $search_ref);
}
if ($search_label) {
    $sql .= natural_search('t.label', $search_label);
}
if ($search_category && $search_category != '-1') {
    $sql .= " AND t.category_event = '".$db->escape($search_category)."'";
}
if ($search_all) {
    $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    /* The fast and low memory method to get and count full list converts the sql into a sql count */
    $sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
    $sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
    $resql = $db->query($sqlforcount);
    if ($resql) {
        $objforcount = $db->fetch_object($resql);
        $nbtotalofrecords = $objforcount->nbtotalofrecords;
    } else {
        dol_print_error($db);
    }

    if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
        $page = 0;
        $offset = 0;
    }
    $db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
    $sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page) {
    $obj = $db->fetch_object($resql);
    $id = $obj->rowid;
    header("Location: ".DOL_URL_ROOT.'/custom/eventrental/equipment/product_card.php?id='.$id);
    exit;
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
    $param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
    $param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
    $param .= '&limit='.urlencode($limit);
}
if ($search_ref) {
    $param .= '&search_ref='.urlencode($search_ref);
}
if ($search_label) {
    $param .= '&search_label='.urlencode($search_label);
}
if ($search_category && $search_category != '-1') {
    $param .= '&search_category='.urlencode($search_category);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object);    // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array();
if ($user->rights->eventrental->equipment->manage) {
    $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
    $arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
    print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss'=>'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-large imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss'=>'reposition'));
if ($user->rights->eventrental->equipment->manage) {
    $newcardbutton .= dolGetButtonTitleSeparator();
    $newcardbutton .= dolGetButtonTitle($langs->trans('NewProduct'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/eventrental/equipment/product_card.php?action=create'.($param ? '&'.$param : ''), '', $user->rights->eventrental->equipment->manage);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListPreListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (!empty($arrayfields['t.ref_product']['checked'])) {
    print '<td class="liste_titre left">';
    print '<input type="text" class="flat maxwidth75imp" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
    print '</td>';
}
if (!empty($arrayfields['t.label']['checked'])) {
    print '<td class="liste_titre left">';
    print '<input type="text" class="flat maxwidth100imp" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
    print '</td>';
}
if (!empty($arrayfields['t.category_event']['checked'])) {
    print '<td class="liste_titre left">';
    $categories_options = array(
        '' => $langs->trans("All"),
        'son' => $langs->trans("Son/Audio"),
        'eclairage' => $langs->trans("Éclairage"),
        'scene' => $langs->trans("Scène/Structure"),
        'mobilier' => $langs->trans("Mobilier"),
        'decoration' => $langs->trans("Décoration"),
        'technique' => $langs->trans("Technique")
    );
    print $form->selectarray('search_category', $categories_options, $search_category, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
    print '</td>';
}
if (!empty($arrayfields['t.prix_location_jour']['checked'])) {
    print '<td class="liste_titre right">';
    print '</td>';
}
if (!empty($arrayfields['t.qty_total']['checked'])) {
    print '<td class="liste_titre center">';
    print '</td>';
}
if (!empty($arrayfields['t.qty_disponible']['checked'])) {
    print '<td class="liste_titre center">';
    print '</td>';
}
if (!empty($arrayfields['t.qty_louee']['checked'])) {
    print '<td class="liste_titre center">';
    print '</td>';
}
if (!empty($arrayfields['t.statut']['checked'])) {
    print '<td class="liste_titre center">';
    print '</td>';
}
// Action column
print '<td class="liste_titre center maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (!empty($arrayfields['t.ref_product']['checked'])) {
    print_liste_field_titre($arrayfields['t.ref_product']['label'], $_SERVER["PHP_SELF"], 't.ref_product', '', $param, 'class="left"', $sortfield, $sortorder);
    $totalarray['nbfield']++;
}
if (!empty($arrayfields['t.label']['checked'])) {
    print_liste_field_titre($arrayfields['t.label']['label'], $_SERVER["PHP_SELF"], 't.label', '', $param, 'class="left"', $sortfield, $sortorder);
    $totalarray['nbfield']++;
}
if (!empty($arrayfields['t.category_event']['checked'])) {
    print_liste_field_titre($arrayfields['t.category_event']['label'], $_SERVER["PHP_SELF"], 't.category_event', '', $param, 'class="left"', $sortfield, $sortorder);
    $totalarray['nbfield']++;
}
if (!empty($arrayfields['t.prix_location_jour']['checked'])) {
    print_liste_field_titre($arrayfields['t.prix_location_jour']['label'], $_SERVER["PHP_SELF"], 't.prix_location_jour', '', $param, 'class="right"', $sortfield, $sortorder);
    $totalarray['nbfield']++;
}
if (!empty($arrayfields['t.qty_total']['checked'])) {
    print_liste_field_titre($arrayfields['t.qty_total']['label'], $_SERVER["PHP_SELF"], 't.qty_total', '', $param, 'class="center"', $sortfield, $sortorder);
    $totalarray['nbfield']++;
}
if (!empty($arrayfields['t.qty_disponible']['checked'])) {
    print_liste_field_titre($arrayfields['t.qty_disponible']['label'], $_SERVER["PHP_SELF"], 't.qty_disponible', '', $param, 'class="center"', $sortfield, $sortorder);
    $totalarray['nbfield']++;
}
if (!empty($arrayfields['t.qty_louee']['checked'])) {
    print_liste_field_titre($arrayfields['t.qty_louee']['label'], $_SERVER["PHP_SELF"], 't.qty_louee', '', $param, 'class="center"', $sortfield, $sortorder);
    $totalarray['nbfield']++;
}
if (!empty($arrayfields['t.statut']['checked'])) {
    print_liste_field_titre($arrayfields['t.statut']['label'], $_SERVER["PHP_SELF"], 't.statut', '', $param, 'class="center"', $sortfield, $sortorder);
    $totalarray['nbfield']++;
}
// Action column
print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
$totalarray['nbfield']++;
print '</tr>'."\n";


// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
$totalarray['nbfield'] = 0;
while ($i < ($limit ? min($num, $limit) : $num)) {
    $obj = $db->fetch_object($resql);
    if (empty($obj)) {
        break; // Should not happen
    }

    // Store properties in $object
    $object->setVarsFromFetchObj($obj);

    print '<tr class="oddeven">';

    // Ref
    if (!empty($arrayfields['t.ref_product']['checked'])) {
        print '<td class="nowraponall">';
        print '<a href="product_card.php?id='.$object->id.'">';
        print $object->ref_product;
        print '</a>';
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Label
    if (!empty($arrayfields['t.label']['checked'])) {
        print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($object->label).'">';
        print '<a href="product_card.php?id='.$object->id.'">';
        print dol_escape_htmltag($object->label);
        print '</a>';
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Category
    if (!empty($arrayfields['t.category_event']['checked'])) {
        print '<td class="center">';
        $category_labels = array(
            'son' => '🎵 Son/Audio',
            'eclairage' => '💡 Éclairage',
            'scene' => '🎪 Scène/Structure',
            'mobilier' => '🪑 Mobilier',
            'decoration' => '🎨 Décoration',
            'technique' => '🔧 Technique'
        );
        print isset($category_labels[$object->category_event]) ? $category_labels[$object->category_event] : $object->category_event;
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Price per day
    if (!empty($arrayfields['t.prix_location_jour']['checked'])) {
        print '<td class="nowraponall right">';
        print price($object->prix_location_jour).' €/j';
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Qty Total
    if (!empty($arrayfields['t.qty_total']['checked'])) {
        print '<td class="center">';
        print $object->qty_total;
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Qty Available
    if (!empty($arrayfields['t.qty_disponible']['checked'])) {
        print '<td class="center">';
        print '<span class="badge badge-info">'.$object->qty_disponible.'</span>';
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Qty Rented
    if (!empty($arrayfields['t.qty_louee']['checked'])) {
        print '<td class="center">';
        if ($object->qty_louee > 0) {
            print '<span class="badge badge-warning">'.$object->qty_louee.'</span>';
        } else {
            print '-';
        }
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Status
    if (!empty($arrayfields['t.statut']['checked'])) {
        print '<td class="center">';
        print $object->getLibStatut(5);
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Action column
   // Action column
print '<td class="nowraponall center">';
// Bouton unités - NOUVEAU
print '<a class="editfielda" href="units_list.php?product_id='.$object->id.'" title="Gérer les unités">🔧</a> ';
if ($user->rights->eventrental->equipment->manage) {
    print '<a class="editfielda" href="product_card.php?id='.$object->id.'&action=edit&token='.newToken().'">'.img_edit($langs->trans('Edit')).'</a>';
}
if ($user->rights->eventrental->equipment->manage) {
    print '<a class="deletefielda" href="product_card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.img_delete($langs->trans('Delete')).'</a>';
}
print '</td>';

    $i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
    $colspan = 1;
    foreach ($arrayfields as $key => $val) {
        if (!empty($val['checked'])) {
            $colspan++;
        }
    }
    print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

$db->free($resql);

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

// End of page
llxFooter();
$db->close();
?>

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
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; 
$tmp2 = realpath(__FILE__); 
$i = strlen($tmp) - 1; 
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Security check
if (!$user->hasRight('eventrental', 'event', 'read')) {
    accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array('eventrental@eventrental'));

$title = $langs->trans("EvenRental");

/*
 * View
 */

llxHeader('', $title);

print '<div class="fichecenter">';

print '<div class="fiche">';

print '<div class="fichetitle">';
print '<h1 class="titre">'.$langs->trans("EvenRental").'</h1>';
print '</div>';

print '<div class="fichethirdleft">';

// Zone événements
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Events").'</th></tr>';

// Statistiques événements
$sql_stats = "SELECT phase_actuelle, COUNT(*) as nb FROM ".MAIN_DB_PREFIX."eventrental_event GROUP BY phase_actuelle";
$resql_stats = $db->query($sql_stats);
$stats_events = array();
if ($resql_stats) {
    while ($obj_stats = $db->fetch_object($resql_stats)) {
        $stats_events[$obj_stats->phase_actuelle] = $obj_stats->nb;
    }
}

print '<tr class="oddeven">';
print '<td><a href="event/list.php">'.$langs->trans("EventsList").'</a></td>';
print '<td class="right">';
$total_events = array_sum($stats_events);
print '<span class="badge">'.$total_events.'</span> 📅';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td><a href="event/event_card.php?action=create">'.$langs->trans("NewEvent").'</a></td>';
print '<td class="right">➕</td>';
print '</tr>';

if (!empty($stats_events)) {
    print '<tr class="oddeven">';
    print '<td colspan="2" class="opacitymedium" style="font-size: 11px; padding-left: 20px;">';
    foreach ($stats_events as $phase => $nb) {
        $phase_icons = array(
            'en_attente' => '⏳',
            'valide' => '✅',
            'en_cours' => '🔄',
            'retour' => '📦',
            'annule' => '❌',
            'archive' => '📁'
        );
        $icon = isset($phase_icons[$phase]) ? $phase_icons[$phase] : '📄';
        print $icon . ' ' . ucfirst($phase) . ': ' . $nb . ' &nbsp; ';
    }
    print '</td>';
    print '</tr>';
}

print '</table></div><br>';

print '</div>';

print '<div class="fichetwothirdright">';

// Zone matériel
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Equipment").'</th></tr>';

print '<tr class="oddeven">';
print '<td><a href="equipment/list.php">'.$langs->trans("EquipmentList").'</a></td>';
print '<td class="right">🔧</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td><a href="equipment/product_card.php?action=create">'.$langs->trans("NewEquipment").'</a></td>';
print '<td class="right">➕</td>';
print '</tr>';

print '</table></div><br>';

// Zone planning
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Planning").'</th></tr>';

print '<tr class="oddeven">';
print '<td><a href="planning/index.php">'.$langs->trans("ViewPlanning").'</a></td>';
print '<td class="right">📊</td>';
print '</tr>';

print '</table></div><br>';

print '</div>';

print '</div>';

print '</div>';

llxFooter();

$db->close();
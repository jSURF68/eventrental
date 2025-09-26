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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once '../class/eventrental_event.class.php';

// Load translation files required by the page
$langs->loadLangs(array("eventrental@eventrental", "other", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ09') ? GETPOST('contextpage', 'aZ09') : 'eventcard';
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new EventRental($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('eventrental_eventcard', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';

$permissiontoread = $user->rights->eventrental->event->read;
$permissiontoadd = $user->rights->eventrental->event->write;
$permissiontodelete = $user->rights->eventrental->event->delete || ($permissiontoadd && isset($object->status));
$permissionnote = $user->rights->eventrental->event->write;
$upload_dir = $conf->eventrental->multidir_output[isset($object->entity) ? $object->entity : 1].'/eventrental_event';

// Security check
if ($user->socid > 0) accessforbidden();
if (!isModEnabled("eventrental")) accessforbidden();
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $error = 0;

    $backurlforlist = DOL_URL_ROOT.'/custom/eventrental/event/list.php';

    if (empty($backtopage) || ($cancel && empty($backtopage))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/custom/eventrental/event/event_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
            }
        }
    }

    // Action to add record
    if ($action == 'add') {
        if ($cancel) {
            $urltogo = str_replace('__ID__', $result, $backtopage);
            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
            header("Location: ".$urltogo);
            exit;
        }

        $error = 0;

        $object->nom_evenement = GETPOST('nom_evenement', 'alpha');
        $object->type_evenement = GETPOST('type_evenement', 'alpha');
        $object->description = GETPOST('description', 'restricthtml');
        $object->socid = GETPOST('socid', 'int');
        $object->fk_user_commercial = GETPOST('fk_user_commercial', 'int');
        
        // Dates
       $date_debut_day = GETPOST('date_debutday', 'int');      // 'day' vs 'debutday'
        $date_debut_month = GETPOST('date_debutmonth', 'int');  // 'month' vs 'debutmonth'
        $date_debut_year = GETPOST('date_debutyear', 'int');    // 'year' vs 'debutyear'
        $date_debut_hour = GETPOST('date_debuthour', 'int');    // 'hour' vs 'debuthour'
        $date_debut_min = GETPOST('date_debutmin', 'int');      // 'min' vs 'debutmin'

        // Vérification et création du timestamp
        if (empty($date_debut_day) || empty($date_debut_month) || empty($date_debut_year)) {
            setEventMessages('Erreur: Date de début incomplète', null, 'errors');
            $action = 'create';
            $error++;
        } else {
            $object->date_debut = dol_mktime($date_debut_hour, $date_debut_min, 0, $date_debut_month, $date_debut_day, $date_debut_year);
            echo "<!-- DEBUG: Date début = " . date('d/m/Y H:i', $object->date_debut) . " -->";
        }

        // Date fin avec les vrais noms
        $date_fin_day = GETPOST('date_finday', 'int');
        $date_fin_month = GETPOST('date_finmonth', 'int');
        $date_fin_year = GETPOST('date_finyear', 'int');
        $date_fin_hour = GETPOST('date_finhour', 'int');
        $date_fin_min = GETPOST('date_finmin', 'int');

        if (empty($date_fin_day) || empty($date_fin_month) || empty($date_fin_year)) {
            setEventMessages('Erreur: Date de fin incomplète', null, 'errors');
            $action = 'create';
            $error++;
        } else {
            $object->date_fin = dol_mktime($date_fin_hour, $date_fin_min, 0, $date_fin_month, $date_fin_day, $date_fin_year);
            echo "<!-- DEBUG: Date fin = " . date('d/m/Y H:i', $object->date_fin) . " -->";
        }

        // Validation des dates - Date fin doit être après date début
        if (!$error && !empty($object->date_debut) && !empty($object->date_fin)) {
            if ($object->date_fin <= $object->date_debut) {
                setEventMessages('Erreur: La date de fin doit être postérieure à la date de début', null, 'errors');
                $action = 'create';
                $error++;
            }
            
            // Vérification durée minimale (optionnel - au moins 1 heure)
            $duree_heures = ($object->date_fin - $object->date_debut) / 3600;
            if ($duree_heures < 1) {
                setEventMessages('Erreur: La durée de l\'événement doit être d\'au moins 1 heure', null, 'errors');
                $action = 'create';
                $error++;
            }
            
            // Vérification durée maximale (optionnel - max 30 jours)
            $duree_jours = $duree_heures / 24;
            if ($duree_jours > 30) {
                setEventMessages('Attention: Événement de plus de 30 jours. Vérifiez les dates.', null, 'warnings');
            }
        }
        
        $object->lieu_evenement = GETPOST('lieu_evenement', 'alpha');
        $object->adresse_evenement = GETPOST('adresse_evenement', 'restricthtml');
        $object->nb_invites = GETPOST('nb_invites', 'int');
        $object->note_public = GETPOST('note_public', 'restricthtml');
        $object->note_private = GETPOST('note_private', 'restricthtml');

        if (!$error) {
            $result = $object->create($user);
            if ($result > 0) {
                // Creation OK
                $urltogo = str_replace('__ID__', $result, $backtopage);
                $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo);
                header("Location: ".$urltogo);
                exit;
            } else {
                // Creation KO
                if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
                else setEventMessages($object->error, null, 'errors');
                $action = 'create';
            }
        }
    }


    // Action delete
if ($action == 'confirm_delete' && $confirm == 'yes') {
    if ($user->rights->eventrental->event->delete) {
        $db->begin();
        $error = 0;
        
        // Suppression en cascade
        
        // 1. Libérer les unités assignées
        $sql_units = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit u 
                      INNER JOIN ".MAIN_DB_PREFIX."eventrental_unit_assignment a ON a.fk_unit = u.rowid
                      SET u.statut = 'disponible'
                      WHERE a.fk_event = ".((int) $object->id)." AND u.statut = 'reserve'";
        if (!$db->query($sql_units)) {
            $error++;
            setEventMessages('Erreur libération unités: '.$db->lasterror(), null, 'errors');
        }
        
        // 2. Supprimer les assignations d'unités
        $sql_assign = "DELETE FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment WHERE fk_event = ".((int) $object->id);
        if (!$db->query($sql_assign)) {
            $error++;
            setEventMessages('Erreur suppression assignations: '.$db->lasterror(), null, 'errors');
        }
        
        // 3. Supprimer les lignes de matériel
        $sql_lines = "DELETE FROM ".MAIN_DB_PREFIX."eventrental_event_line WHERE fk_event = ".((int) $object->id);
        if (!$db->query($sql_lines)) {
            $error++;
            setEventMessages('Erreur suppression lignes: '.$db->lasterror(), null, 'errors');
        }
        
        // 4. Supprimer l'événement
        if (!$error) {
            $result = $object->delete($user);
            if ($result <= 0) {
                $error++;
                setEventMessages('Erreur suppression événement: '.$object->error, null, 'errors');
            }
        }
        
        if (!$error) {
            $db->commit();
            setEventMessages('Événement supprimé avec succès', null, 'mesgs');
            header('Location: list.php');
            exit;
        } else {
            $db->rollback();
        }
    }
}


    // Action to update record
    if ($action == 'update' && !$cancel) {
        $object->nom_evenement = GETPOST('nom_evenement', 'alpha');
        $object->type_evenement = GETPOST('type_evenement', 'alpha');
        $object->description = GETPOST('description', 'restricthtml');
        
        // Dates
        $date_debut_day = GETPOST('date_debutday', 'int');
        $date_debut_month = GETPOST('date_debutmonth', 'int');
        $date_debut_year = GETPOST('date_debutyear', 'int');
        $date_debut_hour = GETPOST('date_debuthour', 'int');
        $date_debut_min = GETPOST('date_debutmin', 'int');
        $object->date_debut = dol_mktime($date_debut_hour, $date_debut_min, 0, $date_debut_month, $date_debut_day, $date_debut_year);
        
        $date_fin_day = GETPOST('date_finday', 'int');
        $date_fin_month = GETPOST('date_finmonth', 'int');
        $date_fin_year = GETPOST('date_finyear', 'int');
        $date_fin_hour = GETPOST('date_finhour', 'int');
        $date_fin_min = GETPOST('date_finmin', 'int');
        $object->date_fin = dol_mktime($date_fin_hour, $date_fin_min, 0, $date_fin_month, $date_fin_day, $date_fin_year);

        if (!empty($object->date_debut) && !empty($object->date_fin)) {
        if ($object->date_fin <= $object->date_debut) {
            setEventMessages('Erreur: La date de fin doit être postérieure à la date de début', null, 'errors');
            $action = 'edit';
            $error++;
        }
    }
        
        $object->lieu_evenement = GETPOST('lieu_evenement', 'alpha');
        $object->adresse_evenement = GETPOST('adresse_evenement', 'restricthtml');
        $object->nb_invites = GETPOST('nb_invites', 'int');
        $object->note_public = GETPOST('note_public', 'restricthtml');
        $object->note_private = GETPOST('note_private', 'restricthtml');

        $result = $object->update($user);
        if ($result > 0) {
            $action = '';
        } else {
            if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
            else setEventMessages($object->error, null, 'errors');
        }
    }

    // Action change phase
    if ($action == 'change_phase') {
        $new_phase = GETPOST('new_phase', 'alpha');
        $reason = GETPOST('reason', 'restricthtml');
        
        if (!empty($new_phase)) {
            $result = $object->changePhase($new_phase, $reason);
            if ($result > 0) {
                setEventMessages('Phase modifiée avec succès', null, 'mesgs');
                $object->fetch($id); // Recharger l'objet
            } else {
                setEventMessages('Erreur changement phase: ' . $object->error, null, 'errors');
            }
        }
    }

    // Actions when printing a doc from card
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Action to build doc
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

    // Actions to send emails
    $triggersendname = 'EVENTRENTAL_EVENT_SENTBYMAIL';
    $autocopy = 'MAIN_MAIL_AUTOCOPY_EVENTRENTAL_EVENT_TO';
    $trackid = 'eventrental_event'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

$title = $langs->trans("EventRental");
$help_url = '';
llxHeader('', $title, $help_url);

// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden($langs->trans('NotEnoughPermissions'), 0, 1);
        exit;
    }

    print load_fiche_titre($langs->trans("NewEvent"), '', 'calendar');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    }

    print dol_get_fiche_head(array(), '');

    print '<table class="border centpercent tableforfieldcreate">'."\n";

    // Nom événement
    print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("EventName").'</td>';
    print '<td><input type="text" name="nom_evenement" size="50" maxlength="255" required autofocus></td></tr>';

    // Type événement
    print '<tr><td>'.$langs->trans("EventType").'</td>';
    print '<td>';
    $event_types = array(
        '' => $langs->trans("Select"),
        'Mariage' => 'Mariage',
        'Anniversaire' => 'Anniversaire',
        'Concert' => 'Concert',
        'Conférence' => 'Conférence',
        'Soirée entreprise' => 'Soirée entreprise',
        'Festival' => 'Festival',
        'Salon' => 'Salon/Exposition',
        'Autre' => 'Autre'
    );
    print $form->selectarray('type_evenement', $event_types, '', 0, 0, 0, '', 0, 0, 0, '', 'maxwidth200');
    print '</td></tr>';

// Client (champ obligatoire)
print '<tr><td class="titlefield fieldrequired">Client <span style="color: red;">*</span></td>';
print '<td>';

// Liste des clients
$sql_clients = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."societe WHERE client IN (1,3) ORDER BY nom";
$resql_clients = $db->query($sql_clients);
$clients_array = array();

if ($resql_clients) {
    while ($obj_client = $db->fetch_object($resql_clients)) {
        $clients_array[$obj_client->rowid] = $obj_client->nom;
    }
}

print $form->selectarray('socid', $clients_array, $object->socid, 'Sélectionner un client', 1, 0, '', 0, 0, 0, '', 'minwidth300');
print '</td></tr>';

    // Commercial
    print '<tr><td>'.$langs->trans("Commercial").'</td>';
    print '<td>';
    print img_picto('', 'user', 'class="pictofixedwidth"');
    print $form->select_dolusers($user->id, 'fk_user_commercial', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
    print '</td></tr>';

    // Date début
    print '<tr><td class="fieldrequired">'.$langs->trans("StartDate").'</td>';
    print '<td>';
    print $form->selectDate(dol_now() + 7*24*3600, 'date_debut', 1, 1, 0, 'add', 1, 1);
    print '</td></tr>';

    // Date fin
    print '<tr><td class="fieldrequired">'.$langs->trans("EndDate").'</td>';
    print '<td>';
    print $form->selectDate(dol_now() + 8*24*3600, 'date_fin', 1, 1, 0, 'add', 1, 1);
    print '</td></tr>';

    // Lieu
    print '<tr><td>'.$langs->trans("EventLocation").'</td>';
    print '<td><input type="text" name="lieu_evenement" size="50" maxlength="255" placeholder="Ex: Château de Versailles"></td></tr>';

    // Adresse
    print '<tr><td>'.$langs->trans("EventAddress").'</td>';
    print '<td><textarea name="adresse_evenement" rows="3" cols="50" placeholder="Adresse complète du lieu"></textarea></td></tr>';

    // Nombre invités
    print '<tr><td>'.$langs->trans("GuestCount").'</td>';
    print '<td><input type="number" name="nb_invites" value="0" min="0" max="10000"></td></tr>';

    // Description
    print '<tr><td>'.$langs->trans("Description").'</td>';
    print '<td><textarea name="description" rows="4" cols="80" placeholder="Description détaillée de l\'événement"></textarea></td></tr>';

    // Note publique
    print '<tr><td>'.$langs->trans("NotePublic").'</td>';
    print '<td><textarea name="note_public" rows="3" cols="80" placeholder="Note visible par le client"></textarea></td></tr>';

    // Note privée
    print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
    print '<td><textarea name="note_private" rows="3" cols="80" placeholder="Note interne, non visible par le client"></textarea></td></tr>';

    print '</table>'."\n";

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel("Create");

    print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans("Modify"), '', 'calendar');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    }

    print dol_get_fiche_head(array(), '');

    print '<table class="border centpercent tableforfieldedit">'."\n";

    // Référence (non modifiable)
    print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
    print '<td>'.$object->ref_event.'</td></tr>';

    // Nom événement
    print '<tr><td class="fieldrequired">'.$langs->trans("EventName").'</td>';
    print '<td><input type="text" name="nom_evenement" size="50" value="'.dol_escape_htmltag($object->nom_evenement).'" required></td></tr>';

    // Type événement
    print '<tr><td>'.$langs->trans("EventType").'</td>';
    print '<td>';
    $event_types = array(
        '' => $langs->trans("Select"),
        'Mariage' => 'Mariage',
        'Anniversaire' => 'Anniversaire',
        'Concert' => 'Concert',
        'Conférence' => 'Conférence',
        'Soirée entreprise' => 'Soirée entreprise',
        'Festival' => 'Festival',
        'Salon' => 'Salon/Exposition',  
        'Autre' => 'Autre'
    );
    print $form->selectarray('type_evenement', $event_types, $object->type_evenement, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth200');
    print '</td></tr>';

    // Date début
    print '<tr><td class="fieldrequired">'.$langs->trans("StartDate").'</td>';
    print '<td>';
    print $form->selectDate($object->date_debut, 'date_debut', 1, 1, 0, 'update', 1, 1);
    print '</td></tr>';

    // Date fin
    print '<tr><td class="fieldrequired">'.$langs->trans("EndDate").'</td>';
    print '<td>';
    print $form->selectDate($object->date_fin, 'date_fin', 1, 1, 0, 'update', 1, 1);
    print '</td></tr>';
    // Lieu
    print '<tr><td>'.$langs->trans("EventLocation").'</td>';
    print '<td><input type="text" name="lieu_evenement" size="50" value="'.dol_escape_htmltag($object->lieu_evenement).'"></td></tr>';

    // Adresse
    print '<tr><td>'.$langs->trans("EventAddress").'</td>';
    print '<td><textarea name="adresse_evenement" rows="3" cols="50">'.dol_escape_htmltag($object->adresse_evenement).'</textarea></td></tr>';

    // Nombre invités
    print '<tr><td>'.$langs->trans("GuestCount").'</td>';
    print '<td><input type="number" name="nb_invites" value="'.$object->nb_invites.'" min="0" max="10000"></td></tr>';

    // Description
    print '<tr><td>'.$langs->trans("Description").'</td>';
    print '<td><textarea name="description" rows="4" cols="80">'.dol_escape_htmltag($object->description).'</textarea></td></tr>';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $res = $object->fetch_optionals();

    $head = eventrental_event_prepare_head($object);

    print dol_get_fiche_head($head, 'card', $langs->trans("Event"), -1, 'calendar');

    $formconfirm = '';

    // Confirmation to delete
    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteEvent'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
    }

    // Confirmation change phase
    if ($action == 'confirm_change_phase') {
        $new_phase = GETPOST('new_phase', 'alpha');
        $form_question = array(
            array('type' => 'text', 'name' => 'reason', 'label' => $langs->trans('Reason'), 'value' => '', 'size' => 50)
        );
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&new_phase='.$new_phase, $langs->trans('ChangePhase'), $langs->trans('ConfirmChangePhase', $new_phase), 'change_phase', $form_question, 'yes', 1);
    }

    // Print form confirm
    print $formconfirm;

    // Object card
    $linkback = '<a href="'.dol_buildpath('/custom/eventrental/event/list.php', 1).'?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref_event', $linkback, 1, 'ref_event', 'ref_event', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">'."\n";

    // Nom événement
    print '<tr><td class="titlefield">'.$langs->trans("EventName").'</td>';
    print '<td><strong>'.$object->nom_evenement.'</strong></td></tr>';

    // Type
    if (!empty($object->type_evenement)) {
        print '<tr><td>'.$langs->trans("EventType").'</td>';
        print '<td>'.$object->type_evenement.'</td></tr>';
    }

    // Client
    print '<tr><td>'.$langs->trans("ThirdParty").'</td>';
    print '<td>';
    if ($object->socid > 0) {
        if (empty($object->thirdparty)) {
            $object->fetch_thirdparty();
        }
        print $object->thirdparty->getNomUrl(1);
    }
    print '</td></tr>';

    // Phase actuelle
    print '<tr><td>'.$langs->trans("CurrentPhase").'</td>';
    print '<td>'.$object->getLibStatut(1).'</td></tr>';

    // Dates
    print '<tr><td>'.$langs->trans("StartDate").'</td>';
    print '<td>'.dol_print_date($object->date_debut, 'dayhour').'</td></tr>';

    print '<tr><td>'.$langs->trans("EndDate").'</td>';
    print '<td>'.dol_print_date($object->date_fin, 'dayhour').'</td></tr>';

    // Durée
    if ($object->date_debut && $object->date_fin) {
        $duration = ($object->date_fin - $object->date_debut) / 3600; // en heures
        print '<tr><td>'.$langs->trans("Duration").'</td>';
        if ($duration >= 24) {
            print '<td>'.round($duration/24, 1).' jours</td></tr>';
        } else {
            print '<td>'.round($duration, 1).' heures</td></tr>';
        }
    }

    // Lieu
    if (!empty($object->lieu_evenement)) {
        print '<tr><td>'.$langs->trans("EventLocation").'</td>';
        print '<td>'.$object->lieu_evenement.'</td></tr>';
    }

    // Adresse
    if (!empty($object->adresse_evenement)) {
        print '<tr><td>'.$langs->trans("EventAddress").'</td>';
        print '<td>'.nl2br($object->adresse_evenement).'</td></tr>';
    }

    // Nombre invités
    if ($object->nb_invites > 0) {
        print '<tr><td>'.$langs->trans("GuestCount").'</td>';
        print '<td>'.$object->nb_invites.' personnes</td></tr>';
    }

    print '</table>';
    print '</div>';

    print '<div class="fichehalfright">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">'."\n";

    // Totaux
    print '<tr><td class="titlefield">'.$langs->trans("TotalHT").'</td>';
    print '<td class="nowrap right">'.price($object->total_ht).'</td></tr>';

    print '<tr><td>'.$langs->trans("TotalTTC").'</td>';
    print '<td class="nowrap right"><strong>'.price($object->total_ttc).'</strong></td></tr>';

    // Dates de gestion
    if ($object->date_validation) {
        print '<tr><td>'.$langs->trans("ValidationDate").'</td>';
        print '<td>'.dol_print_date($object->date_validation, 'dayhour').'</td></tr>';
    }

    if ($object->date_annulation) {
        print '<tr><td>'.$langs->trans("CancellationDate").'</td>';
        print '<td>'.dol_print_date($object->date_annulation, 'dayhour').'</td></tr>';
    }

    // Liaisons
    if ($object->fk_propal) {
        print '<tr><td>'.$langs->trans("Proposal").'</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$object->fk_propal.'">PROP-'.$object->fk_propal.'</a></td></tr>';
    }

    if ($object->fk_facture) {
        print '<tr><td>'.$langs->trans("Invoice").'</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.$object->fk_facture.'">FA-'.$object->fk_facture.'</a></td></tr>';
    }

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    // Description
    if (!empty($object->description)) {
        print '<br>';
        print '<table class="border centpercent tableforfield">';
        print '<tr><td class="titlefield">'.$langs->trans("Description").'</td>';
        print '<td>'.nl2br($object->description).'</td></tr>';
        print '</table>';
    }

    print dol_get_fiche_end();

    /*
     * Actions
     */
    if ($action != 'presend' && $action != 'editline') {
        print '<div class="tabsAction">'."\n";
        $parameters = array();
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);

        if (empty($reshook)) {

            if ($permissiontoadd && $object->id > 0) {
    print dolGetButtonAction('', 'Gérer le matériel', 'default', 'equipment_lines.php?event_id='.$object->id, '', $permissiontoadd);
}

if ($permissiontoadd && $object->id > 0) {
    print dolGetButtonAction('', 'Fiche de sortie', 'default', 'generate_sheet.php?event_id='.$object->id, '', $permissiontoadd);
}

// Bouton Devis/Facturation
if ($permissiontoadd && $object->id > 0) {
    $propal = $object->getLinkedPropal();
    $facture = $object->getLinkedInvoice();
    
    if ($facture) {
        // Facture existante
        print dolGetButtonAction('', 'Voir la facture', 'default', DOL_URL_ROOT.'/compta/facture/card.php?id='.$facture->id, '', $user->rights->facture->lire);
    } elseif ($propal) {
        // Devis existant
        if ($propal->statut == Propal::STATUS_SIGNED) {
            print dolGetButtonAction('', 'Convertir en facture', 'default', DOL_URL_ROOT.'/comm/propal/card.php?id='.$propal->id.'&action=facture', '', $user->rights->facture->creer);
        } else {
            print dolGetButtonAction('', 'Voir le devis', 'default', DOL_URL_ROOT.'/comm/propal/card.php?id='.$propal->id, '', $user->rights->propal->lire);
        }
    } else {
        // Pas de devis
        print dolGetButtonAction('', 'Générer devis', 'default', 'propal.php?id='.$object->id, '', $permissiontoadd && $user->rights->propal->creer);
    }
}

            // Modify
            if ($permissiontoadd) {
                print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
            }

            // Change phase selon phase actuelle
            if ($permissiontoadd) {
                $next_phases = array();
                switch ($object->phase_actuelle) {
                    case 'en_attente':
                        $next_phases = array('valide' => 'Valider', 'annule' => 'Annuler');
                        break;
                    case 'valide':
                        $next_phases = array('en_cours' => 'Démarrer', 'annule' => 'Annuler');
                        break;
                    case 'en_cours':
                        $next_phases = array('retour' => 'Initier retour');
                        break;
                    case 'retour':
                        $next_phases = array('archive' => 'Archiver');
                        break;
                }

                foreach ($next_phases as $phase => $label) {
                    print dolGetButtonAction('', $langs->trans($label), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_change_phase&new_phase='.$phase.'&token='.newToken(), '', $permissiontoadd);
                }
            }

            // Clone
            if ($permissiontoadd) {
                print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=clone&token='.newToken(), '', $permissiontoadd);
            }

            // Delete (need delete permission)
            print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete);
        }
        print '</div>'."\n";
    }
}

// Function to prepare the head array
function eventrental_event_prepare_head($object)
{
    global $db, $langs, $conf;

    $langs->load("eventrental@eventrental");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/custom/eventrental/event/event_card.php", 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'eventrental_event@eventrental');
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'eventrental_event@eventrental', 'remove');

    return $head;
}

/*
 * Documents générés
 */
if ($user->rights->eventrental->event->read) {
    $ref = dol_sanitizeFileName($object->ref_event);
    $relativepath = 'eventrental_event/'.$ref;
    $filedir = $conf->eventrental->dir_output.'/'.$relativepath;
    $urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
    $genallowed = $user->rights->eventrental->event->write;
    $delallowed = $user->rights->eventrental->event->delete;
    
    print '<br><div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';
    print '<tr class="liste_titre">';
    print '<td colspan="3">'.$langs->trans("AttachedFiles").'</td>';
    print '</tr>';

    // Affichage des documents
    if (is_dir($filedir)) {
        $filearray = dol_dir_list($filedir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC, 1);
        
        if (count($filearray) > 0) {
            foreach ($filearray as $key => $file) {
                print '<tr class="oddeven">';
                print '<td>';
                $filename = $file['name'];
                $filesize = dol_print_size($file['size'], 1, 1);
                $filedate = dol_print_date($file['date'], "dayhour");
                
                // Icône du fichier
                print img_mime($filename).' ';
                
                // Lien vers le fichier
                $filepath = DOL_URL_ROOT.'/document.php?modulepart=eventrental&attachment=1&file='.urlencode($relativepath.'/'.$filename);
                print '<a href="'.$filepath.'" target="_blank">'.$filename.'</a>';
                print '</td>';
                
                print '<td class="center">'.$filesize.'</td>';
                print '<td class="center">'.$filedate;
                
                // Bouton suppression si droits
                if ($delallowed) {
                    print '<a class="reposition deletefilelink" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&file='.urlencode($relativepath.'/'.$filename).'">';
                    print img_delete();
                    print '</a>';
                }
                print '</td>';
                print '</tr>';
            }
        } else {
            print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoFileFound").'</td></tr>';
        }
    } else {
        print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoFileFound").'</td></tr>';
    }

    // Bouton génération directe
    if ($genallowed) {
        print '<tr class="oddeven">';
        print '<td colspan="3" class="center">';
        print '<a class="butAction" href="generate_sheet.php?event_id='.$object->id.'&action=generate_pdf&download=1&token='.newToken().'">📄 Générer Nouvelle Fiche PDF</a>';
        print '</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';
}

// JavaScript pour validation temps réel des dates
print '<script type="text/javascript">
$(document).ready(function() {
    // Fonction de validation des dates
    function validateDates() {
        // Récupération des valeurs des champs de date
        var debut_day = $(\'select[name="date_debutday"]\').val();
        var debut_month = $(\'select[name="date_debutmonth"]\').val();
        var debut_year = $(\'select[name="date_debutyear"]\').val();
        var debut_hour = $(\'select[name="date_debuthour"]\').val();
        var debut_min = $(\'select[name="date_debutmin"]\').val();
        
        var fin_day = $(\'select[name="date_finday"]\').val();
        var fin_month = $(\'select[name="date_finmonth"]\').val();
        var fin_year = $(\'select[name="date_finyear"]\').val();
        var fin_hour = $(\'select[name="date_finhour"]\').val();
        var fin_min = $(\'select[name="date_finmin"]\').val();
        
        // Création des objets Date
        if (debut_day && debut_month && debut_year && fin_day && fin_month && fin_year) {
            var date_debut = new Date(debut_year, debut_month-1, debut_day, debut_hour || 0, debut_min || 0);
            var date_fin = new Date(fin_year, fin_month-1, fin_day, fin_hour || 0, fin_min || 0);
            
            // Suppression des anciens messages d\'erreur
            $(\'.date-error\').remove();
            
            if (date_fin <= date_debut) {
                // Affichage erreur
                $(\'select[name="date_finday"]\').after(\'<br><span class="date-error" style="color:red; font-size:11px;">⚠️ Date de fin doit être après la date de début</span>\');
                return false;
            } else {
                // Calcul et affichage de la durée
                var duree_heures = Math.round((date_fin - date_debut) / (1000 * 60 * 60) * 10) / 10;
                var duree_jours = Math.round(duree_heures / 24 * 10) / 10;
                
                var duree_text = "";
                if (duree_jours >= 1) {
                    duree_text = duree_jours + " jour" + (duree_jours > 1 ? "s" : "");
                } else {
                    duree_text = duree_heures + " heure" + (duree_heures > 1 ? "s" : "");
                }
                
                $(\'select[name="date_finday"]\').after(\'<br><span class="date-error" style="color:green; font-size:11px;">✅ Durée: \' + duree_text + \'</span>\');
                return true;
            }
        }
        return true;
    }
    
    // Fonction pour définir une durée rapide
    window.setEventDuration = function(heures) {
        // Récupérer la date de début actuelle
        var debut_day = parseInt($(\'select[name="date_debutday"]\').val());
        var debut_month = parseInt($(\'select[name="date_debutmonth"]\').val());
        var debut_year = parseInt($(\'select[name="date_debutyear"]\').val());
        var debut_hour = parseInt($(\'select[name="date_debuthour"]\').val()) || 0;
        var debut_min = parseInt($(\'select[name="date_debutmin"]\').val()) || 0;
        
        if (debut_day && debut_month && debut_year) {
            // Calculer la date de fin
            var date_debut = new Date(debut_year, debut_month-1, debut_day, debut_hour, debut_min);
            var date_fin = new Date(date_debut.getTime() + (heures * 60 * 60 * 1000));
            
            // Mettre à jour les champs de fin
            $(\'select[name="date_finday"]\').val(date_fin.getDate());
            $(\'select[name="date_finmonth"]\').val(date_fin.getMonth() + 1);
            $(\'select[name="date_finyear"]\').val(date_fin.getFullYear());
            $(\'select[name="date_finhour"]\').val(date_fin.getHours());
            $(\'select[name="date_finmin"]\').val(date_fin.getMinutes());
            
            // Trigger validation
            setTimeout(validateDates, 100);
        } else {
            alert(\'Veuillez d\\\'abord définir une date de début\');
        }
    };
    
    // Validation en temps réel lors des changements
    $(\'select[name^="date_"]\').change(function() {
        setTimeout(validateDates, 100);
    });
    
    // Validation à la soumission du formulaire
    $(\'form\').submit(function(e) {
        if (!validateDates()) {
            e.preventDefault();
            alert("Veuillez corriger les erreurs de dates avant de continuer.");
            return false;
        }
    });
    
    // Validation initiale
    setTimeout(validateDates, 500);
});
</script>';

// End of page
llxFooter();
$db->close();
?>

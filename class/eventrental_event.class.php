<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Classe pour gérer les événements de location
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class EventRental extends CommonObject
{
    /**
     * @var string ID pour module
     */
    public $module = 'eventrental';

    /**
     * @var string ID pour l'objet
     */
    public $element = 'eventrental_event';

    /**
     * @var string Nom de la table
     */
    public $table_element = 'eventrental_event';

    /**
     * @var string Nom de la table des lignes
     */
    public $table_element_line = 'eventrental_event_line';

    /**
     * @var int Support multi-entité
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Support champs extra
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string Nom de la colonne de clé primaire
     */
    public $fk_element = 'fk_eventrental_event';

    /**
     * Constantes de phase
     */
    const PHASE_PENDING = 'en_attente';
    const PHASE_VALIDATED = 'valide';
    const PHASE_IN_PROGRESS = 'en_cours';
    const PHASE_RETURN = 'retour';
    const PHASE_CANCELLED = 'annule';
    const PHASE_ARCHIVED = 'archive';

    /**
     * Colonnes de l'objet
     */
    public $fields = array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>0, 'default'=>1),
        'ref_event' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1'),
        'nom_evenement' => array('type'=>'varchar(255)', 'label'=>'EventName', 'enabled'=>'1', 'position'=>15, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth300'),
        'type_evenement' => array('type'=>'varchar(128)', 'label'=>'EventType', 'enabled'=>'1', 'position'=>16, 'notnull'=>0, 'visible'=>1),
        'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>17, 'notnull'=>0, 'visible'=>3),
        'socid' => array('type'=>'integer:Societe:societe/class/societe.class.php:1:(status:=:1)', 'label'=>'ThirdParty', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'index'=>1),
        'fk_user_commercial' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Commercial', 'enabled'=>'1', 'position'=>35, 'notnull'=>0, 'visible'=>3),
        'date_debut' => array('type'=>'datetime', 'label'=>'StartDate', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1),
        'date_fin' => array('type'=>'datetime', 'label'=>'EndDate', 'enabled'=>'1', 'position'=>45, 'notnull'=>1, 'visible'=>1),
        'date_montage' => array('type'=>'datetime', 'label'=>'SetupDate', 'enabled'=>'1', 'position'=>46, 'notnull'=>0, 'visible'=>3),
        'date_demontage' => array('type'=>'datetime', 'label'=>'TeardownDate', 'enabled'=>'1', 'position'=>47, 'notnull'=>0, 'visible'=>3),
        'lieu_evenement' => array('type'=>'text', 'label'=>'EventLocation', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1),
        'adresse_evenement' => array('type'=>'text', 'label'=>'EventAddress', 'enabled'=>'1', 'position'=>55, 'notnull'=>0, 'visible'=>3),
        'nb_invites' => array('type'=>'integer', 'label'=>'GuestCount', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'phase_actuelle' => array('type'=>'select', 'label'=>'CurrentPhase', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>1, 'default'=>'en_attente', 'arrayofkeyval'=>array('en_attente'=>'En attente', 'valide'=>'Validé', 'en_cours'=>'En cours', 'retour'=>'Retour', 'annule'=>'Annulé', 'archive'=>'Archivé')),
        'date_validation' => array('type'=>'datetime', 'label'=>'ValidationDate', 'enabled'=>'1', 'position'=>75, 'notnull'=>0, 'visible'=>3),
        'date_annulation' => array('type'=>'datetime', 'label'=>'CancellationDate', 'enabled'=>'1', 'position'=>76, 'notnull'=>0, 'visible'=>3),
        'motif_annulation' => array('type'=>'text', 'label'=>'CancellationReason', 'enabled'=>'1', 'position'=>77, 'notnull'=>0, 'visible'=>3),
        'total_ht' => array('type'=>'price', 'label'=>'TotalHT', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>1, 'default'=>'0'),
        'total_tva' => array('type'=>'price', 'label'=>'TotalVAT', 'enabled'=>'1', 'position'=>85, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'total_ttc' => array('type'=>'price', 'label'=>'TotalTTC', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>1, 'default'=>'0'),
        'fk_propal' => array('type'=>'integer:Propal:comm/propal/class/propal.class.php', 'label'=>'Proposal', 'enabled'=>'1', 'position'=>95, 'notnull'=>0, 'visible'=>3),
        'fk_facture' => array('type'=>'integer:Facture:compta/facture/class/facture.class.php', 'label'=>'Invoice', 'enabled'=>'1', 'position'=>96, 'notnull'=>0, 'visible'=>3),
        'fk_projet' => array('type'=>'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label'=>'Project', 'enabled'=>'1', 'position'=>97, 'notnull'=>0, 'visible'=>3),
        'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>0),
        'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>105, 'notnull'=>0, 'visible'=>0),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>0),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>0),
        'fk_user_author' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>0),
        'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>0),
    );



    // Propriétés principales
    public $rowid;
    public $entity;
    public $ref_event;
    public $nom_evenement;
    public $type_evenement;
    public $description;
    public $fk_soc;
    public $socid;
    public $fk_user_commercial;
    public $date_debut;
    public $date_fin;
    public $date_montage;
    public $date_demontage;
    public $lieu_evenement;
    public $adresse_evenement;
    public $nb_invites;
    public $phase_actuelle;
    public $date_validation;
    public $date_annulation;
    public $motif_annulation;
    public $total_ht;
    public $total_tva;
    public $total_ttc;
    public $fk_propal;
    public $fk_facture;
    public $fk_projet;
    public $note_public;
    public $note_private;
    public $date_creation;
    public $tms;
    public $fk_user_author;
    public $fk_user_modif;

    // Objets liés
    public $thirdparty;
    public $lines = array(); // Lignes d'équipement

    /**
     * Constructor
     */
     public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
            $this->fields['rowid']['visible'] = 1;
        }

        if (!isModEnabled('eventrental')) {
            $this->enabled = 0;
        }

        $this->ismultientitymanaged = 0;
        $this->isextrafieldmanaged = 1;

        // Configuration pour les objets liés
        $this->element = 'eventrental_event';
        $this->table_element = 'eventrental_event';
        $this->picto = 'calendar';
        $this->fk_element = 'fk_event';

        // Répertoire des documents générés
        if (!empty($conf->eventrental->multidir_output)) {
            $this->multidir_output = $conf->eventrental->multidir_output;
        } else {
            $this->multidir_output = array($conf->entity => $conf->eventrental->dir_output);
        }
    }

    /**
     * Create object into database
     */
    public function create(User $user, $notrigger = false)
    {
        global $conf;

        $error = 0;

        // Clean parameters
        if (isset($this->ref_event)) {
            $this->ref_event = trim($this->ref_event);
        }
        if (isset($this->nom_evenement)) {
            $this->nom_evenement = trim($this->nom_evenement);
        }

        // Check parameters
        if (empty($this->ref_event)) {
            $this->ref_event = $this->getNextNumRef();
        }
        if (empty($this->nom_evenement)) {
            $this->errors[] = 'ErrorFieldRequired|nom_evenement';
            return -1;
        }
        if (empty($this->socid)) {
            $this->errors[] = 'ErrorFieldRequired|socid';
            return -1;
        }
       if (empty($this->date_debut) || $this->date_debut <= 0) {
            $this->errors[] = 'ErrorFieldRequired|date_debut';
            return -1;
        }
        if (empty($this->date_fin) || $this->date_fin <= 0) {
            $this->errors[] = 'ErrorFieldRequired|date_fin';
            return -1;
        }

        // Insert request
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';
        $sql .= 'entity,';
        $sql .= 'ref_event,';
        $sql .= 'nom_evenement,';
        $sql .= 'type_evenement,';
        $sql .= 'description,';
        $sql .= 'socid,';
        $sql .= 'fk_user_commercial,';
        $sql .= 'date_debut,';
        $sql .= 'date_fin,';
        $sql .= 'date_montage,';
        $sql .= 'date_demontage,';
        $sql .= 'lieu_evenement,';
        $sql .= 'adresse_evenement,';
        $sql .= 'nb_invites,';
        $sql .= 'phase_actuelle,';
        $sql .= 'total_ht,';
        $sql .= 'total_tva,';
        $sql .= 'total_ttc,';
        $sql .= 'note_public,';
        $sql .= 'note_private,';
        $sql .= 'date_creation,';
        $sql .= 'fk_user_author';
        $sql .= ') VALUES (';
        $sql .= (!isset($this->entity) ? $conf->entity : $this->entity).',';
        $sql .= ' '.(empty($this->ref_event) ? 'NULL' : "'".$this->db->escape($this->ref_event)."'").',';
        $sql .= ' '.(empty($this->nom_evenement) ? 'NULL' : "'".$this->db->escape($this->nom_evenement)."'").',';
        $sql .= ' '.(empty($this->type_evenement) ? 'NULL' : "'".$this->db->escape($this->type_evenement)."'").',';
        $sql .= ' '.(empty($this->description) ? 'NULL' : "'".$this->db->escape($this->description)."'").',';
        $sql .= ' '.((int) $this->socid).',';
        $sql .= ' '.(empty($this->fk_user_commercial) ? 'NULL' : ((int) $this->fk_user_commercial)).',';
        $sql .= ' '."'".$this->db->idate($this->date_debut)."'".',';
        $sql .= ' '."'".$this->db->idate($this->date_fin)."'".',';
        $sql .= ' '.(empty($this->date_montage) ? 'NULL' : "'".$this->db->idate($this->date_montage)."'").',';
        $sql .= ' '.(empty($this->date_demontage) ? 'NULL' : "'".$this->db->idate($this->date_demontage)."'").',';
        $sql .= ' '.(empty($this->lieu_evenement) ? 'NULL' : "'".$this->db->escape($this->lieu_evenement)."'").',';
        $sql .= ' '.(empty($this->adresse_evenement) ? 'NULL' : "'".$this->db->escape($this->adresse_evenement)."'").',';
        $sql .= ' '.(empty($this->nb_invites) ? '0' : ((int) $this->nb_invites)).',';
        $sql .= ' '.(empty($this->phase_actuelle) ? "'en_attente'" : "'".$this->db->escape($this->phase_actuelle)."'").',';
        $sql .= ' '.(empty($this->total_ht) ? '0' : $this->total_ht).',';
        $sql .= ' '.(empty($this->total_tva) ? '0' : $this->total_tva).',';
        $sql .= ' '.(empty($this->total_ttc) ? '0' : $this->total_ttc).',';
        $sql .= ' '.(empty($this->note_public) ? 'NULL' : "'".$this->db->escape($this->note_public)."'").',';
        $sql .= ' '.(empty($this->note_private) ? 'NULL' : "'".$this->db->escape($this->note_private)."'").',';
        $sql .= ' '."'".$this->db->idate(dol_now())."'".',';
        $sql .= ' '.((int) $user->id);
        $sql .= ')';

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error '.$this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }

     /**
     * Update object into database
     */
    public function update(User $user, $notrigger = false)
    {
        $error = 0;

        // Clean parameters
        if (isset($this->nom_evenement)) {
            $this->nom_evenement = trim($this->nom_evenement);
        }
        if (isset($this->type_evenement)) {
            $this->type_evenement = trim($this->type_evenement);
        }

        // Check parameters
        if (empty($this->nom_evenement)) {
            $this->errors[] = 'ErrorFieldRequired|nom_evenement';
            return -1;
        }

        // Update request
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET ';
        $sql .= 'nom_evenement = '.(empty($this->nom_evenement) ? 'NULL' : "'".$this->db->escape($this->nom_evenement)."'").',';
        $sql .= 'type_evenement = '.(empty($this->type_evenement) ? 'NULL' : "'".$this->db->escape($this->type_evenement)."'").',';
        $sql .= 'description = '.(empty($this->description) ? 'NULL' : "'".$this->db->escape($this->description)."'").',';
        $sql .= 'date_debut = '."'".$this->db->idate($this->date_debut)."'".',';
        $sql .= 'date_fin = '."'".$this->db->idate($this->date_fin)."'".',';
        $sql .= 'lieu_evenement = '.(empty($this->lieu_evenement) ? 'NULL' : "'".$this->db->escape($this->lieu_evenement)."'").',';
        $sql .= 'adresse_evenement = '.(empty($this->adresse_evenement) ? 'NULL' : "'".$this->db->escape($this->adresse_evenement)."'").',';
        $sql .= 'nb_invites = '.(empty($this->nb_invites) ? '0' : ((int) $this->nb_invites)).',';
        $sql .= 'phase_actuelle = '.(empty($this->phase_actuelle) ? "'en_attente'" : "'".$this->db->escape($this->phase_actuelle)."'").',';
        $sql .= 'date_validation = '.(empty($this->date_validation) ? 'NULL' : "'".$this->db->idate($this->date_validation)."'").',';
        $sql .= 'date_annulation = '.(empty($this->date_annulation) ? 'NULL' : "'".$this->db->idate($this->date_annulation)."'").',';
        $sql .= 'motif_annulation = '.(empty($this->motif_annulation) ? 'NULL' : "'".$this->db->escape($this->motif_annulation)."'").',';
        $sql .= 'total_ht = '.(empty($this->total_ht) ? '0' : $this->total_ht).',';
        $sql .= 'total_tva = '.(empty($this->total_tva) ? '0' : $this->total_tva).',';
        $sql .= 'total_ttc = '.(empty($this->total_ttc) ? '0' : $this->total_ttc).',';
        $sql .= 'note_public = '.(empty($this->note_public) ? 'NULL' : "'".$this->db->escape($this->note_public)."'").',';
        $sql .= 'note_private = '.(empty($this->note_private) ? 'NULL' : "'".$this->db->escape($this->note_private)."'").',';
        $sql .= 'fk_user_modif = '.((int) $user->id);
        $sql .= ' WHERE rowid = '.((int) $this->id);

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error '.$this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

        /**
     * Delete object in database
     *
     * @param User $user      User that deletes
     * @param bool $notrigger false=launch triggers after, true=disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        if (!$error) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('EVENTRENTAL_EVENT_DELETE', $user);
                if ($result < 0) {
                    $error++;
                }
            }
        }

        if (!$error) {
            $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
            $sql .= ' WHERE rowid='.((int) $this->id);

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error ".$this->db->lasterror();
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     * Load object in memory from the database
     */
    public function fetch($id, $ref = null)
    {
        $result = $this->fetchCommon($id, $ref);
        if ($result > 0 && !empty($this->table_element_line)) {
            $this->fetchLines();
        }
        return $result;
    }

    /**
     * Load the third party
     */
     public function fetch_thirdparty($force_thirdparty_id = 0)
    {
        $thirdparty_id = ($force_thirdparty_id > 0) ? $force_thirdparty_id : $this->socid;
        
        if ($thirdparty_id > 0 && empty($this->thirdparty)) {
            $this->thirdparty = new Societe($this->db);
            $result = $this->thirdparty->fetch($thirdparty_id);
            if ($result <= 0) {
                $this->thirdparty = null;
                return -1;
            }
        }
        return 1;
    }

    /**
     * Load lines
     */
    public function fetchLines()
    {
        $this->lines = array();

        $sql = "SELECT rowid, fk_event, fk_product, qty, description, product_label,";
        $sql .= " prix_unitaire, remise_percent, total_ht, tva_rate, total_tva, total_ttc";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element_line;
        $sql .= " WHERE fk_event = ".((int) $this->id);
        $sql .= " ORDER BY rang, rowid";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                $line = new EventRentalLine($this->db);
                $line->id = $obj->rowid;
                $line->fk_event = $obj->fk_event;
                $line->fk_product = $obj->fk_product;
                $line->qty = $obj->qty;
                $line->description = $obj->description;
                $line->product_label = $obj->product_label;
                $line->prix_unitaire = $obj->prix_unitaire;
                $line->remise_percent = $obj->remise_percent;
                $line->total_ht = $obj->total_ht;
                $line->tva_rate = $obj->tva_rate;
                $line->total_tva = $obj->total_tva;
                $line->total_ttc = $obj->total_ttc;

                $this->lines[$i] = $line;
                $i++;
            }
            $this->db->free($resql);
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }

        return 1;
    }

    /**
     * Change phase
     */
    public function changePhase($new_phase, $reason = '')
    {
        global $user;

        $old_phase = $this->phase_actuelle;
        $this->phase_actuelle = $new_phase;

        // Actions spéciales selon les phases
        switch ($new_phase) {
            case self::PHASE_VALIDATED:
                $this->date_validation = dol_now();
                // Bloquer le matériel
                $this->blockEquipment();
                break;

            case self::PHASE_CANCELLED:
                $this->date_annulation = dol_now();
                $this->motif_annulation = $reason;
                // Libérer le matériel
                $this->releaseEquipment();
                break;

            case self::PHASE_IN_PROGRESS:
                // Marquer les unités comme louées
                $this->rentEquipment();
                break;

            case self::PHASE_RETURN:
                // Initier le processus de retour
                $this->initiateReturn();
                break;
        }

        $result = $this->update($user);

        if ($result > 0) {
            // Log du changement de phase
            dol_syslog("Event ".$this->id.": phase changed from ".$old_phase." to ".$new_phase.". Reason: ".$reason);
        }

        return $result;
    }

    /**
     * Générer la prochaine référence
     */
 public function getNextNumRef()
    {
        global $conf;

        $mask = $conf->global->EVENTRENTAL_EVENT_ADDON ?: 'EVT{yy}-{0000}';

        // Récupération du dernier numéro pour l'année en cours
        $year_suffix = date('y'); // 25 pour 2025
        
        $sql = "SELECT ref_event FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE ref_event LIKE 'EVT".$year_suffix."-%'";
        $sql .= " AND entity IN (".getEntity($this->table_element).")";
        $sql .= " ORDER BY ref_event DESC";
        $sql .= " LIMIT 1";

        $resql = $this->db->query($sql);
        
        if ($resql) {
            if ($this->db->num_rows($resql) > 0) {
                $obj = $this->db->fetch_object($resql);
                $last_ref = $obj->ref_event;
                
                // Extraction du numéro depuis la référence (EVT25-0005 -> 5)
                if (preg_match('/EVT'.$year_suffix.'-(\d+)(?:-\d+)?$/', $last_ref, $matches)) {
                    $last_num = intval($matches[1]);
                    $next_num = $last_num + 1;
                } else {
                    $next_num = 1;
                }
            } else {
                $next_num = 1;
            }
            
            $this->db->free($resql);
        } else {
            dol_syslog(get_class($this)."::getNextNumRef Erreur SQL: ".$this->db->lasterror(), LOG_ERR);
            $next_num = 1;
        }
        
        // Génération de la nouvelle référence
        $new_ref = 'EVT'.$year_suffix.'-'.str_pad($next_num, 4, '0', STR_PAD_LEFT);
        
        // Vérification que la référence n'existe pas déjà (sécurité supplémentaire)
        $attempts = 0;
        while ($this->checkRefExists($new_ref) && $attempts < 100) {
            $next_num++;
            $new_ref = 'EVT'.$year_suffix.'-'.str_pad($next_num, 4, '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        if ($attempts >= 100) {
            dol_syslog(get_class($this)."::getNextNumRef Impossible de générer une référence unique après 100 tentatives", LOG_ERR);
            return 'EVT'.$year_suffix.'-'.str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        return $new_ref;
    }
    
    /**
     * Vérifier si une référence existe déjà
     */
    private function checkRefExists($ref)
    {
        $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE ref_event = '".$this->db->escape($ref)."'";
        $sql .= " AND entity IN (".getEntity($this->table_element).")";
        
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return ($obj->nb > 0);
        }
        return false;
    }

    /**
     * Return the status label
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->phase_actuelle, $mode);
    }

    /**
     * Return phase status
     */
    public function LibStatut($phase, $mode = 0)
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            $langs->load("eventrental@eventrental");

            $this->labelStatus = array(
                'en_attente' => $langs->transnoentitiesnoconv('PhasePending'),
                'valide' => $langs->transnoentitiesnoconv('PhaseValidated'),
                'en_cours' => $langs->transnoentitiesnoconv('PhaseInProgress'),
                'retour' => $langs->transnoentitiesnoconv('PhaseReturn'),
                'annule' => $langs->transnoentitiesnoconv('PhaseCancelled'),
                'archive' => $langs->transnoentitiesnoconv('PhaseArchived')
            );

            $this->labelStatusShort = $this->labelStatus;
        }

        $statusType = 'status4';
        switch ($phase) {
            case 'en_attente':
                $statusType = 'status1';
                break;
            case 'valide':
                $statusType = 'status4';
                break;
            case 'en_cours':
                $statusType = 'status6';
                break;
            case 'retour':
                $statusType = 'status3';
                break;
            case 'annule':
                $statusType = 'status9';
                break;
            case 'archive':
                $statusType = 'status8';
                break;
        }

        return dolGetStatus($this->labelStatus[$phase], $this->labelStatusShort[$phase], '', $statusType, $mode);
    }

    /**
     * Return clicable link of object (with eventually picto)
     *
     * @param  int     $withpicto                Add picto into link
     * @param  string  $option                   Where point the link ('', 'nolink')
     * @param  int     $max                      Maxlength of shown text
     * @param  int     $short                    Use short name
     * @param  string  $moretitle                Add more text to title tooltip
     * @param  int     $notooltip                1=No tooltip
     * @param  int     $save_lastsearch_value    -1=Auto, 0=No save, 1=Save
     * @return string                            String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $save_lastsearch_value = -1)
    {
        global $conf, $langs, $hookmanager;

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips
        }

        $result = '';

        if ($option == 'nolink') {
            return '<span class="opacitymedium">' . $this->ref_event . '</span>';
        }

        $params = array(
            'id' => $this->id,
            'objecttype' => $this->element.($this->module ? '@'.$this->module : ''),
            'option' => $option,
        );
        $classfortooltip = 'classfortooltip';
        $dataparams = '';
        if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
            $classfortooltip = 'classforajaxtooltip';
            $dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
            $label = '';
        } else {
            $label = implode($this->getTooltipContentArray($params));
        }

        $url = DOL_URL_ROOT.'/custom/eventrental/event/event_card.php?id='.$this->id;

        if ($short) {
            $linkclose = "";
        }

        if ($option == 'blank') {
            $linkclose .= ' target="_blank"';
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart .= ' title="'.dol_escape_htmltag($label, 1).'" class="'.$classfortooltip.'"';
        $linkstart .= $dataparams;
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';

        $result .= $linkstart;

        if (empty($this->showphoto_on_popup)) {
            if ($withpicto) {
                $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
            }
        } else {
            if ($withpicto) {
                require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                list($class, $module) = explode('@', $this->picto);
                $upload_dir = $conf->{$module}->multidir_output[$conf->entity]."/".$class."/".dol_sanitizeFileName($this->ref);
                $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC, 1);
                if (count($filearray)) {
                    $filename = $filearray[0]['name'];
                    $origfile = $upload_dir."/".$filename;
                    $file = $upload_dir."/".$filename;
                }
                if (!empty($filename)) {
                    $result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($class."/".dol_sanitizeFileName($this->ref)."/".$filename).'"></div></div>';
                } else {
                    $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
                }
            }
        }

        if ($withpicto != 2) {
            $result .= ($max ? dol_trunc($this->ref_event, $max) : $this->ref_event);
        }

        $result .= $linkend;

        global $action;
        $hookmanager->initHooks(array($this->element.'dao'));
        $parameters = array('id'=>$this->id, 'getnomurl' => &$result);
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) {
            $result = $hookmanager->resPrint;
        } elseif ($reshook < 0) {
            $this->error = $hookmanager->error;
            $this->errors = $hookmanager->errors;
            $result = -1;
        }

        return $result;
    }

     /**
     * Génère un devis client à partir de l'événement
     *
     * @param User $user Utilisateur qui génère le devis
     * @return int >0 if OK (ID du devis), <0 if KO
     */
    public function generatePropal(User $user)
    {
        global $conf, $langs;
        
        // Vérifications préalables
        if (empty($this->socid)) {
            $this->error = 'Aucun client associé à cet événement';
            return -1;
        }
        
        if (!empty($this->fk_propal)) {
            $this->error = 'Un devis existe déjà pour cet événement (ID: '.$this->fk_propal.')';
            return -2;
        }
        
        require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
        
        $this->db->begin();
        
        try {
            // Création du devis
            $propal = new Propal($this->db);
            
            // En-tête devis
            $propal->socid = $this->socid;
            $propal->date = dol_now();
            $propal->fin_validite = dol_time_plus_duree($this->date_debut, -7, 'd'); // Valid jusqu'à 7j avant événement
            $propal->cond_reglement_id = $conf->global->PROPAL_COND_REGLEMENT_DEFAUT ?: 1;
            $propal->mode_reglement_id = $conf->global->PROPAL_MODE_REGLEMENT_DEFAUT ?: 1;
            
            // Référence et notes
            $propal->ref_client = $this->ref_event;
            $propal->note_private = "Devis généré automatiquement pour l'événement ".$this->nom_evenement;
            
            // Note publique détaillée
            $propal->note_public = "LOCATION MATÉRIEL ÉVÉNEMENTIEL\n\n";
            $propal->note_public .= "Événement: ".$this->nom_evenement."\n";
            $propal->note_public .= "Date: ".dol_print_date($this->date_debut, 'day')." au ".dol_print_date($this->date_fin, 'day')."\n";
            if (!empty($this->lieu_evenement)) {
                $propal->note_public .= "Lieu: ".$this->lieu_evenement."\n";
            }
            if (!empty($this->adresse_evenement)) {
                $propal->note_public .= "Adresse: ".$this->adresse_evenement."\n";
            }
            $propal->note_public .= "\nCaution : client professionnel donc pas de caution demandée; facturation aux frais réelle après évènement en cas de dégradation, perte, vol...";
            
            // Création du devis
            $propal_id = $propal->create($user);
            
            if ($propal_id <= 0) {
                throw new Exception('Erreur création devis: '.$propal->error);
            }
            
                        // Ajout des lignes de devis avec tarifs détaillés
            $sql_lines = "SELECT l.*, p.ref_product, p.label, p.category_event
              FROM ".MAIN_DB_PREFIX."eventrental_event_line l
              LEFT JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = l.fk_product
              WHERE l.fk_event = ".$this->id."
              ORDER BY p.category_event, p.label";

$resql_lines = $this->db->query($sql_lines);

if ($resql_lines) {
    $current_category = '';
    
    while ($obj_line = $this->db->fetch_object($resql_lines)) {
        
        // Calcul nombre de jours - VERSION CORRIGÉE
               // Calcul nombre de jours
                $date_debut = is_string($this->date_debut) ? strtotime($this->date_debut) : $this->date_debut;
                $date_fin = is_string($this->date_fin) ? strtotime($this->date_fin) : $this->date_fin;
                $nb_jours = max(1, ceil(($date_fin - $date_debut) / 86400));
                
                // Prix et calculs
                $prix_jour = floatval($obj_line->prix_unitaire ?: 0);
                $prix_unitaire_devis = floatval($prix_jour * $nb_jours);
                $quantite_devis = intval($obj_line->qty);
                
                // Description
                $description = $obj_line->ref_product.' - '.($obj_line->product_label ?: $obj_line->label);
                $description .= "\nLocation du ".dol_print_date($date_debut, 'day')." au ".dol_print_date($date_fin, 'day');
                $description .= " (".$nb_jours." jour".($nb_jours > 1 ? 's' : '').")";
                
                if ($prix_jour > 0) {
                    $description .= "\nTarif: ".price($prix_jour)."/jour/unite";
                    if ($nb_jours > 1) {
                        $description .= " x ".$nb_jours." jours = ".price($prix_unitaire_devis)."/unite";
                    }
                } else {
                    $description .= "\nTarif: Sur devis";
                    $prix_unitaire_devis = 0.0;
                }
                
                // SIGNATURE CORRECTE D'ADDLINE - VERSION SIMPLE
                $result_line = $propal->addline(
                    $description,              // Description (string)
                    $prix_unitaire_devis,     // Prix unitaire HT (float)
                    $quantite_devis,          // Quantité (int)  
                    20,                       // Taux TVA (float)
                    0,                        // Taux local tax 1
                    0,                        // Taux local tax 2
                    0,                        // fk_product
                    0                         // Remise percent
                );
        
        if ($result_line <= 0) {
            throw new Exception('Erreur ajout ligne devis: '.$propal->error);
        }
    }
}
            
        
            
            // Validation automatique du devis si configuré
            if (!empty($conf->global->EVENTRENTAL_AUTO_VALIDATE_PROPAL)) {
                $result_validate = $propal->valid($user);
                if ($result_validate <= 0) {
                    throw new Exception('Erreur validation devis: '.$propal->error);
                }
            }
            
            // Mise à jour de l'événement
            $sql_update = "UPDATE ".MAIN_DB_PREFIX."eventrental_event SET 
                           fk_propal = ".$propal_id.",
                           date_devis = '".$this->db->idate(dol_now())."'
                           WHERE rowid = ".$this->id;
            
            if (!$this->db->query($sql_update)) {
                throw new Exception('Erreur mise à jour événement: '.$this->db->lasterror());
            }
            
            $this->fk_propal = $propal_id;
            $this->date_devis = dol_now();
            
            $this->db->commit();
            
            return $propal_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->error = $e->getMessage();
            return -10;
        }
    }
    
    /**
     * Récupère le devis associé à l'événement
     *
     * @return Propal|null
     */
    public function getLinkedPropal()
    {
        if (empty($this->fk_propal)) {
            return null;
        }
        
        require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
        
        $propal = new Propal($this->db);
        $result = $propal->fetch($this->fk_propal);
        
        return ($result > 0) ? $propal : null;
    }
    
    /**
     * Récupère la facture générée à partir du devis de l'événement
     *
     * @return Facture|null
     */
    public function getLinkedInvoice()
    {
        $propal = $this->getLinkedPropal();
        if (!$propal) return null;
        
        // Recherche de la facture générée à partir de ce devis
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture 
                WHERE fk_propal = ".$propal->id." 
                ORDER BY rowid DESC LIMIT 1";
        
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            
            require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $facture = new Facture($this->db);
            $result = $facture->fetch($obj->rowid);
            
            return ($result > 0) ? $facture : null;
        }
        
        return null;
    }

    // TODO: Implémenter les méthodes de gestion du matériel
    private function blockEquipment() { /* À implémenter */ }
    private function releaseEquipment() { /* À implémenter */ }
    private function rentEquipment() { /* À implémenter */ }
    private function initiateReturn() { /* À implémenter */ }

    /**
     * Initialise object with example values
     */
    public function initAsSpecimen()
    {
        return $this->initAsSpecimenCommon();
    }
}

/**
 * Class for event lines
 */
class EventRentalLine extends CommonObjectLine
{
    public $fk_event;
    public $fk_product;
    public $qty;
    public $description;
    public $product_label;
    public $prix_unitaire;
    public $remise_percent;
    public $total_ht;
    public $tva_rate;
    public $total_tva;
    public $total_ttc;
    public $rang;
}



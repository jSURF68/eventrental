<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Classe pour gérer les assignations d'unités aux événements
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class EventRentalAssignment extends CommonObject
{
    /**
     * @var string ID pour module
     */
    public $module = 'eventrental';

    /**
     * @var string ID pour l'objet
     */
    public $element = 'eventrental_assignment';

    /**
     * @var string Nom de la table
     */
    public $table_element = 'eventrental_unit_assignment';

    /**
     * Constantes de statut
     */
    const STATUS_ASSIGNED = 'assigne';
    const STATUS_OUT = 'sorti';
    const STATUS_IN_USE = 'en_cours';
    const STATUS_RETURNED = 'retourne';
    const STATUS_INCIDENT = 'incident';

    public $rowid;
    public $entity;
    public $fk_event;
    public $fk_unit;
    public $fk_event_line;
    public $statut;
    public $date_assignation;
    public $date_sortie;
    public $date_retour_prevu;
    public $date_retour_reel;
    public $etat_sortie;
    public $etat_retour;
    public $fk_user_sortie;
    public $fk_user_retour;
    public $observations_sortie;
    public $observations_retour;
    public $incident_description;
    public $cout_incident;
    public $signature_client_sortie;
    public $signature_technicien_sortie;
    public $signature_client_retour;
    public $signature_technicien_retour;
    public $photos_sortie;
    public $photos_retour;
    public $date_creation;
    public $tms;
    public $fk_user_author;
    public $fk_user_modif;

    // Objets liés
    public $event;
    public $unit;

    /**
     * Constructor
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;
        $this->db = $db;
        
        if (is_object($langs)) {
            $langs->load("eventrental@eventrental");
        }
    }

    /**
     * Assigner automatiquement les unités à un événement
     */
    public static function autoAssignUnits($event_id, $db)
    {
        global $user;
        
        // Récupération des lignes de l'événement
        $sql = "SELECT rowid, fk_product, qty FROM ".MAIN_DB_PREFIX."eventrental_event_line WHERE fk_event = ".((int) $event_id);
        $resql = $db->query($sql);
        
        if ($resql) {
            while ($obj_line = $db->fetch_object($resql)) {
                // Pour chaque ligne, assigner des unités disponibles
                $units_needed = $obj_line->qty;
                
                // Recherche des unités disponibles pour ce produit
                $sql_units = "SELECT rowid FROM ".MAIN_DB_PREFIX."eventrental_unit";
                $sql_units .= " WHERE fk_product = ".((int) $obj_line->fk_product);
                $sql_units .= " AND statut = 'disponible'";
                $sql_units .= " ORDER BY nb_locations ASC, date_derniere_location ASC"; // Prioriser les moins utilisées
                $sql_units .= " LIMIT " . $units_needed;
                
                $resql_units = $db->query($sql_units);
                
                if ($resql_units) {
                    while ($obj_unit = $db->fetch_object($resql_units)) {
                        // Création de l'assignation
                        $assignment = new EventRentalAssignment($db);
                        $assignment->fk_event = $event_id;
                        $assignment->fk_unit = $obj_unit->rowid;
                        $assignment->fk_event_line = $obj_line->rowid;
                        $assignment->statut = self::STATUS_ASSIGNED;
                        $assignment->date_assignation = dol_now();
                        
                        $result = $assignment->create($user);
                        
                        if ($result > 0) {
                            // Mise à jour du statut de l'unité
                            $sql_update = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit SET statut = 'reserve' WHERE rowid = ".((int) $obj_unit->rowid);
                            $db->query($sql_update);
                        }
                    }
                }
            }
        }
    }

    /**
     * Create assignment
     */
    public function create(User $user, $notrigger = false)
    {
        global $conf;

        $error = 0;

        // Vérification unicité
        if ($this->checkAssignmentExists()) {
            $this->errors[] = 'Cette unité est déjà assignée à cet événement';
            return -1;
        }

        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';
        $sql .= 'entity, fk_event, fk_unit, fk_event_line, statut, date_assignation, date_creation, fk_user_author';
        $sql .= ') VALUES (';
        $sql .= (!isset($this->entity) ? $conf->entity : $this->entity).',';
        $sql .= ((int) $this->fk_event).',';
        $sql .= ((int) $this->fk_unit).',';
        $sql .= (empty($this->fk_event_line) ? 'NULL' : ((int) $this->fk_event_line)).',';
        $sql .= "'".$this->db->escape($this->statut ?: self::STATUS_ASSIGNED)."',";
        $sql .= "'".$this->db->idate($this->date_assignation ?: dol_now())."',";
        $sql .= "'".$this->db->idate(dol_now())."',";
        $sql .= ((int) $user->id);
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

        if ($error) {
            $this->db->rollback();
            return -1*$error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }

    /**
     * Vérifier si l'assignation existe déjà
     */
    private function checkAssignmentExists()
    {
        $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE fk_event = ".((int) $this->fk_event);
        $sql .= " AND fk_unit = ".((int) $this->fk_unit);
        
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return ($obj->nb > 0);
        }
        return false;
    }

    /**
     * Marquer comme sorti
     */
    public function markAsOut($user_id, $observations = '', $etat = 'bon')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ";
        $sql .= "statut = '".self::STATUS_OUT."',";
        $sql .= "date_sortie = '".$this->db->idate(dol_now())."',";
        $sql .= "fk_user_sortie = ".((int) $user_id).",";
        $sql .= "etat_sortie = '".$this->db->escape($etat)."',";
        $sql .= "observations_sortie = '".$this->db->escape($observations)."'";
        $sql .= " WHERE rowid = ".((int) $this->id);
        
        $result = $this->db->query($sql);
        
        if ($result) {
            // Mise à jour du statut de l'unité
            $sql_unit = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit SET statut = 'loue' WHERE rowid = ".((int) $this->fk_unit);
            $this->db->query($sql_unit);
            
            $this->statut = self::STATUS_OUT;
            $this->date_sortie = dol_now();
            return 1;
        }
        
        return -1;
    }

    /**
     * Marquer comme retourné
     */
    public function markAsReturned($user_id, $observations = '', $etat = 'bon', $incident = '')
    {
        $new_status = empty($incident) ? self::STATUS_RETURNED : self::STATUS_INCIDENT;
        
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ";
        $sql .= "statut = '".$new_status."',";
        $sql .= "date_retour_reel = '".$this->db->idate(dol_now())."',";
        $sql .= "fk_user_retour = ".((int) $user_id).",";
        $sql .= "etat_retour = '".$this->db->escape($etat)."',";
        $sql .= "observations_retour = '".$this->db->escape($observations)."'";
        if (!empty($incident)) {
            $sql .= ", incident_description = '".$this->db->escape($incident)."'";
        }
        $sql .= " WHERE rowid = ".((int) $this->id);
        
        $result = $this->db->query($sql);
        
        if ($result) {
            // Mise à jour du statut de l'unité
            $unit_status = ($new_status == self::STATUS_INCIDENT) ? 'panne' : 'disponible';
            $sql_unit = "UPDATE ".MAIN_DB_PREFIX."eventrental_unit SET statut = '".$unit_status."' WHERE rowid = ".((int) $this->fk_unit);
            $this->db->query($sql_unit);
            
            return 1;
        }
        
        return -1;
    }

    /**
     * Récupérer les assignations d'un événement
     */
    public static function getEventAssignments($event_id, $db)
    {
        $assignments = array();
        
        $sql = "SELECT a.*, u.numero_serie, u.qr_code, p.ref_product, p.label as product_label, p.category_event";
        $sql .= " FROM ".MAIN_DB_PREFIX."eventrental_unit_assignment a";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."eventrental_unit u ON u.rowid = a.fk_unit";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."eventrental_product p ON p.rowid = u.fk_product";
        $sql .= " WHERE a.fk_event = ".((int) $event_id);
        $sql .= " ORDER BY p.category_event, p.label, u.numero_serie";
        
        $resql = $db->query($sql);
        
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $assignments[] = $obj;
            }
        }
        
        return $assignments;
    }
}
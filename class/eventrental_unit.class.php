<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Classe pour gérer les unités individuelles d'équipement
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class EventRentalUnit extends CommonObject
{
    /**
     * @var string ID pour module
     */
    public $module = 'eventrental';

    /**
     * @var string ID pour l'objet
     */
    public $element = 'eventrental_unit';

    /**
     * @var string Nom de la table
     */
    public $table_element = 'eventrental_unit';

    /**
     * @var int Support multi-entité
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Support champs extra
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var int Quantité d’unités dans ce lot (1 par défaut)
     */
    public $quantite;

    /**
     * @var string Nom de la colonne de clé primaire
     */
    public $fk_element = 'fk_eventrental_unit';

    /**
     * Constantes de statut
     */
    const STATUS_AVAILABLE = 'disponible';
    const STATUS_RESERVED = 'reserve';
    const STATUS_RENTED = 'loue';
    const STATUS_IN_TRANSIT = 'transit';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_BROKEN = 'panne';
    const STATUS_RETIRED = 'reforme';

    /**
     * Constantes d'état physique
     */
    const CONDITION_NEW = 'neuf';
    const CONDITION_EXCELLENT = 'excellent';
    const CONDITION_GOOD = 'bon';
    const CONDITION_AVERAGE = 'moyen';
    const CONDITION_WORN = 'use';
    const CONDITION_DEFECTIVE = 'defaillant';

    /**
     * Colonnes de l'objet
     */
    public $fields = array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>0, 'default'=>1),
        'fk_product' => array('type'=>'integer:EventRentalProduct:custom/eventrental/class/eventrental_product.class.php', 'label'=>'Product', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'index'=>1),
        'numero_serie' => array('type'=>'varchar(128)', 'label'=>'SerialNumber', 'enabled'=>'1', 'position'=>15, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'searchall'=>1),
        'qr_code' => array('type'=>'varchar(128)', 'label'=>'QRCode', 'enabled'=>'1', 'position'=>16, 'notnull'=>1, 'visible'=>1, 'index'=>1),
        'numero_interne' => array('type'=>'varchar(128)', 'label'=>'InternalNumber', 'enabled'=>'1', 'position'=>17, 'notnull'=>0, 'visible'=>3),
        'etiquette_physique' => array('type'=>'varchar(128)', 'label'=>'PhysicalLabel', 'enabled'=>'1', 'position'=>18, 'notnull'=>0, 'visible'=>3),
        'statut' => array('type'=>'select', 'label'=>'Status', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'default'=>'disponible', 'arrayofkeyval'=>array('disponible'=>'Disponible', 'reserve'=>'Réservé', 'loue'=>'Loué', 'transit'=>'En transit', 'maintenance'=>'Maintenance', 'panne'=>'En panne', 'reforme'=>'Réformé')),
        'etat_physique' => array('type'=>'select', 'label'=>'PhysicalCondition', 'enabled'=>'1', 'position'=>35, 'notnull'=>1, 'visible'=>1, 'default'=>'neuf', 'arrayofkeyval'=>array('neuf'=>'Neuf', 'excellent'=>'Excellent', 'bon'=>'Bon', 'moyen'=>'Moyen', 'use'=>'Usé', 'defaillant'=>'Défaillant')),
        'emplacement_actuel' => array('type'=>'varchar(255)', 'label'=>'CurrentLocation', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>1),
        'zone_stockage' => array('type'=>'varchar(128)', 'label'=>'StorageZone', 'enabled'=>'1', 'position'=>45, 'notnull'=>0, 'visible'=>3),
        'heures_utilisation' => array('type'=>'integer', 'label'=>'UsageHours', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'nb_locations' => array('type'=>'integer', 'label'=>'RentalCount', 'enabled'=>'1', 'position'=>55, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'date_derniere_location' => array('type'=>'date', 'label'=>'LastRentalDate', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>3),
        'date_achat' => array('type'=>'date', 'label'=>'PurchaseDate', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>3),
        'date_mise_service' => array('type'=>'date', 'label'=>'ServiceDate', 'enabled'=>'1', 'position'=>75, 'notnull'=>0, 'visible'=>3),
        'prochaine_revision' => array('type'=>'date', 'label'=>'NextRevision', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>3),
        'intervalle_revision' => array('type'=>'integer', 'label'=>'RevisionInterval', 'enabled'=>'1', 'position'=>85, 'notnull'=>0, 'visible'=>0, 'default'=>'365'),
        'prix_achat' => array('type'=>'price', 'label'=>'PurchasePrice', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'valeur_actuelle' => array('type'=>'price', 'label'=>'CurrentValue', 'enabled'=>'1', 'position'=>95, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'observations' => array('type'=>'text', 'label'=>'Observations', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>3),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>0),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>0),
        'fk_user_author' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>0),
        'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>0),
    );

    public $rowid;
    public $entity;
    public $fk_product;
    public $numero_serie;
    public $qr_code;
    public $numero_interne;
    public $etiquette_physique;
    public $statut;
    public $etat_physique;
    public $emplacement_actuel;
    public $zone_stockage;
    public $heures_utilisation;
    public $nb_locations;
    public $date_derniere_location;
    public $date_achat;
    public $date_mise_service;
    public $prochaine_revision;
    public $intervalle_revision;
    public $prix_achat;
    public $valeur_actuelle;
    public $observations;
    public $date_creation;
    public $tms;
    public $fk_user_author;
    public $fk_user_modif;

    // Propriétés pour les objets liés
    public $product; // EventRentalProduct

    /**
     * Constructor
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;
        $this->quantite = 1;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Translations
        if (is_object($langs)) {
            $langs->load("eventrental@eventrental");
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
        if (isset($this->numero_serie)) {
            $this->numero_serie = trim($this->numero_serie);
        }
        if (isset($this->qr_code)) {
            $this->qr_code = trim($this->qr_code);
        }

        // Check parameters
        if (empty($this->fk_product)) {
            $this->errors[] = 'ErrorFieldRequired|fk_product';
            return -1;
        }
        if (empty($this->numero_serie)) {
            $this->errors[] = 'ErrorFieldRequired|numero_serie';
            return -1;
        }
        if (empty($this->qr_code)) {
            $this->errors[] = 'ErrorFieldRequired|qr_code';
            return -1;
        }

        // Vérification unicité numéro de série
        if ($this->checkSerialNumberExists($this->numero_serie)) {
            $this->errors[] = 'SerialNumberAlreadyExists';
            return -1;
        }

        // Vérification unicité QR code
        if ($this->checkQRCodeExists($this->qr_code)) {
            $this->errors[] = 'QRCodeAlreadyExists';
            return -1;
        }

        // Insert request
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';
        $sql .= 'entity,';
        $sql .= 'fk_product,';
        $sql .= 'numero_serie,';
        $sql .= 'qr_code,';
        $sql .= 'numero_interne,';
        $sql .= 'etiquette_physique,';
        $sql .= 'statut,';
        $sql .= 'etat_physique,';
        $sql .= 'emplacement_actuel,';
        $sql .= 'zone_stockage,';
        $sql .= 'heures_utilisation,';
        $sql .= 'nb_locations,';
        $sql .= 'date_derniere_location,';
        $sql .= 'date_achat,';
        $sql .= 'date_mise_service,';
        $sql .= 'prochaine_revision,';
        $sql .= 'intervalle_revision,';
        $sql .= 'prix_achat,';
        $sql .= 'valeur_actuelle,';
        $sql .= 'observations,';
        $sql .= 'date_creation,';
        $sql .= 'fk_user_author';
        $sql .= ') VALUES (';
        $sql .= (!isset($this->entity) ? $conf->entity : $this->entity).',';
        $sql .= ' '.((int) $this->fk_product).',';
        $sql .= ' '.(empty($this->numero_serie) ? 'NULL' : "'".$this->db->escape($this->numero_serie)."'").',';
        $sql .= ' '.(empty($this->qr_code) ? 'NULL' : "'".$this->db->escape($this->qr_code)."'").',';
        $sql .= ' '.(empty($this->numero_interne) ? 'NULL' : "'".$this->db->escape($this->numero_interne)."'").',';
        $sql .= ' '.(empty($this->etiquette_physique) ? 'NULL' : "'".$this->db->escape($this->etiquette_physique)."'").',';
        $sql .= ' '.(empty($this->statut) ? "'disponible'" : "'".$this->db->escape($this->statut)."'").',';
        $sql .= ' '.(empty($this->etat_physique) ? "'neuf'" : "'".$this->db->escape($this->etat_physique)."'").',';
        $sql .= ' '.(empty($this->emplacement_actuel) ? 'NULL' : "'".$this->db->escape($this->emplacement_actuel)."'").',';
        $sql .= ' '.(empty($this->zone_stockage) ? 'NULL' : "'".$this->db->escape($this->zone_stockage)."'").',';
        $sql .= ' '.(empty($this->heures_utilisation) ? '0' : ((int) $this->heures_utilisation)).',';
        $sql .= ' '.(empty($this->nb_locations) ? '0' : ((int) $this->nb_locations)).',';
        $sql .= ' '.(empty($this->date_derniere_location) ? 'NULL' : "'".$this->db->idate($this->date_derniere_location)."'").',';
        $sql .= ' '.(empty($this->date_achat) ? 'NULL' : "'".$this->db->idate($this->date_achat)."'").',';
        $sql .= ' '.(empty($this->date_mise_service) ? 'NULL' : "'".$this->db->idate($this->date_mise_service)."'").',';
        $sql .= ' '.(empty($this->prochaine_revision) ? 'NULL' : "'".$this->db->idate($this->prochaine_revision)."'").',';
        $sql .= ' '.(empty($this->intervalle_revision) ? '365' : ((int) $this->intervalle_revision)).',';
        $sql .= ' '.(empty($this->prix_achat) ? '0' : $this->prix_achat).',';
        $sql .= ' '.(empty($this->valeur_actuelle) ? '0' : $this->valeur_actuelle).',';
        $sql .= ' '.(empty($this->observations) ? 'NULL' : "'".$this->db->escape($this->observations)."'").',';
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
            
            // Mise à jour des compteurs du produit parent
            $this->updateProductCounters();
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
     * Fetch product information
     */
    public function fetchProduct()
    {
        if ($this->fk_product > 0) {
            require_once __DIR__.'/eventrental_product.class.php';
            $this->product = new EventRentalProduct($this->db);
            $this->product->fetch($this->fk_product);
        }
    }

    /**
     * Vérifier si un numéro de série existe déjà
     */
    private function checkSerialNumberExists($serial_number, $exclude_id = 0)
    {
        $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE numero_serie = '".$this->db->escape($serial_number)."'";
        if ($exclude_id > 0) {
            $sql .= " AND rowid != ".((int) $exclude_id);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return ($obj->nb > 0);
        }
        return false;
    }

    /**
     * Vérifier si un QR code existe déjà
     */
    private function checkQRCodeExists($qr_code, $exclude_id = 0)
    {
        $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE qr_code = '".$this->db->escape($qr_code)."'";
        if ($exclude_id > 0) {
            $sql .= " AND rowid != ".((int) $exclude_id);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return ($obj->nb > 0);
        }
        return false;
    }

    /**
     * Met à jour les compteurs du produit parent
     */
    public function updateProductCounters()
    {
        require_once __DIR__.'/eventrental_product.class.php';
        $product = new EventRentalProduct($this->db);
        $product->fetch($this->fk_product);
        return $product->updateQuantityCounters();
    }

    /**
     * Générer un QR code automatiquement
     */
    public function generateQRCode($prefix = 'QR')
    {
        $attempts = 0;
        do {
            $qr_code = $prefix . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $exists = $this->checkQRCodeExists($qr_code);
            $attempts++;
        } while ($exists && $attempts < 10);
        
        return $exists ? false : $qr_code;
    }

    /**
     * Change unit status
     */
     public function changeStatus($new_status, $reason = '')
    {
        global $user;
        
        $old_status = $this->statut;
        
        // Mise à jour directe en base
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " SET statut = '".$this->db->escape($new_status)."'";
        $sql .= ", fk_user_modif = ".((int) $user->id);
        $sql .= " WHERE rowid = ".((int) $this->id);
        
        $result = $this->db->query($sql);
        
        if ($result) {
            // Mise à jour de l'objet en mémoire
            $this->statut = $new_status;
            
            // Log du changement
            $this->logStatusChange($old_status, $new_status, $reason);
            
            // Mise à jour compteurs produit
            $this->updateProductCounters();
            
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Update object into database
     */
    public function update(User $user, $notrigger = false)
    {
        $error = 0;

        // Clean parameters
        if (isset($this->numero_serie)) {
            $this->numero_serie = trim($this->numero_serie);
        }
        if (isset($this->qr_code)) {
            $this->qr_code = trim($this->qr_code);
        }

        // Update request
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET ';
        $sql .= 'statut = '.(empty($this->statut) ? "'disponible'" : "'".$this->db->escape($this->statut)."'").',';
        $sql .= 'etat_physique = '.(empty($this->etat_physique) ? "'neuf'" : "'".$this->db->escape($this->etat_physique)."'").',';
        $sql .= 'emplacement_actuel = '.(empty($this->emplacement_actuel) ? 'NULL' : "'".$this->db->escape($this->emplacement_actuel)."'").',';
        $sql .= 'zone_stockage = '.(empty($this->zone_stockage) ? 'NULL' : "'".$this->db->escape($this->zone_stockage)."'").',';
        $sql .= 'observations = '.(empty($this->observations) ? 'NULL' : "'".$this->db->escape($this->observations)."'").',';
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
     * Log des changements de statut
     */
    private function logStatusChange($old_status, $new_status, $reason)
    {
        // TODO: Implémenter le logging des changements de statut
        dol_syslog("Unit ".$this->id.": status changed from ".$old_status." to ".$new_status.". Reason: ".$reason);
    }

    /**
     * Return the label of the status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     * Return the status label
     */
    public function LibStatut($status, $mode = 0)
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            $langs->load("eventrental@eventrental");
            
            $this->labelStatus = array(
                'disponible' => $langs->transnoentitiesnoconv('Available'),
                'reserve' => $langs->transnoentitiesnoconv('Reserved'),
                'loue' => $langs->transnoentitiesnoconv('Rented'),
                'transit' => $langs->transnoentitiesnoconv('InTransit'),
                'maintenance' => $langs->transnoentitiesnoconv('Maintenance'),
                'panne' => $langs->transnoentitiesnoconv('Broken'),
                'reforme' => $langs->transnoentitiesnoconv('Retired')
            );
            
            $this->labelStatusShort = $this->labelStatus;
        }

        $statusType = 'status4';
        switch ($status) {
            case 'disponible':
                $statusType = 'status4';
                break;
            case 'reserve':
                $statusType = 'status1';
                break;
            case 'loue':
                $statusType = 'status6';
                break;
            case 'transit':
                $statusType = 'status1';
                break;
            case 'maintenance':
                $statusType = 'status3';
                break;
            case 'panne':
                $statusType = 'status8';
                break;
            case 'reforme':
                $statusType = 'status9';
                break;
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     * Initialise object with example values
     */
    public function initAsSpecimen()
    {
        // Set here init that are not common fields
        return $this->initAsSpecimenCommon();
    }
}

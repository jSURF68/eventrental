<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Classe pour gérer les produits événementiels
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class EventRentalProduct extends CommonObject
{
    /**
     * @var string ID pour module, utilisé pour l'ID de permission et pour les répertoires/fichiers nommés
     */
    public $module = 'eventrental';

    /**
     * Constantes de statut
     */
    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * @var string ID pour l'objet ('myobject_mymodule' ou 'mymodule_myobject' ou 'myobject')
     */
    public $element = 'eventrental_product';

    /**
     * @var string Nom de la table (sans préfixe, utilisé partout)
     */
    public $table_element = 'eventrental_product';

    /**
     * @var int  Support multi-entité (1=support, 0=non)
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int  Support extraction de document (1=support, 0=non)
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string Nom de la colonne de clé primaire de la table
     */
    public $fk_element = 'fk_eventrental_product';

    /**
     * Colonnes de l'objet dans la base de données
     * @var array
     */
    public $fields = array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
        'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>0, 'default'=>1),
        'ref_product' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'0', 'default'=>'', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Référence produit"),
        'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth300', 'help'=>'', 'showoncombobox'=>'2'),
        'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>3),
        'category_event' => array('type'=>'select', 'label'=>'CategoryEvent', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('son'=>'Son/Audio', 'eclairage'=>'Éclairage', 'scene'=>'Scène/Structure', 'video'=>'Vidéo', 'cable'=>'Cable', 'accessoire'=>'Accessoire', 'Consommable'=>'consommable', 'mobilier'=>'Mobilier', 'decoration'=>'Décoration', 'technique'=>'Technique')),
        'sub_category' => array('type'=>'varchar(128)', 'label'=>'SubCategory', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>1),
        'prix_location_jour' => array('type'=>'price', 'label'=>'PricePerDay', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>1),
        'prix_location_weekend' => array('type'=>'price', 'label'=>'PriceWeekend', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>1),
        'caution_unitaire' => array('type'=>'price', 'label'=>'SecurityDeposit', 'enabled'=>'1', 'position'=>90, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>1),
        'qty_total' => array('type'=>'integer', 'label'=>'QtyTotal', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>1, 'default'=>'0'),
        'qty_disponible' => array('type'=>'integer', 'label'=>'QtyAvailable', 'enabled'=>'1', 'position'=>110, 'notnull'=>0, 'visible'=>1, 'default'=>'0'),
        'qty_louee' => array('type'=>'integer', 'label'=>'QtyRented', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>1, 'default'=>'0'),
        'qty_maintenance' => array('type'=>'integer', 'label'=>'QtyMaintenance', 'enabled'=>'1', 'position'=>130, 'notnull'=>0, 'visible'=>1, 'default'=>'0'),
        'qty_panne' => array('type'=>'integer', 'label'=>'QtyBroken', 'enabled'=>'1', 'position'=>140, 'notnull'=>0, 'visible'=>1, 'default'=>'0'),
        'poids' => array('type'=>'double(8,3)', 'label'=>'Weight', 'enabled'=>'1', 'position'=>150, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'dimensions' => array('type'=>'varchar(128)', 'label'=>'Dimensions', 'enabled'=>'1', 'position'=>160, 'notnull'=>0, 'visible'=>3),
        'puissance_electrique' => array('type'=>'integer', 'label'=>'PowerConsumption', 'enabled'=>'1', 'position'=>170, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'delai_preparation' => array('type'=>'integer', 'label'=>'PreparationTime', 'enabled'=>'1', 'position'=>180, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'nb_techniciens_requis' => array('type'=>'integer', 'label'=>'TechniciansRequired', 'enabled'=>'1', 'position'=>190, 'notnull'=>0, 'visible'=>3, 'default'=>'0'),
        'compatible_exterieur' => array('type'=>'boolean', 'label'=>'OutdoorCompatible', 'enabled'=>'1', 'position'=>200, 'notnull'=>0, 'visible'=>3, 'default'=>'1'),
        'statut' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>500, 'notnull'=>0, 'visible'=>1, 'default'=>'1'),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>0),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>0),
        'fk_user_author' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>0, 'foreignkey'=>'user.rowid'),
        'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>0),
    );

    public $rowid;
    public $entity;
    public $ref_product;
    public $label;
    public $description;
    public $category_event;
    public $sub_category;
    public $prix_location_jour;
    public $prix_location_weekend;
    public $caution_unitaire;
    public $qty_total;
    public $qty_disponible;
    public $qty_louee;
    public $qty_maintenance;
    public $qty_panne;
    public $poids;
    public $dimensions;
    public $puissance_electrique;
    public $delai_preparation;
    public $nb_techniciens_requis;
    public $compatible_exterieur;
    public $statut;
    public $date_creation;
    public $tms;
    public $fk_user_author;
    public $fk_user_modif;

    /**
     * Constructor
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

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
        if (isset($this->ref_product)) {
            $this->ref_product = trim($this->ref_product);
        }
        if (isset($this->label)) {
            $this->label = trim($this->label);
        }
        if (isset($this->description)) {
            $this->description = trim($this->description);
        }
        if (isset($this->category_event)) {
            $this->category_event = trim($this->category_event);
        }
        if (isset($this->sub_category)) {
            $this->sub_category = trim($this->sub_category);
        }

        // Check parameters
        if (empty($this->ref_product)) {
            $this->errors[] = 'ErrorFieldRequired|ref_product';
            return -1;
        }
        if (empty($this->label)) {
            $this->errors[] = 'ErrorFieldRequired|label';
            return -1;
        }

        // Insert request
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';
        $sql .= 'entity,';
        $sql .= 'ref_product,';
        $sql .= 'label,';
        $sql .= 'description,';
        $sql .= 'category_event,';
        $sql .= 'sub_category,';
        $sql .= 'prix_location_jour,';
        $sql .= 'prix_location_weekend,';
        $sql .= 'caution_unitaire,';
        $sql .= 'qty_total,';
        $sql .= 'qty_disponible,';
        $sql .= 'qty_louee,';
        $sql .= 'qty_maintenance,';
        $sql .= 'qty_panne,';
        $sql .= 'poids,';
        $sql .= 'dimensions,';
        $sql .= 'puissance_electrique,';
        $sql .= 'delai_preparation,';
        $sql .= 'nb_techniciens_requis,';
        $sql .= 'compatible_exterieur,';
        $sql .= 'statut,';
        $sql .= 'date_creation,';
        $sql .= 'fk_user_author';
        $sql .= ') VALUES (';
        $sql .= (!isset($this->entity) ? $conf->entity : $this->entity).',';
        $sql .= ' '.(empty($this->ref_product) ? 'NULL' : "'".$this->db->escape($this->ref_product)."'").',';
        $sql .= ' '.(empty($this->label) ? 'NULL' : "'".$this->db->escape($this->label)."'").',';
        $sql .= ' '.(empty($this->description) ? 'NULL' : "'".$this->db->escape($this->description)."'").',';
        $sql .= ' '.(empty($this->category_event) ? 'NULL' : "'".$this->db->escape($this->category_event)."'").',';
        $sql .= ' '.(empty($this->sub_category) ? 'NULL' : "'".$this->db->escape($this->sub_category)."'").',';
        $sql .= ' '.(empty($this->prix_location_jour) ? '0' : $this->prix_location_jour).',';
        $sql .= ' '.(empty($this->prix_location_weekend) ? '0' : $this->prix_location_weekend).',';
        $sql .= ' '.(empty($this->caution_unitaire) ? '0' : $this->caution_unitaire).',';
        $sql .= ' '.(empty($this->qty_total) ? '0' : $this->qty_total).',';
        $sql .= ' '.(empty($this->qty_disponible) ? '0' : $this->qty_disponible).',';
        $sql .= ' '.(empty($this->qty_louee) ? '0' : $this->qty_louee).',';
        $sql .= ' '.(empty($this->qty_maintenance) ? '0' : $this->qty_maintenance).',';
        $sql .= ' '.(empty($this->qty_panne) ? '0' : $this->qty_panne).',';
        $sql .= ' '.(empty($this->poids) ? '0' : $this->poids).',';
        $sql .= ' '.(empty($this->dimensions) ? 'NULL' : "'".$this->db->escape($this->dimensions)."'").',';
        $sql .= ' '.(empty($this->puissance_electrique) ? '0' : $this->puissance_electrique).',';
        $sql .= ' '.(empty($this->delai_preparation) ? '0' : $this->delai_preparation).',';
        $sql .= ' '.(empty($this->nb_techniciens_requis) ? '0' : $this->nb_techniciens_requis).',';
        $sql .= ' '.(empty($this->compatible_exterieur) ? '0' : $this->compatible_exterieur).',';
        $sql .= ' '.(empty($this->statut) ? '1' : $this->statut).',';
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

            // Uncomment this and change EVENTRENTAL_PRODUCT_ADDON_PDF_ODT_PATH to activate template
            // $this->call_trigger('EVENTRENTAL_PRODUCT_CREATE', $user);
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
        if (isset($this->ref_product)) {
            $this->ref_product = trim($this->ref_product);
        }
        if (isset($this->label)) {
            $this->label = trim($this->label);
        }
        if (isset($this->description)) {
            $this->description = trim($this->description);
        }
        if (isset($this->category_event)) {
            $this->category_event = trim($this->category_event);
        }
        if (isset($this->sub_category)) {
            $this->sub_category = trim($this->sub_category);
        }

        // Check parameters
        if (empty($this->label)) {
            $this->errors[] = 'ErrorFieldRequired|label';
            return -1;
        }

        // Update request
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET ';
        $sql .= 'label = '.(empty($this->label) ? 'NULL' : "'".$this->db->escape($this->label)."'").',';
        $sql .= 'description = '.(empty($this->description) ? 'NULL' : "'".$this->db->escape($this->description)."'").',';
        $sql .= 'category_event = '.(empty($this->category_event) ? 'NULL' : "'".$this->db->escape($this->category_event)."'").',';
        $sql .= 'sub_category = '.(empty($this->sub_category) ? 'NULL' : "'".$this->db->escape($this->sub_category)."'").',';
        $sql .= 'prix_location_jour = '.(empty($this->prix_location_jour) ? '0' : $this->prix_location_jour).',';
        $sql .= 'prix_location_weekend = '.(empty($this->prix_location_weekend) ? '0' : $this->prix_location_weekend).',';
        $sql .= 'caution_unitaire = '.(empty($this->caution_unitaire) ? '0' : $this->caution_unitaire).',';
        $sql .= 'poids = '.(empty($this->poids) ? '0' : $this->poids).',';
        $sql .= 'dimensions = '.(empty($this->dimensions) ? 'NULL' : "'".$this->db->escape($this->dimensions)."'").',';
        $sql .= 'puissance_electrique = '.(empty($this->puissance_electrique) ? '0' : $this->puissance_electrique).',';
        $sql .= 'delai_preparation = '.(empty($this->delai_preparation) ? '0' : $this->delai_preparation).',';
        $sql .= 'nb_techniciens_requis = '.(empty($this->nb_techniciens_requis) ? '0' : $this->nb_techniciens_requis).',';
        $sql .= 'compatible_exterieur = '.(empty($this->compatible_exterieur) ? '0' : $this->compatible_exterieur).',';
        $sql .= 'fk_user_modif = '.((int) $user->id);
        $sql .= ' WHERE rowid = '.((int) $this->id);

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error '.$this->db->lasterror();
        }

        if (!$error && !$notrigger) {
            // Call triggers
            //$result = $this->call_trigger('EVENTRENTAL_PRODUCT_MODIFY', $user);
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
     * Clone object into another one
     */
    public function createFromClone(User $user, $fromid)
    {
        global $langs;

        $error = 0;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $object = new static($this->db);

        $this->db->begin();

        // Load source object
        $result = $object->fetchCommon($fromid);
        if ($result > 0 && !empty($object->table_element_line)) {
            $object->fetchLines();
        }

        // get lines so they will be clone
        //foreach($this->lines as $line)
        //  $line->fetch_optionals();

        // Reset some properties
        unset($object->id);
        unset($object->fk_user_creat);
        unset($object->import_key);

        // Clear fields
        if (property_exists($object, 'ref_product')) {
            $object->ref_product = "copy_of_".$object->ref_product;
        }
        if (property_exists($object, 'label')) {
            $object->label = $langs->trans("CopyOf")." ".$object->label;
        }
        if (property_exists($object, 'date_creation')) {
            $object->date_creation = dol_now();
        }

        // Create clone
        $object->context['createfromclone'] = 'createfromclone';
        $result = $object->createCommon($user);
        if ($result < 0) {
            $error++;
            $this->error = $object->error;
            $this->errors = $object->errors;
        }

        if (!$error) {
            // copy internal contacts
            if ($this->copy_linked_contact($object, 'internal') < 0) {
                $error++;
            }
        }

        if (!$error) {
            // copy external contacts if same company
            if (property_exists($this, 'fk_soc') && $this->fk_soc == $object->fk_soc) {
                if ($this->copy_linked_contact($object, 'external') < 0) {
                    $error++;
                }
            }
        }

        unset($object->context['createfromclone']);

        // End
        if (!$error) {
            $this->db->commit();
            return $object;
        } else {
            $this->db->rollback();
            return -1;
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
     * Return the label of the status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     * Return the status
     */
    public function LibStatut($status, $mode = 0)
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            $langs->load("eventrental@eventrental");
            $this->labelStatus[1] = $langs->transnoentitiesnoconv('Enabled');
            $this->labelStatus[0] = $langs->transnoentitiesnoconv('Disabled');
            $this->labelStatusShort[1] = $langs->transnoentitiesnoconv('Enabled');
            $this->labelStatusShort[0] = $langs->transnoentitiesnoconv('Disabled');
        }

        $statusType = 'status'.$status;
        if ($status == 1) {
            $statusType = 'status4';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     * Load the info information in the object
     */
    public function info($id)
    {
        $sql = "SELECT rowid, date_creation as datec, tms as datem,";
        $sql .= " fk_user_author, fk_user_modif";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.rowid = ".((int) $id);

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                $this->user_creation_id = $obj->fk_user_author;
                $this->user_modification_id = $obj->fk_user_modif;
                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
            }

            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     * Initialise object with example values
     * Id must be 0 if object instance is a specimen
     */
    public function initAsSpecimen()
    {
        // Set here init that are not commonf fields
        // $this->property1 = ...
        // $this->property2 = ...

        return $this->initAsSpecimenCommon();
    }

    /**
     * Retourne le nombre d'unités disponibles pour une période
     */
    public function getAvailableUnits($date_debut, $date_fin)
    {
        // TODO: Implémenter la logique de vérification des disponibilités
        // en fonction des réservations existantes
        
        return $this->qty_disponible;
    }

    /**
     * Met à jour les compteurs de quantités
     */
    public function updateQuantityCounters()
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ";
        $sql .= " qty_total = (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit WHERE fk_product = ".$this->id."),";
        $sql .= " qty_disponible = (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit WHERE fk_product = ".$this->id." AND statut = 'disponible'),";
        $sql .= " qty_louee = (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit WHERE fk_product = ".$this->id." AND statut = 'loue'),";
        $sql .= " qty_maintenance = (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit WHERE fk_product = ".$this->id." AND statut = 'maintenance'),";
        $sql .= " qty_panne = (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."eventrental_unit WHERE fk_product = ".$this->id." AND statut = 'panne')";
        $sql .= " WHERE rowid = ".$this->id;

        $result = $this->db->query($sql);
        if ($result) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }
}
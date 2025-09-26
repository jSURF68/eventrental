<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module EvenRental
 */
class modEvenRental extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     */
    public function __construct($db)
    {
        $this->db = $db;
        
        // Id for module (must be unique)
        $this->numero = 500020; // TODO: Obtenir un numéro officiel
        
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'eventrental';
        
        // Family can be 'base' (core modules), 'crm', 'financial', 'hr', 'projects', 'products', 'ecm', 'technic' (transverse modules), 'interface' (interface modules), 'other'
        $this->family = "crm";
        
        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '90';
        
        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleEvenRentalName' not found (EvenRental is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        
        // Module description, used if translation string 'ModuleEvenRentalDesc' not found (EvenRental is name of module).
        $this->description = "Module de gestion de location événementiel";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "Module complet de gestion de location de matériel événementiel avec suivi des unités individuelles, gestion des phases d'événements, planning temporel et interface mobile terrain.";
        
        // Version
        $this->version = '1.0.0';
        // Url to the file with your last version of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';
        
        // Key used in llx_const table to save module status enabled/disabled (where EVENTRENTAL is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        
        // Name of image file used for this module.
        $this->picto = 'generic'; // TODO: Créer une icône
        
        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            'triggers' => 1,                          // Set this to 1 if module has its own trigger directory (core/triggers)
            'login' => 0,                             // Set this to 1 if module has its own login method file (core/login)
            'substitutions' => 0,                     // Set this to 1 if module has its own substitution function file (core/substitutions)
            'menus' => 0,                             // Set this to 1 if module has its own menus handler directory (core/menus)
            'theme' => 0,                             // Set this to 1 if module has its own theme directory (theme)
            'tpl' => 0,                               // Set this to 1 if module overwrite template dir (core/tpl)
            'barcode' => 0,                           // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'models' => 1,                            // Set this to 1 if module has its own models directory (core/modules/xxx)
            'printing' => 0,                          // Set this to 1 if module has its own printing directory (core/modules/printing)
            'css' => array('/eventrental/css/eventrental.css.php'),    // Set this to relative path of css file if module has its own css file
            'js' => array('/eventrental/js/eventrental.js.php'),       // Set this to relative path of js file if module has its own js file
            'hooks' => array(                         // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>hookmanager' *" on source code.
                'thirdpartycard',
                'invoicecard', 
                'propalcard',
                'expeditioncard'
            ),
        );
        
        // Data directories to create when module is enabled.
        $this->dirs = array("/eventrental/temp", "/eventrental/checkout", "/eventrental/equipment");
                // Documents templates
        $this->const[] = array(
            0 => "EVENTRENTAL_ADDON_PDF",
            1 => "chaine",
            2 => "sortie_standard",
            3 => 'Name of PDF model of EventRental',
            4 => 0
        );

        // Configuration répertoire documents
        $this->const[] = array(
            0 => "EVENTRENTAL_OUTPUTDIR",
            1 => "chaine", 
            2 => DOL_DATA_ROOT."/eventrental",
            3 => 'Directory where to store generated documents for EventRental module',
            4 => 0
        );
        
        // Config pages. Put here list of php page, stored into eventrental/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@eventrental");
        
        // Dependencies
        $this->hidden = false;          // A condition to hide module
        $this->depends = array('modSociete');         // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->requiredby = array();    // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array();  // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
        
        // The language file dedicated to your module
        $this->langfiles = array("eventrental@eventrental");
        
        // Prerequisites
        $this->phpmin = array(7, 4);                    // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(21, 0);   // Minimum version of Dolibarr required by module
        
        // Messages at activation
        $this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        $this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        //$this->automatic_activation = array('FR'=>'EvenRentalWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true;                            // If true, can't be disabled
        
        // Constants
        $this->const = array();
        
        // Boxes/Widgets
        $this->boxes = array();

        // Document templates
        $this->doctemplates = array(
            'eventrental_event' => array(
                'label' => 'EventRentalEvent',
                'type' => 'eventrental_event',
                'template' => 'eventrental',
                'suboptions' => '',
                'scandir' => 'EVENTRENTAL_ADDON_PDF_ODT_PATH',
                'default' => 1
            ),
        );

        // PDF Models for EventRental
        $this->const[] = array(
            0 => "EVENTRENTAL_ADDON_PDF",
            1 => "chaine",
            2 => "sortie_standard",
            3 => 'Name of PDF model of EventRental',
            4 => 0
        );
        
        // Cronjobs (modulepart:label:script:note:frequency:status:test:priority:params)
        $this->cronjobs = array();
        
        // Permissions provided by this module
        $this->rights = array();
        $r = 0;
        
        // Add here list of permission defined by an id, a label, a boolean and two constant strings.
        
        // ÉVÉNEMENTS
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = 'Consulter les événements';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'event';
        $this->rights[$r][5] = 'read';
        $r++;
        
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = 'Créer/modifier les événements';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'event';
        $this->rights[$r][5] = 'write';
        $r++;
        
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = 'Supprimer les événements';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'event';
        $this->rights[$r][5] = 'delete';
        $r++;
        
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = 'Valider les événements';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'event';
        $this->rights[$r][5] = 'validate';
        $r++;
        
        // MATÉRIEL
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = 'Consulter le matériel';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'equipment';
        $this->rights[$r][5] = 'read';
        $r++;
        
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = 'Gérer le matériel';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'equipment';
        $this->rights[$r][5] = 'manage';
        $r++;
        
        // MOBILE
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = 'Utiliser l\'application mobile';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'mobile';
        $this->rights[$r][5] = 'use';
        $r++;
        
        // Main menu entries to add
        $this->menu = array();
        $r = 0;
        
        // Menu principal
        $this->menu[$r++] = array(
            'fk_menu'=>'',      
            'type'=>'top',      
            'titre'=>'EvenRental',
            'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
            'mainmenu'=>'eventrental',
            'leftmenu'=>'',
            'url'=>'/custom/eventrental/index.php',
            'langs'=>'eventrental@eventrental',
            'position'=>1000,
            'enabled'=>'$conf->eventrental->enabled',
            'perms'=>'$user->rights->eventrental->event->read',  // ✅ Syntaxe corrigée
            'target'=>'',
            'user'=>2,
        );
        
        // Sous-menus
        $this->menu[$r++] = array(
            'fk_menu'=>'fk_mainmenu=eventrental',
            'type'=>'left',
            'titre'=>'Événements',
            'mainmenu'=>'eventrental',
            'leftmenu'=>'events',
            'url'=>'/custom/eventrental/event/list.php',
            'langs'=>'eventrental@eventrental',
            'position'=>100,
            'enabled'=>'$conf->eventrental->enabled',
            'perms'=>'$user->rights->eventrental->event->read',  // ✅ Syntaxe corrigée
            'target'=>'',
            'user'=>2,
        );
        
        $this->menu[$r++] = array(
            'fk_menu'=>'fk_mainmenu=eventrental',
            'type'=>'left', 
            'titre'=>'Matériel',
            'mainmenu'=>'eventrental',
            'leftmenu'=>'equipment',
            'url'=>'/custom/eventrental/equipment/list.php',
            'langs'=>'eventrental@eventrental',
            'position'=>200,
            'enabled'=>'$conf->eventrental->enabled',
            'perms'=>'$user->rights->eventrental->equipment->read',  // ✅ Syntaxe corrigée
            'target'=>'',
            'user'=>2,
        );

        // Dans la section des menus, ajoutez après les autres entrées equipment :
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=eventrental,fk_leftmenu=eventrental_equipment',
            'type' => 'left',
            'titre' => 'Gestion en Lot',
            'mainmenu' => 'eventrental',
            'leftmenu' => 'eventrental_equipment_bulk',
            'url' => '/custom/eventrental/equipment/bulk_management.php',
            'langs' => 'eventrental@eventrental',
            'position' => 300 + $r,
            'enabled' => '$conf->eventrental->enabled',
            'perms' => '$user->rights->eventrental->equipment->write',
            'target' => '',
            'user' => 2,
        );
        
        $this->menu[$r++] = array(
            'fk_menu'=>'fk_mainmenu=eventrental',
            'type'=>'left',
            'titre'=>'Planning',
            'mainmenu'=>'eventrental',
            'leftmenu'=>'planning',
            'url'=>'/custom/eventrental/planning/index.php',
            'langs'=>'eventrental@eventrental',
            'position'=>300,
            'enabled'=>'$conf->eventrental->enabled',
            'perms'=>'$user->rights->eventrental->event->read',  // ✅ Syntaxe corrigée
            'target'=>'',
            'user'=>2,
        );
    }
}
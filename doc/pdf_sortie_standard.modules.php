<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Classe pour générer la fiche de sortie PDF
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/eventrental/modules_eventrental.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

class pdf_sortie_standard extends ModelePDFEventRental
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string model name
     */
    public $name;

    /**
     * @var string model description (short)
     */
    public $description;

    /**
     * @var int     Save the name of generated file as the main doc when generating a doc with this template
     */
    public $update_main_doc_field;

    /**
     * @var string document type
     */
    public $type;

    /**
     * @var array Minimum version of PHP required by module.
     */
    public $phpmin = array(7, 0);

    /**
     * Dolibarr version of the loaded document
     * @var string
     */
    public $version = 'dolibarr';

    /**
     * Constructor
     */
    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $langs->loadLangs(array("main", "companies", "eventrental@eventrental"));

        $this->db = $db;
        $this->name = "sortie_standard";
        $this->description = $langs->trans('FicheSortieStandard');
        $this->update_main_doc_field = 1;

        $this->type = 'pdf';
        $this->phpmin = array(7, 0); // Minimum version of PHP required by module
        $this->version = '1.0';

        $this->option_logo = 1;                    // Display logo
        $this->option_tva = 0;                     // Manage the vat option FACTURE_TVAOPTION
        $this->option_modereg = 0;                 // Display payment mode
        $this->option_condreg = 0;                 // Display payment terms
        $this->option_multilang = 0;               // Available in several languages
        $this->option_escompte = 0;                // Displays if there has been a discount
        $this->option_credit_note = 0;             // Support credit notes
        $this->option_freetext = 1;                // Support add of a personalised text
        $this->option_draft_watermark = 0;         // Support add of a watermark on drafts

        // Get source company
        $this->emetteur = $mysoc;
        if (empty($this->emetteur->country_code)) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined

        // Define position of columns
        $this->posxdesc = $this->marge_gauche + 1;  // Position colonne description
        $this->posxqty = $this->page_largeur - $this->marge_droite - 31; // Position colonne quantite
        $this->posxup = $this->page_largeur - $this->marge_droite - 40;  // Position colonne prix unitaire
        $this->posxtotalht = $this->page_largeur - $this->marge_droite - 3; // Position colonne total HT
    }

    /**
     * Function to build pdf onto disk
     */
    public function write_file($event, $outputlangs = '', $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc, $hookmanager;

        if (!is_object($outputlangs)) $outputlangs = $langs;

        $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "eventrental@eventrental"));

        $nblines = count($assignments = EventRentalAssignment::getEventAssignments($event->id, $this->db));

        if ($conf->eventrental->dir_output) {
            $object = $event;

            // Definition of $dir and $file
            if ($object->specimen) {
                $dir = $conf->eventrental->dir_output.'/eventrental_event';
                $file = $dir.'/SPECIMEN.pdf';
            } else {
                $objectref = dol_sanitizeFileName($object->ref_event);
                $dir = $conf->eventrental->dir_output.'/eventrental_event/'.$objectref;
                $file = $dir.'/'.$objectref.'_fiche_sortie.pdf';
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

                // Set nblines with the new lines content after hook
                $nblines = count($assignments);

                // Create pdf instance
                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
                $pdf->SetAutoPageBreak(1, 0);

                $heightforinfotot = 40; // Height reserved to output the info and total part
                $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
                $heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

                // Set path to the background PDF File
                if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref_event));
                $pdf->SetSubject($outputlangs->transnoentities("FicheSortie"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref_event)." ".$outputlangs->transnoentities("FicheSortie"));
                if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

                // New page
                $pdf->AddPage();
                if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                $pagenb++;
                $top_shift = $this->_pagehead($pdf, $event, 1, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, ''); // Set interline to 3
                $pdf->SetTextColor(0, 0, 0);

                $tab_top = 90;
                $tab_top_newpage = (!empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 80);
                $tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

                // Display notes
                $notetoshow = empty($object->note_public) ? '' : $object->note_public;
                if ($notetoshow) {
                    $tab_top = 88;

                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                    $nexY = $pdf->GetY();
                    $height_note = $nexY - $tab_top;

                    // Rect takes a length in 3rd parameter
                    $pdf->SetDrawColor(192, 192, 192);
                    $pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

                    $tab_top = $nexY + 6;
                } else {
                    $height_note = 0;
                }

                $iniY = $tab_top + 7;
                $curY = $tab_top + 7;
                $nexY = $tab_top + 7;

                $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $event->currency_code);
                $bottomlasttab = $tab_top + $this->_tableau_info($pdf, $event, $assignments, $tab_top, $outputlangs);

                // Pagefoot
                $this->_pagefoot($pdf, $event, $outputlangs, 1);
                if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

                $pdf->Close();

                $pdf->Output($file, 'F');

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

                dolChmod($file);

                $this->result = array('fullpath'=>$file);

                return 1; // No error
            } else {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->transnoentities("ErrorConstantNotDefined", "EVENTRENTAL_OUTPUTDIR");
            return 0;
        }
    }

     /**
     * Show table for lines
     */
    protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
    {
        global $conf;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Force to disable hidetop and hidebottom
        $hidebottom = 0;
        if ($hidetop) $hidetop = -1;

        $currency = !empty($currency) ? $currency : $conf->currency;
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Amount in (at tab_top - 1)
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $default_font_size - 2);

        if (empty($hidetop)) {
            $titre = $outputlangs->transnoentities("MaterialList");
            $pdf->SetXY($this->marge_gauche, $tab_top);
            $pdf->MultiCell(190, 5, $titre, 0, 'L', 1);

            $pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5);

            $pdf->SetFont('', 'B', $default_font_size - 3);

            $pdf->SetXY($this->marge_gauche, $tab_top + 6);
            $pdf->MultiCell(25, 4, $outputlangs->transnoentities("Ref"), 0, 'L');

            $pdf->SetXY($this->marge_gauche + 25, $tab_top + 6);
            $pdf->MultiCell(60, 4, $outputlangs->transnoentities("Product"), 0, 'L');

            $pdf->SetXY($this->marge_gauche + 85, $tab_top + 6);
            $pdf->MultiCell(25, 4, $outputlangs->transnoentities("SerialNumber"), 0, 'L');

            $pdf->SetXY($this->marge_gauche + 110, $tab_top + 6);
            $pdf->MultiCell(25, 4, $outputlangs->transnoentities("QRCode"), 0, 'C');

            $pdf->SetXY($this->marge_gauche + 135, $tab_top + 6);
            $pdf->MultiCell(20, 4, $outputlangs->transnoentities("Status"), 0, 'C');

            $pdf->SetXY($this->marge_gauche + 155, $tab_top + 6);
            $pdf->MultiCell(35, 4, $outputlangs->transnoentities("Observations"), 0, 'C');

            $pdf->line($this->marge_gauche, $tab_top + 11, $this->page_largeur - $this->marge_droite, $tab_top + 11);
        }

        $pdf->SetFont('', '', $default_font_size - 3);

        return ($tab_top + 12);
    }

    /**
     * Show info table
     */
    protected function _tableau_info(&$pdf, $object, $assignments, $tab_top, $outputlangs)
    {
        global $conf, $mysoc;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $pdf->SetFont('', '', $default_font_size - 3);

        $curY = $tab_top + 1;
        
        // Groupement par catégorie pour une meilleure lisibilité
        $assignments_by_category = array();
        foreach ($assignments as $assignment) {
            $category = $assignment->category_event ?: 'autre';
            if (!isset($assignments_by_category[$category])) {
                $assignments_by_category[$category] = array();
            }
            $assignments_by_category[$category][] = $assignment;
        }

        $category_icons = array(
            'son' => '🎵',
            'eclairage' => '💡',
            'scene' => '🎪',
            'mobilier' => '🪑',
            'decoration' => '🎨',
            'technique' => '🔧',
            'autre' => '📦'
        );

        foreach ($assignments_by_category as $category => $category_assignments) {
            // Titre de catégorie
            $icon = isset($category_icons[$category]) ? $category_icons[$category] : '📦';
            $pdf->SetFont('', 'B', $default_font_size - 2);
            $pdf->SetXY($this->marge_gauche, $curY);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->MultiCell(190, 6, $icon . ' ' . strtoupper($category), 0, 'L', 1);
            $curY += 7;

            $pdf->SetFont('', '', $default_font_size - 3);

            foreach ($category_assignments as $assignment) {
                // Vérification hauteur de page
                if ($curY + 8 > ($this->page_hauteur - 30)) {
                    $pdf->AddPage();
                    $curY = 50;
                    
                    // Réafficher les en-têtes
                    $this->_tableau($pdf, 40, $this->page_hauteur - 70, 0, $outputlangs, 1, 0);
                    $curY = 52;
                }

                // Référence produit
                $pdf->SetXY($this->marge_gauche, $curY);
                $pdf->MultiCell(25, 6, $assignment->ref_product, 0, 'L');

                // Nom du produit
                $pdf->SetXY($this->marge_gauche + 25, $curY);
                $product_name = dol_trunc($assignment->product_label, 35);
                $pdf->MultiCell(60, 6, $product_name, 0, 'L');

                // Numéro de série
                $pdf->SetXY($this->marge_gauche + 85, $curY);
                $pdf->SetFont('', 'B', $default_font_size - 3);
                $pdf->MultiCell(25, 6, $assignment->numero_serie, 0, 'L');
                $pdf->SetFont('', '', $default_font_size - 3);

                // QR Code (texte + mini QR si possible)
                $pdf->SetXY($this->marge_gauche + 110, $curY);
                $pdf->SetFont('', '', $default_font_size - 4);
                $pdf->MultiCell(25, 6, $assignment->qr_code, 0, 'C');
                
                // Génération mini QR code
                $this->generateMiniQRCode($pdf, $assignment->qr_code, $this->marge_gauche + 117, $curY + 7, 12);

                // Statut
                $pdf->SetXY($this->marge_gauche + 135, $curY);
                $pdf->SetFont('', '', $default_font_size - 4);
                $status_text = $this->getStatusText($assignment->statut);
                $pdf->MultiCell(20, 6, $status_text, 0, 'C');

                // Cases à cocher pour état
                $pdf->SetXY($this->marge_gauche + 155, $curY);
                $pdf->SetFont('', '', $default_font_size - 4);
                $checkbox_text = "☐ OK  ☐ Défaut";
                $pdf->MultiCell(35, 6, $checkbox_text, 0, 'L');

                $curY += 20; // Espacement entre les lignes
            }

            $curY += 3; // Espacement entre catégories
        }

        // Bloc signatures
        $curY += 10;
        $this->addSignatureBlocks($pdf, $curY, $outputlangs);

        // Informations de contact
        $curY += 50;
        $this->addContactInfo($pdf, $curY, $outputlangs);

        return $curY;
    }

    /**
     * Générer un mini QR code
     */
    protected function generateMiniQRCode(&$pdf, $qr_text, $x, $y, $size = 12)
    {
        // Utilisation d'une bibliothèque QR simple ou affichage du texte
        // Pour l'instant, on affiche juste le texte en petit
        $pdf->SetFont('', '', 6);
        $pdf->SetXY($x, $y);
        
        // Création d'un carré pour simuler le QR code
        $pdf->Rect($x, $y, $size, $size);
        
        // Texte du QR code en dessous
        $pdf->SetXY($x - 5, $y + $size + 1);
        $pdf->MultiCell($size + 10, 3, $qr_text, 0, 'C');
    }

    /**
     * Obtenir le texte du statut
     */
    protected function getStatusText($status)
    {
        $statuses = array(
            'assigne' => 'Assigné',
            'sorti' => 'Sorti',
            'en_cours' => 'En cours',
            'retourne' => 'Retourné',
            'incident' => 'Incident'
        );
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    /**
     * Ajouter les blocs de signatures
     */
    protected function addSignatureBlocks(&$pdf, $y, $outputlangs)
    {
        $pdf->SetFont('', 'B', 10);
        
        // Titre
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->MultiCell(190, 6, $outputlangs->transnoentities("SignatureBlocks"), 0, 'C');
        
        $y += 10;
        
        // Bloc signature technicien (gauche)
        $pdf->Rect($this->marge_gauche, $y, 90, 35);
        $pdf->SetXY($this->marge_gauche + 5, $y + 2);
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(80, 5, $outputlangs->transnoentities("TechnicianSignature"), 0, 'L');
        
        $pdf->SetFont('', '', 8);
        $pdf->SetXY($this->marge_gauche + 5, $y + 8);
        $pdf->MultiCell(80, 4, "Nom: ___________________", 0, 'L');
        $pdf->SetXY($this->marge_gauche + 5, $y + 13);
        $pdf->MultiCell(80, 4, "Date: __________________", 0, 'L');
        $pdf->SetXY($this->marge_gauche + 5, $y + 18);
        $pdf->MultiCell(80, 4, "Signature:", 0, 'L');
        
        // Bloc signature client (droite)
        $pdf->Rect($this->marge_gauche + 95, $y, 90, 35);
        $pdf->SetXY($this->marge_gauche + 100, $y + 2);
        $pdf->SetFont('', 'B', 9);
        $pdf->MultiCell(80, 5, $outputlangs->transnoentities("CustomerSignature"), 0, 'L');
        
        $pdf->SetFont('', '', 8);
        $pdf->SetXY($this->marge_gauche + 100, $y + 8);
        $pdf->MultiCell(80, 4, "Nom: ___________________", 0, 'L');
        $pdf->SetXY($this->marge_gauche + 100, $y + 13);
        $pdf->MultiCell(80, 4, "Date: __________________", 0, 'L');
        $pdf->SetXY($this->marge_gauche + 100, $y + 18);
        $pdf->MultiCell(80, 4, "Signature:", 0, 'L');
    }

    /**
     * Ajouter les informations de contact
     */
    protected function addContactInfo(&$pdf, $y, $outputlangs)
    {
        global $mysoc;
        
        $pdf->SetFont('', 'B', 9);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->MultiCell(190, 5, $outputlangs->transnoentities("EmergencyContact"), 0, 'C');
        
        $pdf->SetFont('', '', 8);
        $pdf->SetXY($this->marge_gauche, $y + 6);
        
        $contact_text = "";
        if (!empty($mysoc->phone)) {
            $contact_text .= "Tél: " . $mysoc->phone . " - ";
        }
        if (!empty($mysoc->email)) {
            $contact_text .= "Email: " . $mysoc->email;
        }
        
        $pdf->MultiCell(190, 4, $contact_text, 0, 'C');
        
        // Instructions importantes
        $pdf->SetXY($this->marge_gauche, $y + 12);
        $pdf->SetFont('', 'I', 7);
        $instructions = $outputlangs->transnoentities("ImportantInstructions");
        $pdf->MultiCell(190, 3, "IMPORTANT: Vérifiez l'état du matériel avant utilisation. Signalez immédiatement tout défaut.", 0, 'C');
    }

    /**
     * Show header of page. Return height of header
     */
    protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
    {
        global $conf, $langs, $mysoc;

        $ltrdirection = 'L';
        if ($outputlangs->trans("DIRECTION") == 'rtl') $ltrdirection = 'R';

        $outputlangs->loadLangs(array("main", "bills", "propal", "companies", "eventrental@eventrental"));

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $w = 110;

        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        if ($this->emetteur->logo) {
            $logodir = $conf->mycompany->dir_output;
            if (!empty($conf->mycompany->multidir_output[$object->entity])) $logodir = $conf->mycompany->multidir_output[$object->entity];
            if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
                $logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
            } else {
                $logo = $logodir.'/logos/'.$this->emetteur->logo;
            }
            if (is_readable($logo)) {
                $height = pdf_getHeightForLogo($logo);
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else {
            $text = $this->emetteur->name;
            $pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);
        }

        // Document title
        $pdf->SetFont('', 'B', $default_font_size + 3);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $title = $outputlangs->transnoentities("MaterialOutputSheet");
        $pdf->MultiCell($w, 3, $title, '', 'R');

        $pdf->SetFont('', 'B', $default_font_size);
        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell($w, 4, $outputlangs->transnoentities("Event")." : ".$outputlangs->convToOutputCharset($object->nom_evenement), '', 'R');

        $posy += 4;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell($w, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref_event), '', 'R');

        $posy += 4;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell($w, 4, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->date_debut, "day", false, $outputlangs, true), '', 'R');

        if ($object->thirdparty && $object->thirdparty->id) {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 4, $outputlangs->transnoentities("Customer")." : ".$outputlangs->convToOutputCharset($object->thirdparty->name), '', 'R');
        }

        $pdf->SetTextColor(0, 0, 0);

        // Add list of linked objects
        /* Uncomment to add linked objects
        $posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
        */

        return $posy;
    }

    /**
     * Show footer of page. Return height of footer
     */
    protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        $showdetails = 0;
        return pdf_pagefoot($pdf, $outputlangs, 'EVENTRENTAL_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);
    }
}
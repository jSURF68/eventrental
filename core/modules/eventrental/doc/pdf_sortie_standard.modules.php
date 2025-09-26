<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

// Load Dolibarr environment
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/eventrental/class/eventrental_assignment.class.php';

/**
 * Class to generate PDF for EventRental output sheet
 */
class pdf_sortie_standard extends CommonDocGenerator
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * Constructor
     */
    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = "sortie_standard";
        $this->description = "Fiche de sortie standard";

        // Page format
        $formatarray = pdf_getFormat();
        $this->type = 'pdf';
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 10;
        $this->marge_droite = 10;
        $this->marge_haute = 10;
        $this->marge_basse = 10;

        $this->option_logo = 1;
        $this->option_multilang = 1;
        $this->option_freetext = 1;

        // Get source company
        $this->emetteur = $mysoc;
    }

    /**
     * Function to build pdf onto disk
     */
    public function write_file($object, $outputlangs = '', $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc;

        if (!is_object($outputlangs)) $outputlangs = $langs;

        $outputlangs->loadLangs(array("main", "dict", "companies", "eventrental@eventrental"));

        // Get assignments
        $assignments = EventRentalAssignment::getEventAssignments($object->id, $this->db);

        if ($conf->eventrental->dir_output) {
            // Definition of $dir and $file
            $objectref = dol_sanitizeFileName($object->ref_event);
            $dir = $conf->eventrental->dir_output.'/eventrental_event/'.$objectref;
            $file = $dir.'/'.$objectref.'_fiche_sortie.pdf';

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Create pdf instance
                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);
                $pdf->SetAutoPageBreak(1, 0);

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref_event));
                $pdf->SetSubject($outputlangs->transnoentities("OutputSheet"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

                // Add page
                $pdf->AddPage();
                $pagenb++;

                // Header
                $this->_pagehead($pdf, $object, $outputlangs);

                // Content
                $this->_pageContent($pdf, $object, $assignments, $outputlangs);

                // Footer  
                $this->_pagefoot($pdf, $object, $outputlangs);

                 $pdf->Close();
                $pdf->Output($file, 'F');

                dolChmod($file);

                $this->result = array('fullpath'=>$file);

                // Ajout dans les objets liés via ECM
                if (file_exists($file)) {
                    // Mise à jour de la date de génération du document dans l'événement
                    $sql_update_doc = "UPDATE ".MAIN_DB_PREFIX."eventrental_event 
                                       SET date_generation_fiche = '".$this->db->idate(dol_now())."'
                                       WHERE rowid = ".$object->id;
                    $this->db->query($sql_update_doc);

                    // Ajout du fichier dans la gestion documentaire ECM
                    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
                    
                    $filename = basename($file);
                    $rel_dir = dirname($file);
                    $rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/i', '', $rel_dir);
                    $rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);
                    
                    // Génération de l'entrée ECM pour affichage dans objets liés
                    dol_add_file_process($file, 0, 1, 'upload', '', $object, 'eventrental_event@eventrental');
                }

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
     * Show header of page
     */
    protected function _pagehead(&$pdf, $object, $outputlangs)
    {
        global $conf, $mysoc;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // En-tête avec informations société (gauche) et titre (droite)
        $pdf->SetFont('', 'B', $default_font_size + 2);
        
        // Informations société (côté gauche)
        $pdf->SetXY(10, 10);
        $pdf->SetFont('', 'B', $default_font_size + 1);
        $pdf->Cell(90, 6, $mysoc->name, 0, 1, 'L');
        
        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->SetX(10);
        
        // Adresse société
        if (!empty($mysoc->address)) {
            $pdf->Cell(90, 4, $mysoc->address, 0, 1, 'L');
        }
        
        $address_line = '';
        if (!empty($mysoc->zip)) $address_line .= $mysoc->zip . ' ';
        if (!empty($mysoc->town)) $address_line .= $mysoc->town;
        if (!empty($address_line)) {
            $pdf->SetX(10);
            $pdf->Cell(90, 4, $address_line, 0, 1, 'L');
        }
        
        if (!empty($mysoc->country)) {
            $pdf->SetX(10);
            $pdf->Cell(90, 4, $mysoc->country, 0, 1, 'L');
        }
        
        // Contact société
        if (!empty($mysoc->phone)) {
            $pdf->SetX(10);
            $pdf->Cell(90, 4, 'Tél: ' . $mysoc->phone, 0, 1, 'L');
        }
        
        if (!empty($mysoc->email)) {
            $pdf->SetX(10);
            $pdf->Cell(90, 4, 'Email: ' . $mysoc->email, 0, 1, 'L');
        }
        
        if (!empty($mysoc->url)) {
            $pdf->SetX(10);
            $pdf->Cell(90, 4, 'Web: ' . $mysoc->url, 0, 1, 'L');
        }

        // Titre du document (côté droit)
        $pdf->SetFont('', 'B', $default_font_size + 4);
        $pdf->SetXY(110, 15);
        $pdf->Cell(90, 8, 'FICHE DE SORTIE', 0, 1, 'C');
        $pdf->SetX(110);
        $pdf->Cell(90, 8, 'MATÉRIEL', 0, 1, 'C');

        // Date d'émission
        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->SetXY(110, 35);
        $pdf->Cell(90, 4, 'Émis le: ' . dol_print_date(dol_now(), 'dayhour'), 0, 1, 'C');

        // Ligne de séparation
        $pdf->Line(10, 50, 200, 50);

        // Informations événement dans un cadre
        $y_start = 55;
        $pdf->Rect(10, $y_start, 190, 30);
        
        $pdf->SetFont('', 'B', $default_font_size + 1);
        $pdf->SetXY(12, $y_start + 2);
        $pdf->Cell(0, 6, 'INFORMATIONS ÉVÉNEMENT', 0, 1, 'L');

        $pdf->SetFont('', '', $default_font_size);
        
        // Ligne 1: Référence et Type
        $pdf->SetXY(12, $y_start + 10);
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(25, 5, 'Référence:', 0, 0, 'L');
        $pdf->SetFont('', '', $default_font_size);
        $pdf->Cell(65, 5, $object->ref_event, 0, 0, 'L');
        
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(20, 5, 'Type:', 0, 0, 'L');
        $pdf->SetFont('', '', $default_font_size);
        $pdf->Cell(0, 5, $object->type_evenement ?: 'Non défini', 0, 1, 'L');

        // Ligne 2: Événement
        $pdf->SetXY(12, $y_start + 15);
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(25, 5, 'Événement:', 0, 0, 'L');
        $pdf->SetFont('', '', $default_font_size);
        $pdf->Cell(0, 5, $object->nom_evenement, 0, 1, 'L');

        // Ligne 3: Client
        $pdf->SetXY(12, $y_start + 20);
        if ($object->thirdparty) {
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->Cell(25, 5, 'Client:', 0, 0, 'L');
            $pdf->SetFont('', '', $default_font_size);
            $pdf->Cell(0, 5, $object->thirdparty->name, 0, 1, 'L');
        }

        // Ligne 4: Dates
        $pdf->SetXY(12, $y_start + 25);
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(25, 5, 'Début:', 0, 0, 'L');
        $pdf->SetFont('', '', $default_font_size);
        $pdf->Cell(65, 5, dol_print_date($object->date_debut, 'dayhour'), 0, 0, 'L');
        
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->Cell(20, 5, 'Fin:', 0, 0, 'L');
        $pdf->SetFont('', '', $default_font_size);
        $pdf->Cell(0, 5, dol_print_date($object->date_fin, 'dayhour'), 0, 1, 'L');

        // Lieu et adresse (si présents)
        if (!empty($object->lieu_evenement) || !empty($object->adresse_evenement)) {
            // Extension du cadre
            $pdf->Rect(10, $y_start + 30, 190, 15);
            
            if (!empty($object->lieu_evenement)) {
                $pdf->SetXY(12, $y_start + 32);
                $pdf->SetFont('', 'B', $default_font_size);
                $pdf->Cell(25, 5, 'Lieu:', 0, 0, 'L');
                $pdf->SetFont('', '', $default_font_size);
                $pdf->Cell(0, 5, $object->lieu_evenement, 0, 1, 'L');
            }
            
            if (!empty($object->adresse_evenement)) {
                $pdf->SetXY(12, $y_start + 37);
                $pdf->SetFont('', 'B', $default_font_size);
                $pdf->Cell(25, 5, 'Adresse:', 0, 0, 'L');
                $pdf->SetFont('', '', $default_font_size);
                $pdf->Cell(0, 5, $object->adresse_evenement, 0, 1, 'L');
            }
            
            return $y_start + 50; // Position Y après le cadre étendu
        }

        return $y_start + 35; // Position Y après le cadre standard
    }


     /**
     * Show page content
     */
    protected function _pageContent(&$pdf, $object, $assignments, $outputlangs)
    {
        $start_y = $this->_pagehead($pdf, $object, $outputlangs);
        $pdf->SetY($start_y + 5);
        
        $pdf->SetFont('', 'B', 12);
        $pdf->Cell(0, 8, 'MATÉRIEL ASSIGNÉ ('.count($assignments).' unités)', 0, 1, 'L');
        $pdf->Ln(3);

        // Table header
        $pdf->SetFont('', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(30, 6, 'Référence', 1, 0, 'C', 1);
        $pdf->Cell(65, 6, 'Produit', 1, 0, 'C', 1); 
        $pdf->Cell(30, 6, 'N° Série', 1, 0, 'C', 1);
        $pdf->Cell(30, 6, 'QR Code', 1, 0, 'C', 1);
        $pdf->Cell(35, 6, 'Contrôle/Notes', 1, 1, 'C', 1);

        // Group by category
        $assignments_by_category = array();
        foreach ($assignments as $assignment) {
            $category = $assignment->category_event ?: 'autre';
            if (!isset($assignments_by_category[$category])) {
                $assignments_by_category[$category] = array();
            }
            $assignments_by_category[$category][] = $assignment;
        }

        $category_icons = array(
            'son' => '[SON]',
            'eclairage' => '[ECL]',
            'scene' => '[SCE]',
            'mobilier' => '[MOB]',
            'decoration' => '[DEC]',
            'technique' => '[TEC]',
            'autre' => '[AUT]'
        );

        $pdf->SetFont('', '', 8);

        foreach ($assignments_by_category as $category => $category_assignments) {
            // Category header
            $pdf->SetFont('', 'B', 9);
            $pdf->SetFillColor(200, 200, 200);
            $icon = isset($category_icons[$category]) ? $category_icons[$category] : '📦';
            $pdf->Cell(190, 6, $icon . ' ' . strtoupper($category), 1, 1, 'L', 1);
            
            $pdf->SetFont('', '', 8);
            $pdf->SetFillColor(255, 255, 255);

            foreach ($category_assignments as $assignment) {
                $pdf->Cell(30, 8, $assignment->ref_product, 1, 0, 'L');
                $pdf->Cell(65, 8, substr($assignment->product_label, 0, 40), 1, 0, 'L');
                $pdf->Cell(30, 8, $assignment->numero_serie, 1, 0, 'C');
                $pdf->Cell(30, 8, $assignment->qr_code, 1, 0, 'C');
                
                // Case à cocher avec espace pour notes
                $pdf->SetFont('', '', 7);
                $pdf->Cell(35, 8, '[ ] OK  [ ] Defaut', 1, 1, 'C');
                $pdf->SetFont('', '', 8);
            }
            $pdf->Ln(2);
        }

        // Date de retour prévue
        if ($object->date_fin) {
            $pdf->Ln(5);
            $pdf->SetFont('', 'B', 10);
            $pdf->SetFillColor(255, 255, 200);
            $pdf->Cell(0, 8, '>> DATE DE RETOUR PRÉVUE: ' . dol_print_date($object->date_fin, 'dayhour'), 1, 1, 'C', 1);
        }
    }

   /**
     * Show footer of page
     */
    protected function _pagefoot(&$pdf, $object, $outputlangs)
    {
        $pdf->Ln(10);
        
        // Signatures
        $pdf->SetFont('', 'B', 11);
        $pdf->Cell(0, 6, 'CONTRÔLES ET SIGNATURES', 0, 1, 'C');
        
        $y = $pdf->GetY() + 5;
        
        // Signature technicien (gauche)
        $pdf->Rect(10, $y, 90, 35);
        $pdf->SetXY(15, $y + 2);
        $pdf->SetFont('', 'B', 10);
        $pdf->Cell(80, 6, 'SIGNATURE TECHNICIEN', 0, 1, 'C');
        
        $pdf->SetFont('', '', 9);
        $pdf->SetXY(15, $y + 10);
        $pdf->Cell(80, 5, 'Nom: ___________________________', 0, 1, 'L');
        $pdf->SetX(15);
        $pdf->Cell(80, 5, 'Date: __________________________', 0, 1, 'L');
        $pdf->SetX(15);
        $pdf->Cell(80, 5, 'Signature:', 0, 1, 'L');
        
        // Signature client (droite)
        $pdf->Rect(105, $y, 90, 35);
        $pdf->SetXY(110, $y + 2);
        $pdf->SetFont('', 'B', 10);
        $pdf->Cell(80, 6, 'SIGNATURE CLIENT', 0, 1, 'C');
        
        $pdf->SetFont('', '', 9);
        $pdf->SetXY(110, $y + 10);
        $pdf->Cell(80, 5, 'Nom: ___________________________', 0, 1, 'L');
        $pdf->SetX(110);
        $pdf->Cell(80, 5, 'Date: __________________________', 0, 1, 'L');
        $pdf->SetX(110);
        $pdf->Cell(80, 5, 'Signature:', 0, 1, 'L');
        
        // Notes importantes
        $pdf->SetY($y + 40);
        $pdf->SetFont('', 'I', 8);
        $pdf->SetFillColor(255, 240, 240);
        $pdf->Cell(0, 6, 'IMPORTANT: Vérifiez l\'état du matériel avant utilisation. Signalez immédiatement tout défaut.', 1, 1, 'C', 1);
        
        // Contact d'urgence
        global $mysoc;
        $contact_urgence = '';
        if (!empty($mysoc->phone)) {
            $contact_urgence = 'Contact urgence: ' . $mysoc->phone;
        }
        if (!empty($mysoc->email)) {
            $contact_urgence .= (!empty($contact_urgence) ? ' - ' : '') . 'Email: ' . $mysoc->email;
        }
        
        if (!empty($contact_urgence)) {
            $pdf->SetFont('', '', 8);
            $pdf->Cell(0, 5, $contact_urgence, 0, 1, 'C');
        }

        // Numérotation des pages en bas
        $pdf->SetY($this->page_hauteur - 15);
        $pdf->SetFont('', '', 8);
        $pdf->SetTextColor(128, 128, 128);
        
        // Informations document (gauche)
        $pdf->SetX(10);
        $pdf->Cell(90, 4, 'Fiche de sortie - ' . $object->ref_event, 0, 0, 'L');
        
        // Numéro de page (droite)
        $pdf->Cell(100, 4, 'Page ' . $pdf->getAliasNumPage() . ' / ' . $pdf->getAliasNbPages(), 0, 0, 'R');
        
        // Date génération (centre)
        $pdf->SetY($this->page_hauteur - 10);
        $pdf->SetX(10);
        $pdf->Cell(0, 4, 'Document généré le ' . dol_print_date(dol_now(), 'dayhour'), 0, 1, 'C');
        
        $pdf->SetTextColor(0, 0, 0); // Reset couleur
    }
}

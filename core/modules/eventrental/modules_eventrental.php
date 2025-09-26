<?php
/* Copyright (C) 2025 VotreNom <votre.email@domain.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Parent class for PDF models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 * Parent class for event rental PDF models
 */
abstract class ModelePDFEventRental extends CommonDocGenerator
{
    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors array
     */
    public $errors = array();

    /**
     * @var int     page_largeur
     */
    public $page_largeur;

    /**
     * @var int     page_hauteur
     */
    public $page_hauteur;

    /**
     * @var array   format
     */
    public $format;

    /**
     * @var int     marge_gauche
     */
    public $marge_gauche;

    /**
     * @var int     marge_droite
     */
    public $marge_droite;

    /**
     * @var int     marge_haute
     */
    public $marge_haute;

    /**
     * @var int     marge_basse
     */
    public $marge_basse;

    /**
     * Constructor
     */
    public function __construct($db = 0)
    {
        global $conf, $langs, $mysoc;

        $this->db = $db;

        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
        $this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
        $this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
        $this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

        $this->option_logo = 1;                    // Display logo FAC_PDF_LOGO
        $this->option_tva = 1;                     // Manage the vat option FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Display payment mode
        $this->option_condreg = 1;                 // Display payment terms
        $this->option_codeproduitservice = 1;      // Display product-service code
        $this->option_multilang = 1;               // Available in several languages
        $this->option_escompte = 0;                // Displays if there has been a discount
        $this->option_credit_note = 0;             // Support credit notes
        $this->option_freetext = 1;                // Support add of a personalised text
        $this->option_draft_watermark = 1;         // Support add of a watermark on drafts

        // Get source company
        $this->emetteur = $mysoc;
        if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if was not defined
    }

    /**
     * Function to build a document on disk using the generic odt module.
     *
     * @param      Object       $object             Object source to build document
     * @param      Translate    $outputlangs        Lang output object
     * @param      string       $srctemplatepath    Full path of source filename for generator using a template file
     * @param      int          $hidedetails        Do not show line details
     * @param      int          $hidedesc           Do not show desc
     * @param      int          $hideref            Do not show ref
     * @return     int                              1 if OK, <=0 if KO
     */
    abstract public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0);
}

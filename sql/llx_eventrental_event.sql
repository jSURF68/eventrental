-- Table des événements
CREATE TABLE llx_eventrental_event (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER DEFAULT 1 NOT NULL,
    ref_event VARCHAR(128) NOT NULL,
    
    -- Informations événement
    nom_evenement VARCHAR(255) NOT NULL,
    type_evenement VARCHAR(128),
    description TEXT,
    
    -- Client et commercial
    fk_soc INTEGER NOT NULL,
    fk_user_commercial INTEGER,
    
    -- Dates et durée
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    date_montage DATETIME,
    date_demontage DATETIME,
    
    -- Lieu événement
    lieu_evenement TEXT,
    adresse_evenement TEXT,
    nb_invites INTEGER DEFAULT 0,
    
    -- Gestion phases
    phase_actuelle ENUM('en_attente', 'valide', 'en_cours', 'retour', 'annule', 'archive') DEFAULT 'en_attente',
    date_validation DATETIME,
    date_annulation DATETIME,
    motif_annulation TEXT,
    
    -- Totaux financiers
    total_ht DOUBLE(24,8) DEFAULT 0,
    total_tva DOUBLE(24,8) DEFAULT 0,
    total_ttc DOUBLE(24,8) DEFAULT 0,
    
    -- Liaisons Dolibarr
    fk_propal INTEGER,
    fk_facture INTEGER,
    fk_projet INTEGER,
    
    -- Notes
    note_public TEXT,
    note_private TEXT,
    
    -- Audit
    date_creation DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_author INTEGER,
    fk_user_modif INTEGER,
    
    -- Index
    UNIQUE KEY uk_eventrental_event_ref (ref_event, entity),
    FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid),
    INDEX idx_eventrental_event_phase (phase_actuelle),
    INDEX idx_eventrental_event_dates (date_debut, date_fin),
    INDEX idx_eventrental_event_entity (entity)
) ENGINE=InnoDB;
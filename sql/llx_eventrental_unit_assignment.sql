-- Table des assignations d'unités à des événements
CREATE TABLE llx_eventrental_unit_assignment (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER DEFAULT 1 NOT NULL,
    fk_event INTEGER NOT NULL,
    fk_unit INTEGER NOT NULL,
    fk_event_line INTEGER,
    
    -- Statut assignation
    statut ENUM('assigne', 'sorti', 'en_cours', 'retourne', 'incident') DEFAULT 'assigne',
    
    -- Dates et contrôles
    date_assignation DATETIME NOT NULL,
    date_sortie DATETIME,
    date_retour_prevu DATETIME,
    date_retour_reel DATETIME,
    
    -- Contrôles qualité
    etat_sortie ENUM('neuf', 'excellent', 'bon', 'moyen', 'use', 'defaillant'),
    etat_retour ENUM('neuf', 'excellent', 'bon', 'moyen', 'use', 'defaillant'),
    
    -- Responsables
    fk_user_sortie INTEGER,
    fk_user_retour INTEGER,
    
    -- Observations
    observations_sortie TEXT,
    observations_retour TEXT,
    
    -- Incidents
    incident_description TEXT,
    cout_incident DOUBLE(24,8) DEFAULT 0,
    
    -- Signatures digitales (chemins vers fichiers)
    signature_client_sortie VARCHAR(255),
    signature_technicien_sortie VARCHAR(255),
    signature_client_retour VARCHAR(255),
    signature_technicien_retour VARCHAR(255),
    
    -- Photos (chemins vers fichiers)
    photos_sortie TEXT, -- JSON array des chemins
    photos_retour TEXT, -- JSON array des chemins
    
    -- Audit
    date_creation DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_author INTEGER,
    fk_user_modif INTEGER,
    
    -- Index et contraintes
    FOREIGN KEY (fk_event) REFERENCES llx_eventrental_event(rowid) ON DELETE CASCADE,
    FOREIGN KEY (fk_unit) REFERENCES llx_eventrental_unit(rowid),
    UNIQUE KEY uk_unit_event (fk_unit, fk_event),
    INDEX idx_assignment_event (fk_event),
    INDEX idx_assignment_statut (statut),
    INDEX idx_assignment_dates (date_sortie, date_retour_prevu),
    INDEX idx_assignment_entity (entity)
) ENGINE=InnoDB;

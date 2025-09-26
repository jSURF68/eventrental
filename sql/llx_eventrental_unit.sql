-- Table des unités individuelles (chaque exemplaire physique)
CREATE TABLE llx_eventrental_unit (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER DEFAULT 1 NOT NULL,
    fk_product INTEGER NOT NULL,
    
    -- Identification unique
    numero_serie VARCHAR(128) UNIQUE NOT NULL,
    qr_code VARCHAR(128) UNIQUE NOT NULL,
    numero_interne VARCHAR(128),
    etiquette_physique VARCHAR(128),
    
    -- État et statut
    statut ENUM('disponible', 'reserve', 'loue', 'transit', 'maintenance', 'panne', 'reforme') DEFAULT 'disponible',
    etat_physique ENUM('neuf', 'excellent', 'bon', 'moyen', 'use', 'defaillant') DEFAULT 'neuf',
    
    -- Localisation physique
    emplacement_actuel VARCHAR(255),
    zone_stockage VARCHAR(128),
    
    -- Historique utilisation
    heures_utilisation INTEGER DEFAULT 0,
    nb_locations INTEGER DEFAULT 0,
    date_derniere_location DATE,
    
    -- Maintenance
    date_achat DATE,
    date_mise_service DATE,
    prochaine_revision DATE,
    intervalle_revision INTEGER DEFAULT 365, -- jours
    
    -- Valeurs
    prix_achat DOUBLE(24,8),
    valeur_actuelle DOUBLE(24,8),
    
    -- Notes
    observations TEXT,
    
    -- Audit
    date_creation DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_author INTEGER,
    fk_user_modif INTEGER,
    
    -- Index et contraintes
    FOREIGN KEY (fk_product) REFERENCES llx_eventrental_product(rowid),
    INDEX idx_eventrental_unit_statut (statut),
    INDEX idx_eventrental_unit_product (fk_product),
    INDEX idx_eventrental_unit_location (emplacement_actuel),
    INDEX idx_eventrental_unit_entity (entity)
) ENGINE=InnoDB;
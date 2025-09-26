-- Table des lignes d'événement (matériel par événement)
CREATE TABLE llx_eventrental_event_line (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER DEFAULT 1 NOT NULL,
    fk_event INTEGER NOT NULL,
    fk_product INTEGER NOT NULL,
    
    -- Quantités et description
    qty INTEGER NOT NULL,
    description TEXT,
    product_label VARCHAR(255),
    
    -- Tarification
    prix_unitaire DOUBLE(24,8) DEFAULT 0,
    remise_percent DOUBLE(6,3) DEFAULT 0,
    total_ht DOUBLE(24,8) DEFAULT 0,
    tva_rate DOUBLE(6,3) DEFAULT 0,
    total_tva DOUBLE(24,8) DEFAULT 0,
    total_ttc DOUBLE(24,8) DEFAULT 0,
    
    -- Ordre d'affichage
    rang INTEGER DEFAULT 0,
    
    -- Audit
    date_creation DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index et contraintes
    FOREIGN KEY (fk_event) REFERENCES llx_eventrental_event(rowid) ON DELETE CASCADE,
    FOREIGN KEY (fk_product) REFERENCES llx_eventrental_product(rowid),
    INDEX idx_eventrental_event_line_event (fk_event),
    INDEX idx_eventrental_event_line_product (fk_product),
    INDEX idx_eventrental_event_line_entity (entity)
) ENGINE=InnoDB;
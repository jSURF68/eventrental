-- Table des produits événementiels (catalogue matériel)
CREATE TABLE llx_eventrental_product (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER DEFAULT 1 NOT NULL,
    ref_product VARCHAR(128) NOT NULL,
    label VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Catégorisation événementiel
    category_event ENUM('son', 'eclairage', 'scene', 'video', 'mobilier', 'decoration', 'technique') NOT NULL,
    sub_category VARCHAR(128),
    
    -- Données location
    prix_location_jour DOUBLE(24,8) DEFAULT 0,
    prix_location_weekend DOUBLE(24,8) DEFAULT 0,
    caution_unitaire DOUBLE(24,8) DEFAULT 0,
    
    -- Quantités totales
    qty_total INTEGER DEFAULT 0,
    qty_disponible INTEGER DEFAULT 0,
    qty_louee INTEGER DEFAULT 0,
    qty_maintenance INTEGER DEFAULT 0,
    qty_panne INTEGER DEFAULT 0,
    
    -- Spécifications techniques
    poids DOUBLE(8,3) DEFAULT 0,
    dimensions VARCHAR(128),
    puissance_electrique INTEGER DEFAULT 0,
    
    -- Contraintes événementiel
    delai_preparation INTEGER DEFAULT 0,
    nb_techniciens_requis INTEGER DEFAULT 0,
    compatible_exterieur BOOLEAN DEFAULT 1,
    
    -- Statut
    statut INTEGER DEFAULT 1,
    
    -- Audit
    date_creation DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_author INTEGER,
    fk_user_modif INTEGER,
    
    -- Index
    UNIQUE KEY uk_eventrental_product_ref (ref_product, entity),
    INDEX idx_eventrental_product_category (category_event),
    INDEX idx_eventrental_product_entity (entity)
) ENGINE=InnoDB;
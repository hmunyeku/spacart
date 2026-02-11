-- SpaCart - Cart line items
CREATE TABLE llx_spacart_cart_item (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_cart         INTEGER NOT NULL,
    fk_product      INTEGER NOT NULL,
    fk_variant      INTEGER DEFAULT NULL,
    qty             REAL DEFAULT 1,
    price_ht        DOUBLE(24,8) DEFAULT 0,
    tva_tx          DOUBLE(7,4) DEFAULT 0,
    price_ttc       DOUBLE(24,8) DEFAULT 0,
    total_ht        DOUBLE(24,8) DEFAULT 0,
    total_tva       DOUBLE(24,8) DEFAULT 0,
    total_ttc       DOUBLE(24,8) DEFAULT 0,
    options_json    TEXT,
    options_price   DOUBLE(24,8) DEFAULT 0,
    weight          DOUBLE(24,8) DEFAULT 0,
    date_creation   DATETIME NOT NULL
) ENGINE=InnoDB;

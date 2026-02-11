-- SpaCart - Wholesale/tiered pricing
CREATE TABLE llx_spacart_wholesale_price (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_product      INTEGER NOT NULL,
    fk_variant      INTEGER DEFAULT 0,
    membership_id   INTEGER DEFAULT 0,
    qty_min         REAL DEFAULT 1,
    price           DOUBLE(24,8) NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

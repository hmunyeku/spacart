-- SpaCart - Featured products
CREATE TABLE llx_spacart_featured (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_product      INTEGER NOT NULL,
    position        INTEGER DEFAULT 0,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

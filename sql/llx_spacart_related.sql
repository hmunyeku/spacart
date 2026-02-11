-- SpaCart - Related products
CREATE TABLE llx_spacart_related (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_product      INTEGER NOT NULL,
    fk_related      INTEGER NOT NULL,
    position        INTEGER DEFAULT 0
) ENGINE=InnoDB;

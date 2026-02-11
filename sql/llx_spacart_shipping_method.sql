-- SpaCart - Shipping methods
CREATE TABLE llx_spacart_shipping_method (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    description     TEXT DEFAULT NULL,
    destination     VARCHAR(5) DEFAULT 'N',
    position        INTEGER DEFAULT 0,
    status          SMALLINT DEFAULT 1,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

-- SpaCart - Shipping zones
CREATE TABLE llx_spacart_shipping_zone (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    status          SMALLINT DEFAULT 1,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

-- SpaCart - Tax rates per zone
CREATE TABLE llx_spacart_tax_rate (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_tax          INTEGER NOT NULL,
    fk_zone         INTEGER NOT NULL,
    rate_value      DOUBLE(7,4) DEFAULT 0,
    rate_type       VARCHAR(5) DEFAULT '%',
    apply_shipping  TINYINT DEFAULT 0,
    membership_id   INTEGER DEFAULT 0
) ENGINE=InnoDB;

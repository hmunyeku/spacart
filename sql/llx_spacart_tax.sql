-- SpaCart - Tax rules
CREATE TABLE llx_spacart_tax (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    status          SMALLINT DEFAULT 1,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

-- SpaCart - Shop configuration (key-value store)
CREATE TABLE llx_spacart_config (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    category        VARCHAR(100) DEFAULT 'General',
    name            VARCHAR(255) NOT NULL,
    value           TEXT DEFAULT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

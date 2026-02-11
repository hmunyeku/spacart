-- SpaCart - Product option groups (Size, Color, Material...)
CREATE TABLE llx_spacart_option_group (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_product      INTEGER NOT NULL,
    name            VARCHAR(255) NOT NULL,
    type            VARCHAR(10) DEFAULT 'select',
    required        TINYINT DEFAULT 0,
    position        INTEGER DEFAULT 0,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

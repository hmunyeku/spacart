-- SpaCart - Variant items (maps variant to option combinations)
CREATE TABLE llx_spacart_variant_item (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_variant      INTEGER NOT NULL,
    fk_option_group INTEGER NOT NULL,
    fk_option       INTEGER NOT NULL
) ENGINE=InnoDB;

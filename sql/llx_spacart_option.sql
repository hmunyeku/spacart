-- SpaCart - Individual options within a group
CREATE TABLE llx_spacart_option (
    rowid               INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_option_group     INTEGER NOT NULL,
    name                VARCHAR(255) NOT NULL,
    price_modifier      DOUBLE(24,8) DEFAULT 0,
    price_modifier_type VARCHAR(5) DEFAULT '$',
    weight_modifier     DOUBLE(24,8) DEFAULT 0,
    position            INTEGER DEFAULT 0,
    status              SMALLINT DEFAULT 1
) ENGINE=InnoDB;

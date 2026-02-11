-- SpaCart - Product variants (size, color, etc.)
CREATE TABLE llx_spacart_variant (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_product      INTEGER NOT NULL,
    title           VARCHAR(255) DEFAULT NULL,
    sku             VARCHAR(128) DEFAULT NULL,
    price           DOUBLE(24,8) DEFAULT NULL,
    weight          DOUBLE(24,8) DEFAULT NULL,
    avail           SMALLINT DEFAULT 1,
    stock           REAL DEFAULT 0,
    position        INTEGER DEFAULT 0,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

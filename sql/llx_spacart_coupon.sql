-- SpaCart - Coupons / discount codes
CREATE TABLE llx_spacart_coupon (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(64) NOT NULL,
    type            VARCHAR(10) DEFAULT 'percent',
    value           DOUBLE(24,8) DEFAULT 0,
    min_order       DOUBLE(24,8) DEFAULT 0,
    max_uses        INTEGER DEFAULT 0,
    used_count      INTEGER DEFAULT 0,
    per_customer    INTEGER DEFAULT 0,
    date_start      DATE DEFAULT NULL,
    date_end        DATE DEFAULT NULL,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

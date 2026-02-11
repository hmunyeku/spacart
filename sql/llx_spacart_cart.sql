-- SpaCart - Shopping carts
-- Copyright (C) 2024-2026 CoexDis

CREATE TABLE llx_spacart_cart (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    session_id      VARCHAR(128) NOT NULL,
    fk_soc          INTEGER DEFAULT NULL,
    fk_customer     INTEGER DEFAULT NULL,
    email           VARCHAR(255) DEFAULT NULL,
    status          SMALLINT DEFAULT 0,
    coupon_code     VARCHAR(64) DEFAULT NULL,
    coupon_discount DOUBLE(24,8) DEFAULT 0,
    giftcard_code   VARCHAR(64) DEFAULT NULL,
    giftcard_amount DOUBLE(24,8) DEFAULT 0,
    shipping_method INTEGER DEFAULT NULL,
    shipping_cost   DOUBLE(24,8) DEFAULT 0,
    subtotal_ht     DOUBLE(24,8) DEFAULT 0,
    total_tva       DOUBLE(24,8) DEFAULT 0,
    total_ttc       DOUBLE(24,8) DEFAULT 0,
    note            TEXT,
    reminded_1      TINYINT DEFAULT 0,
    reminded_2      TINYINT DEFAULT 0,
    date_creation   DATETIME NOT NULL,
    date_modification DATETIME NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

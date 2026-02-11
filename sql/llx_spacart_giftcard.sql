-- SpaCart - Gift cards
CREATE TABLE llx_spacart_giftcard (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(64) NOT NULL,
    initial_amount  DOUBLE(24,8) DEFAULT 0,
    balance         DOUBLE(24,8) DEFAULT 0,
    fk_customer     INTEGER DEFAULT NULL,
    sender_name     VARCHAR(200) DEFAULT NULL,
    sender_email    VARCHAR(255) DEFAULT NULL,
    recipient_name  VARCHAR(200) DEFAULT NULL,
    recipient_email VARCHAR(255) DEFAULT NULL,
    message         TEXT DEFAULT NULL,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    date_expiry     DATE DEFAULT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

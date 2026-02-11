-- SpaCart - Customer addresses (shipping/billing)
CREATE TABLE llx_spacart_customer_address (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_customer     INTEGER NOT NULL,
    type            VARCHAR(10) DEFAULT 'shipping',
    firstname       VARCHAR(100) DEFAULT NULL,
    lastname        VARCHAR(100) DEFAULT NULL,
    company_name    VARCHAR(200) DEFAULT NULL,
    address         TEXT DEFAULT NULL,
    address2        VARCHAR(255) DEFAULT NULL,
    zip             VARCHAR(25) DEFAULT NULL,
    town            VARCHAR(100) DEFAULT NULL,
    fk_country      INTEGER DEFAULT NULL,
    fk_state        INTEGER DEFAULT NULL,
    phone           VARCHAR(30) DEFAULT NULL,
    is_default      TINYINT DEFAULT 0,
    date_creation   DATETIME NOT NULL
) ENGINE=InnoDB;

-- SpaCart - Shop customers (guest + registered)
CREATE TABLE llx_spacart_customer (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL,
    password        VARCHAR(255) DEFAULT NULL,
    firstname       VARCHAR(100) DEFAULT NULL,
    lastname        VARCHAR(100) DEFAULT NULL,
    company_name    VARCHAR(200) DEFAULT NULL,
    phone           VARCHAR(30) DEFAULT NULL,
    fk_soc          INTEGER DEFAULT NULL,
    fk_socpeople    INTEGER DEFAULT NULL,
    membership_id   INTEGER DEFAULT 0,
    session_token   VARCHAR(128) DEFAULT NULL,
    remember_token  VARCHAR(128) DEFAULT NULL,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    date_last_login DATETIME DEFAULT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

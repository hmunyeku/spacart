-- SpaCart - Payment methods configuration
CREATE TABLE llx_spacart_payment_method (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    code            VARCHAR(30) NOT NULL,
    description     TEXT DEFAULT NULL,
    param1          VARCHAR(500) DEFAULT NULL,
    param2          VARCHAR(500) DEFAULT NULL,
    param3          VARCHAR(500) DEFAULT NULL,
    position        INTEGER DEFAULT 0,
    status          SMALLINT DEFAULT 1,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

-- SpaCart - Newsletter subscribers
CREATE TABLE llx_spacart_subscriber (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL,
    firstname       VARCHAR(100) DEFAULT NULL,
    lastname        VARCHAR(100) DEFAULT NULL,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

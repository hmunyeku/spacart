-- SpaCart - Product reviews and ratings
CREATE TABLE llx_spacart_review (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_product      INTEGER NOT NULL,
    fk_customer     INTEGER DEFAULT NULL,
    author_name     VARCHAR(200) DEFAULT NULL,
    author_email    VARCHAR(255) DEFAULT NULL,
    rating          SMALLINT DEFAULT 5,
    title           VARCHAR(255) DEFAULT NULL,
    content         TEXT DEFAULT NULL,
    status          SMALLINT DEFAULT 0,
    date_creation   DATETIME NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

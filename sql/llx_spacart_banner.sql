-- SpaCart - Banners (homepage, category pages)
CREATE TABLE llx_spacart_banner (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) DEFAULT NULL,
    image           VARCHAR(255) NOT NULL,
    link            VARCHAR(500) DEFAULT NULL,
    location        VARCHAR(50) DEFAULT 'home',
    fk_category     INTEGER DEFAULT NULL,
    position        INTEGER DEFAULT 0,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

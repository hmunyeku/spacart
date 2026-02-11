-- SpaCart - Static CMS pages
CREATE TABLE llx_spacart_page (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(500) NOT NULL,
    slug            VARCHAR(255) NOT NULL,
    content         LONGTEXT DEFAULT NULL,
    meta_title      VARCHAR(255) DEFAULT NULL,
    meta_description VARCHAR(500) DEFAULT NULL,
    position        INTEGER DEFAULT 0,
    show_in_menu    TINYINT DEFAULT 0,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    date_modification DATETIME DEFAULT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

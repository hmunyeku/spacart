-- SpaCart - News articles
CREATE TABLE llx_spacart_news (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(500) NOT NULL,
    slug            VARCHAR(255) DEFAULT NULL,
    content         LONGTEXT DEFAULT NULL,
    excerpt         TEXT DEFAULT NULL,
    image           VARCHAR(255) DEFAULT NULL,
    author          VARCHAR(200) DEFAULT NULL,
    meta_title      VARCHAR(255) DEFAULT NULL,
    meta_description VARCHAR(500) DEFAULT NULL,
    views           INTEGER DEFAULT 0,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    date_modification DATETIME DEFAULT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

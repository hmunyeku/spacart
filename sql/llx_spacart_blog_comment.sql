-- SpaCart - Blog comments
CREATE TABLE llx_spacart_blog_comment (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_blog         INTEGER NOT NULL,
    fk_customer     INTEGER DEFAULT NULL,
    author_name     VARCHAR(200) DEFAULT NULL,
    author_email    VARCHAR(255) DEFAULT NULL,
    content         TEXT NOT NULL,
    status          SMALLINT DEFAULT 0,
    date_creation   DATETIME NOT NULL
) ENGINE=InnoDB;

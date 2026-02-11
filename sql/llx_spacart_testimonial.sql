-- SpaCart - Customer testimonials
CREATE TABLE llx_spacart_testimonial (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    author_name     VARCHAR(200) NOT NULL,
    author_title    VARCHAR(200) DEFAULT NULL,
    author_image    VARCHAR(255) DEFAULT NULL,
    content         TEXT NOT NULL,
    rating          SMALLINT DEFAULT 5,
    position        INTEGER DEFAULT 0,
    status          SMALLINT DEFAULT 1,
    date_creation   DATETIME NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;

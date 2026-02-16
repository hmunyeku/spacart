CREATE TABLE IF NOT EXISTS llx_spacart_language (
    rowid           integer AUTO_INCREMENT PRIMARY KEY,
    code            varchar(10) NOT NULL,
    label           varchar(100) NOT NULL DEFAULT '',
    flag_icon       varchar(10) NOT NULL DEFAULT '',
    active          tinyint NOT NULL DEFAULT 1,
    is_default      tinyint NOT NULL DEFAULT 0,
    date_creation   datetime NOT NULL
) ENGINE=InnoDB;

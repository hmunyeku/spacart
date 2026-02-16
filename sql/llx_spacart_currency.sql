CREATE TABLE IF NOT EXISTS llx_spacart_currency (
    rowid           integer AUTO_INCREMENT PRIMARY KEY,
    code            varchar(3) NOT NULL,
    symbol          varchar(10) NOT NULL DEFAULT '',
    rate            double(24,8) NOT NULL DEFAULT 1.00000000,
    active          tinyint NOT NULL DEFAULT 1,
    is_default      tinyint NOT NULL DEFAULT 0,
    date_creation   datetime NOT NULL,
    tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

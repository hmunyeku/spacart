-- SpaCart Admin Users table
-- Copyright (C) 2024-2026 CoexDis <contact@coexdis.com>

CREATE TABLE llx_spacart_admin (
    rowid           integer AUTO_INCREMENT PRIMARY KEY,
    email           varchar(255) NOT NULL,
    password        varchar(255) NOT NULL,
    firstname       varchar(100) DEFAULT NULL,
    lastname        varchar(100) DEFAULT NULL,
    role            varchar(50) DEFAULT 'admin',
    status          smallint DEFAULT 1,
    fk_user         integer DEFAULT NULL,
    last_login      datetime DEFAULT NULL,
    remember_token  varchar(255) DEFAULT NULL,
    entity          integer DEFAULT 1,
    date_creation   datetime DEFAULT NULL,
    tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

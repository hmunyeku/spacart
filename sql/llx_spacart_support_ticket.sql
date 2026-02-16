CREATE TABLE IF NOT EXISTS llx_spacart_support_ticket (
    rowid           integer AUTO_INCREMENT PRIMARY KEY,
    fk_customer     integer DEFAULT 0,
    customer_name   varchar(255) NOT NULL DEFAULT '',
    customer_email  varchar(255) NOT NULL DEFAULT '',
    subject         varchar(500) NOT NULL,
    status          varchar(20) NOT NULL DEFAULT 'open',
    priority        varchar(20) NOT NULL DEFAULT 'normal',
    date_creation   datetime NOT NULL,
    date_closed     datetime,
    tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

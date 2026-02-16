CREATE TABLE IF NOT EXISTS llx_spacart_support_message (
    rowid           integer AUTO_INCREMENT PRIMARY KEY,
    fk_ticket       integer NOT NULL,
    sender_type     varchar(20) NOT NULL DEFAULT 'customer',
    sender_name     varchar(255) NOT NULL DEFAULT '',
    message         text NOT NULL,
    date_creation   datetime NOT NULL
) ENGINE=InnoDB;

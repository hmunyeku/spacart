-- SpaCart - Wishlist
CREATE TABLE llx_spacart_wishlist (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_customer     INTEGER NOT NULL,
    fk_product      INTEGER NOT NULL,
    date_creation   DATETIME NOT NULL
) ENGINE=InnoDB;

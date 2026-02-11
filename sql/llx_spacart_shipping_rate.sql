-- SpaCart - Shipping rates per zone/method
CREATE TABLE llx_spacart_shipping_rate (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_method       INTEGER NOT NULL,
    fk_zone         INTEGER NOT NULL,
    rate_flat       DOUBLE(24,8) DEFAULT 0,
    rate_percent    DOUBLE(7,4) DEFAULT 0,
    rate_per_weight DOUBLE(24,8) DEFAULT 0,
    rate_per_item   DOUBLE(24,8) DEFAULT 0,
    weight_from     DOUBLE(24,8) DEFAULT 0,
    weight_to       DOUBLE(24,8) DEFAULT 0,
    amount_from     DOUBLE(24,8) DEFAULT 0,
    amount_to       DOUBLE(24,8) DEFAULT 0,
    free_above      DOUBLE(24,8) DEFAULT 0
) ENGINE=InnoDB;

-- SpaCart - Zone elements (countries, states, cities, zipcodes in a zone)
CREATE TABLE llx_spacart_shipping_zone_elem (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_zone         INTEGER NOT NULL,
    field_type      VARCHAR(5) NOT NULL,
    field_value     VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

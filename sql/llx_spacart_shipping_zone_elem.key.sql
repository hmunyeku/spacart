ALTER TABLE llx_spacart_shipping_zone_elem ADD INDEX idx_spacart_zoneelem_fk_zone (fk_zone);
ALTER TABLE llx_spacart_shipping_zone_elem ADD CONSTRAINT fk_spacart_zoneelem_zone FOREIGN KEY (fk_zone) REFERENCES llx_spacart_shipping_zone(rowid) ON DELETE CASCADE;

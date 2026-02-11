ALTER TABLE llx_spacart_tax_rate ADD INDEX idx_spacart_taxrate_tax (fk_tax);
ALTER TABLE llx_spacart_tax_rate ADD INDEX idx_spacart_taxrate_zone (fk_zone);

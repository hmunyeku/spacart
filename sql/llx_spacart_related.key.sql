ALTER TABLE llx_spacart_related ADD UNIQUE INDEX uk_spacart_related (fk_product, fk_related);
ALTER TABLE llx_spacart_related ADD INDEX idx_spacart_related_product (fk_product);

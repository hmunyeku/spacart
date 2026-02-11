-- SpaCart - Variant indexes
ALTER TABLE llx_spacart_variant ADD INDEX idx_spacart_variant_fk_product (fk_product);
ALTER TABLE llx_spacart_variant ADD INDEX idx_spacart_variant_sku (sku);
ALTER TABLE llx_spacart_variant ADD INDEX idx_spacart_variant_entity (entity);

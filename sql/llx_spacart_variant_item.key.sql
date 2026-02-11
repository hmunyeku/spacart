-- SpaCart - Variant item indexes
ALTER TABLE llx_spacart_variant_item ADD INDEX idx_spacart_varitem_fk_variant (fk_variant);
ALTER TABLE llx_spacart_variant_item ADD CONSTRAINT fk_spacart_varitem_variant FOREIGN KEY (fk_variant) REFERENCES llx_spacart_variant(rowid) ON DELETE CASCADE;

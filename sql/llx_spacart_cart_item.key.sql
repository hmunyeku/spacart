-- SpaCart - Cart item indexes
ALTER TABLE llx_spacart_cart_item ADD INDEX idx_spacart_cart_item_fk_cart (fk_cart);
ALTER TABLE llx_spacart_cart_item ADD INDEX idx_spacart_cart_item_fk_product (fk_product);
ALTER TABLE llx_spacart_cart_item ADD CONSTRAINT fk_spacart_cart_item_cart FOREIGN KEY (fk_cart) REFERENCES llx_spacart_cart(rowid) ON DELETE CASCADE;

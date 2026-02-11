-- SpaCart - Cart indexes
ALTER TABLE llx_spacart_cart ADD INDEX idx_spacart_cart_session (session_id);
ALTER TABLE llx_spacart_cart ADD INDEX idx_spacart_cart_fk_soc (fk_soc);
ALTER TABLE llx_spacart_cart ADD INDEX idx_spacart_cart_email (email);
ALTER TABLE llx_spacart_cart ADD INDEX idx_spacart_cart_status (status);
ALTER TABLE llx_spacart_cart ADD INDEX idx_spacart_cart_entity (entity);

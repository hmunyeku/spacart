-- SpaCart - Customer indexes
ALTER TABLE llx_spacart_customer ADD UNIQUE INDEX uk_spacart_customer_email (email, entity);
ALTER TABLE llx_spacart_customer ADD INDEX idx_spacart_customer_fk_soc (fk_soc);
ALTER TABLE llx_spacart_customer ADD INDEX idx_spacart_customer_session (session_token);
ALTER TABLE llx_spacart_customer ADD INDEX idx_spacart_customer_remember (remember_token);
ALTER TABLE llx_spacart_customer ADD INDEX idx_spacart_customer_entity (entity);

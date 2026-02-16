ALTER TABLE llx_spacart_admin ADD UNIQUE INDEX uk_spacart_admin_email (email, entity);
ALTER TABLE llx_spacart_admin ADD INDEX idx_spacart_admin_status (status);
ALTER TABLE llx_spacart_admin ADD INDEX idx_spacart_admin_entity (entity);

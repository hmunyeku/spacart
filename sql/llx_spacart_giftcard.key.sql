ALTER TABLE llx_spacart_giftcard ADD UNIQUE INDEX uk_spacart_giftcard_code (code, entity);
ALTER TABLE llx_spacart_giftcard ADD INDEX idx_spacart_giftcard_status (status);

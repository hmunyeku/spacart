ALTER TABLE llx_spacart_coupon ADD UNIQUE INDEX uk_spacart_coupon_code (code, entity);
ALTER TABLE llx_spacart_coupon ADD INDEX idx_spacart_coupon_status (status);

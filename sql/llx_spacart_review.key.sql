ALTER TABLE llx_spacart_review ADD INDEX idx_spacart_review_product (fk_product);
ALTER TABLE llx_spacart_review ADD INDEX idx_spacart_review_status (status);
ALTER TABLE llx_spacart_review ADD INDEX idx_spacart_review_entity (entity);

-- SpaCart - Customer address indexes
ALTER TABLE llx_spacart_customer_address ADD INDEX idx_spacart_custaddr_fk_customer (fk_customer);
ALTER TABLE llx_spacart_customer_address ADD CONSTRAINT fk_spacart_custaddr_customer FOREIGN KEY (fk_customer) REFERENCES llx_spacart_customer(rowid) ON DELETE CASCADE;

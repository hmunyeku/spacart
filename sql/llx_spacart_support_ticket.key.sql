ALTER TABLE llx_spacart_support_ticket ADD INDEX idx_spacart_support_ticket_customer (fk_customer);
ALTER TABLE llx_spacart_support_ticket ADD INDEX idx_spacart_support_ticket_status (status);

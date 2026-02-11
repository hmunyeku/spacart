-- SpaCart - Option indexes
ALTER TABLE llx_spacart_option ADD INDEX idx_spacart_option_fk_group (fk_option_group);
ALTER TABLE llx_spacart_option ADD CONSTRAINT fk_spacart_option_group FOREIGN KEY (fk_option_group) REFERENCES llx_spacart_option_group(rowid) ON DELETE CASCADE;

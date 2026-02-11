ALTER TABLE llx_spacart_news_comment ADD INDEX idx_spacart_newscomm_fk_news (fk_news);
ALTER TABLE llx_spacart_news_comment ADD CONSTRAINT fk_spacart_newscomm_news FOREIGN KEY (fk_news) REFERENCES llx_spacart_news(rowid) ON DELETE CASCADE;

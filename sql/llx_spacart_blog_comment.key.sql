ALTER TABLE llx_spacart_blog_comment ADD INDEX idx_spacart_blogcomm_fk_blog (fk_blog);
ALTER TABLE llx_spacart_blog_comment ADD CONSTRAINT fk_spacart_blogcomm_blog FOREIGN KEY (fk_blog) REFERENCES llx_spacart_blog(rowid) ON DELETE CASCADE;

-- SpaCart - Initial data

-- Default SPACART configuration constants (inserted if absent)
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_ENABLED', '1', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_TITLE', 'SpaCart Store', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_COMPANY_NAME', 'My Company', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_COMPANY_EMAIL', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_COMPANY_SLOGAN', 'Your online store', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_COMPANY_PHONE', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_COMPANY_FAX', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_COMPANY_ADDRESS', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_CURRENCY', 'EUR', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_CURRENCY_SYMBOL', '€', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_WEIGHT_SYMBOL', 'kg', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_PRODUCTS_PER_PAGE', '12', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_GUEST_CHECKOUT', '1', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_SHOP_CLOSED', '0', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_FREE_SHIPPING_THRESHOLD', '50', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_THEME_COLOR', '#E65100', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_THEME_COLOR_2', '#2e2e2e', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_LOGO_URL', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_FAVICON', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_FOOTER_LOGO_URL', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_FACEBOOK_URL', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_TWITTER_URL', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_INSTAGRAM_URL', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_YOUTUBE_URL', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_SHOW_SERVICES', '0', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_ENABLE_CUSTOMIZER', '1', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_FONT_FAMILY', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_CUSTOM_CSS', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_BORDER_RADIUS_CARD', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_BORDER_RADIUS_BUTTON', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_CONTAINER_WIDTH', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_DEFAULT_LANGUAGE', 'fr_FR', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_RECAPTCHA_SITE_KEY', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_RECAPTCHA_SECRET_KEY', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_TAWKTO_ID', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_ANALYTICS_ID', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_META_TITLE', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_META_DESCRIPTION', '', 'chaine', 1, 0);
INSERT IGNORE INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_META_KEYWORDS', '', 'chaine', 1, 0);

-- Default payment methods
INSERT INTO llx_spacart_payment_method (name, code, description, position, status, entity) VALUES ('Stripe', 'stripe', 'Paiement par carte bancaire via Stripe', 1, 1, 1);
INSERT INTO llx_spacart_payment_method (name, code, description, position, status, entity) VALUES ('PayPal', 'paypal', 'Paiement via PayPal', 2, 0, 1);
INSERT INTO llx_spacart_payment_method (name, code, description, position, status, entity) VALUES ('Braintree', 'braintree', 'Paiement par carte via Braintree', 3, 0, 1);
INSERT INTO llx_spacart_payment_method (name, code, description, position, status, entity) VALUES ('Virement bancaire', 'bank_transfer', 'Paiement par virement bancaire', 4, 0, 1);
INSERT INTO llx_spacart_payment_method (name, code, description, position, status, entity) VALUES ('A la livraison', 'cod', 'Paiement a la livraison', 5, 0, 1);

-- Default shipping zone
INSERT INTO llx_spacart_shipping_zone (name, status, entity) VALUES ('National', 1, 1);
INSERT INTO llx_spacart_shipping_zone (name, status, entity) VALUES ('International', 1, 1);

-- Default shipping method
INSERT INTO llx_spacart_shipping_method (name, description, destination, position, status, entity) VALUES ('Livraison standard', 'Livraison en 3-5 jours ouvrables', 'N', 1, 1, 1);
INSERT INTO llx_spacart_shipping_method (name, description, destination, position, status, entity) VALUES ('Livraison express', 'Livraison en 24-48h', 'N', 2, 1, 1);
INSERT INTO llx_spacart_shipping_method (name, description, destination, position, status, entity) VALUES ('Retrait en magasin', 'Retrait gratuit a notre adresse', 'N', 3, 1, 1);

-- Default tax
INSERT INTO llx_spacart_tax (name, status, entity) VALUES ('TVA Standard', 1, 1);

-- Default CMS pages
INSERT INTO llx_spacart_page (title, slug, content, position, show_in_menu, status, date_creation, entity) VALUES ('Conditions generales de vente', 'cgv', '<h2>Conditions generales de vente</h2><p>Contenu a completer...</p>', 1, 1, 1, NOW(), 1);
INSERT INTO llx_spacart_page (title, slug, content, position, show_in_menu, status, date_creation, entity) VALUES ('Mentions legales', 'mentions-legales', '<h2>Mentions legales</h2><p>Contenu a completer...</p>', 2, 1, 1, NOW(), 1);
INSERT INTO llx_spacart_page (title, slug, content, position, show_in_menu, status, date_creation, entity) VALUES ('Politique de confidentialite', 'confidentialite', '<h2>Politique de confidentialite</h2><p>Contenu a completer...</p>', 3, 1, 1, NOW(), 1);
INSERT INTO llx_spacart_page (title, slug, content, position, show_in_menu, status, date_creation, entity) VALUES ('Livraison et retours', 'livraison-retours', '<h2>Livraison et retours</h2><p>Contenu a completer...</p>', 4, 1, 1, NOW(), 1);
INSERT INTO llx_spacart_page (title, slug, content, position, show_in_menu, status, date_creation, entity) VALUES ('A propos', 'a-propos', '<h2>A propos</h2><p>Contenu a completer...</p>', 5, 1, 1, NOW(), 1);
INSERT INTO llx_spacart_page (title, slug, content, position, show_in_menu, status, date_creation, entity) VALUES ('Contact', 'contact', '<h2>Contactez-nous</h2><p>Contenu a completer...</p>', 6, 1, 1, NOW(), 1);

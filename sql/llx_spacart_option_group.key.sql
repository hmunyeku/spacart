-- SpaCart - Option group indexes
ALTER TABLE llx_spacart_option_group ADD INDEX idx_spacart_optgrp_fk_product (fk_product);
ALTER TABLE llx_spacart_option_group ADD INDEX idx_spacart_optgrp_entity (entity);

<?php
/**
 * SpaCart - Customer/User functions
 * Registration, login, profile, link to Dolibarr tiers
 */

/**
 * Register a new customer
 */
function spacart_register_customer($data)
{
    global $db;

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $firstname = trim($data['firstname'] ?? '');
    $lastname = trim($data['lastname'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $company = trim($data['company'] ?? '');

    // Validate
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return array('success' => false, 'message' => 'Email invalide');
    }
    if (strlen($password) < 6) {
        return array('success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères');
    }
    if (!$firstname || !$lastname) {
        return array('success' => false, 'message' => 'Nom et prénom sont obligatoires');
    }

    // Check email uniqueness
    $sqlCheck = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_customer WHERE email = '".$db->escape($email)."'";
    $resCheck = $db->query($sqlCheck);
    if ($resCheck && $db->num_rows($resCheck)) {
        return array('success' => false, 'message' => 'Un compte existe déjà avec cet email');
    }

    // Hash password
    $hashedPassword = spacartHashPassword($password);

    // Create Dolibarr tiers (societe)
    $fkSoc = spacart_create_dolibarr_tiers($firstname, $lastname, $email, $phone, $company);

    // Insert customer
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_customer";
    $sql .= " (email, password, firstname, lastname, phone, company, fk_soc, active, date_creation, tms)";
    $sql .= " VALUES ('".$db->escape($email)."', '".$db->escape($hashedPassword)."',";
    $sql .= " '".$db->escape($firstname)."', '".$db->escape($lastname)."',";
    $sql .= " '".$db->escape($phone)."', '".$db->escape($company)."',";
    $sql .= " ".(int) $fkSoc.", 1, NOW(), NOW())";

    $db->query($sql);
    $customerId = $db->last_insert_id(MAIN_DB_PREFIX."spacart_customer");

    if (!$customerId) {
        return array('success' => false, 'message' => 'Erreur lors de la création du compte');
    }

    // Auto-login
    $_SESSION['spacart_customer_id'] = $customerId;

    // Merge anonymous cart
    spacart_merge_customer_cart($customerId);

    return array('success' => true, 'message' => 'Compte créé avec succès', 'customer_id' => $customerId);
}

/**
 * Login customer
 */
function spacart_login_customer($email, $password, $remember = false)
{
    global $db;

    $sql = "SELECT rowid, email, password, firstname, lastname, active";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_customer";
    $sql .= " WHERE email = '".$db->escape($email)."'";
    $resql = $db->query($sql);

    if (!$resql || !$db->num_rows($resql)) {
        return array('success' => false, 'message' => 'Identifiants incorrects');
    }

    $customer = $db->fetch_object($resql);

    if (!$customer->active) {
        return array('success' => false, 'message' => 'Compte désactivé');
    }

    if (!spacartVerifyPassword($password, $customer->password)) {
        return array('success' => false, 'message' => 'Identifiants incorrects');
    }

    // Set session
    $_SESSION['spacart_customer_id'] = $customer->rowid;

    // Remember me
    if ($remember) {
        $token = spacartGenerateToken(64);
        $expiry = date('Y-m-d H:i:s', time() + 30 * 86400);

        $sqlToken = "UPDATE ".MAIN_DB_PREFIX."spacart_customer";
        $sqlToken .= " SET remember_token = '".$db->escape($token)."',";
        $sqlToken .= " remember_expiry = '".$db->escape($expiry)."'";
        $sqlToken .= " WHERE rowid = ".(int) $customer->rowid;
        $db->query($sqlToken);

        setcookie('spacart_remember', $token, time() + 30 * 86400, '/', '', true, true);
    }

    // Merge anonymous cart
    spacart_merge_customer_cart($customer->rowid);

    // Update last login
    $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_customer SET last_login = NOW() WHERE rowid = ".(int) $customer->rowid);

    return array('success' => true, 'message' => 'Connexion réussie');
}

/**
 * Logout customer
 */
function spacart_logout_customer()
{
    global $db;

    if (!empty($_SESSION['spacart_customer_id'])) {
        // Clear remember token
        $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_customer SET remember_token = NULL, remember_expiry = NULL WHERE rowid = ".(int) $_SESSION['spacart_customer_id']);
    }

    unset($_SESSION['spacart_customer_id']);
    setcookie('spacart_remember', '', time() - 3600, '/', '', true, true);

    return array('success' => true, 'message' => 'Déconnexion réussie');
}

/**
 * Load customer by remember token
 */
function spacart_load_customer_by_remember($token)
{
    global $db;

    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_customer";
    $sql .= " WHERE remember_token = '".$db->escape($token)."'";
    $sql .= " AND remember_expiry > NOW()";
    $sql .= " AND active = 1";
    $resql = $db->query($sql);

    if ($resql && $db->num_rows($resql)) {
        $obj = $db->fetch_object($resql);
        return spacart_load_customer($obj->rowid);
    }
    return null;
}

/**
 * Load customer by ID
 */
function spacart_load_customer($id)
{
    global $db;

    $sql = "SELECT c.rowid, c.email, c.firstname, c.lastname, c.phone, c.company,";
    $sql .= " c.fk_soc, c.active, c.date_creation, c.last_login";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_customer c";
    $sql .= " WHERE c.rowid = ".(int) $id." AND c.active = 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        return $db->fetch_object($resql);
    }
    return null;
}

/**
 * Update customer profile
 */
function spacart_update_profile($customerId, $data)
{
    global $db;

    $fields = array();
    if (isset($data['firstname'])) $fields[] = "firstname = '".$db->escape(trim($data['firstname']))."'";
    if (isset($data['lastname'])) $fields[] = "lastname = '".$db->escape(trim($data['lastname']))."'";
    if (isset($data['phone'])) $fields[] = "phone = '".$db->escape(trim($data['phone']))."'";
    if (isset($data['company'])) $fields[] = "company = '".$db->escape(trim($data['company']))."'";

    // Change email
    if (!empty($data['email'])) {
        $newEmail = trim($data['email']);
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            // Check uniqueness
            $sqlCheck = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_customer WHERE email = '".$db->escape($newEmail)."' AND rowid != ".(int) $customerId;
            $resCheck = $db->query($sqlCheck);
            if ($resCheck && $db->num_rows($resCheck)) {
                return array('success' => false, 'message' => 'Cet email est déjà utilisé');
            }
            $fields[] = "email = '".$db->escape($newEmail)."'";
        }
    }

    // Change password
    if (!empty($data['new_password'])) {
        if (strlen($data['new_password']) < 6) {
            return array('success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères');
        }
        $fields[] = "password = '".$db->escape(spacartHashPassword($data['new_password']))."'";
    }

    if (empty($fields)) {
        return array('success' => false, 'message' => 'Rien à mettre à jour');
    }

    $fields[] = "tms = NOW()";

    $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_customer";
    $sql .= " SET ".implode(', ', $fields);
    $sql .= " WHERE rowid = ".(int) $customerId;
    $db->query($sql);

    // Update Dolibarr tiers
    $customer = spacart_load_customer($customerId);
    if ($customer && $customer->fk_soc > 0) {
        spacart_update_dolibarr_tiers($customer->fk_soc, $data);
    }

    return array('success' => true, 'message' => 'Profil mis à jour');
}

/**
 * Get customer addresses
 */
function spacart_get_customer_addresses($customerId)
{
    global $db;
    $addresses = array();

    $sql = "SELECT a.rowid, a.fk_customer, a.type, a.firstname, a.lastname,";
    $sql .= " a.address, a.zip, a.city, a.fk_country, a.fk_state, a.phone,";
    $sql .= " a.is_default, c.label as country_name";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_customer_address a";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country c ON c.rowid = a.fk_country";
    $sql .= " WHERE a.fk_customer = ".(int) $customerId;
    $sql .= " ORDER BY a.is_default DESC, a.rowid DESC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $addresses[] = $obj;
        }
    }
    return $addresses;
}

/**
 * Save customer address
 */
function spacart_save_address($customerId, $data, $addressId = 0)
{
    global $db;

    $firstname = $db->escape(trim($data['firstname'] ?? ''));
    $lastname = $db->escape(trim($data['lastname'] ?? ''));
    $address = $db->escape(trim($data['address'] ?? ''));
    $zip = $db->escape(trim($data['zip'] ?? ''));
    $city = $db->escape(trim($data['city'] ?? ''));
    $countryId = (int) ($data['fk_country'] ?? 1);
    $stateId = (int) ($data['fk_state'] ?? 0);
    $phone = $db->escape(trim($data['phone'] ?? ''));
    $type = ($data['type'] ?? 'shipping') === 'billing' ? 'billing' : 'shipping';
    $isDefault = !empty($data['is_default']) ? 1 : 0;

    if ($isDefault) {
        $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_customer_address SET is_default = 0 WHERE fk_customer = ".(int) $customerId." AND type = '".$type."'");
    }

    if ($addressId > 0) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_customer_address SET";
        $sql .= " firstname = '".$firstname."', lastname = '".$lastname."',";
        $sql .= " address = '".$address."', zip = '".$zip."', city = '".$city."',";
        $sql .= " fk_country = ".$countryId.", fk_state = ".$stateId.",";
        $sql .= " phone = '".$phone."', is_default = ".$isDefault.", tms = NOW()";
        $sql .= " WHERE rowid = ".(int) $addressId." AND fk_customer = ".(int) $customerId;
        $db->query($sql);
    } else {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_customer_address";
        $sql .= " (fk_customer, type, firstname, lastname, address, zip, city,";
        $sql .= " fk_country, fk_state, phone, is_default, date_creation, tms)";
        $sql .= " VALUES (".(int) $customerId.", '".$type."',";
        $sql .= " '".$firstname."', '".$lastname."', '".$address."', '".$zip."',";
        $sql .= " '".$city."', ".$countryId.", ".$stateId.", '".$phone."', ".$isDefault.", NOW(), NOW())";
        $db->query($sql);
        $addressId = $db->last_insert_id(MAIN_DB_PREFIX."spacart_customer_address");
    }

    return array('success' => true, 'address_id' => $addressId);
}

/**
 * Get customer orders (from Dolibarr)
 */
function spacart_get_customer_orders($fkSoc, $limit = 20)
{
    global $db;
    $orders = array();

    if (!$fkSoc) return $orders;

    $sql = "SELECT c.rowid, c.ref, c.date_commande, c.total_ht, c.total_ttc,";
    $sql .= " c.fk_statut, c.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande c";
    $sql .= " WHERE c.fk_soc = ".(int) $fkSoc;
    $sql .= " AND c.module_source = 'spacart'";
    $sql .= " ORDER BY c.date_creation DESC";
    $sql .= " LIMIT ".(int) $limit;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->status_label = spacart_order_status_label($obj->fk_statut);
            $orders[] = $obj;
        }
    }
    return $orders;
}

/**
 * Get order status label
 */
function spacart_order_status_label($status)
{
    $labels = array(
        -1 => 'Annulée',
        0 => 'Brouillon',
        1 => 'Validée',
        2 => 'En cours',
        3 => 'Livrée'
    );
    return $labels[$status] ?? 'Inconnue';
}

/**
 * Create Dolibarr third-party (societe) for a new customer
 */
function spacart_create_dolibarr_tiers($firstname, $lastname, $email, $phone = '', $company = '')
{
    global $db, $user;

    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

    $societe = new Societe($db);
    $societe->name = $company ?: ($firstname.' '.$lastname);
    $societe->name_alias = $firstname.' '.$lastname;
    $societe->client = 1; // Client
    $societe->fournisseur = 0;
    $societe->email = $email;
    $societe->phone = $phone;
    $societe->status = 1;
    $societe->entity = 1;

    // Use technical user for API operations
    $techUserId = getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1);
    require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
    $techUser = new User($db);
    $techUser->fetch($techUserId);

    $result = $societe->create($techUser);

    if ($result > 0) {
        // Create contact
        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
        $contact = new Contact($db);
        $contact->socid = $result;
        $contact->firstname = $firstname;
        $contact->lastname = $lastname;
        $contact->email = $email;
        $contact->phone_pro = $phone;
        $contact->statut = 1;
        $contact->create($techUser);

        return $result;
    }

    return 0;
}

/**
 * Update Dolibarr tiers info
 */
function spacart_update_dolibarr_tiers($fkSoc, $data)
{
    global $db;

    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

    $societe = new Societe($db);
    if ($societe->fetch($fkSoc) > 0) {
        if (!empty($data['company'])) $societe->name = $data['company'];
        if (!empty($data['email'])) $societe->email = $data['email'];
        if (!empty($data['phone'])) $societe->phone = $data['phone'];

        $techUserId = getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1);
        $techUser = new User($db);
        $techUser->fetch($techUserId);

        $societe->update($societe->id, $techUser);
    }
}

/**
 * Merge anonymous cart to customer cart after login
 */
function spacart_merge_customer_cart($customerId)
{
    global $db;

    require_once SPACART_PATH.'/includes/func/func.cart.php';

    $anonymousCartId = !empty($_SESSION['spacart_cart_id']) ? (int) $_SESSION['spacart_cart_id'] : 0;
    if (!$anonymousCartId) return;

    // Check if anonymous cart has items
    $sqlCheck = "SELECT fk_customer FROM ".MAIN_DB_PREFIX."spacart_cart WHERE rowid = ".$anonymousCartId;
    $resCheck = $db->query($sqlCheck);
    if ($resCheck && $db->num_rows($resCheck)) {
        $cartOwner = $db->fetch_object($resCheck);
        if ($cartOwner->fk_customer > 0 && $cartOwner->fk_customer == $customerId) {
            return; // Already the customer's cart
        }
    }

    // Get or create customer cart
    $customerCart = spacart_get_or_create_cart('', $customerId);
    if (!$customerCart) return;

    if ($anonymousCartId != $customerCart->rowid) {
        spacart_merge_carts($anonymousCartId, $customerCart->rowid);
        $_SESSION['spacart_cart_id'] = $customerCart->rowid;
    } else {
        // Just assign customer to existing cart
        $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_cart SET fk_customer = ".(int) $customerId." WHERE rowid = ".$anonymousCartId);
    }
}

/**
 * Get customer wishlist
 */
function spacart_get_wishlist($customerId)
{
    global $db;
    $items = array();

    $sql = "SELECT w.rowid, w.fk_product, w.date_creation,";
    $sql .= " p.ref, p.label, p.price, p.price_ttc, p.stock_reel";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_wishlist w";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = w.fk_product";
    $sql .= " WHERE w.fk_customer = ".(int) $customerId;
    $sql .= " ORDER BY w.date_creation DESC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->photo_url = spacart_product_photo_url($obj->fk_product, $obj->ref);
            $items[] = $obj;
        }
    }
    return $items;
}

/**
 * Toggle wishlist item
 */
function spacart_toggle_wishlist($customerId, $productId)
{
    global $db;

    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_wishlist";
    $sql .= " WHERE fk_customer = ".(int) $customerId." AND fk_product = ".(int) $productId;
    $resql = $db->query($sql);

    if ($resql && $db->num_rows($resql)) {
        $obj = $db->fetch_object($resql);
        $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_wishlist WHERE rowid = ".(int) $obj->rowid);
        return array('success' => true, 'in_wishlist' => false, 'message' => 'Retiré des favoris');
    } else {
        $sqlIns = "INSERT INTO ".MAIN_DB_PREFIX."spacart_wishlist (fk_customer, fk_product, date_creation)";
        $sqlIns .= " VALUES (".(int) $customerId.", ".(int) $productId.", NOW())";
        $db->query($sqlIns);
        return array('success' => true, 'in_wishlist' => true, 'message' => 'Ajouté aux favoris');
    }
}

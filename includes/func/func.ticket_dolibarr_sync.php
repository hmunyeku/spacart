<?php
/**
 * SpaCart -> Dolibarr Ticket Sync Functions
 * Syncs SpaCart tickets into Dolibarr native llx_ticket table
 * and ticket messages into llx_actioncomm (Dolibarr event system).
 * Created: 2026-02-15
 */

function _dol_ticket_generate_ref() {
     = date('ymd');
     = 'TS' .  . '-1';
     = ->field("SELECT MAX(ref) FROM llx_ticket WHERE ref LIKE '" . addslashes() . "%' AND entity=1");
    if () {
         = substr(, strlen());
         = intval() + 1;
    } else {
         = 1;
    }
    return  . str_pad(, 4, '0', STR_PAD_LEFT);
}

function _dol_ticket_generate_track_id() {
    return strtoupper(substr(md5( . microtime(true) . mt_rand()), 0, 12));
}

function spacart_sync_ticket_to_dolibarr() {
    global ;
     = addslashes();
     = 'SPACART-' . ;

     = ->field("SELECT rowid FROM llx_ticket WHERE ref_ext = '" . addslashes() . "' AND entity = 1");
    if () return intval();

     = ->row("SELECT * FROM tickets WHERE ticketid='" .  . "'");
    if (!) return false;

     = _dol_ticket_generate_ref();
     = _dol_ticket_generate_track_id();
     = (['date'] > 0) ? date('Y-m-d H:i:s', ['date']) : date('Y-m-d H:i:s');

    // SpaCart status -> Dolibarr fk_statut
     = array('O'=>1, 'Q'=>0, 'C'=>8, '3'=>8, '1'=>3, '2'=>5);
     = isset([['status']]) ? [['status']] : 0;

     = array(1=>'HIGH', 2=>'NORMAL', 3=>'LOW');
     = isset([intval(['priority'])]) ? [intval(['priority'])] : 'NORMAL';

     = ->row("SELECT message FROM tickets_messages WHERE ticketid='" .  . "' ORDER BY messageid ASC LIMIT 1");
     =  ? ['message'] : (['message'] ?? '');
     = (['type'] == 'P') ? 'COM' : 'OTHER';

     = ( >= 1) ? "'" . addslashes() . "'" : 'NULL';
     = ( == 8) ? "'" . addslashes() . "'" : 'NULL';
     = isset(['REMOTE_ADDR']) ? ['REMOTE_ADDR'] : '';

     = "INSERT INTO llx_ticket (
                ref, entity, ref_ext, track_id, fk_soc,
                subject, message, fk_statut,
                type_code, category_code, severity_code,
                datec, date_read, date_close,
                email_from, origin_email,
                fk_user_create, fk_user_assign,
                notify_tiers_at_create, ip
            ) VALUES (
                '" . addslashes() . "',
                1,
                '" . addslashes() . "',
                '" . addslashes() . "',
                0,
                '" . addslashes(['subject']) . "',
                '" . addslashes() . "',
                " . intval() . ",
                '" . addslashes() . "',
                'OTHER',
                '" . addslashes() . "',
                '" . addslashes() . "',
                " .  . ",
                " .  . ",
                '" . addslashes(['email']) . "',
                '" . addslashes(['email']) . "',
                1,
                NULL,
                0,
                '" . addslashes() . "'
            )";

    ->query();
     = ->field("SELECT LAST_INSERT_ID()");
    if (!) return false;

     = 'Ticket ' .  . ' created (from SpaCart #' .  . ')';
    ->query("INSERT INTO llx_actioncomm (code, label, note, datec, datep, datep2, fk_action, fk_user_action, fk_user_author, fk_element, elementtype, entity, percent, priority) VALUES ('AC_TICKET_CREATE', '" . addslashes() . "', '" . addslashes() . "', '" . addslashes() . "', '" . addslashes() . "', '" . addslashes() . "', 0, 1, 1, " . intval() . ", 'ticket', 1, -1, 0)");

    return intval();
}

function spacart_sync_ticket_message(, ,  = false) {
    global ;
     = 'SPACART-' . addslashes();

     = ->row("SELECT rowid, ref FROM llx_ticket WHERE ref_ext = '" .  . "' AND entity = 1");
    if (!) {
         = spacart_sync_ticket_to_dolibarr();
        if (!) return false;
         = ->row("SELECT rowid, ref FROM llx_ticket WHERE rowid = " . intval());
        if (!) return false;
    }

     = date('Y-m-d H:i:s');
     =  ? 'TICKET_MSG_SENTBYMAIL' : 'TICKET_MSG';
     = 'Message on ticket ' . ['ref'] . ' (from SpaCart)';

    ->query("INSERT INTO llx_actioncomm (code, label, note, datec, datep, datep2, fk_action, fk_user_action, fk_user_author, fk_element, elementtype, entity, percent, priority) VALUES ('" . addslashes() . "', '" . addslashes() . "', '" . addslashes() . "', '" . addslashes() . "', '" . addslashes() . "', '" . addslashes() . "', 0, 1, 1, " . intval(['rowid']) . ", 'ticket', 1, -1, 0)");

    ->query("UPDATE llx_ticket SET date_last_msg_sent = '" . addslashes() . "' WHERE rowid = " . intval(['rowid']));
    return true;
}

function spacart_sync_ticket_status(, ) {
    global ;
     = 'SPACART-' . addslashes();
     = ->field("SELECT rowid FROM llx_ticket WHERE ref_ext = '" .  . "' AND entity = 1");
    if (!) return false;

     = array('O'=>1, 'Q'=>0, 'C'=>8, '3'=>8, '1'=>3, '2'=>5);
     = isset([]) ? [] : 0;
     = date('Y-m-d H:i:s');

     = "UPDATE llx_ticket SET fk_statut = " . intval();
    if ( == 8) {
         .= ", date_close = '" .  . "'";
    }
     .= " WHERE rowid = " . intval();
    ->query();
    return true;
}

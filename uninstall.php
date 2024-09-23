<?php
/**
 * Funções de desinstalação do plugin
 */

function mp_split_uninstall() {
    delete_option('mp_split_settings');
}
register_uninstall_hook(__FILE__, 'mp_split_uninstall');

<?php
/**
 * Classe de integração com WCFM
 */
class MP_Split_WCFM {

    public function __construct() {
        add_action('wcfm_product_manage_meta_end', array($this, 'add_split_payment_option'));
        add_action('wcfm_product_manage_meta_save', array($this, 'save_split_payment_option'), 10, 2);
    }

    public function add_split_payment_option($product_id) {
        $split_enabled = get_post_meta($product_id, '_mp_split_enabled', true);
        ?>
        <div class="wcfm-content">
            <h2><?php _e('Opções de Split de Pagamento', 'mp-split'); ?></h2>
            <label for="mp_split_enabled">
                <input type="checkbox" id="mp_split_enabled" name="mp_split_enabled" value="yes" <?php checked($split_enabled, 'yes'); ?> />
                <?php _e('Habilitar Split de Pagamento', 'mp-split'); ?>
            </label>
        </div>
        <?php
    }

    public function save_split_payment_option($product_id, $post_data) {
        $split_enabled = isset($post_data['mp_split_enabled']) ? 'yes' : 'no';
        update_post_meta($product_id, '_mp_split_enabled', $split_enabled);
    }
}

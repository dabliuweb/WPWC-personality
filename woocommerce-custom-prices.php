<?php
/**
 * Plugin Name: WooCommerce Custom Prices
 * Description: Opções de personalizações para Woocommerce.
 * Version: 1.3.6
 * Author: Douglas Lelis
 * Text Domain: woocommerce-custom-prices
 */


// Adicionar menu no painel de administração
require_once 'functions.php';
require_once 'shop_functions.php';

function wc_custom_prices_menu() {
    add_menu_page(
        __('Preços Personalizados', 'woocommerce-custom-prices'),
        __('Preços Personalizados', 'woocommerce-custom-prices'),
        'manage_options',
        'wc-custom-prices',
        'wc_custom_prices_settings_page',
        'dashicons-admin-generic',
        56
    );
}
add_action('admin_menu', 'wc_custom_prices_menu');


function wc_custom_prices_settings_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Configurações de Preços Personalizados', 'woocommerce-custom-prices'); ?></h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=wc-custom-prices&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Configurações Gerais', 'woocommerce-custom-prices'); ?>
            </a>
            <a href="?page=wc-custom-prices&tab=fields" class="nav-tab <?php echo $active_tab === 'fields' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Campos Personalizados', 'woocommerce-custom-prices'); ?>
            </a>
        </h2>
        <form method="post" action="">
            <?php
            if ($active_tab === 'general') {
                wc_custom_prices_general_settings();
            } elseif ($active_tab === 'fields') {
                wc_custom_prices_fields_settings();
            }
            ?>
        </form>
    </div>
    <?php
}

// Página de configurações
function wc_custom_prices_general_settings() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wc_custom_prices_settings_nonce']) && wp_verify_nonce($_POST['wc_custom_prices_settings_nonce'], 'wc_custom_prices_settings')) {
        update_option('wc_custom_prices_display_value', sanitize_text_field($_POST['wc_custom_prices_display_value']));
        update_option('wc_custom_prices_show_prices', sanitize_text_field($_POST['wc_custom_prices_show_prices']));
        update_option('wc_custom_prices_variable_price_display', sanitize_text_field($_POST['wc_custom_prices_variable_price_display']));
        update_option('wc_custom_prices_hide_sku', isset($_POST['wc_custom_prices_hide_sku']) ? 1 : 0);

        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $avista_methods = [];
        $aprazo_methods = [];

        foreach ($gateways as $gateway_id => $gateway) {
            if (isset($_POST["payment_type_$gateway_id"])) {
                $type = sanitize_text_field($_POST["payment_type_$gateway_id"]);
                if ($type === 'avista') {
                    $avista_methods[] = $gateway_id;
                } elseif ($type === 'aprazo') {
                    $aprazo_methods[] = $gateway_id;
                }
            }
        }

        update_option('wc_custom_prices_methods_avista', $avista_methods);
        update_option('wc_custom_prices_methods_aprazo', $aprazo_methods);

        echo '<div class="updated"><p>' . __('Configurações salvas!', 'woocommerce-custom-prices') . '</p></div>';
    }

    $display_value = get_option('wc_custom_prices_display_value', 'menor');
    $show_prices = get_option('wc_custom_prices_show_prices', 'all');
    $variable_price_display = get_option('wc_custom_prices_variable_price_display', 'range');
    $avista_methods = get_option('wc_custom_prices_methods_avista', []);
    $aprazo_methods = get_option('wc_custom_prices_methods_aprazo', []);
    $gateways = WC()->payment_gateways->get_available_payment_gateways();

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Configurações de Preços Personalizados', 'woocommerce-custom-prices'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('wc_custom_prices_settings', 'wc_custom_prices_settings_nonce'); ?>

            <h2><?php esc_html_e('Exibir Valor', 'woocommerce-custom-prices'); ?></h2>
            <label>
                <input type="radio" name="wc_custom_prices_display_value" value="menor" <?php checked($display_value, 'menor'); ?> />
                <?php esc_html_e('Menor Valor', 'woocommerce-custom-prices'); ?>
            </label><br>
            <label>
                <input type="radio" name="wc_custom_prices_display_value" value="maior" <?php checked($display_value, 'maior'); ?> />
                <?php esc_html_e('Maior Valor', 'woocommerce-custom-prices'); ?>
            </label>

            <h2><?php esc_html_e('Ocultar SKU na Página de Produto', 'woocommerce-custom-prices'); ?></h2>
            <label>
                <input type="checkbox" name="wc_custom_prices_hide_sku" value="1" <?php checked(get_option('wc_custom_prices_hide_sku'), 1); ?> />
                <?php esc_html_e('Ocultar SKU', 'woocommerce-custom-prices'); ?>
            </label>

            <h2><?php esc_html_e('Exibir Preços Para', 'woocommerce-custom-prices'); ?></h2>
            <label>
                <input type="radio" name="wc_custom_prices_show_prices" value="all" <?php checked($show_prices, 'all'); ?> />
                <?php esc_html_e('Todos os Usuários', 'woocommerce-custom-prices'); ?>
            </label><br>
            <label>
                <input type="radio" name="wc_custom_prices_show_prices" value="logged_in" <?php checked($show_prices, 'logged_in'); ?> />
                <?php esc_html_e('Apenas Usuários Logados', 'woocommerce-custom-prices'); ?>
            </label>

            <h2><?php esc_html_e('Exibição de Preços Variáveis', 'woocommerce-custom-prices'); ?></h2>
            <label>
                <input type="radio" name="wc_custom_prices_variable_price_display" value="range" <?php checked($variable_price_display, 'range'); ?> />
                <?php esc_html_e('De R$ XX,XX a R$ XX,XX', 'woocommerce-custom-prices'); ?>
            </label><br>
            <label>
                <input type="radio" name="wc_custom_prices_variable_price_display" value="starting_from" <?php checked($variable_price_display, 'starting_from'); ?> />
                <?php esc_html_e('Mostrar a partir de (menor preço)', 'woocommerce-custom-prices'); ?>
            </label>

            <h2><?php esc_html_e('Métodos de Pagamento', 'woocommerce-custom-prices'); ?></h2>
            <table class="form-table">
                <?php foreach ($gateways as $gateway_id => $gateway) : ?>
                    <tr>
                        <td><?php echo esc_html($gateway->get_title()); ?></td>
                        <td>
                            <label>
                                <input type="radio" name="payment_type_<?php echo esc_attr($gateway_id); ?>" value="avista" <?php checked(in_array($gateway_id, $avista_methods)); ?> />
                                <?php esc_html_e('À Vista', 'woocommerce-custom-prices'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="payment_type_<?php echo esc_attr($gateway_id); ?>" value="aprazo" <?php checked(in_array($gateway_id, $aprazo_methods)); ?> />
                                <?php esc_html_e('A Prazo', 'woocommerce-custom-prices'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="payment_type_<?php echo esc_attr($gateway_id); ?>" value="" <?php checked(!in_array($gateway_id, $avista_methods) && !in_array($gateway_id, $aprazo_methods)); ?> />
                                <?php esc_html_e('Nenhum', 'woocommerce-custom-prices'); ?>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function wc_custom_prices_fields_settings() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wc_custom_prices_fields_nonce']) && wp_verify_nonce($_POST['wc_custom_prices_fields_nonce'], 'wc_custom_prices_fields')) {
        $required_fields = isset($_POST['required_fields']) ? array_map('sanitize_text_field', $_POST['required_fields']) : [];
        $custom_fields_saved = false;

        // Salvar campos obrigatórios
        update_option('wc_custom_prices_required_fields', $required_fields);

        // Processar campos personalizados
        if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
            $custom_fields = [];
            foreach ($_POST['custom_fields'] as $index => $field) {
                if (is_array($field) && !empty($field['name'])) { // Ignorar campos vazios
                    $custom_fields[$index] = [
                        'name' => sanitize_text_field($field['name']),
                        'required' => isset($field['required']) ? (bool) $field['required'] : false,
                    ];
                }
            }
            update_option('wc_custom_prices_custom_fields', $custom_fields);
            $custom_fields_saved = true;
        } else {
            // Remover todos os campos personalizados caso não existam mais
            update_option('wc_custom_prices_custom_fields', []);
        }

        // Salvar página de registro
        if (isset($_POST['registration_page'])) {
            $registration_page = sanitize_text_field($_POST['registration_page']);
            update_option('wc_custom_prices_registration_page', $registration_page);
        }

        // Exibe a mensagem de sucesso
        if ($custom_fields_saved || isset($_POST['required_fields']) || isset($_POST['registration_page'])) {
            echo '<div class="updated"><p>' . __('Configurações salvas!', 'woocommerce-custom-prices') . '</p></div>';
        }
    }

    $required_fields = get_option('wc_custom_prices_required_fields', []);
    $custom_fields = get_option('wc_custom_prices_custom_fields', []);
    $registration_page = get_option('wc_custom_prices_registration_page', '');
    $pages = get_pages();
    ?>

    <h2><?php esc_html_e('Página de Registro', 'woocommerce-custom-prices'); ?></h2>
    <!-- Código HTML continua como está -->

    <h2><?php esc_html_e('Campos Personalizados', 'woocommerce-custom-prices'); ?></h2>

    <button type="button" id="add-field" class="button button-primary"><?php esc_html_e('Adicionar Campo', 'woocommerce-custom-prices'); ?></button>

    <p class="description"><?php esc_html_e('Adicione campos personalizados, marque como obrigatório se necessário.', 'woocommerce-custom-prices'); ?></p>

    <div id="custom-fields-container" style="margin-top:20px;">
        <?php foreach ($custom_fields as $index => $field) : ?>
            <div class="custom-field">
                <input type="text" name="custom_fields[<?php echo $index; ?>][name]" value="<?php echo esc_attr($field['name']); ?>" placeholder="Nome do Campo">
                <input type="checkbox" name="custom_fields[<?php echo $index; ?>][required]" <?php checked($field['required'], true); ?> /> <?php esc_html_e('Obrigatório', 'woocommerce-custom-prices'); ?>
                <button type="button" class="remove-field button button-secondary">Remover</button>
            </div>
        <?php endforeach; ?>
    </div>

    <?php wp_nonce_field('wc_custom_prices_fields', 'wc_custom_prices_fields_nonce'); ?>
    <?php submit_button(); ?>

    <script>
        document.getElementById('add-field').addEventListener('click', function() {
            var container = document.getElementById('custom-fields-container');
            var newIndex = container.children.length;
            var newField = document.createElement('div');
            newField.classList.add('custom-field');
            newField.innerHTML = `
                <input type="text" name="custom_fields[${newIndex}][name]" placeholder="Nome do Campo">
                <input type="checkbox" name="custom_fields[${newIndex}][required]" /> <?php esc_html_e('Obrigatório', 'woocommerce-custom-prices'); ?>
                <button type="button" class="remove-field button button-secondary">Remover</button>
            `;
            container.appendChild(newField);

            // Adicionar evento de remoção
            newField.querySelector('.remove-field').addEventListener('click', function() {
                container.removeChild(newField);
            });
        });

        // Adicionar evento de remoção aos campos existentes
        document.querySelectorAll('.remove-field').forEach(function(button) {
            button.addEventListener('click', function() {
                var field = button.closest('.custom-field');
                field.parentNode.removeChild(field);
            });
        });
    </script>

    <?php
}


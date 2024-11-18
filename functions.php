<?php


// Exibir preços apenas para usuários logados, se configurado
function wc_custom_prices_maybe_hide_prices() {
    $show_prices = get_option('wc_custom_prices_show_prices', 'all');

    if ($show_prices === 'logged_in' && !is_user_logged_in()) {
        // Ocultar preços e exibir mensagem
        add_filter('woocommerce_get_price_html', function() {
            return __('Faça login para ver os preços.', 'woocommerce-custom-prices');
        });

        // Substituir o botão "Adicionar ao Carrinho" para redirecionar para login/cadastro
        add_filter('woocommerce_loop_add_to_cart_link', 'wc_redirect_to_login_for_cart', 10, 2);
        add_filter('woocommerce_single_product_summary', 'wc_redirect_to_login_on_single', 31);
    }
}
add_action('init', 'wc_custom_prices_maybe_hide_prices');

// Redirecionar botão de adicionar ao carrinho na página de loop
function wc_redirect_to_login_for_cart($button, $product) {
    $login_url = wc_get_page_permalink('myaccount'); // URL da página de login/cadastro do WooCommerce
    $button = sprintf(
        '<a href="%s" class="button">%s</a>',
        esc_url($login_url),
        __('Faça login para comprar', 'woocommerce-custom-prices')
    );
    return $button;
}

// Substituir botão "Adicionar ao Carrinho" na página de produto único
function wc_redirect_to_login_on_single() {
    if (!is_user_logged_in()) {
        $login_url = wc_get_page_permalink('myaccount'); // URL da página de login/cadastro do WooCommerce
        echo sprintf(
            '<a href="%s" class="button single_add_to_cart_button">%s</a>',
            esc_url($login_url),
            __('Faça login para comprar', 'woocommerce-custom-prices')
        );
    }
}

// Personalizar exibição de preços para produtos variáveis
function wc_custom_prices_variable_price_display($price, $product) {
    if ($product->is_type('variable')) {
        $variable_price_display = get_option('wc_custom_prices_variable_price_display', 'range');

        if ($variable_price_display === 'starting_from') {
            $min_price = $product->get_variation_price('min', true);
            return sprintf(__('A partir de %s', 'woocommerce-custom-prices'), wc_price($min_price));
        }
    }
    return $price;
}
add_filter('woocommerce_get_price_html', 'wc_custom_prices_variable_price_display', 10, 2);


// Adicionar campo "Preço à Vista" na tela de edição/cadastro de produto
function wc_custom_prices_add_custom_field() {
    // Campo para produtos simples e variáveis
    woocommerce_wp_text_input([
        'id' => '_vista_price',
        'label' => __('Preço à Vista (R$)', 'woocommerce'),
        'description' => __('Insira o preço à vista para este produto.', 'woocommerce'),
        'type' => 'text',
        'desc_tip' => true,
        'data_type' => 'price',
    ]);
}
add_action('woocommerce_product_options_pricing', 'wc_custom_prices_add_custom_field');


// Adicionar o campo "Preço à Vista" para variações
function wc_custom_prices_add_vista_price_to_variations($loop, $variation_data, $variation) {
    woocommerce_wp_text_input([
        'id' => '_vista_price_' . $variation->ID,
        'name' => 'vista_price[' . $variation->ID . ']',
        'value' => get_post_meta($variation->ID, '_vista_price', true),
        'label' => __('Preço à Vista (R$)', 'woocommerce'),
        'description' => __('Insira o preço à vista para esta variação.', 'woocommerce'),
        'type' => 'text',
        'desc_tip' => true,
        'data_type' => 'price'
    ]);
}
add_action('woocommerce_product_after_variable_attributes', 'wc_custom_prices_add_vista_price_to_variations', 10, 3);


function wc_custom_prices_enqueue_admin_scripts($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_script('wc-custom-prices-admin', plugin_dir_url(__FILE__) . 'admin.js', ['jquery'], '1.0', true);
    }
}
add_action('admin_enqueue_scripts', 'wc_custom_prices_enqueue_admin_scripts');


// Salvar valor do "Preço à Vista" ao salvar o produto
function wc_custom_prices_save_custom_field($post_id) {
    if (isset($_POST['_vista_price'])) {
        $vista_price = wc_format_decimal($_POST['_vista_price']);
        if (!empty($vista_price)) {
            update_post_meta($post_id, '_vista_price', $vista_price);
        } else {
            delete_post_meta($post_id, '_vista_price');
        }
    }
}
add_action('woocommerce_process_product_meta', 'wc_custom_prices_save_custom_field');


function wc_custom_prices_save_vista_price($variation_id) {
    if (isset($_POST['vista_price'][$variation_id])) {
        $vista_price = wc_format_decimal($_POST['vista_price'][$variation_id]);
        if (!empty($vista_price)) {
            update_post_meta($variation_id, '_vista_price', $vista_price);
        } else {
            delete_post_meta($variation_id, '_vista_price');
        }
    }
}
add_action('woocommerce_save_product_variation', 'wc_custom_prices_save_vista_price', 10, 1);

// Alterar a exibição do preço no frontend para incluir o preço à vista
function wc_custom_prices_display_vista_price($price, $product) {
    $vista_prices = [];
    $default_prices = [];
    $variable_price_display = get_option('wc_custom_prices_variable_price_display', 'range');

    // Detectar se estamos na página de descrição do produto
    $is_product_page = is_product();

    if ($product->is_type('variable')) {
        foreach ($product->get_available_variations() as $variation) {
            $vista_price = get_post_meta($variation['variation_id'], '_vista_price', true);
            $default_price = $variation['display_price'];

            if (!empty($vista_price)) {
                $vista_prices[] = floatval($vista_price);
            }

            if (!empty($default_price)) {
                $default_prices[] = floatval($default_price);
            }
        }

        if (!empty($vista_prices)) {
            $min_vista_price = min($vista_prices);
            $min_default_price = !empty($default_prices) ? min($default_prices) : $min_vista_price;

            if ($variable_price_display === 'starting_from') {
                $price = sprintf(
                    __('A partir de %s (à vista)', 'woocommerce'),
                    wc_price($min_vista_price)
                );

                if ($is_product_page) {
                    $price .= sprintf(
                        '<br>' . __('ou %s (parcelado sem juros)', 'woocommerce'),
                        wc_price($min_default_price)
                    );
                }
            } else {
                $price = sprintf(
                    __('De %s a %s (à vista)', 'woocommerce'),
                    wc_price($min_vista_price),
                    wc_price(max($vista_prices))
                );

                if ($is_product_page) {
                    $price .= sprintf(
                        '<br>' . __('ou %s (parcelado sem juros)', 'woocommerce'),
                        wc_price($min_default_price)
                    );
                }
            }
        } elseif (!empty($default_prices)) {
            $min_default_price = min($default_prices);
            $max_default_price = max($default_prices);

            if ($variable_price_display === 'starting_from') {
                $price = sprintf(
                    __('A partir de %s', 'woocommerce'),
                    wc_price($min_default_price)
                );

                if ($is_product_page) {
                    $price .= sprintf(
                        '<br>' . __('ou %s (parcelado sem juros)', 'woocommerce'),
                        wc_price($min_default_price)
                    );
                }
            } else {
                $price = sprintf(
                    __('De %s a %s', 'woocommerce'),
                    wc_price($min_default_price),
                    wc_price($max_default_price)
                );

                if ($is_product_page) {
                    $price .= sprintf(
                        '<br>' . __('ou %s (parcelado sem juros)', 'woocommerce'),
                        wc_price($min_default_price)
                    );
                }
            }
        }
    } elseif ($product->is_type('variation')) {
        $vista_price = get_post_meta($product->get_id(), '_vista_price', true);
        $default_price = $product->get_price();

        if (!empty($vista_price)) {
            $price = sprintf(
                __('À vista: %s', 'woocommerce'),
                wc_price($vista_price)
            );

            if ($is_product_page) {
                $price .= sprintf(
                    '<br>' . __('ou %s (parcelado sem juros)', 'woocommerce'),
                    wc_price($default_price)
                );
            }
        }
    } else {
        $vista_price = get_post_meta($product->get_id(), '_vista_price', true);
        $default_price = $product->get_price();

        if (!empty($vista_price)) {
            $price = sprintf(
                __('À vista: %s', 'woocommerce'),
                wc_price($vista_price)
            );

            if ($is_product_page) {
                $price .= sprintf(
                    '<br>' . __('ou %s (parcelado sem juros)', 'woocommerce'),
                    wc_price($default_price)
                );
            }
        }
    }

    return $price;
}
add_filter('woocommerce_get_price_html', 'wc_custom_prices_display_vista_price', 10, 2);

# Esconde ou exibe o campo SKU nos detalhes do produto
function wc_custom_prices_hide_sku() {
    // Verificar se a opção está ativada no painel de configurações
    if (get_option('wc_custom_prices_hide_sku', 0) == 1) {
        // Remover a exibição do SKU, desanexando a ação que exibe o meta do produto
        remove_action('woocommerce_product_meta_end', 'woocommerce_template_single_meta', 40);
        
        // Também podemos ocultar o SKU usando CSS (para garantir que nenhum outro método esteja exibindo o SKU)
        add_filter('woocommerce_product_meta_end', function() {
            echo '<style>.product_meta .sku_wrapper { display: none !important; }</style>';
        });
    }
}
add_action('wp', 'wc_custom_prices_hide_sku');


// Adicionar campos personalizados no cadastro
function custom_registration_form_shortcode() {
    if (is_user_logged_in()) {
        return '<p>' . __('Você já está logado.', 'woocommerce') . '</p>';
    }

    // Obtenha os campos obrigatórios e personalizados do banco de dados
    $required_fields = get_option('wc_custom_prices_required_fields', []);
    $custom_fields = get_option('wc_custom_prices_custom_fields', []);

    ob_start();
    ?>
    <form method="post" class="woocommerce-form woocommerce-form-register register">

        <?php do_action('woocommerce_register_form_start'); ?>

        <!-- Adicionar campos obrigatórios -->
        <?php foreach ($required_fields as $field) : ?>
            <?php if ($field === 'email') continue; // Ignorar o campo email ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="reg_<?php echo esc_attr($field); ?>"><?php echo ucfirst(str_replace('_', ' ', $field)); ?>&nbsp;<span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="<?php echo esc_attr($field); ?>" id="reg_<?php echo esc_attr($field); ?>" value="<?php echo esc_attr(!empty($_POST[$field]) ? $_POST[$field] : ''); ?>" />
            </p>
        <?php endforeach; ?>

        <!-- Campos padrão do WooCommerce -->
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="reg_email"><?php esc_html_e('Email', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
            <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo esc_attr(!empty($_POST['email']) ? $_POST['email'] : ''); ?>" />
        </p>

        <!-- Adicionar campos personalizados -->
        <?php foreach ($custom_fields as $field) : ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="custom_<?php echo esc_attr(sanitize_text_field($field['name'])); ?>"><?php echo esc_html($field['name']); ?><?php if (!empty($field['required'])) : ?>&nbsp;<span class="required">*</span><?php endif; ?></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="custom_<?php echo esc_attr(sanitize_text_field($field['name'])); ?>" id="custom_<?php echo esc_attr(sanitize_text_field($field['name'])); ?>" value="<?php echo esc_attr(!empty($_POST['custom_' . $field['name']]) ? sanitize_text_field($_POST['custom_' . $field['name']]) : ''); ?>" />
            </p>
        <?php endforeach; ?>

        <?php do_action('woocommerce_register_form'); ?>

        <p class="woocommerce-form-row form-row">
            <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
            <button type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e('Register', 'woocommerce'); ?>"><?php esc_html_e('Registrar', 'woocommerce'); ?></button>
        </p>

        <?php do_action('woocommerce_register_form_end'); ?>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_registration_form', 'custom_registration_form_shortcode');


add_action('woocommerce_register_post', 'validate_custom_registration_fields', 10, 3);
function validate_custom_registration_fields($username, $email, $validation_errors) {
    $required_fields = get_option('wc_custom_prices_required_fields', []);
    $custom_fields = get_option('wc_custom_prices_custom_fields', []);

    // Validação dos campos obrigatórios
    foreach ($required_fields as $field) {
        if (empty($_POST[$field]) && $field !== 'email') {
            $validation_errors->add('required_field', sprintf(__('O campo %s é obrigatório.', 'woocommerce'), ucfirst(str_replace('_', ' ', $field))));
        }
    }

    // Validação dos campos personalizados
    foreach ($custom_fields as $field) {
        if (!empty($field['required']) && empty($_POST['custom_' . $field['name']])) {
            $validation_errors->add('required_custom_field', sprintf(__('O campo %s é obrigatório.', 'woocommerce'), $field['name']));
        }
    }
}


function add_register_link_to_my_account() {
    if (!is_user_logged_in()) {
        $registration_page_id = get_option('wc_custom_prices_registration_page', '');
        if ($registration_page_id) {
            $register_page_url = get_permalink($registration_page_id);
            echo '<p class="woocommerce-info register-link-container">';
            echo sprintf(
                __('Novo por aqui? <a href="%s" class="button alt register-button">Crie uma conta</a>', 'woocommerce'),
                esc_url($register_page_url)
            );
            echo '</p>';
            echo '<style>
                .register-link-container {
                    text-align: center;
                    background-color: #f7f7f7; /* Fundo leve para destacar */
                    padding: 20px;
                    border: 1px solid #e0e0e0;
                    border-radius: 10px;
                    margin-bottom: 20px;
                }

                /* Estilo do botão */
                .register-link-container .register-button {
                    background-color: #007cba; /* Cor de destaque */
                    color: #fff; /* Texto branco */
                    padding: 10px 20px;
                    border-radius: 5px;
                    text-transform: uppercase;
                    font-weight: bold;
                    text-decoration: none;
                    transition: background-color 0.3s ease;
                }

                .register-link-container .register-button:hover {
                    background-color: #005a9e; /* Cor mais escura ao passar o mouse */
                }

                .woocommerce-info{
                    display: flex;
                    flex-direction: column;
                    align-content: center;
                    justify-content: space-around;
                    align-items: center;
                }

                .woocommerce-error::before,
                .woocommerce-info::before,
                .woocommerce-message::before {
                    content: none !important; /* Remove o ícone */
                }

            </style>';
        }
    }
}
add_action('woocommerce_before_customer_login_form', 'add_register_link_to_my_account');
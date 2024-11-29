<?php

function custom_shop_scripts() {
    if ( is_shop() ) {
        wp_enqueue_script( 'custom-shop-ajax', plugin_dir_url( __FILE__ ) . 'js/custom-shop.js', array( 'jquery' ), null, true );
        wp_localize_script( 'custom-shop-ajax', 'shop_params', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'custom_shop_scripts' );

function custom_shop_styles() {
    if ( is_shop() ) { // Verifica se é a página da loja
        wp_enqueue_style( 
            'custom-shop-style', // Identificador único para o estilo
            plugin_dir_url( __FILE__ ) . 'css/shop-style.css', // Caminho para o arquivo CSS dentro do plugin
            array(), // Dependências (deixe vazio ou adicione outros estilos necessários)
            null, // Versão (pode usar null ou definir uma versão específica)
            'all' // Tipo de mídia
        );
    }
}
add_action( 'wp_enqueue_scripts', 'custom_shop_styles' );

// Funções AJAX
add_action( 'wp_ajax_load_products_by_category', 'load_products_by_category' );
add_action( 'wp_ajax_nopriv_load_products_by_category', 'load_products_by_category' );
add_action( 'wp_ajax_load_products_by_subcategory', 'load_products_by_subcategory' );
add_action( 'wp_ajax_nopriv_load_products_by_subcategory', 'load_products_by_subcategory' );
add_action( 'wp_ajax_load_more_products', 'load_more_products' );
add_action( 'wp_ajax_nopriv_load_more_products', 'load_more_products' );


function load_products_by_category() {

	error_log( 'Requisição AJAX recebida' );
    error_log( print_r( $_POST, true ) );

    ob_start();

    $category_id = $_POST['category_id'] === "all" ? 0 : intval( $_POST['category_id'] );
	$filter = $_POST['filter'];

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 16
    );

	if($category_id > 0){
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'id',
				'terms'    => $category_id,
			),
		);
	}

	$args = array_merge($args, filters_products($filter));

    $query = new WP_Query( $args );
	$total_products = $query->found_posts;

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            wc_get_template_part( 'content', 'product' );
        }
    } else {
        echo '<p>Nenhum produto encontrado.</p>';
    }

	$term = get_term($category_id);
	$image_id = get_term_meta($category_id, 'thumbnail_id', true);
	$term->thumb = $image_id ? wp_get_attachment_url($image_id) : '';
	$term->video = get_term_meta($category_id, 'product_cat_video', true);

	$video_url = $term->video;
	
	if(!empty($video_url)){
        $video_html = "<div class=\"row\">";

        // Verifica se o vídeo é do YouTube
        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
            // URL do YouTube: extraímos o ID do vídeo e criamos o código embed
            preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $matches);
            $video_id = isset($matches[1]) ? $matches[1] : '';
            if ($video_id) {
                $video_html .= "<iframe class=\"w-100\" width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$video_id}\" frameborder=\"0\" allowfullscreen></iframe>";
            }
        } 
        // Verifica se o vídeo é do Vimeo
        elseif (strpos($video_url, 'vimeo.com') !== false) {
            // URL do Vimeo: extraímos o ID do vídeo e criamos o código embed
            preg_match('/(?:vimeo\.com\/)([0-9]+)/', $video_url, $matches);
            $video_id = isset($matches[1]) ? $matches[1] : '';
            if ($video_id) {
                $video_html .= "<iframe class=\"w-100\" src=\"https://player.vimeo.com/video/{$video_id}\" width=\"640\" height=\"360\" frameborder=\"0\" allow=\"autoplay; fullscreen; picture-in-picture\" allowfullscreen></iframe>";
            }
        }
        // Caso contrário, considera como um vídeo local
        else {
            $video_html .= "<video class=\"w-100\" controls>
                <source src=\"$video_url\" type=\"video/mp4\">
                <source src=\"$video_url\" type=\"video/ogg\">
                Your browser does not support the video tag.
            </video>";
        }

        $video_html .= "</div>";
    }



    wp_reset_postdata();
    $content['products'] = ob_get_clean();
	$content['pagination'] = '<p class="woocommerce-result-count">Exibindo '. ( $total_products < 16 ? $total_products : "16" ) . ' de ' . $total_products . ' resultados</p>';
	$content['info'] = "<div class=\"card\">
							<div class=\"row\">
								<div class=\"col-12 col-md-2 card-img\">
									<img src=\"$term->thumb\" alt=\"\" class=\"w-100\">
								</div>
								<div class=\"col-12 col-md-10 card-content\">
									<h2>$term->name</h2>
									<h5>
										$term->description
									</h5>
								</div>
							</div>
							$video_html
                    </div>";

    wp_send_json_success( $content );
}

function load_products_by_subcategory() {

	error_log( 'Requisição AJAX recebida' );
    error_log( print_r( $_POST, true ) );

    ob_start();

    $subcategory_id = intval( $_POST['category_id'] );

	$filter = $_POST['filter'];

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 16,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'id',
                'terms'    => $subcategory_id,
            ),
        ),
    );

	$args = array_merge($args, filters_products($filter));

    $query = new WP_Query( $args );
	$total_products = $query->found_posts;

	$term = get_parent_category_data_by_child_id($subcategory_id);
	$video_url = $term->video;

    if(!empty($video_url)){
        $video_html = "<div class=\"row\">";

        // Verifica se o vídeo é do YouTube
        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
            // URL do YouTube: extraímos o ID do vídeo e criamos o código embed
            preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $matches);
            $video_id = isset($matches[1]) ? $matches[1] : '';
            if ($video_id) {
                $video_html .= "<iframe class=\"w-100\" width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$video_id}\" frameborder=\"0\" allowfullscreen></iframe>";
            }
        } 
        // Verifica se o vídeo é do Vimeo
        elseif (strpos($video_url, 'vimeo.com') !== false) {
            // URL do Vimeo: extraímos o ID do vídeo e criamos o código embed
            preg_match('/(?:vimeo\.com\/)([0-9]+)/', $video_url, $matches);
            $video_id = isset($matches[1]) ? $matches[1] : '';
            if ($video_id) {
                $video_html .= "<iframe class=\"w-100\" src=\"https://player.vimeo.com/video/{$video_id}\" width=\"640\" height=\"360\" frameborder=\"0\" allow=\"autoplay; fullscreen; picture-in-picture\" allowfullscreen></iframe>";
            }
        }
        // Caso contrário, considera como um vídeo local
        else {
            $video_html .= "<video class=\"w-100\" controls>
                <source src=\"$video_url\" type=\"video/mp4\">
                <source src=\"$video_url\" type=\"video/ogg\">
                Your browser does not support the video tag.
            </video>";
        }
    
        $video_html .= "</div>";
    }else{
        $video_html = "";
    }

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            wc_get_template_part( 'content', 'product' );
        }
    } else {
        echo '<p>Nenhum produto encontrado.</p>';
    }

    wp_reset_postdata();
    $content['products'] = ob_get_clean();
	$content['pagination'] = '<p class="woocommerce-result-count">Exibindo '. ( $total_products < 16 ? $total_products : "16" ) . ' de ' . $total_products . ' resultados</p>';
	$content['info'] = "<div class=\"card\">
		<div class=\"row\">
			<div class=\"col-12 col-md-2 card-img\">
				<img src=\"$term->thumb\" alt=\"\" class=\"w-100\">
			</div>
			<div class=\"col-12 col-md-10 card-content\">
				<h2>$term->name</h2>
				<h5>
					$term->description
				</h5>
			</div>
		</div>
		$video_html
	</div>";
    wp_send_json_success( $content );
}

function load_more_products() {
	if ( ! isset( $_POST['page'] ) || empty( $_POST['page'] ) ) {
        wp_send_json_error( 'Parâmetro page ausente', 400 );
    }

	if ( ! isset( $_POST['category_id'] ) || empty( $_POST['category_id'] ) ) {
		$category_id = 0;
    }else{
		$category_id = intval( $_POST['category_id'] );
	}
	
    $page = intval($_POST['page'] );

	$orderby = isset( $_GET['orderby'] ) ? wc_clean( (string) wp_unslash( $_GET['orderby'] ) ) : "relevance";
	$filter = $_POST['filter'];


	$args = array(
        'post_type' => 'product',
        'posts_per_page' => 16,
		'paged' => $page,
		'orderby' => $orderby,
		'order' => ('DESC' === $_GET['order']) ? 'DESC' : 'ASC'
    );

	if($category_id > 0){
		$args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'id',
                'terms'    => $category_id,
            ),
        );
	}

	$args = array_merge($args, filters_products($filter));

    $query = new WP_Query( $args );

    $total_products = $query->found_posts;

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            wc_get_template_part( 'content', 'product' );
        }
    }
    wp_reset_postdata();
	$content['products'] = ob_get_clean();
	$content['pagination'] = '<p class="woocommerce-result-count">Exibindo ' . ($total_products < ($page * 16) ? $total_products : ($page * 16)) . 	' de ' . $total_products . ' resultados</p>';
 
    wp_send_json_success( $content );
    wp_die();
}

function filters_products($filter){
	switch ($filter) {
		case 'menu_order': // Mais populares
			$args['orderby'] = 'menu_order';
			$args['order'] = 'ASC'; // Ordem padrão para menu_order
			break;
	
		case 'rating': // Melhor avaliados
			$args['orderby'] = 'rating';
			$args['order'] = 'DESC'; // Avaliação mais alta primeiro
			break;
	
		case 'date': // Mais recentes
			$args['orderby'] = 'date';
			$args['order'] = 'DESC'; // Mais recente primeiro
			break;
	
		case 'price': // Menor preço
			$args['meta_key'] = '_price';
			$args['orderby'] = 'meta_value_num';
			$args['order'] = 'ASC'; // Menor preço primeiro
			break;
	
		case 'price-desc': // Maior preço
			$args['meta_key'] = '_price';
			$args['orderby'] = 'meta_value_num';
			$args['order'] = 'DESC'; // Maior preço primeiro
			break;
	
		default: // Padrão (menu_order)
			$args['orderby'] = 'menu_order';
			$args['order'] = 'ASC';
			break;
	}
	return $args;
}

function get_parent_category_data_by_child_id($child_category_id) {
    $child_term = get_term($child_category_id, 'product_cat');
    if ($child_term && $child_term->parent) {
        $parent_id = $child_term->parent;
        $parent_term = get_term($parent_id, 'product_cat');
        if ($parent_term) {
			$parent_term->thumb = get_term_meta($parent_term->term_id, 'thumbnail_id', true) ? wp_get_attachment_url(get_term_meta($parent_term->term_id, 'thumbnail_id', true)) : '';
			$parent_term->video = get_term_meta($parent_term->term_id, 'product_cat_video', true);
            return $parent_term;
        }
    }
    return null;
}


add_filter( 'template_include', 'custom_shop_template', 99 );

function custom_shop_template( $template ) {
    if ( is_shop() ) {
        $custom_template = plugin_dir_path( __FILE__ ) . 'templates/archive-product.php';
        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
    }
    return $template;
}


function custom_product_loop() {
    $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== "all" ? intval($_POST['category_id']) : 0;
    $filter = isset($_POST['filter']) ? $_POST['filter'] : '';

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 16,
        'paged' => get_query_var('paged', 1),
    );

    if ($category_id > 0) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'id',
                'terms'    => $category_id,
            ),
        );
    }

    $args = array_merge($args, filters_products($filter));

    $query = new WP_Query($args);
    $total_products = $query->found_posts;

    if ($query->have_posts()) :
        echo '<ul class="products columns-4">';

        while ( $query->have_posts() ) {
            $query->the_post();
            wc_get_template_part( 'content', 'product' );
        }

        echo '</ul>';
    else :
        echo '<p>Nenhum produto encontrado.</p>';
    endif;

    wp_reset_postdata();
}
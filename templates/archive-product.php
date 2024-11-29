<?php
defined( 'ABSPATH' ) || exit;
get_header( 'shop' );
?>

<div class="shop-container">
    <div class="row">
        <div class="col-12 col-md-4 col-lg-3">
            <aside class="shop-sidebar">
                <h2>Categorias</h2>
                <ul class="product-categories">
                    <li class="category-item" data-open="false" data-sub="false" data-category-id="all">
                        <a href="#">Todos Produtos</a>
                    </li>
                    <?php
                        $categories = get_terms( array(
                            'taxonomy'   => 'product_cat',
                            'orderby'    => 'name',
                            'hide_empty' => true,
                            'parent' => 0
                        ) );

                        foreach ( $categories as $category ) {
                            $subcategories = get_terms( array(
                                'taxonomy'   => 'product_cat',
                                'orderby'    => 'name',
                                'hide_empty' => true,
                                'parent' => esc_attr($category->term_id)
                            ) );
                            $hasSub = count($subcategories) > 0 ? "true" : "false";

                            echo '<li class="category-item" data-open="false" data-sub="'.$hasSub.'" data-category-id="' . esc_attr( $category->term_id ) . '">';
                            echo '<a href="#">' . esc_html( $category->name ) . '</a>';
                            echo count($subcategories) > 0 ? '<span><i class="fas fa-plus"></i></span>' : "";
                            echo '</li>';
                            if(count($subcategories)>0){
                                echo '<ul class="subproduct-categories" data-parent="' . esc_attr( $category->term_id ) .'">';
                                foreach ($subcategories as $sub) {
                                    echo '<li class="subcategory-item" data-subcategory-id="' . esc_attr( $sub->term_id ) . '">';
                                    echo '<a href="#">' . esc_html( $sub->name ) . '</a>';
                                    echo '</li>';
                                }
                                echo '</ul>';
                            }
                        }
                    ?>
                </ul>
            </aside>
        </div>
        <div class="col-12 col-md-8 col-lg-9">
            <!-- Conteúdo principal da loja -->
            <main class="shop-main">
            
                <div class="filter-container">
                    <p class="woocommerce-result-count"></p>
                    <!-- Filtro -->
                    <div class="filter-group">
                        <label for="filter-btn">Ordenar por:</label>
                        <button id="filter-btn" class="filter-button">Mais populares</button>
                        <div class="popover" id="filter-popover">
                            <ul>
                                <li data-value="menu_order" class="active">Mais populares</li>
                                <li data-value="rating">Melhor avaliados</li>
                                <li data-value="date">Mais recentes</li>
                                <li data-value="price">Menor preço</li>
                                <li data-value="price-desc">Maior preço</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="w-100" id="category_description"></div>
                <?php
                custom_product_loop();
                do_action( 'woocommerce_after_main_content' );
                ?>
            </main>
        </div>
    </div>

</div>

<?php
get_footer( 'shop' );

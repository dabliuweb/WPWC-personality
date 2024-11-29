jQuery(document).ready(function ($) {

    var filter = "menu_order";

    const filterButton = document.getElementById('filter-btn');
    const filterPopover = document.getElementById('filter-popover');

    // Função para abrir/fechar popover
    function togglePopover(button, popover) {
        const isOpen = button.parentElement.classList.contains('open');
        closeAllPopovers();
        if (!isOpen) {
        button.parentElement.classList.add('open');
        }
    }

    function closeAllPopovers() {
        document.querySelectorAll('.filter-group').forEach((group) => group.classList.remove('open'));
    }

    // Evento para clique nos botões
    filterButton.addEventListener('click', () => togglePopover(filterButton, filterPopover));

    // Seleção de opções
    document.querySelectorAll('.popover li').forEach((item) => {
        item.addEventListener('click', function () {
        const value = this.getAttribute('data-value');
        filter = value;
        const text = this.textContent;

        // Atualiza o botão correspondente
        const parent = this.closest('.filter-group');
        const button = parent.querySelector('.filter-button');
        button.textContent = text;

        // Define ativo e fecha o popover
        this.parentElement.querySelectorAll('li').forEach((li) => li.classList.remove('active'));
        this.classList.add('active');
        closeAllPopovers();

        // Atualiza os produtos
        getProducts(categoryId);

        });
    });

    // Fecha os popovers ao clicar fora
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.filter-group')) {
        closeAllPopovers();
        }
    });

    var categoryId = "all";
    var subcategoryId = 0;
    var isSub = false;

    function getProducts(cat, parent = null){
        var data = {
            action: isSub ? 'load_products_by_subcategory' : 'load_products_by_category',
            category_id: isSub ? subcategoryId : categoryId,
            filter: filter
        };

        $.ajax({
            url: shop_params.ajax_url,
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    $('.products').html(response.data.products);
                    var resultCount = response.data.pagination
                    $('.woocommerce-result-count').replaceWith(resultCount);

                    if(!isSub && response.data.info){
                        $("#category_description").show();
                        $("#category_description").html(response.data.info)
                    }

                    if(isSub && response.data.info){
                        if(cat !== parent){
                            $("#category_description").show();
                            $("#category_description").html(response.data.info)
                        }

                        
                    }

                    if(!isSub && categoryId === "all"){
                        $("#category_description").html("");
                        $("#category_description").hide();
                    }
                } else {
                    alert('Erro ao carregar produtos.');
                }
            },
            error: function () {
                alert('Erro ao carregar produtos.');
            }
        });

    }

    function disableLi(e){
        var elements = $(".product-categories").children("li")
        elements.removeClass("active")

        var elements2 = $(".product-categories").children("ul").children("li")
        elements2.removeClass("active")

        $(e).addClass('active');
    }

    $('.category-item').on('click', function (e) {
        e.preventDefault();
        isSub = false;
        let current = categoryId;
        categoryId = $(this).data('category-id');
        
        var open = $(this).data('open');
        var sub = $(this).data('sub');

        if(sub){
            $(this).data('open', !open);

            let icon = $(this).children('span').children('i');

            if(!open){
                $(this).next().show();
                icon.removeClass('fa-plus').addClass('fa-minus');
            }else{
                $(this).next().hide();
                icon.removeClass('fa-minus').addClass('fa-plus');
            }
        }

        disableLi($(this))        
        getProducts(current);
        
    });

    $('.subcategory-item').on('click', function (e) {
        e.preventDefault();
        isSub = true;

        subcategoryId = $(this).data('subcategory-id');
        let parent = $(this).parent().data('parent');
        
        getProducts(categoryId, parent);
        categoryId = parent
        disableLi($(this))
    });

    var page = 2;
    var loading = false;

    $(window).scroll(function () {
        if (loading) return;

        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
            loading = true;

            var data = {
                action: 'load_more_products',
                page: page,
                category_id: isSub ? subcategoryId : categoryId,
                filter: filter
            };

            $.ajax({
                url: shop_params.ajax_url,
                type: 'POST',
                data: data,
                success: function (response) {
                    if (response.success && response.data.products.length > 0) {
                        $('.products').append(response.data.products);
                        page++;
                        var resultCount = response.data.pagination
                        $('.woocommerce-result-count').replaceWith(resultCount);
                        loading = false;
                    } else {
                        loading = true;
                    }
                },
                error: function () {
                    alert('Erro ao carregar mais produtos.');
                }
            });
        }
    });
});
variations = null;
attributes = null;
shopifyPrpoducts = null;
chose_variations = [];
jQuery(document).ready(function ($) {

    jQuery('#products-select').select2({
        dropdownParent: jQuery('#edit-product-modal')
    })
    jQuery('select[name=chose-variations]').each(function () {
        //this wrapped in jQuery will give us the current .letter-q div
        jQuery(this).html('');
    });

    JPIODFW_getProducts();
    if (jQuery(".table-view-list").length) {

        if (jQuery("#search_id-search-input").length) {
            var search_string = jQuery("#search_id-search-input").val();

            if (search_string != "") {
                console.log(search_string);
                jQuery(".pagination-links a").each(function () {
                    this.href = this.href + "&s=" + search_string;
                });
            }
        }
    }

    let $sobnombre = jQuery('#sob-nombre');
    let $sobdescripcion = jQuery('#sob-descripcion');
    let $sobprecio = jQuery('#sob-precio');
    let $sobimages = jQuery('#sob-images');
    let $productsselect = jQuery('#products-select');

    let $selectAll = jQuery('#selectAll');
    let $productaction = jQuery('input[type=radio][name=product-action]');
    $sobnombre.change(function () {
        if (this.checked) {
            jQuery('#row-nombre').show();
        } else {
            jQuery('#row-nombre').hide();
        }
    });
    $sobdescripcion.change(function () {
        if (this.checked) {
            jQuery('#row-descripcion').show();
        } else {
            jQuery('#row-descripcion').hide();
        }
    });
    $sobprecio.change(function () {
        if (this.checked) {
            jQuery('#row-precio').show();
        } else {
            jQuery('#row-precio').hide();
        }
    });

    $sobprecio.change(function () {
        if (this.checked) {
            jQuery('#row-precio').show();
        } else {
            jQuery('#row-precio').hide();
        }
    });
    $productaction.change(function () {
        console.log(this.value);
        if (this.value == 'SYNC') {
            jQuery('#row-products-select').show();
            $("#sob-nombre").prop('checked', false);
            $("#sob-precio").prop('checked', false);
            $("#sob-images").prop('checked', false);
            $("#sob-stock").prop('checked', false);
            $("#sob-descripcion").prop('checked', false);
            $("#row-nombre").hide();
            $("#row-descripcion").hide();
            $("#row-precio").hide();
            
            jQuery('select[name=chose-variations]').each(function () {
                //this wrapped in jQuery will give us the current .letter-q div
                jQuery(this).show();

            });
        } else {
            jQuery('#row-products-select').hide();
            jQuery('select[name=chose-variations]').each(function () {
                //this wrapped in jQuery will give us the current .letter-q div
                jQuery(this).hide();
            });
        }
    });
    $selectAll.change(function () {
        if (this.checked) {
            jQuery('input[type=checkbox][name=variations]').prop('checked', true);
        } else {
            jQuery('input[type=checkbox][name=variations]').prop('checked', false);
        }
    });
    $productsselect.change(function () {
        productSelected = this.value;
        let indexFound = shopifyPrpoducts.findIndex(e => e.id == productSelected);

        let options = '<option value="crear">Crear nueva</option>';
        if (indexFound != undefined) {

            if (shopifyPrpoducts[indexFound].variations != undefined) {
                console.log('shopifyPrpoducts[indexFound]', shopifyPrpoducts[indexFound]);
                for (const variation of shopifyPrpoducts[indexFound].variations) {

                    console.log('variation.attributes', variation.attributes);

                    let variationtitle = '';
                    Object.entries(variation.attributes).forEach(element => {

                        variationtitle += element[1] + ' ';
                    });

                    options += '<option value="' + variation.variation_id + '">' + variationtitle + '</option>';
                }

                jQuery('select[name=chose-variations]').each(function () {
                    //this wrapped in jQuery will give us the current .letter-q div
                    jQuery(this).html('');
                });

                jQuery('select[name=chose-variations]').each(function () {
                    //this wrapped in jQuery will give us the current .letter-q div
                    jQuery(this).append(options);
                });

                jQuery('select[name=chose-variations]').each(function () {
                    //this wrapped in jQuery will give us the current .letter-q div
                    jQuery(this).show();
                });

            } else {
                jQuery('select[name=chose-variations]').each(function () {
                    //this wrapped in jQuery will give us the current .letter-q div
                    jQuery(this).hide();
                });


            }


        }
    });

    $(".img-dropi-import").on("click", async function (e) {
        e.preventDefault();
        let img_url = jQuery(this).data('src');
        console.log(img_url);
        Swal.fire({
            showConfirmButton: false,
            showCloseButton: true,
            imageUrl: img_url,
            // imageHeight: 1500,
            imageAlt: ''
        })
    });


    $(".btn-dropi-import").on("click", async function (e) {
        e.preventDefault();
        let product_name = jQuery(this).data('name');
        let product_id = jQuery(this).data('id');
        let product_description = jQuery(this).data('description');
        let product_price = jQuery(this).data('price');
        let url = jQuery(this).attr('href');
        let item = jQuery(this).data('item');
        let store =jQuery(this).data('store');
        tr = jQuery(this).closest("tr");

        $("#variant-select").html('');
        $("#products-select").val('');
        jQuery('#row-products-select').hide();
        jQuery("#new-product").prop("checked", true);


        jQuery("#product-name").val(product_name);
        jQuery("#product-description").val(product_description);
        jQuery("#product-id").val(product_id);
        jQuery("#product-price").val(product_price);
        jQuery("#product-url").val(url);
        jQuery('#store').val(store);


        attributes = item.attributes;

        variations = item.variations;
        let options = ' ';
        console.log(item.type);
        if (item.type == 'VARIABLE' && item.variations != undefined && item.variations.length > 0) {


            $("#row-variations").show();

            item.variations.forEach(variation => {
                options += '<div class="row"><div class="col">' +
                    '<input id="variation" name="variations" checked type="checkbox" value="' + variation.id + '" class="focus:ring-indigo-500' +
                    'h-4 w-4 text-indigo-600 border-gray-300 rounded">' +
                    '' +
                    '<label for="candidates" class="font-medium text-gray-700">';
                variation.attribute_values.forEach(attr => {
                    options += attr.value + '/';
                });
                options += '</label></div><div class="col">';

                options += '<select class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" name="chose-variations" id="chose-variations-' + variation.id + '">';
                options += '<option></option></select>';
                options += '  </div></div>';
            });
            console.log('options', options);
            $("#variant-select").append(options);

            jQuery('select[name=chose-variations]').each(function () {
                //this wrapped in jQuery will give us the current .letter-q div
                jQuery(this).hide();
            });

        } else {
            $("#row-variations").hide();

        }



        //MOSTRARMODAL DE EDICION
        JPIODFW_myModal = new bootstrap.Modal(document.getElementById('edit-product-modal'), {
            backdrop: 'static',
            keyboard: false
        })

        JPIODFW_myModal.show();

        

    });
});
JPIODFW_myModal = null;

tr = null;


/**
 * manda a traer los productos de dropi
 */
function JPIODFW_getProducts() {

    let data = {};
    const url = ajax_var.url;
    jQuery.ajax({
        type: "GET",
        dataType: "json",
        url: url,
        data: "action=" + ajax_var.action + "&nonce=" + ajax_var.nonce,
        cache: false,
        method: 'GET',

        success: function (response) {
            shopifyPrpoducts = response;

            let opciones = '';

            opciones += "<option value=''>Selecciona un producto</option>"
            response.forEach(element => {
                opciones += "<option value='" + element.id + "'>" + element.name + " - " + element.id + " - " + element.sku + "</option>"
            });

            jQuery("#products-select").append(opciones);


            jQuery('#products-select').select2();

            jQuery('#products-select').select2({
                width: '100%',

                dropdownParent: jQuery('#edit-product-modal')
            });



        },
        error: function (error) {
            console.log(error);
            Swal.close();
            JPIODFW_showAlert('Error', error.statusText, 'error', 'Error al obtener productos');

        }
    });


}



/**funcion que procesa el fomrulario modal */
function JPIODFW_proces_form() {
    chose_variations = [];
    let product_name = jQuery("#product-name").val();
    let product_description = jQuery("#product-description").val();
    let product_id = jQuery("#product-id").val();
    let product_price = jQuery("#product-price").val();
    //la url que genera el backend con el nonce
    let product_url = jQuery("#product-url").val();
    let sob_nombre = jQuery("#sob-nombre").is(':checked');
    let sob_descripcion = jQuery("#sob-descripcion").is(':checked');
    let sob_precio = jQuery("#sob-precio").is(':checked');
    let sob_images = jQuery("#sob-images").is(':checked');
    let sob_stock = jQuery("#sob-stock").is(':checked');
    let store =  jQuery("#store").val();

    let variationstoimport = jQuery.map(jQuery('input[type=checkbox][name=variations]:checked'), function (c) {
        return c.value;
    })

    jQuery('input[type=checkbox][name=variations]:checked').each(function () {
        //let item=  {}{'`${this.value}`':jQuery('#chose-variations-'+this.value).val()};
        if (jQuery('#chose-variations-' + this.value).val() != 'crear') {
            let item = {};

            item[this.value] = jQuery('#chose-variations-' + this.value).val();
            chose_variations.push(item);
        }

    })
    console.log('chose_variations', chose_variations);

    let productaction = jQuery('input[type=radio][name=product-action]:checked').val();

    let productselect = jQuery("#products-select").val();

    JPIODFW_import(product_url, product_name, product_description, product_price,
        sob_nombre, sob_descripcion, sob_precio, sob_images, variationstoimport, productaction, productselect, chose_variations, sob_stock, store);
}

function JPIODFW_showAlert(title, text, incon, confirmButtonText) {
    Swal.fire({
        title: title,
        text: text,
        icon: incon,
        confirmButtonText: confirmButtonText,
        allowOutsideClick: false
    })
}


function JPIODFW_import(product_url, product_name, product_description, product_price, sob_nombre,
    sob_descripcion, sob_precio, sob_images, variationstoimport, productaction, productselect, chose_variations, sob_stock, store) {

    let data = {};
    if (product_name != undefined) {
        data.product_name = product_name;
        data.product_description = product_description;
        data.product_price = product_price;
        data.sob_nombre = sob_nombre;
        data.sob_descripcion = sob_descripcion;
        data.sob_precio = sob_precio;
        data.sob_images = sob_images;
        data.sob_stock = sob_stock;
        data.variationstoimport = variationstoimport;
        data.productaction = productaction;
        data.productselect = productselect;
        data.variations = variations;
        data.attributes = attributes;
        data.store = store;

    }

    if (chose_variations != undefined) {
        data.chose_variations = chose_variations
    }


    JPIODFW_showAlert('Importing product', 'Please wait...', 'info', '');
    Swal.showLoading();


    jQuery.ajax({
        type: "POST",
        dataType: "json",
        url: ajax_var.url + product_url,
        cache: false,
        method: 'POST',
        data: data,

        success: function (response) {

            Swal.close();
            if (response.success == true) {
                let form = jQuery("edit-product-form");
                form.trigger("reset");
                //MOSTRARMODAL DE EDICION


                if (JPIODFW_myModal != null && JPIODFW_myModal != undefined) {
                    JPIODFW_myModal.hide();
                }

                // col7 = tr.find("td:eq(8)").html('<span class="dashicons dashicons-yes" style="color:#2bee2b"></span>'); // get 
                JPIODFW_showAlert('Felicidades!', 'El producto ha sido sincronizado con dropi exitosamente', 'success', 'Ok');

            } else {
                console.log(response);
                JPIODFW_showAlert('Error', response.message, 'error', 'OK');

            }


        },
        error: function (error) {
            console.log(error);
            Swal.close();
            JPIODFW_showAlert('Error', error.statusText, 'error', 'Error');

        }
    });
}
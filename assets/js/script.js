jQuery(function($) {
    const urlParams = new URLSearchParams(window.location.search);
    const productValue = urlParams.get('product_value');
    
    if (productValue) {
        $('#form-field-_product').val(decodeURIComponent(productValue));
    }
});
jQuery(document).ready(function ($) {

    function JPIODFW_showAlert(title, text, incon, confirmButtonText) {
        Swal.fire({
            title: title,
            text: text,
            icon: incon,
            confirmButtonText: confirmButtonText
        })
    }
    $(".wc-action-button-sync-to-dropi").on("click", function (e) {
        e.preventDefault();
        JPIODFW_showAlert('Syncing with dropi', 'Please wait...', 'info', '');
        Swal.showLoading()
        let url = jQuery(this).attr('href');
        jQuery.ajax({
            type: "POST",
            dataType: "json",
            url: url,
            cache: false,
            method: 'POST',

            success: function (response) {
                console.log(response),
                Swal.close();
                if (response.success == true) {
                    JPIODFW_showAlert('Felicidades!', 'La orden ha sido sincronizada con dropi exitosamente', 'success', 'Ok');

                } else {
                    JPIODFW_showAlert('Error', response.message, 'error', 'OK');

                }
                

            },
            error: function (error) {
                console.log(error);
                Swal.close();
                JPIODFW_showAlert('Error', error.statusText, 'error', 'OK');
               
            }
        });
    });
});
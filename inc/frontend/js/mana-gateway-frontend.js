jQuery(function ($) {
    $('#mgw-payment').submit(function () {
        var valid = true;
        if ($('#mgw-name').val() === '') {
            valid = false;
            $('#mgw-name').attr('style', 'border-color: #f45336; color: #f45336;');
        }
        else {
            $('#mgw-name').removeAttr('style');
        }
        var re = /\S+@\S+\.\S+/;
        if (!re.test($('#mgw-email').val())) {
            valid = false;
            $('#mgw-email').attr('style', 'border-color: #f45336; color: #f45336;');
        }
        else {
            $('#mgw-email').removeAttr('style');
        }
        re = /09\d{9}/;
        if (!re.test($('#mgw-mobile').val())) {
            valid = false;
            $('#mgw-mobile').attr('style', 'border-color: #f45336; color: #f45336;');
        }
        else {
            $('#mgw-mobile').removeAttr('style');
        }
        if ($('#mgw-address').val() === '') {
            valid = false;
            $('#mgw-address').attr('style', 'border-color: #f45336; color: #f45336;');
        }
        else {
            $('#mgw-address').removeAttr('style');
        }
        if ($('#mgw-gateway').val() === 'empty') {
            valid = false;
            $('#mgw-gateway').attr('style', 'border-color: #f45336; color: #f45336;');
        }
        else {
            $('#mgw-gateway').removeAttr('style');
        }
        if (!valid) {
            window.scrollTo(0, 0);
        }
        return valid;
    });
});
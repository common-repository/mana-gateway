jQuery(function ($) {
    $("#mgw-gateway-list")
        .on('click', '.mgw-arrow-button-up', 'up', reorder)
        .on('click', '.mgw-arrow-button-down', 'down', reorder)
        .on('click', '.mgw-tgl-btn', function () {
            let title = $(this).attr('title');
            $(this).attr('title', $(this).attr('toggled-title'));
            $(this).attr('toggled-title', title);
            $.ajax({
                url: mgw_params.ajaxurl,
                method: 'POST',
                data: {
                    'action': mgw_params.plugin_text_domain + '_toggle_gateway_status',
                    'id': $(this).attr('data-id'),
                    'nonce': $(this).attr('data-nonce')
                }
            })
        });
    $(".mgw-set > a").on("click", function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(this)
                .siblings(".mgw-content")
                .slideUp(200);
        } else {
            $(this).removeClass("active");
            $(this).addClass("active");
            $(".content").slideUp(200);
            $(this)
                .siblings(".mgw-content")
                .slideDown(200);
        }
    });
    $('.column-status > .dashicons-no-alt').parent().parent().css('background-color', '#ff000033');
    $('.column-status > .dashicons-yes').parent().parent().css('background-color', '#00800033');

    let urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('filter') === 'success') {
        $('.display-only#status_success').prop('checked', 'checked');
    }
    else if (urlParams.get('filter') === 'error') {
        $('.display-only#status_error').prop('checked', 'checked');
    }
    else {
        $('.display-only#status_all').prop('checked', 'checked');
    }

    $('.display-only').change(function () {
        let urlParams = new URLSearchParams(window.location.search);
        let url = window.location.href.split('?')[0];
        if (urlParams.has('filter')) {
            urlParams.set('filter', $(this).val().toString());
            if ($(this).val().toString() === 'all') {
                urlParams.delete('filter');
            }
        }
        else {
            urlParams.append('filter', $(this).val().toString());
        }
        url += '?' + urlParams.toString();
        document.location = url;
    });

    function reorder(event) {
        let tr = $(this).parent().parent();
        let tbody = tr.parent();
        let index = tr.index();
        if (((event.data === 'up') && (tr.index() !== 0)) || ((event.data === 'down') && (!tr.is(':last-child')))) {
            let other = $("tr:nth-child(" + (tr.index() + ((event.data === 'up') ? 0 : 2)) + ")", tbody);
            let tcontent = other.html();
            other.html(tr.html());
            tr.html(tcontent);
            $.ajax({
                url: mgw_params.ajaxurl,
                method: 'POST',
                data: {
                    'action': mgw_params.plugin_text_domain + '_reorder_gateways',
                    'index': index,
                    'direction': event.data
                }
            })
        }
    }
});


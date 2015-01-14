jQuery(document).ready(function () {
    jQuery('[data-toggle="tooltip"]').tooltip({html: true});
    jQuery('.collapse').collapse();

    jQuery('.dropdown-toggle').dropdown();

    jQuery('.submit').click(function (event) {
        event.preventDefault();
        var form = jQuery(this).parents('form');
        var formAction = jQuery(form).attr('action');
        var cssClass = 'alert alert-dismissable';

        jQuery.ajax({
            url: formAction,
            method: 'post',
            dataType: 'json',
            data: jQuery(form).serialize(),
            success: function (response) {
                if (typeof response.success != 'undefined' && response.success == true) {
                    var message = '';
                    jQuery.each(response.messages, function (index, object) {
                        if (typeof object != 'undefined') {
                            message += object['text'] + "\n";
                        }
                        if (object.type == 'error') {
                            cssClass += ' alert-danger';
                        } else if (object.type == 'success') {
                            cssClass += ' alert-success';
                        }
                    });

                    jQuery('.flash-messages').html(jQuery('<div/>', {
                        html: message,
                        'class': cssClass
                    })).append(jQuery('<button/>', {
                        'class': 'close',
                        'data-dismiss': 'alert',
                        'aria-hidden': 'true'
                    }));
                }
            }
        });
    });
});
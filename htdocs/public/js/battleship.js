jQuery(document).ready(function () {
    jQuery('[data-toggle="tooltip"]').tooltip({html: true});
    jQuery('.collapse').collapse();

    jQuery('.dropdown-toggle').dropdown();

    jQuery('.submit').click(function (event) {
        event.preventDefault();
        var form = jQuery(this).parents('form');
        var formAction = jQuery(form).attr('action');
        var cssClass = 'alert alert-dismissable';
        var button = jQuery(this);
        var formData = jQuery(form).serialize();
        var hitCoordinate = formData.replace('field_coordinates=', '').toUpperCase();

        jQuery.ajax({
            url: formAction,
            method: 'post',
            dataType: 'json',
            data: jQuery(form).serialize(),
            success: function (response) {
                if (typeof response.success != 'undefined' && response.success == true) {
                    var message = '';
                    var labelClass = '';
                    jQuery.each(response.messages, function (index, object) {
                        if (typeof object != 'undefined') {
                            message += object['text'] + "\n";
                        }
                        if (object.type == 'error') {
                            labelClass = 'danger';
                            cssClass += ' alert-danger';
                        } else if (object.type == 'success') {
                            labelClass = 'success';
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

                    jQuery('#' + hitCoordinate).parents('td').html(jQuery('<span/>', {
                        class: 'label label-' + labelClass,
                        text: response.statusSign
                    }));
                }
            }
        });
    });
});
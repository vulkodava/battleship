jQuery(document).ready(function () {
    jQuery('.submit').click(function () {
        jQuery(this).attr('disabled', 'disabled').val('...');
    });

    jQuery('[data-toggle="tooltip"]').tooltip({html: true});
    jQuery('.collapse').collapse();

    jQuery('.dropdown-toggle').dropdown();
});
jQuery(document).ready(function ($) {

    jQuery('input#PLWN_logo_media_manager').click(function (e) {
        e.preventDefault();
        var image_frame;
        if (image_frame) {
            image_frame.open();
        }
        image_frame = wp.media({
            title: 'Select Media',
            multiple: false,
            library: {
                type: 'image',
            }
        });
        image_frame.on('close', function () {
            var selection = image_frame.state().get('selection');
            var gallery_ids = new Array();
            var my_index = 0;
            selection.each(function (attachment) {
                gallery_ids[my_index] = attachment['id'];
                my_index++;
            });
            var ids = gallery_ids.join(",");
            if (ids.length === 0) return true;
            jQuery('input#PLWN-logo_image_id').val(ids);
            Refresh_Image(ids);
        });
        image_frame.on('open', function () {
            var selection = image_frame.state().get('selection');
            var ids = jQuery('input#PLWN-logo_image_id').val().split(',');
            ids.forEach(function (id) {
                var attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add(attachment ? [attachment] : []);
            });
        });
        image_frame.open();
    });
});

// Ajax request to refresh the image preview
function Refresh_Image(the_id) {
    var data = {
        action: 'PLWN_logo_get_image',
        id: the_id,
        nonce: PLWN_admin_plugin_ajax.nonce
    };

    jQuery.get(ajaxurl, data, function (response) {

        if (response.success === true) {
            jQuery('#PLWN-logo-preview-image').replaceWith(response.data.image);
        }
    });
}
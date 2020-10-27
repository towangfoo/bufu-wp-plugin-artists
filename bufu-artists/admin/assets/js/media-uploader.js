/**
 * Load media uploader on pages with our custom metabox
 */
jQuery(document).ready(function($){
    'use strict';

    var metaImageFrame;

    $( 'body' ).click(function(e) {
        var btn = e.target;
        if ( !btn || !$( btn ).attr( 'data-media-uploader-target' ) ) return;

        e.preventDefault();

        var fieldId = $( btn ).data( 'media-uploader-target' );

        metaImageFrame = wp.media.frames.metaImageFrame = wp.media({
            title: bufu_artist_admin_meta_media_upload.title,
            button: { text:  bufu_artist_admin_meta_media_upload.button },
        });

        metaImageFrame.on('select', function() {
            // Grabs the attachment selection and creates a JSON representation of the model.
            var media_attachment = metaImageFrame.state().get('selection').first().toJSON();

            // Sends the attachment URL to our custom image input field.
            $( fieldId ).val(media_attachment.url);

            // show preview
            $( fieldId + '_preview'  ).removeClass('hidden').attr({ 'src': media_attachment.sizes.medium.url });
        });

        metaImageFrame.open();
    });
});
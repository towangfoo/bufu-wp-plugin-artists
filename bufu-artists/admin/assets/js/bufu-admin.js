jQuery(document).ready(function($){
    'use strict';

    // click handler for add-item button
    $( 'body' ).click(function(e) {
        var btn = e.target;
        if ( !btn || !$( btn ).attr( 'data-action-id' ) || $( btn ).attr( 'data-action-id' ) !== 'multiple-item-add' ) return;

        e.preventDefault();

        var t = $( btn ).parent().find( '.template' );
        var tt = t.clone();
        $( tt ).removeClass( 'hidden template' ).find( '[disabled]' ).removeAttr( 'disabled' );
        $( t ).before( tt );

        var min = parseInt( $( btn ).data( 'min' ), 10 );
        var max = parseInt( $( btn ).data( 'max' ), 10 );
        var items = $( btn ).parent().find( '.multiple-item:not(.template)' );
        var idxPlaceholder = $( btn ).attr( 'data-replace-with-count' );

        if ( max > 0 && items.length >= max ) {
            $( btn ).addClass( 'hidden' );
        }

        items.each(function(i, item) {
            // show delete buttons, if length > min
            if (items.length > min) {
                $( item ).find( 'button[data-action-id]' ).removeClass('hidden');
            }

            // replace index placeholders w/ positional index
            if (idxPlaceholder) {
                $( item ).find( '[id]' ).each(function (ii, elem) {
                    for (const a of [ 'id', 'data-media-uploader-target' ]) {
                        var val = $(elem).attr( a );
                        if (val !== undefined) {
                            const changed = val.replace( idxPlaceholder, i );
                            if ( changed !== val ) {
                                $( elem ).attr( a, changed );
                            }
                        }
                    }
                });
            }

        });
    });


    // click handler for remove-item button
    $( 'body' ).click(function(e) {
        var btn = e.target;
        if ( !btn || !$( btn ).attr( 'data-action-id' ) || $( btn ).attr( 'data-action-id' ) !== 'multiple-item-remove' ) return;

        e.preventDefault();

        var wrapper = $( btn ).parents( '.multiple' );
        var addBtn  = $( wrapper ).find( '[data-action-id="multiple-item-add"]' );
        var min     = parseInt( $( addBtn ).data( 'min' ), 10 );

        // remove the item
        $( btn ).parent().remove();

        $( addBtn ).removeClass( 'hidden' );

        var items = $( wrapper ).find( '.multiple-item:not(.template)' );

        if (items.length <= min) {
            items.each(function(i, item) {
                // hide delete buttons?
                $( item ).find( 'button[data-action-id]' ).addClass('hidden');
            });
        }
    });
});
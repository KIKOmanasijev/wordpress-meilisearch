import axios from  'axios';

// Counts valid indexed posts.
var valid       = 0;

// Counts corrupted indexed posts.
var corrupted   = 0;

(function( $ ) {
	'use strict';

    $( window ).load(function() {
 		$(".start-reindex").on( 'click', ( evt ) => {
            evt.preventDefault();

            let index = $(evt.target).attr('data-index')

            $(evt.target).attr('disabled', true);

            let statusBadge = $(evt.target).closest('tr').find('.status-badge');

            statusBadge.text('indexing...');
            statusBadge.removeClass (function (index, css) {
                return (css.match (/^(bg-|text-)/g) || []).join(' ');
            }).addClass('bg-orange-600 text-orange-100');

            $("#error-logs-parent").removeClass('blur-lg cursor-not-allowed');

            start_reindex( 'start_reindex', index, 0, evt.target, statusBadge );
        });
    });

    async function start_reindex( action, index, offset, target = null, statusBadge = null ){
        let params = new URLSearchParams();
        let progressBar = $(".progress[data-index='item']");

        params.append('action', action);
        params.append('index', index );
        params.append('offset', offset );

        // Initialise progress bar at 0%.
        update_progress_bar( progressBar, 0 )

        let data = await axios.post(wpMeiliRest.ajaxUrl, params);

        try {

            if ( data.data.posts_per_page * offset <= parseInt(data.data.total) ){
                let percentage  = ((data.data.posts_per_page * offset) / parseInt(data.data.total)) * 100;
                update_counters( data.data.succeeded, data.data.failed );
                start_reindex( action, index, ++offset, target, statusBadge );
                update_progress_bar( progressBar, percentage )
            } else {
                if ( target ){
                    $(target).attr('disabled', false);
                    update_progress_bar(progressBar, 100);
                }

                if ( statusBadge ){
                    statusBadge.text('completed');
                    statusBadge.removeClass (function (index, css) {
                        return (css.match (/^(bg-|text-)/g) || []).join(' ');
                    }).addClass('bg-green-100 text-green-700');
                    progressBar.removeClass('bg-orange-600').addClass('bg-green-500')
                }
            }
        } catch(e){
            start_reindex( action, index, offset, statusBadge );
            console.log('Error occurred, uploading the same batch again.');
            console.log(e);
        }
    }

    function update_progress_bar( progressBar, percentage ){
        progressBar.css( 'width', parseInt( percentage ) + '%' )
        progressBar.text( parseInt( percentage ) + '%' )
        progressBar.parent().removeClass('hidden');
    }

    function update_counters( validProducts, corruptedProducts ){
        valid += parseInt( validProducts );
        corrupted += parseInt( corruptedProducts );

        $("#countIndexed").text( valid );
        $("#countFailed").text( corrupted );
    }

})( jQuery );

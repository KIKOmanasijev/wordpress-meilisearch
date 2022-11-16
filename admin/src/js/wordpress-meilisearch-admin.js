import axios from  'axios';

(function( $ ) {
	'use strict';

    $( window ).load(function() {
 		$(".start-reindex").on( 'click', ( evt ) => {
            evt.preventDefault();

            let index = $(evt.target).attr('data-index')
            $(evt.target).attr('disabled', true);

            start_reindex( 'start_reindex', index, 0, evt.target );
        });
    });

    async function start_reindex( action, index, offset, target = null ){
        var params = new URLSearchParams();

        params.append('action', action);
        params.append('index', index );
        params.append('offset', offset );

        let data = await axios.post(wpMeiliRest.ajaxUrl, params);

        try {
            let progressBar = $(".progress[data-index='item']");

            if ( data.data.posts_per_page * offset <= parseInt(data.data.total) ){
                let percentage  = ((data.data.posts_per_page * offset) / parseInt(data.data.total)) * 100;

                start_reindex( action, index, ++offset, target );

                update_progress_bar( progressBar, percentage )
            } else {
                if ( target ){
                    $(target).attr('disabled', false);
                    update_progress_bar(progressBar, 100);
                }
            }
        } catch(e){
            start_reindex( action, index, offset );
            console.log('Error occurred, uploading the same batch again.');
            console.log(e);
        }
    }

    function update_progress_bar( progressBar, percentage ){
        progressBar.css('width', parseInt( percentage ) + '%' )
        progressBar.text(parseInt( percentage ) + '%' )
        progressBar.parent().removeClass('hidden');
    }

})( jQuery );

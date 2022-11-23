import axios from  'axios';
import Swal from 'sweetalert2'

// Counts valid indexed posts.
var valid       = 0;

// Counts corrupted indexed posts.
var corrupted   = 0;

// Indexing status. Could be either idle (0) or active (1).
var status      = 0;
var warningExitTimesShown = 0;

(function( $ ) {
	'use strict';

    $( window ).load(function() {
 		$(".start-reindex").on( 'click', ( evt ) => {
            evt.preventDefault();

            let index = $(evt.target).attr('data-index')

            $('.start-reindex').attr('disabled', true);
            $('.clear-index').attr('disabled', true);

            let statusBadge = $(evt.target).closest('tr').find('.status-badge');

            statusBadge.text('indexing...');
            statusBadge.removeClass (function (index, css) {
                return (css.match (/^(bg-|text-)/g) || []).join(' ');
            }).addClass('bg-orange-600 text-orange-100');

            $("#error-logs-parent").removeClass('blur-lg cursor-not-allowed');

            status = 1;

            start_reindex( 'start_reindex', index, 0, evt.target, statusBadge );
        });

        $(".clear-index").on( 'click', ( evt ) => {
            evt.preventDefault();

            let index = $(evt.target).attr('data-index')

            Swal.fire({
                title: 'Are you sure you want to clear the index?',
                text: 'Once you clear the index there is no going back, think twice before proceeding 😉',
                confirmButtonColor: '#dc2626',
                showCancelButton: true,
                confirmButtonText: '🗑️ Clear Index',
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    clear_index( 'clear_index', index );
                }
            })
        });

        $( document ).mouseleave( () => {
            if ( status && ! warningExitTimesShown++ ){
                Swal.fire(
                    'Reindexing is not finished',
                    'The process of reindexing is not complete, you will have to start it again to finish it completetly in future.',
                    'info'
                )
            }
        });
    });

    async function start_reindex( action, index, offset, target = null, statusBadge = null ){
        let params = new URLSearchParams();
        let progressBar = $(`.progress[data-index='${index}']`);

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
                    $('.start-reindex').attr('disabled', false);
                    $('.clear-index').attr('disabled', false);
                    update_progress_bar(progressBar, 100);
                }

                if ( statusBadge ){
                    statusBadge.text('completed');
                    statusBadge.removeClass (function (index, css) {
                        return (css.match (/^(bg-|text-)/g) || []).join(' ');
                    }).addClass('bg-green-100 text-green-700');
                    progressBar.removeClass('bg-orange-600').addClass('bg-green-500')
                }

                status = 0;
            }
        } catch(e){
            start_reindex( action, index, offset, statusBadge );
            console.log('Error occurred, uploading the same batch again.');
            console.log(e);
        }
    }

    async function clear_index( action, index = 'post'){
        let params = new URLSearchParams();

        params.append('action', action);
        params.append('index', index);

        let data = await axios.post(wpMeiliRest.ajaxUrl, params);

        try {
            Swal.fire(
                'Index cleared!',
                '',
                'success'
            )
        } catch(e){
            console.log('Error occurred, try clearing the index later.');
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

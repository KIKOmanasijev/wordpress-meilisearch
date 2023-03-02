document.addEventListener("DOMContentLoaded", function(event) {
	const $ = jQuery;

	document.body.addEventListener('htmx:afterRequest', function (evt) {
		const targetError = evt.target.attributes.getNamedItem('hx-target-error')
		const messageContainer =  $(evt.target).parents('section').find('#message');
		if (evt.detail.failed && targetError) {
			let errorMessage = JSON.parse(evt.detail.xhr.response);
			messageContainer.css( 'display', 'block' );
			messageContainer.html(`
          <div class="bg-red-100 text-red-500 text-sm px-4 py-3 w-full rounded-md mb-4">
           ${ errorMessage.message }
          </div>
        `);
		}
	});

	document.body.addEventListener("htmx:configRequest", function(e){
		let posts_per_page = localStorage.getItem('posts_per_page') ?? '18';
		let query_posts_per_page = e.detail.parameters['hit-per-page'];

		let sorting = localStorage.getItem('orderby') ?? 'posts_product_post_date_desc';
		let query_sorting = e.detail.parameters['orderby'];

		if ( posts_per_page !== query_posts_per_page ){
			// Algolia compatability
			e.detail.unfilteredParameters['currentPage'] = '0';

			// Meilisearch related
			if (! $(e.target).hasClass('meili-pagination-option') ){
				e.detail.unfilteredParameters['current_page'] = '1';
			}

			localStorage.setItem('posts_per_page', query_posts_per_page);
		}

		if ( sorting != query_sorting ){
			// Algolia compatability
			e.detail.unfilteredParameters['currentPage'] = '0';

			// Meilisearch related
			if (! $(e.target).hasClass('meili-pagination-option') ){
				e.detail.unfilteredParameters['current_page'] = '1';
			}

			localStorage.setItem('orderby', query_sorting);
		}
	});

	document.body.addEventListener("htmx:afterSwap", function( e ){
		// Reusing event 'algolia_received_results', so we don't have modify conversion.js
		// from the Currency Converter plugin.
		$(document.body).trigger('algolia_received_results');

		$("#meili-pagination button").click(function(e){
			window.scrollTo({top: 0, behavior: 'smooth'});
		});

		jQuery(".multilevel_checkbox").change(function (e){
			let parent = $(".multilevel_checkbox_parent");
			let targetElement = jQuery(e.target);

			if ( targetElement.prop('checked') ){
				// Unselects parents when selecting inputs on a deeper level.
				targetElement.parentsUntil(parent, '.multilevel_checkbox_input_wrapper').each(function(){
					let input = jQuery( jQuery(this).find('input')[0] );

					if (! input.is(targetElement) ){
						input.prop('checked', false);
					}
				})

				// Unselects all children elements when selecting inputs from above level.
				targetElement.closest('.multilevel_checkbox_input_wrapper')
					.find('.multilevel_checkbox_input_wrapper')
					.find('input')
					.each(function(){
						jQuery(this).prop('checked', false);
					})
			}

		});

		jQuery(".skeleton-products").addClass('hidden');
	});

	$(document).on("finished", function(){
		jQuery('.skeleton-products').addClass('hidden');
	});

	htmx.process(document.body);
})
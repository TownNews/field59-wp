(function ($) {
	$(document).ready(function () {
		$('body').on('click', '#field59-video-filter .search-box #search-submit', searchF59);
		$('body').on('click', '#field59-video-filter .f59-header .f59-type button', searchF59);

		function searchF59(e) {
			e.preventDefault();
			// e.stopImmediatePropagation();
			var $searchbox = $('.search-box');
			var $form = $searchbox.closest('form');
			var $spinner = $searchbox.find('.spinner');
			var $tablebody = $('table.field59-videos tbody');
			var $subtitle = $('.content .subtitle');
			var $pages_top = $('.content .tablenav.top');
			var $pages_bot = $('.content .tablenav.bottom');
			var $typeBtns = $('#field59-video-filter .f59-header .f59-type button');

			// Interactive UI.
			$typeBtns.removeClass('active');
			$(this).addClass('active');
			$spinner.addClass('is-active');

			// Collect params to pass to video search AJAX request.
			ajax_params = {
				action: 'field59-search-videos',
			};
			var fields = $form.find(':input').serialize();
			fields += '&type=' + String($(this).attr('id'));
			ajax_params = fields + '&' + $.param(ajax_params);

			console.log('ajax_params: ', ajax_params);

			// Make ajax request.
			$.get(ajaxurl, ajax_params, function (response) {
				$spinner.removeClass('is-active');

				if (!response.success) {
					$tablebody.html(response.data.tbody);
					$pages_top.html(response.data.pagination_top);
					$pages_bot.html(response.data.pagination_bottom);
				} else {
					$tablebody.html(response.result.tbody);
					$subtitle.html(response.result.subtitle);
					$pages_top.html(response.result.pagination_top);
					$pages_bot.html(response.result.pagination_bottom);
				}
			}).fail(function () {
				$spinner.removeClass('is-active');
				$tablebody.html(
					'<tr><td colspan="5">There was an error processing your request - Code f59-004</td></tr>'
				);
			});
		}

		/**
		 * Listen for pagination clicks then trigger a re-submit on the form
		 * to make the AJAX call, passing the correct page based on button clicked.
		 */
		$('body').on('click', '.pagination-links a', function (e) {
			e.preventDefault();
			var $clicked = $(this);
			var $form = $clicked.parent('form');

			var search_btn = $('#field59-video-filter .search-box #search-submit');
			var cur_page = parseInt($('#current-page-selector').val());

			if ($clicked.hasClass('first-page')) {
				$('#current-page-selector').val(1);
				search_btn.trigger('click');
			}
			if ($clicked.hasClass('prev-page')) {
				$('#current-page-selector').val(cur_page - 1);
				search_btn.trigger('click');
			}
			if ($clicked.hasClass('next-page')) {
				$('#current-page-selector').val(cur_page + 1);
				search_btn.trigger('click');
			}
			if ($clicked.hasClass('last-page')) {
				var max_page = parseInt($('.total-pages').html());
				$('#current-page-selector').val(max_page);
				search_btn.trigger('click');
			}
		});
	});
})(jQuery);

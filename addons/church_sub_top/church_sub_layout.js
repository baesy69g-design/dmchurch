(function ($) {
	'use strict';

	function normalizePath(path) {
		var p = (path || '/').replace(/\/index\.php$/i, '').replace(/\/$/, '');
		return p || '/';
	}

	$(function () {
		if (!$('.visual.sub').length || !$('.body .lnb').length) {
			return;
		}

		var current = normalizePath(window.location.pathname);

		$('.body .lnb a').each(function () {
			var href = normalizePath(this.pathname);
			if (href !== current) {
				return;
			}

			var $li = $(this).closest('li');
			$li.addClass('on');
			$li.parents('.lnb li').addClass('on');
		});
	});
})(jQuery);

(function ($) {
	'use strict';

	$(function () {
		var $gnb = $('.header .gnb.pc-gnb');
		if (!$gnb.length) {
			return;
		}

		var $wrap = $('.header_wrap');
		if (!$wrap.length) {
			return;
		}

		$gnb.addClass('church-gnb');

		var $bar = $('<div class="church-gnb-subbar" role="navigation" aria-label="서브메뉴">' +
			'<div class="church-gnb-subbar-inner"></div></div>');
		$wrap.append($bar);
		var $inner = $bar.find('.church-gnb-subbar-inner');
		var hideTimer = null;

		function clearHideTimer() {
			if (hideTimer) {
				clearTimeout(hideTimer);
				hideTimer = null;
			}
		}

		function hideSub() {
			clearHideTimer();
			hideTimer = setTimeout(function () {
				$bar.removeClass('is-open');
				$inner.empty();
				$gnb.find('> ul > li').removeClass('church-gnb-on');
				$('body').removeClass('church-gnb-open');
			}, 150);
		}

		function showSub($li) {
			clearHideTimer();
			var $depth2 = $li.children('.depth2');
			if (!$depth2.length) {
				$bar.removeClass('is-open');
				$inner.empty();
				$gnb.find('> ul > li').removeClass('church-gnb-on');
				$('body').removeClass('church-gnb-open');
				return;
			}

			$gnb.find('> ul > li').removeClass('church-gnb-on');
			$li.addClass('church-gnb-on');
			$inner.empty().append($depth2.clone().removeAttr('style').show());

			// 호버한 대메뉴 바로 아래·중앙에 정렬 (공백 없이 딱 붙임)
			var wrapRect = $wrap[0].getBoundingClientRect();
			var liRect = $li[0].getBoundingClientRect();
			var top = liRect.bottom - wrapRect.top;
			var center = liRect.left - wrapRect.left + liRect.width / 2;

			$bar.css({ top: top + 'px', left: center + 'px' }).addClass('is-open');

			// 화면 좌우로 넘치지 않도록 보정
			var barW = $bar.outerWidth();
			var half = barW / 2;
			var minLeft = half + 8;
			var maxLeft = wrapRect.width - half - 8;
			if (center < minLeft) {
				center = minLeft;
			}
			if (maxLeft > minLeft && center > maxLeft) {
				center = maxLeft;
			}
			$bar.css('left', center + 'px');

			$('body').addClass('church-gnb-open');
		}

		$gnb.on('mouseenter focusin', '> ul > li', function () {
			showSub($(this));
		});

		$wrap.on('mouseleave', function () {
			hideSub();
		});

		$bar.on('mouseenter focusin', function () {
			clearHideTimer();
		}).on('mouseleave', function () {
			hideSub();
		});

		$bar.on('mouseenter focusin', '.depth2 > li.more', function () {
			$(this).addClass('on').siblings('.more').removeClass('on');
		}).on('mouseleave', '.depth2 > li.more', function () {
			$(this).removeClass('on');
		});

		/* 서브페이지 LNB: 현재 메뉴 강조 + 국내/해외선교 분기별 서브 표시 */
		if ($('.visual.sub').length && $('.body .lnb').length) {
			var current = (location.pathname || '/').replace(/\/index\.php$/i, '').replace(/\/$/, '') || '/';

			function missionBranchFromPath(path) {
				var m = path.match(/\/p(\d+)(?:\/|$)/);
				if (!m) {
					return null;
				}
				var n = parseInt(m[1], 10);
				if (n === 92 || n === 146 || n === 93) {
					return 'mission-gallery';
				}
				if (n === 25 || (n >= 251 && n < 261)) {
					return 'domestic';
				}
				if (n === 26 || (n >= 261 && n < 271)) {
					return 'overseas';
				}
				return null;
			}

			var branch = missionBranchFromPath(current);

			$('.body .lnb > ul > li').each(function () {
				var $a = $(this).children('a').first();
				var href = ($a.attr('href') || '').replace(/\/index\.php$/i, '').replace(/\/$/, '');
				if (/\/p25(?:\/|$)/.test(href)) {
					$(this).addClass('church-mission-domestic-root');
				}
				if (/\/p26(?:\/|$)/.test(href)) {
					$(this).addClass('church-mission-overseas-root');
				}
			});

			if (branch === 'domestic') {
				$('.church-mission-overseas-root > ul').hide();
			} else if (branch === 'overseas') {
				$('.church-mission-domestic-root > ul').hide();
			} else if (branch === 'mission-gallery') {
				$('.church-mission-domestic-root > ul, .church-mission-overseas-root > ul').hide();
			}

			$('.body .lnb a').each(function () {
				var href = (this.pathname || '/').replace(/\/index\.php$/i, '').replace(/\/$/, '') || '/';
				if (href !== current) {
					return;
				}
				var $li = $(this).closest('li');
				$li.addClass('on');
				$li.parents('.lnb li').addClass('on');
			});
		}
	});
})(jQuery);

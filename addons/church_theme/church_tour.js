(function () {
	'use strict';

	function initCarousel(root) {
		var track = root.querySelector('.church-carousel-track');
		var slides = Array.prototype.slice.call(root.querySelectorAll('.church-carousel-slide'));
		if (!track || slides.length <= 1) {
			return;
		}
		var thumbs = Array.prototype.slice.call(root.querySelectorAll('.church-carousel-thumb'));
		var prev = root.querySelector('.church-carousel-prev');
		var next = root.querySelector('.church-carousel-next');
		var interval = parseInt(root.getAttribute('data-interval'), 10) || 4000;
		var current = 0;
		var timer = null;

		function show(index) {
			index = (index + slides.length) % slides.length;
			current = index;
			track.style.transform = 'translateX(' + (-index * 100) + '%)';

			slides.forEach(function (s, i) {
				var active = i === index;
				var img = s.querySelector('img');
				if (img) {
					// Ken Burns 모션 재시작
					img.style.animation = 'none';
					if (active) {
						// reflow 강제 후 애니메이션 재적용
						void img.offsetWidth;
						img.style.animation = '';
					}
				}
				s.classList.toggle('is-active', active);
			});

			thumbs.forEach(function (t, i) {
				t.classList.toggle('is-active', i === index);
			});
		}

		function go(step) {
			show(current + step);
		}

		function start() {
			stop();
			timer = window.setInterval(function () {
				go(1);
			}, interval);
		}

		function stop() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		if (next) {
			next.addEventListener('click', function () {
				go(1);
				start();
			});
		}
		if (prev) {
			prev.addEventListener('click', function () {
				go(-1);
				start();
			});
		}
		thumbs.forEach(function (thumb) {
			thumb.addEventListener('click', function () {
				var idx = parseInt(thumb.getAttribute('data-index'), 10) || 0;
				show(idx);
				start();
			});
		});

		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', start);

		var touchX = null;
		root.addEventListener('touchstart', function (e) {
			touchX = e.touches[0].clientX;
			stop();
		}, { passive: true });
		root.addEventListener('touchend', function (e) {
			if (touchX === null) {
				return;
			}
			var dx = e.changedTouches[0].clientX - touchX;
			if (Math.abs(dx) > 40) {
				go(dx < 0 ? 1 : -1);
			}
			touchX = null;
			start();
		});

		show(0);
		start();
	}

	function initAll() {
		Array.prototype.slice.call(document.querySelectorAll('.church-carousel')).forEach(initCarousel);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
})();

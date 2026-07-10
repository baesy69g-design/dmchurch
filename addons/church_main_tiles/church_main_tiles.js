(function () {
	var DELAY_MS = 2000;

	function initChurchMainSlide() {
		var root = document.querySelector('.church-main-slide.swiper-container');
		if (!root) {
			return;
		}

		var slides = root.querySelectorAll('.swiper-slide');
		if (slides.length <= 1) {
			return;
		}

		var index = 0;
		var timer = null;
		var paused = false;
		var pagination = root.querySelector('.swiper-pagination');

		function buildPagination() {
			if (!pagination || pagination.childElementCount === slides.length) {
				return;
			}
			pagination.innerHTML = '';
			for (var i = 0; i < slides.length; i++) {
				var bullet = document.createElement('span');
				bullet.className = 'swiper-pagination-bullet';
				bullet.setAttribute('data-index', String(i));
				bullet.addEventListener('click', function () {
					show(parseInt(this.getAttribute('data-index'), 10) || 0);
				});
				pagination.appendChild(bullet);
			}
		}

		function updatePagination() {
			if (!pagination) {
				return;
			}
			var bullets = pagination.querySelectorAll('.swiper-pagination-bullet');
			for (var i = 0; i < bullets.length; i++) {
				bullets[i].className = i === index
					? 'swiper-pagination-bullet swiper-pagination-bullet-active'
					: 'swiper-pagination-bullet';
			}
		}

		function show(nextIndex) {
			index = ((nextIndex % slides.length) + slides.length) % slides.length;
			for (var i = 0; i < slides.length; i++) {
				slides[i].style.opacity = i === index ? '1' : '0';
				slides[i].style.zIndex = i === index ? '2' : '1';
				slides[i].classList.toggle('swiper-slide-active', i === index);
			}
			updatePagination();
		}

		function tick() {
			if (!paused) {
				show(index + 1);
			}
		}

		function start() {
			stop();
			timer = setInterval(tick, DELAY_MS);
		}

		function stop() {
			if (timer) {
				clearInterval(timer);
				timer = null;
			}
		}

		for (var s = 0; s < slides.length; s++) {
			slides[s].style.position = 'absolute';
			slides[s].style.inset = '0';
			slides[s].style.transition = 'opacity 0.6s ease';
		}
		root.querySelector('.swiper-wrapper').style.position = 'relative';

		buildPagination();
		show(0);
		start();

		root.addEventListener('mouseenter', function () { paused = true; });
		root.addEventListener('mouseleave', function () { paused = false; });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initChurchMainSlide);
	} else {
		initChurchMainSlide();
	}
})();

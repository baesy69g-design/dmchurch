(function () {
	function boot() {
		var root = document.querySelector('.church-main-slide.swiper-container');
		if (!root || typeof Swiper === 'undefined') {
			return;
		}
		if (root.getAttribute('data-church-slide-ready') === '1') {
			return;
		}
		var slides = root.querySelectorAll('.swiper-slide');
		if (slides.length < 2) {
			return;
		}

		root.setAttribute('data-church-slide-ready', '1');
		new Swiper(root, {
			autoplay: 2000,
			autoplayDisableOnInteraction: false,
			loop: true,
			mode: 'horizontal',
			pagination: root.querySelector('.swiper-pagination'),
			paginationClickable: true
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

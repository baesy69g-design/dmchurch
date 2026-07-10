(function () {
	function initChurchMainSlide() {
		var el = document.querySelector('.church-main-slide.swiper-container');
		if (!el || typeof Swiper === 'undefined') {
			return;
		}
		new Swiper(el, {
			autoplay: 2000,
			loop: true,
			pagination: '.church-main-slide .swiper-pagination',
			paginationClickable: true
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initChurchMainSlide);
	} else {
		initChurchMainSlide();
	}
})();

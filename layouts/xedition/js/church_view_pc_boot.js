/* PC 화면 쿠키/로컬스토리지 있으면 viewport를 즉시 1280으로 (CSS 로드 전) */
(function () {
	try {
		var on = false;
		var c = document.cookie || '';
		if (c.indexOf('church_view_pc=1') >= 0) {
			on = true;
		} else if (window.localStorage && localStorage.getItem('church_view_pc') === '1') {
			on = true;
		}
		if (!on) {
			return;
		}
		var m = document.querySelector('meta[name="viewport"]');
		if (!m) {
			m = document.createElement('meta');
			m.setAttribute('name', 'viewport');
			(document.head || document.documentElement).appendChild(m);
		}
		m.setAttribute('content', 'width=1280, user-scalable=yes');
		document.documentElement.classList.add('church-view-pc');
	} catch (e) {}
})();

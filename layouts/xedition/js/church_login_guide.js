(function () {
	'use strict';
	var LS = 'church_pc_onboarded';
	var COOKIE = 'church_onboard';
	var PC = 'church_pc_ok';

	function hasPcFlag() {
		try {
			if (localStorage.getItem(LS) === '1') return true;
		} catch (e) {}
		if (document.cookie.indexOf(PC + '=1') >= 0) return true;
		var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + COOKIE + '=([^;]*)'));
		if (m && decodeURIComponent(m[1]).trim()) return true;
		if (window.churchOnboardIds && window.churchOnboardIds.length) return true;
		return false;
	}

	function markPc() {
		try { localStorage.setItem(LS, '1'); } catch (e) {}
		var exp = new Date(Date.now() + 365 * 86400000).toUTCString();
		document.cookie = PC + '=1; path=/; expires=' + exp + '; SameSite=Lax';
	}

	function guideEl() {
		return document.getElementById('church_login_guide');
	}

	function setGuide(show) {
		var g = guideEl();
		if (!g) return;
		if (!show) {
			markPc();
			g.style.display = 'none';
			g.setAttribute('data-church-suppressed', '1');
		} else {
			g.style.display = '';
			g.removeAttribute('data-church-suppressed');
		}
	}

	function checkServer(uid) {
		var q = '/index.php?module=church_member&act=dispChurchCheckOnboard';
		if (uid) q += '&user_id=' + encodeURIComponent(uid);
		var opts = { credentials: 'same-origin' };
		opts.headers = { 'X-Requested-With': 'XMLHttpRequest' };
		fetch(q, opts)
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (!d.show_guide) markPc();
				setGuide(!!d.show_guide);
			})
			.catch(function () {});
	}

	function apply() {
		if (hasPcFlag()) {
			setGuide(false);
			return;
		}
		var input = document.querySelector('.login_widget #church_login_uid') || document.querySelector('.login_widget #uid');
		var uid = input ? input.value.trim() : '';
		if (!uid) {
			checkServer('');
			return;
		}
		checkServer(uid);
	}

	function bindInput() {
		var input = document.querySelector('.login_widget #church_login_uid') || document.querySelector('.login_widget #uid');
		if (!input || input.dataset.churchBound) return;
		input.dataset.churchBound = '1';
		var t;
		input.addEventListener('input', function () {
			clearTimeout(t);
			t = setTimeout(function () { apply(); }, 200);
		});
	}

	apply();
	bindInput();
	document.addEventListener('DOMContentLoaded', function () { apply(); bindInput(); });
	setInterval(function () { apply(); bindInput(); }, 1000);
})();

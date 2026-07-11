/**
 * 모바일 ↔ PC 화면 전환
 * - 클릭: 쿠키 저장 후 새로고침 (미디어쿼리 재계산)
 * - HTTPS 사이트는 Secure 쿠키 필수 (Rhymix cookies_ssl)
 */
(function () {
	'use strict';

	var COOKIE = 'church_view_pc';
	var MAX_AGE = 60 * 60 * 12;

	function secureFlag() {
		return window.location.protocol === 'https:' ? '; Secure' : '';
	}

	function readCookie() {
		var parts = (document.cookie || '').split(';');
		for (var i = 0; i < parts.length; i++) {
			var p = parts[i].replace(/^\s+|\s+$/g, '');
			if (p.indexOf(COOKIE + '=') === 0) {
				return decodeURIComponent(p.substring(COOKIE.length + 1));
			}
		}
		return '';
	}

	function setCookie(on) {
		if (on) {
			document.cookie = COOKIE + '=1; path=/; max-age=' + MAX_AGE + '; SameSite=Lax' + secureFlag();
		} else {
			document.cookie = COOKIE + '=; path=/; max-age=0; SameSite=Lax' + secureFlag();
		}
		try {
			if (on) {
				localStorage.setItem(COOKIE, '1');
			} else {
				localStorage.removeItem(COOKIE);
			}
		} catch (e) {}
	}

	function wantPc() {
		if (readCookie() === '1') {
			return true;
		}
		try {
			return localStorage.getItem(COOKIE) === '1';
		} catch (e) {
			return false;
		}
	}

	function setViewport(content) {
		var meta = document.querySelector('meta[name="viewport"]');
		if (!meta) {
			meta = document.createElement('meta');
			meta.setAttribute('name', 'viewport');
			if (document.head) {
				document.head.insertBefore(meta, document.head.firstChild);
			}
		}
		meta.setAttribute('content', content);
	}

	function markDom(on) {
		var root = document.documentElement;
		if (on) {
			root.classList.add('church-view-pc');
			if (document.body) {
				document.body.classList.add('church-view-pc');
			}
		} else {
			root.classList.remove('church-view-pc');
			if (document.body) {
				document.body.classList.remove('church-view-pc');
			}
		}
	}

	function applyPcNow() {
		setViewport('width=1280, user-scalable=yes');
		markDom(true);
	}

	function applyMobileNow() {
		setViewport('width=device-width, initial-scale=1.0, user-scalable=yes');
		markDom(false);
	}

	// 페이지 로드 시 즉시 적용 (가능하면 CSS 해석 전)
	if (wantPc()) {
		applyPcNow();
	}

	function goPc() {
		setCookie(true);
		applyPcNow();
		window.location.reload();
	}

	function goMobile() {
		setCookie(false);
		applyMobileNow();
		window.location.reload();
	}

	function bind() {
		document.addEventListener('click', function (e) {
			var t = e.target;
			if (!t || !t.closest) {
				return;
			}
			if (t.closest('a.js-church-view-pc, .church-view-switch--pc a')) {
				e.preventDefault();
				e.stopPropagation();
				goPc();
				return;
			}
			if (t.closest('a.js-church-view-mobile, .church-view-switch--mobile a')) {
				e.preventDefault();
				e.stopPropagation();
				goMobile();
			}
		}, true);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}
})();

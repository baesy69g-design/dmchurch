/**

 * 로그인 안내: 인증 완료 회원은 쿠키·저장값으로 숨김

 */

(function () {

	var COOKIE_NAME = 'church_onboard';

	var LS_KEY = 'church_onboard';



	function parseIds(raw) {

		return String(raw || '')

			.split(',')

			.map(function (s) { return s.trim().toLowerCase(); })

			.filter(function (s) { return /^[a-z0-9._-]{2,40}$/.test(s); });

	}



	function readCookieIds() {

		var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + COOKIE_NAME + '=([^;]*)'));

		return m ? parseIds(decodeURIComponent(m[1])) : [];

	}



	function readStorageIds() {

		try {

			var raw = localStorage.getItem(LS_KEY);

			if (!raw) return [];

			var parsed = JSON.parse(raw);

			return Array.isArray(parsed) ? parseIds(parsed.join(',')) : [];

		} catch (e) {

			return [];

		}

	}



	function getKnownIds() {

		var map = {};

		var all = readCookieIds().concat(readStorageIds());

		if (window.churchOnboardIds && window.churchOnboardIds.length) {

			all = all.concat(window.churchOnboardIds);

		}

		all.forEach(function (id) { map[id] = true; });

		return Object.keys(map);

	}



	function rememberId(uid) {

		uid = (uid || '').trim().toLowerCase();

		if (!uid) return;

		var ids = getKnownIds();

		if (ids.indexOf(uid) < 0) {

			ids.push(uid);

		}

		if (ids.length > 30) {

			ids = ids.slice(-30);

		}

		try {

			localStorage.setItem(LS_KEY, JSON.stringify(ids));

		} catch (e) {}

		var expires = new Date(Date.now() + 365 * 86400000).toUTCString();

		document.cookie = COOKIE_NAME + '=' + encodeURIComponent(ids.join(',')) + '; path=/; expires=' + expires + '; SameSite=Lax';

	}



	function absorbUrlOnboard() {

		var m = window.location.search.match(/[?&]church_ob=([^&]+)/);

		if (!m) return;

		rememberId(decodeURIComponent(m[1].replace(/\+/g, ' ')));

	}



	function shouldHide(uid) {

		uid = (uid || '').trim().toLowerCase();

		var known = getKnownIds();

		if (uid && known.indexOf(uid) >= 0) {

			return true;

		}

		if (!uid && known.length > 0) {

			return true;

		}

		return false;

	}



	function bindLoginGuide(guide) {

		if (!guide || guide.dataset.bound === '1') return;

		guide.dataset.bound = '1';

		var root = guide.closest('.login_widget') || guide.parentElement || document;

		var input = root.querySelector('#uemail') || root.querySelector('#uid');

		var timer = null;



		function applyGuide(uid) {

			guide.style.display = shouldHide(uid) ? 'none' : '';

		}



		function updateGuide(uid) {

			uid = (uid || '').trim().toLowerCase();

			if (!uid) {

				applyGuide('');

				return;

			}

			if (shouldHide(uid)) {

				applyGuide(uid);

				return;

			}

			fetch('/index.php?module=church_member&act=dispChurchCheckOnboard&user_id=' + encodeURIComponent(uid), {

				headers: { 'X-Requested-With': 'XMLHttpRequest' },

				credentials: 'same-origin'

			})

				.then(function (r) { return r.json(); })

				.then(function (d) {

					if (!d.show_guide && uid) {

						rememberId(uid);

					}

					guide.style.display = d.show_guide ? '' : 'none';

				})

				.catch(function () {});

		}



		applyGuide(input ? input.value : '');



		if (input) {

			input.addEventListener('input', function () {

				clearTimeout(timer);

				var uid = input.value;

				if (shouldHide(uid)) {

					applyGuide(uid);

					return;

				}

				timer = setTimeout(function () { updateGuide(uid); }, 250);

			});

			input.addEventListener('blur', function () { updateGuide(input.value); });

		}

	}



	function scanGuides() {

		document.querySelectorAll('#church_login_guide').forEach(bindLoginGuide);

	}



	absorbUrlOnboard();

	scanGuides();



	if (document.readyState === 'loading') {

		document.addEventListener('DOMContentLoaded', scanGuides);

	}



	if (window.MutationObserver) {

		var observer = new MutationObserver(scanGuides);

		observer.observe(document.documentElement, { childList: true, subtree: true });

	}

})();



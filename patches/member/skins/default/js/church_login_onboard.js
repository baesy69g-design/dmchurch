(function () {
	'use strict';
	var C = 'church_onboard';

	function ids() {
		var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + C + '=([^;]*)'));
		var l = m ? decodeURIComponent(m[1]).split(',') : [];
		if (window.churchOnboardIds) {
			l = l.concat(window.churchOnboardIds);
		}
		return l.map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean);
	}

	function hide(uid) {
		var g = document.getElementById('church_login_guide');
		if (!g) return;
		uid = (uid || '').trim().toLowerCase();
		var k = ids();
		if ((uid && k.indexOf(uid) >= 0) || (!uid && k.length > 0)) {
			g.style.display = 'none';
			return;
		}
		if (!uid) {
			g.style.display = '';
			return;
		}
		var url = '/index.php?module=church_member&act=dispChurchCheckOnboard&user_id=' + encodeURIComponent(uid);
		var opts = { credentials: 'same-origin' };
		opts.headers = { 'X-Requested-With': 'XMLHttpRequest' };
		fetch(url, opts).then(function (r) { return r.json(); }).then(function (d) {
			g.style.display = d.show_guide ? '' : 'none';
		});
	}

	function bind() {
		var i = document.querySelector('#uid') || document.querySelector('#uemail');
		hide(i ? i.value : '');
		if (!i || i.dataset.churchBound) return;
		i.dataset.churchBound = '1';
		var t;
		i.addEventListener('input', function () {
			clearTimeout(t);
			var v = i.value;
			if (ids().indexOf(v.trim().toLowerCase()) >= 0) {
				hide(v);
				return;
			}
			t = setTimeout(function () { hide(v); }, 250);
		});
	}

	bind();
	document.addEventListener('DOMContentLoaded', bind);
})();

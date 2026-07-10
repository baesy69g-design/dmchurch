(function () {
	'use strict';

	var SKIP_PATH = /\/(?:dmcadmin|admin)(?:\/|$)/i;
	var SKIP_ACT = /(?:write|login|signup|edit|setup|admin|dmcadmin|proc)/i;

	function isProtectPage() {
		var path = (location.pathname || '').toLowerCase();
		if (SKIP_PATH.test(path)) {
			return false;
		}
		var params = new URLSearchParams(location.search);
		var act = (params.get('act') || '').toLowerCase();
		if (act && SKIP_ACT.test(act)) {
			return false;
		}
		return true;
	}

	function isAllowedTarget(node) {
		if (!node || node.nodeType !== 1) {
			return false;
		}
		if (node.closest('.church-protect-allow')) {
			return true;
		}
		var tag = node.tagName;
		if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
			return true;
		}
		if (node.isContentEditable) {
			return true;
		}
		return !!node.closest('input, textarea, select, [contenteditable="true"], .church-protect-allow');
	}

	function blockEvent(e) {
		if (!isAllowedTarget(e.target)) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	}

	function blockShortcut(e) {
		if (isAllowedTarget(e.target)) {
			return;
		}
		var key = e.key || '';
		var ctrl = e.ctrlKey || e.metaKey;
		var shift = e.shiftKey;
		var blocked = false;

		if (key === 'F12') {
			blocked = true;
		} else if (ctrl && shift && /^(i|j|c|k|u)$/i.test(key)) {
			blocked = true;
		} else if (ctrl && /^(u|s|p|a)$/i.test(key)) {
			blocked = true;
		}

		if (blocked) {
			e.preventDefault();
			e.stopPropagation();
		}
	}

	if (!isProtectPage()) {
		return;
	}

	document.documentElement.classList.add('church-protected');

	document.addEventListener('contextmenu', blockEvent, true);
	document.addEventListener('dragstart', blockEvent, true);
	document.addEventListener('copy', blockEvent, true);
	document.addEventListener('cut', blockEvent, true);
	document.addEventListener('selectstart', blockEvent, true);
	document.addEventListener('keydown', blockShortcut, true);
})();

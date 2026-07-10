(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		var btn = document.querySelector('.church-bulk-del');
		if (!btn) {
			return;
		}

		btn.addEventListener('click', function (e) {
			e.preventDefault();

			var checks = document.querySelectorAll('#board_list input[name="cart"]:checked');
			if (!checks.length) {
				alert('삭제할 글을 선택해 주세요.');
				return;
			}
			if (!confirm('선택한 ' + checks.length + '개 글을 삭제할까요? 삭제 후에는 되돌릴 수 없습니다.')) {
				return;
			}

			var srls = [];
			Array.prototype.forEach.call(checks, function (c) {
				srls.push(c.value);
			});

			var meta = document.querySelector('meta[name="csrf-token"]');
			var token = meta ? meta.getAttribute('content') : '';

			var fd = new FormData();
			fd.append('module', 'church_write');
			fd.append('act', 'procChurchWriteDeleteDocuments');
			fd.append('srls', srls.join(','));
			fd.append('_rx_csrf_token', token);

			btn.disabled = true;
			var orig = btn.innerHTML;
			btn.innerHTML = '삭제 중...';

			fetch('/index.php', {
				method: 'POST',
				body: fd,
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': token }
			})
				.then(function (r) { return r.json(); })
				.then(function (d) {
					if (d && (d.error === 0 || d.error === '0')) {
						location.reload();
					} else {
						alert((d && d.message) || '삭제 중 오류가 발생했습니다.');
						btn.disabled = false;
						btn.innerHTML = orig;
					}
				})
				.catch(function () {
					alert('삭제 중 오류가 발생했습니다.');
					btn.disabled = false;
					btn.innerHTML = orig;
				});
		});
	});
})();

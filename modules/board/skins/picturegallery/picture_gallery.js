(function () {
	'use strict';

	function bindDeleteButtons() {
		var cfg = window.CHURCH_BOARD_UI;
		if (!cfg || !cfg.isChurchAdmin) {
			return;
		}
		document.querySelectorAll('.church-pic-del[data-srl]').forEach(function (btn) {
			if (btn.dataset.bound) {
				return;
			}
			btn.dataset.bound = '1';
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var srl = btn.getAttribute('data-srl');
				if (!srl) {
					return;
				}
				if (!confirm('이 게시물을 삭제할까요?')) {
					return;
				}
				var meta = document.querySelector('meta[name="csrf-token"]');
				var token = (cfg.csrf_token || (meta ? meta.getAttribute('content') : ''));
				var fd = new FormData();
				fd.append('module', 'church_write');
				fd.append('act', 'procChurchWriteDeleteDocuments');
				fd.append('srls', srl);
				fd.append('_rx_csrf_token', token);
				btn.style.pointerEvents = 'none';
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
							btn.style.pointerEvents = '';
						}
					})
					.catch(function () {
						alert('삭제 중 오류가 발생했습니다.');
						btn.style.pointerEvents = '';
					});
			});
		});
	}

	function init() {
		var slideSets = [];
		var nodes = document.querySelectorAll('.church-pic-slides');
		nodes.forEach(function (box) {
			var imgs = box.querySelectorAll('.pg-img');
			if (imgs.length > 1) {
				slideSets.push({ imgs: imgs, idx: 0 });

				// 사진 장수 인디케이터
				var thumb = box.parentNode;
				if (thumb && !thumb.querySelector('.church-pic-count')) {
					var badge = document.createElement('span');
					badge.className = 'church-pic-count';
					badge.textContent = imgs.length;
					thumb.appendChild(badge);
				}
			}
		});

		if (slideSets.length) {
			setInterval(function () {
				slideSets.forEach(function (set) {
					set.imgs[set.idx].classList.remove('is-active');
					set.idx = (set.idx + 1) % set.imgs.length;
					set.imgs[set.idx].classList.add('is-active');
				});
			}, 1000);
		}

		bindDeleteButtons();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

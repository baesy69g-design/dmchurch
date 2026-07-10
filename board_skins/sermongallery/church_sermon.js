(function () {
	'use strict';

	function init() {
		var modal = document.getElementById('church-sermon-modal');
		if (!modal) {
			return;
		}
		var frame = modal.querySelector('.church-sermon-modal-frame');
		var titleEl = modal.querySelector('.church-sermon-modal-title');
		var scriptEl = modal.querySelector('.church-sermon-modal-script');
		var closeBtn = modal.querySelector('.church-sermon-modal-close');
		var backdrop = modal.querySelector('.church-sermon-modal-backdrop');

		function getScriptHtml(srl) {
			if (!srl) {
				return '';
			}
			var node = document.querySelector('.church-sermon-script-data[data-srl="' + srl + '"]');
			return node ? node.innerHTML : '';
		}

		function hit(srl, card) {
			if (!srl) {
				return;
			}
			var url = '/index.php?module=church_write&act=procChurchWriteHitDocument&srl=' +
				encodeURIComponent(srl);
			fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (res) { return res.json(); })
				.then(function (data) {
					if (!data || data.error != 0 || !card) {
						return;
					}
					var cnt = data.readed_count;
					if (cnt === undefined || cnt === null) {
						return;
					}
					var el = card.querySelector('.church-sermon-views');
					if (el) {
						var em = el.querySelector('em');
						el.textContent = Number(cnt).toLocaleString();
						if (em) {
							el.insertBefore(em, el.firstChild);
						}
					}
				})
				.catch(function () {});
		}

		function open(youtubeId, title, srl, card) {
			var src = 'https://www.youtube.com/embed/' + encodeURIComponent(youtubeId) +
				'?autoplay=1&rel=0&modestbranding=1&playsinline=1';
			frame.innerHTML = '<iframe src="' + src + '" title="' + (title || '설교 영상') +
				'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
			titleEl.textContent = title || '';

			var html = getScriptHtml(srl);
			if (html && html.replace(/<[^>]*>/g, '').trim() !== '') {
				scriptEl.innerHTML =
					'<div class="church-sermon-script-head"><span class="church-sermon-script-quote">\u201C</span>' +
					'<span class="church-sermon-script-label">\uBCF8\uBB38</span></div>' +
					'<div class="church-sermon-script-body">' + html + '</div>';
				scriptEl.scrollTop = 0;
				modal.classList.remove('no-script');
			} else {
				scriptEl.innerHTML = '';
				modal.classList.add('no-script');
			}

			modal.classList.add('is-open');
			modal.setAttribute('aria-hidden', 'false');
			document.body.style.overflow = 'hidden';

			hit(srl, card);
		}

		function close() {
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
			frame.innerHTML = '';
			scriptEl.innerHTML = '';
			document.body.style.overflow = '';
		}

		document.addEventListener('click', function (e) {
			var card = e.target.closest ? e.target.closest('.church-sermon-card') : null;
			if (!card) {
				return;
			}
			var yid = card.getAttribute('data-youtube');
			if (!yid) {
				e.preventDefault(); // 접근 불가 영상: 아무 동작 안 함
				return;
			}
			// 새 탭(Ctrl/Cmd/가운데 클릭)은 그대로 두기
			if (e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) {
				return;
			}
			e.preventDefault();
			open(yid, card.getAttribute('data-title') || '', card.getAttribute('data-srl') || '', card);
		});

		closeBtn.addEventListener('click', close);
		backdrop.addEventListener('click', close);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && modal.classList.contains('is-open')) {
				close();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

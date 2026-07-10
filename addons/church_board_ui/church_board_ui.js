(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	function todayISO() {
		var d = new Date();
		var m = String(d.getMonth() + 1).padStart(2, '0');
		var day = String(d.getDate()).padStart(2, '0');
		return d.getFullYear() + '-' + m + '-' + day;
	}

	// 업로드 전 브라우저에서 이미지 리사이즈 (최대 변 maxDim, 최대 용량 maxBytes)
	function resizeImage(file, maxDim, maxBytes) {
		return new Promise(function (resolve) {
			if (!file || !file.type || file.type.indexOf('image/') !== 0 || file.type === 'image/gif') {
				resolve(file);
				return;
			}
			var url = URL.createObjectURL(file);
			var img = new Image();
			img.onload = function () {
				URL.revokeObjectURL(url);
				var w = img.naturalWidth, h = img.naturalHeight;
				if (!w || !h) { resolve(file); return; }
				var ratio = Math.min(1, maxDim / Math.max(w, h));
				if (ratio === 1 && file.size <= maxBytes) { resolve(file); return; }
				var tw = Math.max(1, Math.round(w * ratio));
				var th = Math.max(1, Math.round(h * ratio));
				var canvas = document.createElement('canvas');
				canvas.width = tw;
				canvas.height = th;
				var ctx = canvas.getContext('2d');
				ctx.drawImage(img, 0, 0, tw, th);
				var q = 0.85;
				function attempt() {
					canvas.toBlob(function (blob) {
						if (!blob) { resolve(file); return; }
						if (blob.size <= maxBytes || q <= 0.5) {
							var name = (file.name || 'photo').replace(/\.(png|webp|bmp|gif)$/i, '.jpg');
							if (!/\.jpe?g$/i.test(name)) { name += '.jpg'; }
							resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
						} else {
							q -= 0.1;
							attempt();
						}
					}, 'image/jpeg', q);
				}
				attempt();
			};
			img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
			img.src = url;
		});
	}

	// 폼 데이터 구성 (이미지 파일은 리사이즈 후 첨부)
	function buildFormData(form, cfg) {
		var fileInputs = Array.prototype.slice.call(form.querySelectorAll('input[type=file]'));
		var resized = [];
		var tasks = [];
		fileInputs.forEach(function (inp, i) {
			resized[i] = [];
			Array.prototype.slice.call(inp.files || []).forEach(function (f) {
				tasks.push(resizeImage(f, 1600, 1.6 * 1024 * 1024).then(function (nf) {
					resized[i].push(nf);
				}));
			});
		});
		return Promise.all(tasks).then(function () {
			var fd = new FormData();
			Array.prototype.forEach.call(form.elements, function (el) {
				if (!el.name || el.type === 'file' || el.type === 'submit' || el.type === 'button') { return; }
				if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) { return; }
				fd.append(el.name, el.value);
			});
			fileInputs.forEach(function (inp, i) {
				resized[i].forEach(function (nf) { fd.append(inp.name, nf, nf.name); });
			});
			fd.append('module', 'church_write');
			fd.append('act', 'procChurchWriteInsertDocument');
			fd.append('module_srl', String(cfg.module_srl));
			fd.append('_rx_csrf_token', cfg.csrf_token);
			return fd;
		});
	}

	function fieldHtml(field) {
		var req = field.required ? '<span class="req">*</span>' : '';
		var help = field.help ? '<div class="cbu-help">' + field.help + '</div>' : '';
		var val = field.default || '';
		var input = '';

		switch (field.type) {
			case 'textarea':
				input = '<textarea name="' + field.name + '" rows="4"></textarea>';
				break;
			case 'date':
				input = '<input type="date" name="' + field.name + '" value="' + todayISO() + '" />';
				break;
			case 'file':
				input = '<input type="file" name="' + field.name + '" accept="' + (field.accept || '') + '"'
					+ (field.multiple ? ' multiple' : '') + ' />';
				break;
			case 'url':
				input = '<input type="url" name="' + field.name + '" placeholder="https://..." value="' + val + '" />';
				break;
			default:
				input = '<input type="text" name="' + field.name + '" value="' + val + '"'
					+ (field.placeholder ? ' placeholder="' + field.placeholder + '"' : '') + ' />';
		}

		return '<div class="cbu-field"><label>' + field.label + req + '</label>' + input + help + '</div>';
	}

	function openModal(cfg) {
		if (!cfg.form) {
			return;
		}
		var fields = cfg.form.fields.map(fieldHtml).join('');
		var overlay = document.createElement('div');
		overlay.className = 'cbu-overlay';
		overlay.innerHTML =
			'<div class="cbu-modal" role="dialog" aria-modal="true">'
			+ '<div class="cbu-head"><h2>' + cfg.form.title + '</h2>'
			+ '<button type="button" class="cbu-close" aria-label="닫기">&times;</button></div>'
			+ '<form class="cbu-form" enctype="multipart/form-data">'
			+ '<div class="cbu-body">' + fields + '</div>'
			+ '<div class="cbu-msg" style="display:none"></div>'
			+ '<div class="cbu-foot"><button type="submit" class="cbu-btn-submit">등록</button></div>'
			+ '</form></div>';

		document.body.appendChild(overlay);

		function close() {
			overlay.remove();
		}

		overlay.addEventListener('click', function (e) {
			if (e.target === overlay) {
				close();
			}
		});
		overlay.querySelector('.cbu-close').addEventListener('click', close);

		overlay.querySelector('.cbu-form').addEventListener('submit', function (e) {
			e.preventDefault();
			var form = e.target;
			var btn = form.querySelector('.cbu-btn-submit');
			var msg = overlay.querySelector('.cbu-msg');
			btn.disabled = true;
			msg.style.display = 'none';
			btn.textContent = '사진 처리 중...';

			buildFormData(form, cfg).then(function (fd) {
			btn.textContent = '등록';
			return fetch(cfg.api_url, {
				method: 'POST',
				body: fd,
				credentials: 'same-origin',
				redirect: 'follow',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
			})
				.then(function (res) {
					if (res.redirected) {
						window.location.href = res.url;
						return;
					}
					return res.text();
				})
				.then(function (text) {
					if (!text) {
						return;
					}
					// Rhymix error JSON or HTML
					try {
						var j = JSON.parse(text);
						if (j.error && j.message) {
							throw new Error(j.message);
						}
					} catch (err) {
						if (err.message && err.message !== 'Unexpected token') {
							throw err;
						}
					}
					window.location.reload();
				})
				.catch(function (err) {
					msg.textContent = err.message || '등록 중 오류가 발생했습니다.';
					msg.style.display = 'block';
					btn.disabled = false;
					btn.textContent = '등록';
				});
			});
		});
	}

	function setupButtons(cfg) {
		var areas = document.querySelectorAll('.btnArea');
		var writeLinks = document.querySelectorAll('a[href*="dispBoardWrite"]');

		if (cfg.usePopup) {
			writeLinks.forEach(function (a) {
				a.style.display = 'none';
			});
			areas.forEach(function (area) {
				if (area.querySelector('.cbu-write-btn')) {
					return;
				}
				var btn = document.createElement('a');
				btn.href = '#';
				btn.className = 'btn cbu-write-btn';
				btn.innerHTML = '<i class="xi-pen"></i> 등록';
				btn.addEventListener('click', function (e) {
					e.preventDefault();
					openModal(cfg);
				});
				area.insertBefore(btn, area.firstChild);
			});
		} else if (!cfg.canStandardWrite) {
			writeLinks.forEach(function (a) {
				a.style.display = 'none';
			});
		}
	}

	ready(function () {
		var cfg = window.CHURCH_BOARD_UI;
		if (!cfg) {
			return;
		}
		setupButtons(cfg);
	});
})();

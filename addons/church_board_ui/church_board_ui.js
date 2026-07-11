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

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

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

	function csrfToken(cfg) {
		var meta = document.querySelector('meta[name="csrf-token"]');
		return (cfg && cfg.csrf_token) || (meta ? meta.getAttribute('content') : '') || '';
	}

	function buildFormData(form, cfg, editSrl) {
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
			var token = csrfToken(cfg);
			fd.append('module', 'church_write');
			fd.append('act', editSrl ? 'procChurchWriteUpdateDocument' : 'procChurchWriteInsertDocument');
			fd.append('module_srl', String(cfg.module_srl));
			if (editSrl) {
				fd.append('target_srl', String(editSrl));
			}
			fd.append('_rx_csrf_token', token);
			return fd;
		});
	}

	function fieldHtml(field, values, isEdit) {
		var vals = values || {};
		var req = field.required && !isEdit ? '<span class="req">*</span>' : '';
		var help = field.help ? '<div class="cbu-help">' + field.help + '</div>' : '';
		if (isEdit && field.type === 'file') {
			help = '<div class="cbu-help">비워두면 기존 파일이 유지됩니다. 새 파일을 선택하면 교체됩니다.</div>';
		}
		var cur = vals[field.name];
		if (cur == null) { cur = field.default || ''; }
		var input = '';
		var preview = '';

		switch (field.type) {
			case 'textarea':
				input = '<textarea name="' + field.name + '" rows="4">' + esc(cur) + '</textarea>';
				break;
			case 'date':
				input = '<input type="date" name="' + field.name + '" value="' + esc(cur || todayISO()) + '" />';
				break;
			case 'file':
				input = '<input type="file" name="' + field.name + '" accept="' + (field.accept || '') + '"'
					+ (field.multiple ? ' multiple' : '') + ' />';
				var urlKey = field.name + '_url';
				if (isEdit && vals[urlKey]) {
					preview = '<div class="cbu-preview"><img src="' + esc(vals[urlKey]) + '" alt="현재 이미지" /></div>';
				}
				break;
			case 'url':
				input = '<input type="url" name="' + field.name + '" placeholder="https://..." value="' + esc(cur) + '" />';
				break;
			default:
				input = '<input type="text" name="' + field.name + '" value="' + esc(cur) + '"'
					+ (field.placeholder ? ' placeholder="' + esc(field.placeholder) + '"' : '') + ' />';
		}

		return '<div class="cbu-field"><label>' + field.label + req + '</label>' + preview + input + help + '</div>';
	}

	function openModal(cfg, editData) {
		if (!cfg.form) {
			return;
		}
		var isEdit = !!(editData && editData.document_srl);
		var values = (editData && editData.fields) || {};
		var fields = cfg.form.fields.map(function (f) { return fieldHtml(f, values, isEdit); }).join('');
		var title = isEdit
			? String(cfg.form.title || '등록').replace(/등록\s*$/, '수정')
			: cfg.form.title;
		if (isEdit && title === cfg.form.title) {
			title = cfg.form.title + ' (수정)';
		}
		var overlay = document.createElement('div');
		overlay.className = 'cbu-overlay';
		overlay.innerHTML =
			'<div class="cbu-modal" role="dialog" aria-modal="true">'
			+ '<div class="cbu-head"><h2>' + esc(title) + '</h2>'
			+ '<button type="button" class="cbu-close" aria-label="닫기">&times;</button></div>'
			+ '<form class="cbu-form" enctype="multipart/form-data">'
			+ '<div class="cbu-body">' + fields + '</div>'
			+ '<div class="cbu-msg" style="display:none"></div>'
			+ '<div class="cbu-foot"><button type="submit" class="cbu-btn-submit">'
			+ (isEdit ? '저장' : '등록') + '</button></div>'
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
			var submitLabel = isEdit ? '저장' : '등록';
			var titleInput = form.querySelector('[name="title"]');
			if (titleInput && !String(titleInput.value || '').trim()) {
				msg.textContent = '제목을 입력해 주세요.';
				msg.style.display = 'block';
				return;
			}
			btn.disabled = true;
			msg.style.display = 'none';
			btn.textContent = '처리 중...';

			buildFormData(form, cfg, isEdit ? editData.document_srl : 0).then(function (fd) {
			btn.textContent = submitLabel;
			return fetch('/index.php', {
				method: 'POST',
				body: fd,
				credentials: 'same-origin',
				redirect: isEdit ? 'error' : 'follow',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					'X-CSRF-Token': csrfToken(cfg),
					'Accept': 'application/json'
				},
			})
				.then(function (res) {
					if (!isEdit && (res.redirected || res.status === 301 || res.status === 302 || res.status === 303)) {
						window.location.href = res.url || location.href;
						return null;
					}
					return res.text().then(function (text) {
						return { status: res.status, text: text };
					});
				})
				.then(function (payload) {
					if (!payload) {
						return;
					}
					var text = payload.text || '';
					if (!text) {
						throw new Error(isEdit ? '저장 응답이 비었습니다. 다시 시도해 주세요.' : '등록 응답이 비었습니다.');
					}
					var j;
					try {
						j = JSON.parse(text);
					} catch (err) {
						if (!isEdit) {
							window.location.reload();
							return;
						}
						throw new Error('저장에 실패했습니다. 로그인 상태를 확인한 뒤 다시 시도해 주세요.');
					}
					if (j.error && j.error !== 0 && j.error !== '0') {
						throw new Error(j.message || '저장에 실패했습니다.');
					}
					if (isEdit && !j.saved && j.saved !== 1 && j.saved !== '1') {
						// success_updated 등만 있어도 통과
						if (!(j.message && String(j.message).indexOf('success') >= 0)) {
							// still ok if error=0
						}
					}
					window.location.reload();
				})
				.catch(function (err) {
					msg.textContent = err.message || (isEdit ? '저장 중 오류가 발생했습니다.' : '등록 중 오류가 발생했습니다.');
					msg.style.display = 'block';
					btn.disabled = false;
					btn.textContent = submitLabel;
				});
			});
		});
	}

	function fetchEditData(cfg, srl) {
		var token = csrfToken(cfg);
		var fd = new FormData();
		fd.append('module', 'church_write');
		fd.append('act', 'procChurchWriteGetDocument');
		fd.append('target_srl', String(srl));
		fd.append('module_srl', String(cfg.module_srl || ''));
		fd.append('_rx_csrf_token', token);
		return fetch('/index.php', {
			method: 'POST',
			body: fd,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': token, 'Accept': 'application/json' }
		}).then(function (r) {
			return r.text().then(function (text) {
				var d;
				try {
					d = JSON.parse(text);
				} catch (err) {
					throw new Error('글을 불러오지 못했습니다. 다시 로그인한 뒤 시도해 주세요.');
				}
				return d;
			});
		}).then(function (d) {
			if (!d || (d.error && d.error !== 0 && d.error !== '0')) {
				throw new Error((d && d.message) || '글을 불러오지 못했습니다.');
			}
			return {
				document_srl: d.document_srl || srl,
				fields: d.fields || {}
			};
		});
	}

	function bindEditButtons(cfg) {
		if (!cfg.isChurchAdmin || !cfg.usePopup) {
			return;
		}
		document.querySelectorAll('.church-pic-edit[data-srl], .church-sermon-edit[data-srl]').forEach(function (btn) {
			if (btn.dataset.boundEdit) {
				return;
			}
			btn.dataset.boundEdit = '1';
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var srl = btn.getAttribute('data-srl');
				if (!srl) {
					return;
				}
				btn.classList.add('is-loading');
				fetchEditData(cfg, srl)
					.then(function (data) {
						openModal(cfg, data);
					})
					.catch(function (err) {
						alert(err.message || '글을 불러오지 못했습니다.');
					})
					.finally(function () {
						btn.classList.remove('is-loading');
					});
			});
		});
	}

	function bindSermonDeleteButtons(cfg) {
		if (!cfg.isChurchAdmin) {
			return;
		}
		document.querySelectorAll('.church-sermon-del[data-srl]').forEach(function (btn) {
			if (btn.dataset.boundDel) {
				return;
			}
			btn.dataset.boundDel = '1';
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var srl = btn.getAttribute('data-srl');
				if (!srl || !confirm('이 영상을 삭제할까요?')) {
					return;
				}
				var meta = document.querySelector('meta[name="csrf-token"]');
				var token = cfg.csrf_token || (meta ? meta.getAttribute('content') : '');
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
		bindEditButtons(cfg);
		bindSermonDeleteButtons(cfg);
	});
})();

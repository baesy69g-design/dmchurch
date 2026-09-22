/**
 * 파송선교(p27) — 탭·편지/영상 피드·응원·기도 수정
 */
(function () {
	'use strict';

	/** 팝업 닫은 뒤 하단 게시판 탭으로 이동 */
	var goToBoardTab = function () {};
	var ytGoToBoardOnClose = false;
	var letterGoToBoardOnClose = false;

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	function $(sel, root) { return (root || document).querySelector(sel); }
	function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

	function parseConfig() {
		var el = document.getElementById('cd-cheer-config');
		if (!el) return null;
		try { return JSON.parse(el.textContent || '{}'); }
		catch (e) { return null; }
	}

	function postJson(url, payload) {
		var body = new URLSearchParams();
		Object.keys(payload || {}).forEach(function (k) {
			if (payload[k] === undefined || payload[k] === null) return;
			body.append(k, String(payload[k]));
		});
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: body.toString()
		}).then(function (res) {
			return res.json().catch(function () { return {}; }).then(function (data) {
				if (!res.ok || (data.error && Number(data.error) !== 0)) {
					throw new Error(data.message || data.msg || '요청에 실패했습니다.');
				}
				return data;
			});
		});
	}

	function getJson(url) {
		return fetch(url, {
			method: 'GET',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (res) {
			return res.json().catch(function () { return {}; }).then(function (data) {
				if (!res.ok || (data.error && Number(data.error) !== 0)) {
					throw new Error(data.message || data.msg || '불러오기에 실패했습니다.');
				}
				return data;
			});
		});
	}

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;').replace(/</g, '&lt;')
			.replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	function reactionRow(cfg, target, id, reactions) {
		var html = '<div class="cd-react" data-target="' + esc(target) + '" data-id="' + esc(id) + '">';
		(cfg.reactions || []).forEach(function (r) {
			var info = (reactions && reactions[r.key]) || { count: 0, mine: false };
			html += '<button type="button" class="cd-react-btn' + (info.mine ? ' is-mine' : '') + '" data-type="' + esc(r.key) + '" title="' + esc(r.label) + '">';
			html += '<span aria-hidden="true">' + r.emoji + '</span><em>' + (info.count || 0) + '</em></button>';
		});
		return html + '</div>';
	}

	function cheerSnippet(text, maxLen) {
		var t = String(text == null ? '' : text).replace(/\s+/g, ' ').trim();
		var max = maxLen || 28;
		if (t.length <= max) return t;
		return t.slice(0, max) + '…';
	}

	function renderQuickCheer(cfg, comments) {
		var qCheer = document.getElementById('cd-quick-cheer');
		if (!qCheer) return;
		if (!comments || !comments.length) {
			qCheer.innerHTML = '<strong class="cd-muted">' + esc(cfg.ui.empty_cheer || '등록된 응원이 없습니다.') + '</strong>';
			return;
		}
		var seen = {};
		var rows = [];
		comments.forEach(function (c) {
			var key = String(c.member_srl || '') || ('n:' + (c.nick_name || ''));
			if (seen[key]) return;
			seen[key] = true;
			rows.push(c);
		});
		rows = rows.slice(0, 5);
		var html = '<ul class="cd-quick-cheer-list">';
		rows.forEach(function (c) {
			html += '<li><strong>' + esc(c.nick_name || '') + '</strong>'
				+ '<span>' + esc(cheerSnippet(c.body, 26)) + '</span></li>';
		});
		qCheer.innerHTML = html + '</ul>';
	}

	function renderList(cfg, comments) {
		renderQuickCheer(cfg, comments);
		var list = document.getElementById('cd-cheer-list');
		if (!list) return;
		if (!comments || !comments.length) {
			list.innerHTML = '<p class="cd-empty">아직 응원이 없습니다. 첫 응원을 남겨 주세요.</p>';
			return;
		}
		var html = '';
		comments.forEach(function (c) {
			html += '<article class="cd-cheer-card" data-id="' + esc(c.id) + '">';
			html += '<header class="cd-cheer-head"><strong>' + esc(c.nick_name) + '</strong><time>' + esc(c.created) + '</time>';
			if (c.can_delete) {
				html += '<button type="button" class="cd-cheer-del" data-action="delete-comment" data-id="' + esc(c.id) + '">삭제</button>';
			}
			html += '</header>';
			html += '<p class="cd-cheer-body">' + esc(c.body).replace(/\n/g, '<br>') + '</p>';
			html += reactionRow(cfg, 'comment', c.id, c.reactions);
			if (c.reply) {
				html += '<div class="cd-cheer-reply"><header class="cd-cheer-head"><span class="cd-badge">' + esc(cfg.ui.reply_badge || '선교사') + '</span>';
				html += '<strong>' + esc(c.reply.nick_name) + '</strong><time>' + esc(c.reply.created) + '</time>';
				if (c.reply.can_edit) {
					html += '<button type="button" class="cd-cheer-del" data-action="delete-reply" data-id="' + esc(c.id) + '">답글삭제</button>';
				}
				html += '</header><p class="cd-cheer-body">' + esc(c.reply.body).replace(/\n/g, '<br>') + '</p>';
				html += reactionRow(cfg, 'reply', c.id, c.reply.reactions) + '</div>';
			} else if (cfg.canReply) {
				html += '<div class="cd-reply-form"><textarea rows="2" maxlength="500" placeholder="' + esc(cfg.ui.reply_placeholder || '') + '" data-reply-for="' + esc(c.id) + '"></textarea>';
				html += '<button type="button" class="cd-btn" data-action="reply" data-id="' + esc(c.id) + '">답글 남기기</button></div>';
			}
			html += '</article>';
		});
		list.innerHTML = html;
	}

	function renderComposer(cfg) {
		var box = document.getElementById('cd-cheer-composer');
		if (!box) return;
		if (!cfg.isLogged) {
			box.innerHTML =
				'<div class="cd-login-hint">'
				+ '<p>' + esc(cfg.ui.login_needed || '응원을 남기려면 회원가입 또는 로그인이 필요합니다.') + '</p>'
				+ '<div class="cd-login-actions">'
				+ '<a class="cd-btn cd-btn-primary" href="' + esc(cfg.loginUrl || '/?church_login=1') + '">'
				+ esc((cfg.ui && cfg.ui.login_btn) || '로그인') + '</a>'
				+ '<a class="cd-btn" href="' + esc(cfg.signupUrl || '/index.php?act=dispMemberSignUpForm') + '">'
				+ esc((cfg.ui && cfg.ui.signup_btn) || '회원가입') + '</a>'
				+ '</div></div>';
			return;
		}
		box.innerHTML =
			'<label class="cd-composer-label" for="cd-cheer-input">응원 한마디</label>' +
			'<textarea id="cd-cheer-input" rows="3" maxlength="300" placeholder="' + esc(cfg.ui.cheer_placeholder || '') + '"></textarea>' +
			'<div class="cd-composer-actions"><span class="cd-count"><em id="cd-cheer-count">0</em>/300</span>' +
			'<button type="button" class="cd-btn cd-btn-primary" id="cd-cheer-submit">' + esc(cfg.ui.cheer_submit || '응원 남기기') + '</button></div>';
		var ta = document.getElementById('cd-cheer-input');
		var cnt = document.getElementById('cd-cheer-count');
		if (ta && cnt) ta.addEventListener('input', function () { cnt.textContent = String(ta.value.length); });
	}

	function promptGuestCheer(cfg) {
		var msg = (cfg.ui && cfg.ui.login_needed) || '응원을 남기려면 회원가입 또는 로그인이 필요합니다.';
		if (window.confirm(msg + '\n\n확인 = 로그인\n취소 = 이 화면에서 회원가입·로그인 선택')) {
			location.href = cfg.loginUrl || '/?church_login=1';
		}
	}

	function goToCheerWrite(cfg) {
		goToBoardTab('cheer');
		setTimeout(function () {
			var composer = document.getElementById('cd-cheer-composer');
			if (composer) {
				composer.scrollIntoView({ behavior: 'smooth', block: 'center' });
				composer.classList.add('is-focus');
				setTimeout(function () { composer.classList.remove('is-focus'); }, 1600);
			}
			if (!cfg || !cfg.isLogged) {
				promptGuestCheer(cfg || {});
				return;
			}
			var ta = document.getElementById('cd-cheer-input');
			if (ta) {
				ta.focus();
				try { ta.select(); } catch (e) { /* ignore */ }
			}
		}, 80);
	}

	function bindQuickCheerWrite(root, cfg) {
		var btn = document.getElementById('cd-quick-cheer-write');
		if (!btn) return;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			goToCheerWrite(cfg);
		});
		var card = document.getElementById('cd-quick-cheer-card');
		if (card) {
			card.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					if (e.target === btn) return;
					e.preventDefault();
					goToBoardTab('cheer');
				}
			});
		}
	}

	function loadCheer(cfg) {
		return getJson(cfg.listUrl).then(function (data) {
			renderList(cfg, data.comments || []);
		}).catch(function (err) {
			var list = document.getElementById('cd-cheer-list');
			if (list) list.innerHTML = '<p class="cd-empty">' + esc(err.message) + '</p>';
		});
	}

	function formatViews(n) {
		var v = parseInt(n, 10);
		if (isNaN(v) || v < 0) v = 0;
		return '조회 ' + v.toLocaleString('ko-KR');
	}

	function hitDocument(srl) {
		srl = parseInt(srl, 10) || 0;
		if (!srl) return;
		fetch('/index.php?module=church_write&act=procChurchWriteHitDocument&srl=' + srl, {
			method: 'GET',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
		}).then(function (res) {
			return res.json().catch(function () { return {}; });
		}).then(function (data) {
			if (data && data.readed_count != null) {
				$$('[data-views-for="' + srl + '"]').forEach(function (el) {
					el.textContent = formatViews(data.readed_count);
				});
			}
		}).catch(function () { /* ignore */ });
	}

	function openYoutubePopup(id, srl) {
		var popup = document.getElementById('cd-yt-popup');
		var frame = document.getElementById('cd-yt-popup-frame');
		if (!popup || !frame || !id) return;
		frame.innerHTML = '<iframe src="https://www.youtube.com/embed/' + encodeURIComponent(id) +
			'?autoplay=1&rel=0" title="YouTube" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
		popup.hidden = false;
		document.body.style.overflow = 'hidden';
		if (srl) hitDocument(srl);
	}

	function closeYoutubePopup() {
		var popup = document.getElementById('cd-yt-popup');
		var frame = document.getElementById('cd-yt-popup-frame');
		var goBoard = !!ytGoToBoardOnClose;
		ytGoToBoardOnClose = false;
		if (frame) frame.innerHTML = '';
		if (popup) popup.hidden = true;
		document.body.style.overflow = '';
		if (goBoard) goToBoardTab('video');
	}

	function bindYoutubePopup(root) {
		var popup = document.getElementById('cd-yt-popup');
		if (!popup) return;
		var closeBtn = document.getElementById('cd-yt-popup-close');
		if (closeBtn) closeBtn.addEventListener('click', function (e) { e.stopPropagation(); closeYoutubePopup(); });
		popup.addEventListener('click', function (e) {
			if (e.target === popup) closeYoutubePopup();
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !popup.hidden) closeYoutubePopup();
		});

		$$('.cd-quick-card[data-cd-tab="video"]', root).forEach(function (card) {
			card.addEventListener('click', function (e) {
				var id = card.getAttribute('data-yt') || '';
				if (!id) return;
				e.preventDefault();
				e.stopImmediatePropagation();
				ytGoToBoardOnClose = true;
				openYoutubePopup(id, card.getAttribute('data-srl') || '');
			}, true);
		});
	}

	function bindTabs(root) {
		var tabs = $$('.cd-tab', root);
		var panels = $$('.cd-panel', root);
		function activate(name, opts) {
			if (!name) return;
			tabs.forEach(function (t) {
				var on = t.getAttribute('data-cd-tab') === name;
				t.classList.toggle('is-active', on);
				t.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			panels.forEach(function (p) {
				var on = p.getAttribute('data-cd-panel') === name;
				p.classList.toggle('is-active', on);
				if (on) p.removeAttribute('hidden');
				else p.setAttribute('hidden', 'hidden');
			});
			var scroll = !opts || opts.scroll !== false;
			if (scroll) {
				var panel = document.getElementById('cd-tab-' + name)
					|| root.querySelector('[data-cd-panel="' + name + '"]');
				if (panel) {
					setTimeout(function () {
						panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
					}, 40);
				}
			}
		}
		goToBoardTab = activate;
		tabs.forEach(function (t) {
			t.addEventListener('click', function () { activate(t.getAttribute('data-cd-tab')); });
		});
		$$('[data-cd-tab]', root).forEach(function (el) {
			if (el.classList.contains('cd-tab')) return;
			el.addEventListener('click', function (e) {
				var name = el.getAttribute('data-cd-tab');
				if (!name) return;
				if (name === 'video' && el.getAttribute('data-yt')) return;
				if (name === 'letter' && (el.getAttribute('data-letter-imgs') || el.getAttribute('data-letter-img'))) return;
				e.preventDefault();
				activate(name);
			});
		});
		if (location.hash === '#cd-tab-video') activate('video');
		else if (location.hash === '#cd-tab-cheer') activate('cheer');
		else if (location.hash === '#cd-tab-letter') activate('letter');
	}

	function openWrite(cfg, boardKey) {
		var wcfg = (cfg.writeConfigs || {})[boardKey];
		if (!wcfg || !wcfg.form) {
			alert('등록 폼을 불러올 수 없습니다. 게시판 설정을 확인해 주세요.');
			return;
		}
		if (window.ChurchBoardUI && typeof window.ChurchBoardUI.openModal === 'function') {
			window.ChurchBoardUI.openModal(wcfg);
			return;
		}
		location.href = '/index.php?mid=' + encodeURIComponent(boardKey) + '&act=dispBoardWrite';
	}

	function renderToolbar(cfg) {
		var letterBar = document.getElementById('cd-letter-toolbar');
		var videoBar = document.getElementById('cd-video-toolbar');
		if (cfg.canWriteBoards) {
			if (letterBar) {
				letterBar.hidden = false;
				letterBar.innerHTML = '<button type="button" class="cd-btn cd-btn-primary" id="cd-write-letter">' + esc(cfg.ui.write_letter || '선교편지 등록') + '</button>';
				$('#cd-write-letter', letterBar).addEventListener('click', function () { openWrite(cfg, cfg.letterMid || 'dispatch_letter'); });
			}
			if (videoBar) {
				videoBar.hidden = false;
				videoBar.innerHTML = '<button type="button" class="cd-btn cd-btn-primary" id="cd-write-video">' + esc(cfg.ui.write_video || '동영상 등록') + '</button>';
				$('#cd-write-video', videoBar).addEventListener('click', function () { openWrite(cfg, cfg.videoMid || 'dispatch_video'); });
			}
		}
	}

	function renderFeed(cfg, data) {
		var letters = data.letters || [];
		var videos = data.videos || [];
		var letterList = document.getElementById('cd-letter-list');
		var videoList = document.getElementById('cd-video-list');
		var qLetter = document.getElementById('cd-quick-letter');
		var qVideo = document.getElementById('cd-quick-video');

		if (qLetter) {
			var lCard = document.querySelector('.cd-quick-card[data-cd-tab="letter"]');
			if (letters[0]) {
				var L = letters[0];
				var thumb = (L.images && L.images[0]) || '';
				qLetter.innerHTML = (thumb ? '<span class="cd-quick-thumb" style="background-image:url(' + esc(thumb) + ')"></span>' : '')
					+ '<strong>' + esc(L.title) + '</strong><em>' + esc(L.date) + '</em>';
				if (lCard) {
					if (thumb) lCard.setAttribute('data-letter-img', thumb);
					else lCard.removeAttribute('data-letter-img');
					try {
						lCard.setAttribute('data-letter-imgs', JSON.stringify(L.images || []));
					} catch (e) {
						lCard.removeAttribute('data-letter-imgs');
					}
					if (L.document_srl) lCard.setAttribute('data-srl', String(L.document_srl));
					else lCard.removeAttribute('data-srl');
				}
			} else {
				qLetter.innerHTML = '<strong class="cd-muted">' + esc(cfg.ui.empty_letter || '') + '</strong>';
				if (lCard) lCard.removeAttribute('data-letter-img');
				if (lCard) lCard.removeAttribute('data-letter-imgs');
				if (lCard) lCard.removeAttribute('data-srl');
			}
		}
		if (qVideo) {
			var vCard = document.getElementById('cd-quick-video-card');
			if (videos[0] && videos[0].youtube_id) {
				var V = videos[0];
				var yt = 'https://i.ytimg.com/vi/' + encodeURIComponent(V.youtube_id) + '/hqdefault.jpg';
				qVideo.innerHTML = '<span class="cd-quick-thumb" style="background-image:url(' + esc(yt) + ')"></span>'
					+ '<strong>' + esc(V.title) + '</strong><em>' + esc(V.date) + '</em>';
				if (vCard) {
					vCard.setAttribute('data-yt', V.youtube_id);
					if (V.document_srl) vCard.setAttribute('data-srl', String(V.document_srl));
					else vCard.removeAttribute('data-srl');
				}
			} else {
				qVideo.innerHTML = '<strong class="cd-muted">' + esc(cfg.ui.empty_video || '') + '</strong>';
				if (vCard) {
					vCard.removeAttribute('data-yt');
					vCard.removeAttribute('data-srl');
				}
			}
		}

		if (letterList) {
			if (!letters.length) {
				letterList.innerHTML = '<p class="cd-empty">' + esc(cfg.ui.empty_letter || '') + '</p>';
			} else {
				var lh = '<ul class="cd-letter-list">';
				letters.forEach(function (item) {
					lh += '<li class="cd-letter-item" data-srl="' + esc(item.document_srl) + '"><div class="cd-letter-meta"><strong>' + esc(item.title) + '</strong>';
					lh += '<div class="cd-meta-row"><time>' + esc(item.date) + '</time>';
					lh += '<span class="cd-views" data-views-for="' + esc(item.document_srl) + '">' + esc(formatViews(item.views)) + '</span></div></div>';
					lh += '<div class="cd-letter-pages">';
					(item.images || []).forEach(function (src) {
						lh += '<a class="cd-letter-page" href="' + esc(src) + '" target="_blank" rel="noopener"><img src="' + esc(src) + '" alt="" loading="lazy" /></a>';
					});
					lh += '</div>';
					if (item.can_edit) {
						lh += '<div class="cd-item-actions"><button type="button" class="cd-btn cd-edit-doc" data-board="dispatch_letter" data-srl="' + item.document_srl + '">수정</button>';
						lh += '<button type="button" class="cd-btn cd-del-doc" data-srl="' + item.document_srl + '">삭제</button></div>';
					}
					lh += '</li>';
				});
				letterList.innerHTML = lh + '</ul>';
			}
		}

		if (videoList) {
			if (!videos.length) {
				videoList.innerHTML = '<p class="cd-empty">' + esc(cfg.ui.empty_video || '') + '</p>';
			} else {
				var vh = '<ul class="cd-video-list">';
				videos.forEach(function (item) {
					var thumb = item.youtube_id ? 'https://i.ytimg.com/vi/' + encodeURIComponent(item.youtube_id) + '/hqdefault.jpg' : '';
					vh += '<li class="cd-video-item"><button type="button" class="cd-video-play" data-yt="' + esc(item.youtube_id) + '" data-srl="' + esc(item.document_srl) + '">';
					if (thumb) vh += '<span class="cd-video-thumb" style="background-image:url(' + esc(thumb) + ')"></span>';
					vh += '<span class="cd-video-play-icon" aria-hidden="true">▶</span></button>';
					vh += '<div class="cd-video-body"><strong>' + esc(item.title) + '</strong>';
					vh += '<div class="cd-meta-row"><time>' + esc(item.date) + '</time>';
					vh += '<span class="cd-views" data-views-for="' + esc(item.document_srl) + '">' + esc(formatViews(item.views)) + '</span></div>';
					if (item.summary) vh += '<p>' + esc(item.summary) + '</p>';
					if (item.can_edit) {
						vh += '<div class="cd-item-actions"><button type="button" class="cd-btn cd-edit-doc" data-board="dispatch_video" data-srl="' + item.document_srl + '">수정</button>';
						vh += '<button type="button" class="cd-btn cd-del-doc" data-srl="' + item.document_srl + '">삭제</button></div>';
					}
					vh += '</div></li>';
				});
				videoList.innerHTML = vh + '</ul>';
			}
		}

		$$('.cd-video-play').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var id = btn.getAttribute('data-yt') || '';
				if (!id) return;
				ytGoToBoardOnClose = true;
				openYoutubePopup(id, btn.getAttribute('data-srl') || '');
			});
		});

		$$('.cd-edit-doc').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var board = btn.getAttribute('data-board');
				var srl = btn.getAttribute('data-srl');
				var wcfg = (cfg.writeConfigs || {})[board];
				if (!wcfg || !window.ChurchBoardUI) return;
				window.ChurchBoardUI.fetchEditData(wcfg, srl).then(function (data) {
					window.ChurchBoardUI.openModal(wcfg, data);
				}).catch(function (err) { alert(err.message || '글을 불러오지 못했습니다.'); });
			});
		});

		$$('.cd-del-doc').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var srl = btn.getAttribute('data-srl');
				if (!srl || !confirm('이 글을 삭제할까요?')) return;
				var fd = new FormData();
				fd.append('module', 'church_write');
				fd.append('act', 'procChurchWriteDeleteDocuments');
				fd.append('srls', srl);
				fd.append('_rx_csrf_token', cfg.csrf || '');
				fetch('/index.php', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then(function (r) { return r.json(); })
					.then(function (d) {
						if (d && (d.error === 0 || d.error === '0')) location.reload();
						else alert((d && d.message) || '삭제 실패');
					});
			});
		});
	}

	function loadFeed(cfg) {
		if (!cfg.feedUrl) return Promise.resolve();
		return getJson(cfg.feedUrl).then(function (data) { renderFeed(cfg, data); })
			.catch(function (err) {
				var letterList = document.getElementById('cd-letter-list');
				if (letterList) letterList.innerHTML = '<p class="cd-empty">' + esc(err.message) + '</p>';
			});
	}

	function bindPrayer(cfg) {
		var wrap = document.getElementById('cd-prayer');
		var btn = document.getElementById('cd-prayer-edit');
		var text = document.getElementById('cd-prayer-text');
		if (!wrap || !btn || !text) return;
		if (cfg.canEditPrayer) {
			btn.hidden = false;
			wrap.classList.add('cd-prayer--editable');
		}
		if (cfg.prayerLine != null && String(cfg.prayerLine) !== '') {
			text.textContent = cfg.prayerLine;
			wrap.setAttribute('data-prayer', cfg.prayerLine);
		}
		btn.addEventListener('click', function () {
			var cur = wrap.getAttribute('data-prayer') || text.textContent || '';
			if (cur === '—') cur = '';
			var next = window.prompt(cfg.ui.edit_prayer || '기도 제목 수정', cur);
			if (next == null) return;
			next = String(next).trim();
			postJson(cfg.prayerUrl, { _rx_csrf_token: cfg.csrf, prayer_line: next })
				.then(function (data) {
					var line = data.prayer_line != null ? data.prayer_line : next;
					text.textContent = line || '—';
					wrap.setAttribute('data-prayer', line);
				})
				.catch(function (err) { alert(err.message); });
		});
	}

	function bindCheer(cfg) {
		var root = document.getElementById('cd-cheer-root');
		if (!root) return;
		var submit = document.getElementById('cd-cheer-submit');
		if (submit) {
			submit.addEventListener('click', function () {
				var ta = document.getElementById('cd-cheer-input');
				var body = (ta && ta.value || '').trim();
				if (!body) { alert('응원 내용을 입력해 주세요.'); return; }
				submit.disabled = true;
				postJson(cfg.addUrl, { _rx_csrf_token: cfg.csrf, body: body })
					.then(function () {
						if (ta) ta.value = '';
						var cnt = document.getElementById('cd-cheer-count');
						if (cnt) cnt.textContent = '0';
						return loadCheer(cfg);
					})
					.catch(function (err) { alert(err.message); })
					.then(function () { submit.disabled = false; });
			});
		}
		root.addEventListener('click', function (e) {
			var btn = e.target.closest('button');
			if (!btn) return;
			if (btn.classList.contains('cd-react-btn')) {
				if (!cfg.isLogged) {
					promptGuestCheer(cfg);
					return;
				}
				var wrap = btn.closest('.cd-react');
				postJson(cfg.reactUrl, {
					_rx_csrf_token: cfg.csrf,
					comment_id: wrap.getAttribute('data-id'),
					target: wrap.getAttribute('data-target'),
					reaction: btn.getAttribute('data-type')
				}).then(function () { return loadCheer(cfg); }).catch(function (err) { alert(err.message); });
				return;
			}
			var action = btn.getAttribute('data-action');
			if (!action) return;
			var id = btn.getAttribute('data-id');
			if (action === 'reply') {
				var ta = root.querySelector('textarea[data-reply-for="' + id + '"]');
				var body = (ta && ta.value || '').trim();
				if (!body) { alert('답글 내용을 입력해 주세요.'); return; }
				btn.disabled = true;
				postJson(cfg.replyUrl, { _rx_csrf_token: cfg.csrf, comment_id: id, body: body })
					.then(function () { return loadCheer(cfg); })
					.catch(function (err) { alert(err.message); })
					.then(function () { btn.disabled = false; });
			}
			if (action === 'delete-comment' || action === 'delete-reply') {
				if (!confirm(action === 'delete-reply' ? '답글을 삭제할까요?' : '응원을 삭제할까요?')) return;
				postJson(cfg.deleteUrl, {
					_rx_csrf_token: cfg.csrf,
					comment_id: id,
					mode: action === 'delete-reply' ? 'reply' : 'comment'
				}).then(function () { return loadCheer(cfg); }).catch(function (err) { alert(err.message); });
			}
		});
	}

	function bindPhotoZoom(root) {
		var zoom = document.getElementById('cd-photo-zoom');
		if (!zoom) return;
		var card = zoom.querySelector('.cd-photo-zoom-card');
		var img = zoom.querySelector('img');
		var btnPrev = zoom.querySelector('.cd-zoom-prev');
		var btnNext = zoom.querySelector('.cd-zoom-next');
		var pageEl = document.getElementById('cd-zoom-page');
		var btnClose = zoom.querySelector('.cd-zoom-close');
		var showTimer = null;
		var hideTimer = null;
		var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
		var currentSrc = '';
		var gallery = [];
		var galleryIndex = 0;

		function clearTimers() {
			if (showTimer) { clearTimeout(showTimer); showTimer = null; }
			if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
		}
		function updateNav() {
			var multi = gallery.length > 1;
			if (btnPrev) btnPrev.hidden = !multi;
			if (btnNext) btnNext.hidden = !multi;
			if (pageEl) {
				if (multi) {
					pageEl.hidden = false;
					pageEl.textContent = (galleryIndex + 1) + ' / ' + gallery.length;
				} else {
					pageEl.hidden = true;
					pageEl.textContent = '';
				}
			}
		}
		function showAt(index, sticky) {
			if (!gallery.length || !img) return;
			galleryIndex = ((index % gallery.length) + gallery.length) % gallery.length;
			var src = gallery[galleryIndex];
			if (!src) return;
			clearTimers();
			currentSrc = src;
			img.src = src;
			zoom.hidden = false;
			zoom.classList.toggle('is-sticky', !!sticky);
			document.body.style.overflow = sticky ? 'hidden' : '';
			updateNav();
		}
		function show(src, sticky) {
			if (!src) return;
			gallery = [src];
			showAt(0, sticky);
		}
		function showGallery(srcs, startIndex, sticky) {
			var list = (srcs || []).map(function (s) { return String(s || '').trim(); }).filter(Boolean);
			if (!list.length) return;
			gallery = list;
			showAt(startIndex || 0, sticky !== false);
		}
		function hide() {
			var goBoard = letterGoToBoardOnClose;
			letterGoToBoardOnClose = false;
			clearTimers();
			zoom.hidden = true;
			zoom.classList.remove('is-sticky');
			currentSrc = '';
			gallery = [];
			galleryIndex = 0;
			document.body.style.overflow = '';
			if (img) img.removeAttribute('src');
			updateNav();
			if (goBoard) goToBoardTab('letter');
		}
		function step(delta) {
			if (gallery.length < 2) return;
			showAt(galleryIndex + delta, true);
		}
		function scheduleShow(src) {
			clearTimers();
			showTimer = setTimeout(function () { show(src); }, 180);
		}
		function scheduleHide() {
			if (showTimer) { clearTimeout(showTimer); showTimer = null; }
			if (hideTimer) clearTimeout(hideTimer);
			hideTimer = setTimeout(hide, 80);
		}
		function parseImgsAttr(el) {
			if (!el) return [];
			var raw = el.getAttribute('data-letter-imgs') || '';
			if (!raw) {
				var one = el.getAttribute('data-letter-img') || '';
				return one ? [one] : [];
			}
			try {
				var arr = JSON.parse(raw);
				return Array.isArray(arr) ? arr : [];
			} catch (e) {
				return [];
			}
		}
		function letterSrcsFromDom() {
			var cardEl = root.querySelector('.cd-quick-card[data-cd-tab="letter"]');
			var fromCard = parseImgsAttr(cardEl);
			if (fromCard.length) return fromCard;
			return $$('.cd-letter-item:first-child .cd-letter-page', root).map(function (a) {
				return a.getAttribute('href') || (a.querySelector('img') && a.querySelector('img').getAttribute('src')) || '';
			}).filter(Boolean);
		}
		function showFirstLetterPhoto() {
			letterGoToBoardOnClose = true;
			showGallery(letterSrcsFromDom(), 0, true);
			var cardEl = root.querySelector('.cd-quick-card[data-cd-tab="letter"]');
			hitDocument(cardEl && cardEl.getAttribute('data-srl'));
		}

		$$('.cd-collage-item', root).forEach(function (fig) {
			var src = fig.getAttribute('data-full') || (fig.querySelector('img') && fig.querySelector('img').src) || '';
			if (finePointer) {
				fig.addEventListener('mouseenter', function () { scheduleShow(src); });
				fig.addEventListener('mouseleave', scheduleHide);
			}
			fig.addEventListener('click', function (e) {
				e.preventDefault();
				if (!finePointer) {
					if (!zoom.hidden && currentSrc === src) hide();
					else show(src);
				}
			});
		});

		/* 최신 선교편지 클릭 → 선교편지 갤러리(크게 + 좌우 이동) */
		$$('.cd-quick-card[data-cd-tab="letter"]', root).forEach(function (cardEl) {
			cardEl.addEventListener('click', function (e) {
				e.preventDefault();
				showFirstLetterPhoto();
			});
		});

		/* 편지 목록 썸네일 클릭 → 해당 편지 페이지 갤러리 */
		root.addEventListener('click', function (e) {
			var page = e.target.closest('.cd-letter-page');
			if (!page || !root.contains(page)) return;
			e.preventDefault();
			var item = page.closest('.cd-letter-item');
			var pages = item ? $$('.cd-letter-page', item) : [page];
			var srcs = pages.map(function (a) {
				return a.getAttribute('href') || (a.querySelector('img') && a.querySelector('img').getAttribute('src')) || '';
			}).filter(Boolean);
			var idx = Math.max(0, pages.indexOf(page));
			letterGoToBoardOnClose = true;
			showGallery(srcs, idx, true);
			hitDocument(item && item.getAttribute('data-srl'));
		});

		if (btnPrev) btnPrev.addEventListener('click', function (e) { e.stopPropagation(); step(-1); });
		if (btnNext) btnNext.addEventListener('click', function (e) { e.stopPropagation(); step(1); });
		if (btnClose) btnClose.addEventListener('click', function (e) { e.stopPropagation(); hide(); });
		if (card) card.addEventListener('click', function (e) { e.stopPropagation(); });

		document.addEventListener('keydown', function (e) {
			if (zoom.hidden) return;
			if (e.key === 'Escape') hide();
			else if (e.key === 'ArrowLeft') step(-1);
			else if (e.key === 'ArrowRight') step(1);
		});
		zoom.addEventListener('click', function (e) {
			if (e.target === zoom) hide();
		});
	}

	ready(function () {
		var root = document.getElementById('church-dispatch');
		if (!root) return;
		bindTabs(root);
		bindPhotoZoom(root);
		bindYoutubePopup(root);
		var base = parseConfig();
		if (!base) return;

		var boot = Promise.resolve(base);
		if (base.configUrl) {
			boot = getJson(base.configUrl).then(function (live) {
				return Object.assign({}, base, {
					isLogged: !!live.isLogged,
					memberSrl: live.memberSrl || 0,
					nickName: live.nickName || '',
					canReply: !!live.canReply,
					canEditPrayer: !!live.canEditPrayer,
					canWriteBoards: !!live.canWriteBoards,
					csrf: live.csrf || '',
					prayerLine: live.prayerLine || '',
					writeConfigs: live.writeConfigs || {}
				});
			}).catch(function () {
				return Object.assign({}, base, { isLogged: false, canReply: false, canEditPrayer: false, canWriteBoards: false, csrf: '' });
			});
		}

		boot.then(function (cfg) {
			bindPrayer(cfg);
			renderToolbar(cfg);
			loadFeed(cfg);
			renderComposer(cfg);
			bindCheer(cfg);
			bindQuickCheerWrite(root, cfg);
			loadCheer(cfg);
		});
	});
})();

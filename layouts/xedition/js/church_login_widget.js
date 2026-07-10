jQuery(function ($) {
	var keep_msg = $('#warning');
	var $widget = $('.login_widget');
	var $loginForm = $widget.find('form.rx_ajax');
	var $uid = $widget.find('#church_login_uid');
	var $pw = $widget.find('#church_login_pw');
	var $error = $widget.find('.login-error-msg');
	var lastSubmit = { uid: '', pw: '' };

	function cleanLoginReturnUrl(url) {
		if (!url) {
			return window.location.pathname || '/';
		}
		try {
			var u = new URL(url, window.location.origin);
			u.searchParams.delete('church_login');
			var qs = u.searchParams.toString();
			return u.pathname + (qs ? '?' + qs : '') + (u.hash || '');
		} catch (e) {
			return String(url)
				.replace(/([?&])church_login=1(&|$)/, '$1')
				.replace(/[?&]$/, '');
		}
	}

	function syncReturnUrlFields() {
		var clean = cleanLoginReturnUrl(window.location.href);
		$loginForm.find('input[name="success_return_url"]').val(clean);
		$loginForm.find('input[name="error_return_url"]').val(clean);
	}

	function syncFloatLabels() {
		$widget.find('input[type="text"], input[type="email"], input[type="password"]').each(function () {
			var $input = $(this);
			if ($input.val()) {
				$input.addClass('used');
			} else {
				$input.removeClass('used');
			}
		});
	}

	function blockAutofill() {
		$uid.prop('readonly', true);
		$pw.prop('readonly', true);
		window.setTimeout(function () {
			$uid.prop('readonly', false);
			$pw.prop('readonly', false);
		}, 120);
	}

	function showError(msg) {
		if (!msg) {
			$error.hide().text('');
			return;
		}
		$error.text(msg).show();
	}

	function hideLoginWidget() {
		$widget.hide();
		showError('');
	}

	function restoreSubmittedValues() {
		if (lastSubmit.uid) {
			$uid.val(lastSubmit.uid);
		}
		if (lastSubmit.pw) {
			$pw.val(lastSubmit.pw);
		}
		syncFloatLabels();
	}

	function showLoginWidget() {
		syncReturnUrlFields();
		blockAutofill();
		$widget.show();
		syncFloatLabels();
		window.setTimeout(syncFloatLabels, 200);
		window.setTimeout(syncFloatLabels, 600);
		$uid.trigger('focus');
	}

	function applyReturnUrl() {
		var params = new URLSearchParams(window.location.search);
		var ret = params.get('success_return_url');
		if (!ret) {
			syncReturnUrlFields();
			return;
		}
		var clean = cleanLoginReturnUrl(ret);
		$loginForm.find('input[name="success_return_url"]').val(clean);
		$loginForm.find('input[name="error_return_url"]').val(clean);
	}

	function shouldAutoOpen() {
		var params = new URLSearchParams(window.location.search);
		if (params.get('church_login') === '1') {
			return true;
		}
		return document.cookie.indexOf('church_open_login=1') >= 0;
	}

	function clearAutoOpenState() {
		var params = new URLSearchParams(window.location.search);
		if (params.get('church_login') === '1') {
			params.delete('church_login');
			var qs = params.toString();
			var next = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
			window.history.replaceState({}, '', next);
		}
		document.cookie = 'church_open_login=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';
	}

	keep_msg.hide();
	$('.chk_label').on('mouseenter mouseleave focusin focusout', function (e) {
		if (e.type === 'mouseenter' || e.type === 'focusin') {
			keep_msg.show();
		} else {
			keep_msg.hide();
		}
	});

	$('#ly_login_btn, #ly_btn').click(function () {
		showError('');
		applyReturnUrl();
		showLoginWidget();
		return false;
	});

	$('.church-login-close, .btn_ly_popup').click(function () {
		hideLoginWidget();
		return false;
	});

	$widget.find('.ly_dimmed').click(function () {
		hideLoginWidget();
		return false;
	});

	$widget.find('input').on('input focus blur change', syncFloatLabels);

	$('#church_pw_toggle').on('click', function () {
		var $icon = $(this).find('i');
		var $btn = $(this);
		var show = $pw.attr('type') === 'password';
		var label = show ? '비밀번호 숨기기' : '비밀번호 보기';
		$pw.attr('type', show ? 'text' : 'password');
		$icon.attr('class', show ? 'xi-eye-off' : 'xi-eye');
		$btn.attr('aria-label', label);
		$btn.attr('title', label);
		$pw.trigger('focus');
		syncFloatLabels();
	});

	$loginForm.data('callbackSuccess', function (data) {
		hideLoginWidget();
		clearAutoOpenState();
		var url = cleanLoginReturnUrl((data && data.redirect_url) ? String(data.redirect_url).replace(/&amp;/g, '&') : window.location.href);
		window.location.href = url;
	});

	$loginForm.data('callbackError', function (data) {
		var msg = (data && data.message) ? String(data.message).replace(/\\n/g, '\n') : '아이디 또는 비밀번호가 일치하지 않습니다.';
		restoreSubmittedValues();
		showError(msg);
		showLoginWidget();
		return false;
	});

	$loginForm.on('submit', function () {
		lastSubmit.uid = $uid.val();
		lastSubmit.pw = $pw.val();
		showError('');
		syncReturnUrlFields();
	});

	syncReturnUrlFields();

	if (shouldAutoOpen()) {
		applyReturnUrl();
		showLoginWidget();
		clearAutoOpenState();
	}

	syncFloatLabels();
});

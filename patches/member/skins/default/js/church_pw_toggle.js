jQuery(function ($) {
	$('.church-pw-toggle').on('click', function () {
		var targetId = $(this).attr('data-target');
		var $pw = $('#' + targetId);
		var $icon = $(this).find('i');
		var $btn = $(this);
		var show = $pw.attr('type') === 'password';
		var label = show ? '비밀번호 숨기기' : '비밀번호 보기';
		$pw.attr('type', show ? 'text' : 'password');
		$icon.attr('class', show ? 'xi-eye-off' : 'xi-eye');
		$btn.attr('aria-label', label);
		$btn.attr('title', label);
		$pw.trigger('focus');
	});
});

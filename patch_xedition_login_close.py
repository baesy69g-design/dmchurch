#!/usr/bin/env python3
"""로그인 팝업 우상단 닫기 버튼 + 배경 클릭 닫기"""
from pathlib import Path

LAYOUT = Path('/root/church-web/html/layouts/xedition/layout.html')
text = LAYOUT.read_text(encoding='utf-8')

close_btn = (
    '<button type="button" class="church-login-close" aria-label="닫기" title="닫기">'
    '<span aria-hidden="true">&times;</span></button>'
)

if 'church-login-close' not in text:
    text = text.replace('<div class="signin">', '<div class="signin">' + close_btn, 1)

if '$(".church-login-close")' not in text:
    text = text.replace(
        '\t\t$(".btn_ly_popup").click(function () {\n'
        '\t\t\t$(".login_widget").hide();\n'
        '\t\t\treturn false;\n'
        '\t\t});',
        '\t\t$(".church-login-close, .btn_ly_popup").click(function () {\n'
        '\t\t\t$(".login_widget").hide();\n'
        '\t\t\treturn false;\n'
        '\t\t});\n'
        '\t\t$(".login_widget .ly_dimmed").click(function () {\n'
        '\t\t\t$(".login_widget").hide();\n'
        '\t\t\treturn false;\n'
        '\t\t});',
        1,
    )

LAYOUT.write_text(text, encoding='utf-8')
print('layout login close button patched')

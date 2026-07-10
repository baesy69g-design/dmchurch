#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""dmcadmin.model.php 한글 문자열 전면 복구."""
from __future__ import annotations

import re
from pathlib import Path

MODEL = Path(__file__).resolve().parents[1] / "modules" / "dmcadmin" / "dmcadmin.model.php"

# 전체 블록 교체 (순서 무관)
BLOCKS: list[tuple[str, str]] = [
    (
        """\tpublic const MAIN_TILES = [
\t\t'worship_time' => ['label' => '????', 'target' => 'page', 'id' => '78'],
\t\t'event_photo' => ['label' => '??????', 'target' => 'mid', 'id' => 'picture'],
\t\t'rice_share' => ['label' => '??? ????', 'target' => 'page', 'id' => '92'],
\t\t'church_school' => ['label' => '????', 'target' => 'page', 'id' => '109'],
\t\t'pastoral_schedule' => ['label' => '????', 'target' => 'page', 'id' => '84'],
\t\t'weekly_bulletin' => ['label' => '??? ??', 'target' => 'mid', 'id' => 'jubo'],
\t\t'new_family' => ['label' => '?????', 'target' => 'mid', 'id' => 'newface'],
\t\t'scholarship' => ['label' => '?????', 'target' => 'page', 'id' => '146'],
\t];""",
        """\tpublic const MAIN_TILES = [
\t\t'worship_time' => ['label' => '예배시간', 'target' => 'page', 'id' => '78'],
\t\t'event_photo' => ['label' => '교회행사사진', 'target' => 'mid', 'id' => 'picture'],
\t\t'rice_share' => ['label' => '사랑의 쌀나누기', 'target' => 'page', 'id' => '92'],
\t\t'church_school' => ['label' => '교회학교', 'target' => 'page', 'id' => '109'],
\t\t'pastoral_schedule' => ['label' => '목회일정', 'target' => 'page', 'id' => '84'],
\t\t'weekly_bulletin' => ['label' => '금주의 주보', 'target' => 'mid', 'id' => 'jubo'],
\t\t'new_family' => ['label' => '새가족소개', 'target' => 'mid', 'id' => 'newface'],
\t\t'scholarship' => ['label' => '장학위원회', 'target' => 'page', 'id' => '146'],
\t];""",
    ),
    (
        """\tpublic const MAIN_QUICK_LINKS = [
\t\t['label' => '?? ?? ?? ???', 'target' => 'mid', 'id' => 'sermon'],
\t\t['label' => '?? ?? ???', 'target' => 'mid', 'id' => 'choir'],
\t\t['label' => '??? ???', 'target' => 'mid', 'id' => 'peniel'],
\t\t['label' => '???? ???', 'target' => 'mid', 'id' => 'eventvideo'],
\t];""",
        """\tpublic const MAIN_QUICK_LINKS = [
\t\t['label' => '주일 예배 설교 동영상', 'target' => 'mid', 'id' => 'sermon'],
\t\t['label' => '샤론 시온 성가대', 'target' => 'mid', 'id' => 'choir'],
\t\t['label' => '브니엘 찬양팀', 'target' => 'mid', 'id' => 'peniel'],
\t\t['label' => '주요행사 동영상', 'target' => 'mid', 'id' => 'eventvideo'],
\t];""",
    ),
    (
        """\tpublic const SUB_TOP_MENUS = [
\t\t'info' => ['label' => '????', 'legacy' => 'top.15014945664036.jpg'],
\t\t'news' => ['label' => '????', 'legacy' => 'top.15014945609550.jpg'],
\t\t'mission' => ['label' => '??? ??', 'legacy' => 'top.15014945691236.jpg'],
\t\t'school' => ['label' => '????', 'legacy' => 'top.15022460688328.jpg'],
\t\t'broadcast' => ['label' => '????', 'legacy' => 'top.15022460663461.jpg'],
\t\t'community' => ['label' => '????', 'legacy' => 'top.15014945708645.jpg'],
\t];""",
        """\tpublic const SUB_TOP_MENUS = [
\t\t'info' => ['label' => '교회안내', 'legacy' => 'top.15014945664036.jpg'],
\t\t'news' => ['label' => '교회소식', 'legacy' => 'top.15014945609550.jpg'],
\t\t'mission' => ['label' => '선교와 봉사', 'legacy' => 'top.15014945691236.jpg'],
\t\t'school' => ['label' => '교회학교', 'legacy' => 'top.15022460688328.jpg'],
\t\t'broadcast' => ['label' => '교회방송', 'legacy' => 'top.15022460663461.jpg'],
\t\t'community' => ['label' => '커뮤니티', 'legacy' => 'top.15014945708645.jpg'],
\t];""",
    ),
    (
        """\tpublic const GUIDE_PAGE_MIDS = [
\t\t'p8' => '???? ??',
\t];""",
        """\tpublic const GUIDE_PAGE_MIDS = [
\t\t'p8' => '담임목사 인사',
\t];""",
    ),
    (
        """\tpublic const HISTORY_PAGE_MIDS = [
\t\t'p9' => '?? ??',
\t];""",
        """\tpublic const HISTORY_PAGE_MIDS = [
\t\t'p9' => '교회 연혁',
\t];""",
    ),
    (
        """\tpublic const PEOPLE_PAGE_MIDS = [
\t\t'p79' => '??? ?',
\t];""",
        """\tpublic const PEOPLE_PAGE_MIDS = [
\t\t'p79' => '섬기는 분',
\t];""",
    ),
    (
        """\tpublic const PEOPLE_CATEGORIES = ['???', '????', '????'];""",
        """\tpublic const PEOPLE_CATEGORIES = ['교역자', '은퇴장로', '시무장로'];""",
    ),
    (
        """\tpublic const PEOPLE_DISPLAY_VIEWS = [
\t\t'p79' => ['???'],
\t\t'p154' => ['???'],
\t\t'p155' => ['????', '????'],
\t];""",
        """\tpublic const PEOPLE_DISPLAY_VIEWS = [
\t\t'p79' => ['교역자'],
\t\t'p154' => ['교역자'],
\t\t'p155' => ['은퇴장로', '시무장로'],
\t];""",
    ),
    (
        """\tpublic const WORSHIP_PAGE_MIDS = [
\t\t'p78' => '????',
\t];""",
        """\tpublic const WORSHIP_PAGE_MIDS = [
\t\t'p78' => '예배시간',
\t];""",
    ),
    (
        """\tpublic const WORSHIP_CATEGORIES = ['??', '???', '????', '??'];""",
        """\tpublic const WORSHIP_CATEGORIES = ['예배', '기도회', '주일학교', '모임'];""",
    ),
    (
        """\tpublic const NEWFAMILY_PAGE_MIDS = [
\t\t'p108' => '??? ??',
\t];""",
        """\tpublic const NEWFAMILY_PAGE_MIDS = [
\t\t'p108' => '새가족 안내',
\t];""",
    ),
    (
        """\tpublic const TOUR_PAGE_MIDS = [
\t\t'p147' => '??????',
\t];""",
        """\tpublic const TOUR_PAGE_MIDS = [
\t\t'p147' => '교회둘러보기',
\t];""",
    ),
    (
        """\tpublic const SCHOOL_PAGE_MIDS = [
\t\t'p109' => '???',
\t\t'p112' => '???',
\t\t'p115' => '????',
\t\t'p118' => '???',
\t];""",
        """\tpublic const SCHOOL_PAGE_MIDS = [
\t\t'p109' => '유치부',
\t\t'p112' => '아동부',
\t\t'p115' => '청소년부',
\t\t'p118' => '청년부',
\t];""",
    ),
]

# 단일 라인/패턴 교체
REPLACEMENTS: list[tuple[str, str]] = [
    ("return self::SCHOOL_PAGE_MIDS[$mid] . ' ??';", "return self::SCHOOL_PAGE_MIDS[$mid] . ' 소개';"),
    ("$layout_info->logo_text = '????';", "$layout_info->logo_text = '동명교회';"),
    ('img.alt="????"', 'img.alt="동명교회"'),
    ("$o->kind = '???? ???';", "$o->kind = '담임목사 인사형';"),
    ("$o->kind = '????? (??? ?)';", "$o->kind = '예배시간형 (구분별 표)';"),
    ("$o->kind = '???? (?? 2? + ????)';", "$o->kind = '새가족형 (사진 2장 + 고정안내)';"),
    ("$o->kind = '???? (?? ?? 7?)';", "$o->kind = '갤러리형 (사진 최대 7장)';"),
    ("<th class=\"cw-name\">??</th><th class=\"cw-time\">????</th><th class=\"cw-place\">??</th>",
     "<th class=\"cw-name\">집회명</th><th class=\"cw-time\">집회시간</th><th class=\"cw-place\">장소</th>"),
    ("<span class=\"cs-theme-label\">????</span>", "<span class=\"cs-theme-label\">교육주제</span>"),
    ("['goal', '???? ? ??',", "['goal', '교육목표 및 방향',"),
    ("['staff', '?? ??? ? ??',", "['staff', '담당 교역자 및 교사',"),
    ("['worship', '?? ? ??',", "['worship', '예배 안내',"),
    ("['worship', '?? ??',", "['worship', '예배 안내',"),
    ("<span class=\"church-main-pastor-title\">???? ?? ? ????</span>",
     "<span class=\"church-main-pastor-title\">담임목사 인사 · 교회소개</span>"),
    ("<span class=\"church-main-pastor-sub\">???????? ???????.</span>",
     "<span class=\"church-main-pastor-sub\">동명교회를 소개합니다.</span>"),
    ('alt="??? ?? ?? ', 'alt="새가족 안내 사진 '),
    ('alt="??? ???? ?????"', 'alt="새가족 등록 안내 이미지"'),
    ('alt="?????? ?? ', 'alt="교회둘러보기 사진 '),
    ('aria-label="?? ??"', 'aria-label="이전 사진"'),
    ('aria-label="?? ??"', 'aria-label="다음 사진"'),
    ("return new BaseObject(-1, '??? ? ?? ??????.');", "return new BaseObject(-1, '편집할 수 없는 페이지입니다.');"),
    ("return new BaseObject(-1, '???? ?? ? ????.');", "return new BaseObject(-1, '페이지를 찾을 수 없습니다.');"),
    ("return new BaseObject(-1, '??? ??? ?? ? ????.');", "return new BaseObject(-1, '페이지 모듈을 찾을 수 없습니다.');"),
    ("return new BaseObject(-1, '?? ??? ??? ??? ??????.');", "return new BaseObject(-1, '안내 페이지 데이터 저장에 실패했습니다.');"),
    ("return new BaseObject(-1, '??? ? ??? ??? ??????.');", "return new BaseObject(-1, '섬기는 분 데이터 저장에 실패했습니다.');"),
    ("return new BaseObject(-1, '???? ??? ??? ??????.');", "return new BaseObject(-1, '예배시간 데이터 저장에 실패했습니다.');"),
    ("return new BaseObject(-1, '??? ?? ??? ??? ??????.');", "return new BaseObject(-1, '새가족 안내 저장에 실패했습니다.');"),
    ("return new BaseObject(-1, '?????? ??? ??? ??????.');", "return new BaseObject(-1, '교회둘러보기 저장에 실패했습니다.');"),
    ("return new BaseObject(-1, '???? ??? ??? ??????.');", "return new BaseObject(-1, '교회학교 데이터 저장에 실패했습니다.');"),
    ("return new BaseObject(-1, '???? ?? ID? ?? ' . self::MAX_PRAYER_READERS . '??? ??? ? ????.');",
     "return new BaseObject(-1, '기도자 ID는 최대 ' . self::MAX_PRAYER_READERS . '명까지 등록할 수 있습니다.');"),
    ("return '<div class=\"church-main-slide church-main-slide--empty\"><span>?? ?? ??</span></div>';",
     "return '<div class=\"church-main-slide church-main-slide--empty\"><span>슬라이드 없음</span></div>';"),
    ("throw new Rhymix\\Framework\\Exception('??? GD ??? ?????? ?????.');",
     "throw new Rhymix\\Framework\\Exception('서버 GD 확장이 설치되어 있지 않습니다.');"),
    ("throw new Rhymix\\Framework\\Exception('????? ?? 2? ??? ?????.');",
     "throw new Rhymix\\Framework\\Exception('서브 TOP은 최소 2장 이상 필요합니다.');"),
    ("throw new Rhymix\\Framework\\Exception('?? ??? ?? ' . self::SUB_TOP_STITCH_MAX . '??? ?????.');",
     "throw new Rhymix\\Framework\\Exception('한 번에 최대 ' . self::SUB_TOP_STITCH_MAX . '장까지 이어 붙일 수 있습니다.');"),
    ("throw new Rhymix\\Framework\\Exception('???? ?? ? ????: ' . basename($src));",
     "throw new Rhymix\\Framework\\Exception('이미지 읽기에 실패: ' . basename($src));"),
    ("throw new Rhymix\\Framework\\Exception('?? ??? ??? ??????.');",
     "throw new Rhymix\\Framework\\Exception('합성 이미지 저장에 실패했습니다.');"),
    ("throw new Rhymix\\Framework\\Exception('??? ??? ???? ? ????.');",
     "throw new Rhymix\\Framework\\Exception('업로드 파일을 읽을 수 없습니다.');"),
    ("throw new Rhymix\\Framework\\Exception('???? ?? ? ????.');",
     "throw new Rhymix\\Framework\\Exception('이미지 처리에 실패했습니다.');"),
    ("throw new Rhymix\\Framework\\Exception('?? ??? ??????.');",
     "throw new Rhymix\\Framework\\Exception('파일 저장에 실패했습니다.');"),
]

# regex 교체 (깨진 중간점 등)
REGEX_REPLACEMENTS: list[tuple[str, str]] = [
    (r"\$o->kind = '\?\?\? \([^']+\)';", "$o->kind = '연혁형 (연대·사진·내용)';"),
    (r"\$o->kind = '\?\?\? \([^']+\)';", "$o->kind = '인물형 (교역자·장로 카드)';"),
    (r'<span class="church-main-pastor-title">[^<]+</span>',
     '<span class="church-main-pastor-title">담임목사 인사 · 교회소개</span>'),
]


def fix_get_info_page_list(text: str) -> str:
    """getInfoPageList 내 kind/label 수동 복구."""
    lines = text.split("\n")
    in_fn = False
    for i, line in enumerate(lines):
        if "function getInfoPageList" in line:
            in_fn = True
            continue
        if in_fn and line.strip() == "}" and "return $out" in "\n".join(lines[max(0, i - 3) : i]):
            break
        if not in_fn:
            continue
        if "$o->kind = " in line and "?" in line:
            if "HISTORY_PAGE_MIDS" in "\n".join(lines[max(0, i - 8) : i]):
                lines[i] = re.sub(r"\$o->kind = '[^']*';", "$o->kind = '연혁형 (연대·사진·내용)';", line)
            elif "PEOPLE_PAGE_MIDS" in "\n".join(lines[max(0, i - 8) : i]):
                lines[i] = re.sub(r"\$o->kind = '[^']*';", "$o->kind = '인물형 (교역자·장로 카드)';", line)
        if "$o->label = '????'" in line or "$o->label = '????';" in line:
            nxt = lines[i + 1] if i + 1 < len(lines) else ""
            if "DOMESTIC_MISSION" in "\n".join(lines[i : i + 5]):
                lines[i] = "\t\t$o->label = '국내선교';"
                if "$o->kind" in nxt:
                    lines[i + 1] = "\t\t$o->kind = '국내선교형 (구분·이름 목록 + 상세 sub)';"
            elif "school_first" in "\n".join(lines[max(0, i - 5) : i + 1]):
                lines[i] = "\t\t$o->label = '교회학교';"
                if "$o->kind" in nxt:
                    lines[i + 1] = "\t\t$o->kind = '교회학교형 (부서 선택·소개·사진 4장)';"
    return "\n".join(lines)


def main() -> None:
    raw = MODEL.read_bytes()
    text = raw.decode("utf-8", errors="replace")
    text = text.replace("\r\n", "\n").replace("\r", "\n")

    for old, new in BLOCKS:
        if old in text:
            text = text.replace(old, new)
        else:
            # try with replacement chars for middle dots
            old2 = old.replace("·", "\ufffd")
            if old2 in text:
                text = text.replace(old2, new)

    for old, new in REPLACEMENTS:
        text = text.replace(old, new)

    for pat, repl in REGEX_REPLACEMENTS:
        text = re.sub(pat, repl, text, count=1)

    text = fix_get_info_page_list(text)

    # 잘못된 단독 바이트 정리 (latin-1 middle dot)
    text = text.replace("\ufffd", "·")  # only if was replacement char for broken utf8

    MODEL.write_text(text, encoding="utf-8", newline="\n")

    verify = MODEL.read_text(encoding="utf-8")
    checks = ["담임목사 인사", "교회 연혁", "예배시간", "국내선교", "getInfoPageList"]
    print("restore done:", MODEL)
    for c in checks:
        print(f"  {c}: {'OK' if c in verify else 'MISSING'}")
    print("  ???? count:", verify.count("????"))


if __name__ == "__main__":
    main()

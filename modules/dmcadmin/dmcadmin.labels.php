<?php
/**
 * dmcadmin 화면·페이지 한글 라벨 (UTF-8 전용).
 * PowerShell Add-Content / 비UTF-8 병합 금지. 수정 후 scripts/verify_dmcadmin_encoding.py 실행.
 */
return [
	'main_tiles' => [
		'event_photo' => '행사사진',
		'worship_time' => '예배시간',
		'rice_share' => '사랑의 쌀나누기',
		'church_school' => '교회학교',
		'dongkeyday' => '동키데이',
		'weekly_bulletin' => '금주의 주보',
		'new_family' => '새가족소개',
		'scholarship' => '장학사업',
	],
	'main_tile_styles' => [
		'worship_time' => ['mode' => 'none'],
		'weekly_bulletin' => ['mode' => 'none'],
		'event_photo' => ['mode' => 'title_only', 'title_color' => '#ffffff'],
		'rice_share' => ['mode' => 'title_only', 'title_color' => '#ffffff'],
		'church_school' => ['mode' => 'title_only', 'title_color' => '#ffffff'],
		'dongkeyday' => ['mode' => 'title_only', 'title_color' => '#ffffff'],
		'new_family' => ['mode' => 'none'],
		'scholarship' => ['mode' => 'none'],
	],
	'main_quick_links' => [
		['label' => '주일 예배 설교 동영상', 'id' => 'sermon'],
		['label' => '샤론 시온 성가대', 'id' => 'choir'],
		['label' => '브니엘 찬양팀', 'id' => 'peniel'],
		['label' => '주요행사 동영상', 'id' => 'eventvideo'],
	],
	'sub_top_menus' => [
		'info' => '교회안내',
		'news' => '교회소식',
		'mission' => '선교와 봉사',
		'school' => '교회학교',
		'broadcast' => '교회방송',
		'community' => '커뮤니티',
	],
	'guide_page_mids' => [
		'p8' => '담임목사 인사',
	],
	'history_page_mids' => [
		'p9' => '교회 연혁',
	],
	'people_page_mids' => [
		'p79' => '섬기는 분',
	],
	'people_categories' => ['교역자', '은퇴장로', '시무장로'],
	'worship_page_mids' => [
		'p78' => '예배시간',
	],
	'worship_categories' => ['예배', '기도회', '주일학교', '모임'],
	'newfamily_page_mids' => [
		'p108' => '새가족 안내',
	],
	'tour_page_mids' => [
		'p147' => '교회둘러보기',
		'p92' => '사랑의 쌀나누기',
		'p146' => '장학사업',
	],
	'school_page_mids' => [
		'p109' => '유치부',
		'p112' => '아동부',
		'p115' => '청소년부',
		'p118' => '청년부',
	],
	'dongkeyday_page' => [
		'mid' => 'p93',
		'label' => '동키데이',
		'apply_label' => '동키데이 참가 신청',
		'apply_note' => '구글 폼으로 이동합니다.',
		'photo_alt_suffix' => ' 사진 ',
		'star_primary' => '✦',
		'star_secondary' => '❋',
	],
	'domestic_mission' => [
		'page_title' => '국내선교',
		'categories' => [
			'church' => '우리가 돕는 교회',
			'org' => '우리가 돕는 기관',
		],
		'list_label_school' => '교회학교',
	],
	'overseas_mission' => [
		'page_title' => '해외선교',
		'categories' => [
			'dispatch' => '우리교회 파송선교사',
			'support' => '우리교회가 돕는 선교지',
		],
	],
	'info_page_kinds' => [
		'guide' => '담임목사 인사형',
		'history' => '연혁형 (연대·사진·내용)',
		'people' => '인물형 (교역자·장로 카드)',
		'worship' => '예배시간형 (구분별 표)',
		'newfamily' => '새가족형 (사진 2장 + 고정안내)',
		'tour' => '갤러리형 (사진 최대 7장 + 공통 설명)',
		'school' => '교회학교형 (부서 선택·소개·사진 4장)',
		'dongkeyday' => '동키데이형 (사진 9장·중앙 소개·신청폼)',
		'domestic' => '국내선교형 (구분·이름 목록 + 상세 sub)',
		'overseas' => '해외선교형 (구분·선교사·목록 + 상세 sub)',
	],
	'misc' => [
		'church_name' => '동명교회',
		'school_intro_suffix' => ' 소개',
		'domestic_more' => '자세히 보기',
	],
];

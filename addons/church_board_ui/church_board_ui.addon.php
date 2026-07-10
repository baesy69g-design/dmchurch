<?php
/**
 * @file church_board_ui.addon.php
 * @brief 게시판별 간편등록 팝업 UI / 기도요청(pray) 열람 제어
 */
if (!defined('__XE__'))
{
	exit();
}

getModel('church_member');
$act = (string)(Context::get('act') ?: ($_GET['act'] ?? ''));
if (isset($_GET['mid']) && $_GET['mid'] !== '' && in_array($act, ['dispChurchMemberProfile', 'procChurchSaveProfile'], true))
{
	$url = church_memberModel::getMemberProfileUrl();
	$msg = (string)Context::get('msg');
	if ($msg === '' && isset($_GET['msg']))
	{
		$msg = (string)$_GET['msg'];
	}
	if ($msg !== '')
	{
		$url .= (strpos($url, '?') !== false ? '&' : '?') . 'msg=' . rawurlencode($msg);
	}
	header('Location: ' . $url);
	Context::close();
	exit;
}
if ($act === 'dispMemberLoginForm' && !Context::get('is_logged'))
{
	$return_url = (string)Context::get('success_return_url');
	header('Location: ' . church_memberModel::getLayoutLoginUrl($return_url));
	Context::close();
	exit;
}

getModel('church_write');

$mid = Context::get('mid');
$mid_map = [
	'sermon' => 110, 'community' => 112, 'jubo' => 114, 'choir' => 116,
	'peniel' => 118, 'eventvideo' => 120, 'picture' => 122, 'newface' => 124,
	'pray' => 126,
];

if (!isset($mid_map[$mid]))
{
	return;
}

$module_srl = (int)Context::get('module_srl');
if (!$module_srl)
{
	$module_srl = $mid_map[$mid];
}

$is_pray = church_writeModel::isPrayBoard($module_srl, $mid);

if ($is_pray && $called_position === 'before_module_proc' && Context::getResponseMethod() === 'HTML')
{
	if (!Context::get('is_logged'))
	{
		$return_url = Context::getRequestUri();
		header('Location: ' . church_memberModel::getLayoutLoginUrl($return_url));
		Context::close();
		exit;
	}
	return;
}

if ($called_position !== 'after_module_proc' || Context::getResponseMethod() !== 'HTML')
{
	return;
}

if ($is_pray)
{
	church_writeModel::applyPrayBoardView();
}

// 갤러리형 영상 게시판(설교/성가대/브니엘찬양팀/교회행사): 본문 유튜브 썸네일/말씀(메모) 맵 구성
$gallery_mids = ['sermon', 'choir', 'peniel', 'eventvideo'];
if (in_array($mid, $gallery_mids, true))
{
	$sermon_srls = [];
	foreach (['notice_list', 'document_list'] as $list_key)
	{
		$list = Context::get($list_key);
		if (is_array($list))
		{
			foreach ($list as $doc)
			{
				if (is_object($doc) && !empty($doc->document_srl))
				{
					$sermon_srls[] = (int)$doc->document_srl;
				}
			}
		}
	}

	$sermon_thumbs = [];
	$sermon_scripts = [];
	if ($sermon_srls)
	{
		$query_args = new stdClass();
		$query_args->document_srls = $sermon_srls;
		$query_args->list_count = count($sermon_srls);
		$query_args->order_type = 'asc';
		$query_args->page = 0;
		$query_output = executeQueryArray('document.getDocuments', $query_args, ['document_srl', 'content']);
		if ($query_output->toBool() && is_array($query_output->data))
		{
			foreach ($query_output->data as $row)
			{
				$content = (string)($row->content ?? '');
				$yid = '';
				if ($content !== '')
				{
					if (preg_match('@youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})@', $content, $m))
					{
						$yid = $m[1];
					}
					elseif (preg_match('@youtu\.be/([A-Za-z0-9_-]{6,})@', $content, $m))
					{
						$yid = $m[1];
					}
					elseif (preg_match('@youtube(?:-nocookie)?\.com/watch\?[^"\']*v=([A-Za-z0-9_-]{6,})@', $content, $m))
					{
						$yid = $m[1];
					}
				}
				$sermon_thumbs[(int)$row->document_srl] = $yid;

				// 팝업에 함께 표시할 설교 말씀(영상 영역 제거)
				$script = $content;
				$script = preg_replace('@<div[^>]*class="[^"]*sermon-video[^"]*"[^>]*>.*?</div>@is', '', $script);
				$script = preg_replace('@<iframe\b.*?</iframe>@is', '', $script);
				$script = preg_replace('@<(script|style)\b.*?</\1>@is', '', $script);
				$sermon_scripts[(int)$row->document_srl] = trim($script);
			}
		}
	}
	Context::set('sermon_thumbs', $sermon_thumbs);
	Context::set('sermon_scripts', $sermon_scripts);

	// 설교일(확장변수 sermon_date, var_idx=1) 조회 → 목록 날짜로 사용 (설교 게시판만 별도 설교일 사용)
	$sermon_dates = [];
	if ($mid === 'sermon' && $sermon_srls)
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$placeholders = implode(',', array_fill(0, count($sermon_srls), '?'));
		$params = array_merge([$module_srl, 1], array_map('intval', $sermon_srls));
		$stmt = $oDB->query('SELECT document_srl, value FROM document_extra_vars WHERE module_srl = ? AND var_idx = ? AND document_srl IN (' . $placeholders . ')', ...$params);
		if ($stmt)
		{
			foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $row)
			{
				$val = preg_replace('/[^0-9]/', '', (string)$row->value);
				if (strlen($val) >= 8)
				{
					$sermon_dates[(int)$row->document_srl] = substr($val, 0, 8);
				}
			}
		}
	}
	Context::set('sermon_dates', $sermon_dates);
}

// 행사사진(picture)/새가족소개(newface)/주보(jubo): 게시물 내부 첨부 이미지를 썸네일로 표시
// - picture/newface: 첨부 사진을 순환 슬라이드(최대 6장)
// - jubo: '교회소식'(첫 번째) 이미지 1장으로 고정
if (in_array($mid, ['picture', 'newface', 'jubo'], true))
{
	$pic_srls = [];
	foreach (['notice_list', 'document_list'] as $list_key)
	{
		$list = Context::get($list_key);
		if (is_array($list))
		{
			foreach ($list as $doc)
			{
				if (is_object($doc) && !empty($doc->document_srl))
				{
					$pic_srls[] = (int)$doc->document_srl;
				}
			}
		}
	}

	$max_imgs = ($mid === 'jubo') ? 1 : 6;
	$picture_slides = [];
	if ($pic_srls)
	{
		$q = new stdClass();
		$q->document_srls = $pic_srls;
		$q->list_count = count($pic_srls);
		$q->order_type = 'asc';
		$q->page = 0;
		$out = executeQueryArray('document.getDocuments', $q, ['document_srl', 'content']);
		if ($out->toBool() && is_array($out->data))
		{
			foreach ($out->data as $row)
			{
				$content = (string)($row->content ?? '');
				$imgs = '';
				$count = 0;
				if ($content !== '' && preg_match_all('@<img[^>]+src="([^"]+)"@i', $content, $ms))
				{
					foreach ($ms[1] as $src)
					{
						if ($count >= $max_imgs)
						{
							break;
						}
						$cls = $count === 0 ? 'pg-img is-active' : 'pg-img';
						$imgs .= '<img class="' . $cls . '" src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" loading="lazy" alt="" onerror="this.classList.add(\'pg-broken\')">';
						$count++;
					}
				}
				$picture_slides[(int)$row->document_srl] = $imgs;
			}
		}
	}
	Context::set('picture_slides', $picture_slides);
}

$logged_info = Context::get('logged_info');
Context::set('church_board_admin', church_writeModel::isChurchAdmin($logged_info) ? 'Y' : 'N');
$config = church_writeModel::getClientConfig($module_srl, $logged_info, $mid);
if (!$config)
{
	return;
}

Context::loadFile('./addons/church_board_ui/church_board_ui.css');
Context::loadFile(['./addons/church_board_ui/church_board_ui.js', 'body', '', null], true);

$json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
Context::addHtmlFooter('<script>window.CHURCH_BOARD_UI=' . $json . ';</script>');

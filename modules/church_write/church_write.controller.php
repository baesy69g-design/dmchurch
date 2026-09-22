<?php
/**
 * @class  church_writeController
 */
class church_writeController extends church_write
{
	function procChurchWriteInsertDocument()
	{
		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}

		$logged_info = Context::get('logged_info');
		$module_srl = (int)Context::get('module_srl');
		$forms = church_writeModel::getBoardForms();

		if (!isset($forms[$module_srl]))
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		$is_public = in_array($module_srl, church_writeModel::publicWriteModuleSrls(), true);
		$mid = (string)($forms[$module_srl]['mid'] ?? Context::get('mid') ?? '');
		if (!$is_public && !church_writeModel::isChurchAdmin($logged_info) && !church_writeModel::canWriteMissionBoard($logged_info, $module_srl, $mid))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		if (!$module_info)
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		$args = Context::getRequestVars();
		$form = $forms[$module_srl];
		$this->validateRequiredFields($form['fields'], $args, $module_srl);

		$obj = new stdClass;
		$obj->module_srl = $module_srl;
		$obj->title = trim($args->title ?? '');
		$obj->content = '<p></p>';
		$obj->commentStatus = 'ALLOW';
		$obj->status = 'PUBLIC';

		// 수동 등록(manual_inserted=true)은 작성자 정보를 자동으로 채우지 않으므로 직접 설정
		$obj->member_srl = (int)($logged_info->member_srl ?? 0);
		$obj->user_id = $logged_info->user_id ?? '';
		$obj->user_name = $logged_info->user_name ?? '';
		$obj->nick_name = $logged_info->nick_name ?? ($logged_info->user_name ?? '');
		$obj->email_address = $logged_info->email_address ?? '';
		$obj->homepage = $logged_info->homepage ?? '';

		if (!empty($args->pubdate))
		{
			$obj->regdate = church_writeModel::regdateFromDate($args->pubdate);
		}
		elseif (!empty($args->reg_date))
		{
			$obj->regdate = church_writeModel::regdateFromDate($args->reg_date);
		}

		if (!empty($args->subtitle))
		{
			$sub = trim($args->subtitle);
			if ($sub)
			{
				$obj->title = $obj->title . ' / ' . $sub;
			}
		}

		$oDocumentController = DocumentController::getInstance();
		$output = $oDocumentController->insertDocument($obj, true);
		if (!$output->toBool())
		{
			return $output;
		}

		$document_srl = (int)$output->get('document_srl');
		$file_urls = $this->processUploads($module_srl, $document_srl, $args);
		$content = $this->buildContent($module_srl, $document_srl, $args, $file_urls);

		// 신규 글을 목록 맨 앞으로 보내기 위한 list_order 계산.
		// (마이그레이션 글은 list_order가 -(원래 srl)로 매우 작아, 작은 srl을 받는 신규 글이 맨 뒤로 밀린다)
		$oDB = Rhymix\Framework\DB::getInstance();
		$min_row = $oDB->query('SELECT MIN(list_order) AS minord FROM documents WHERE module_srl = ?', $module_srl)->fetch(\PDO::FETCH_OBJ);
		$new_order = ((int)($min_row->minord ?? 0)) - 1;

		// updateDocument()의 첫 인자는 원본 문서 객체여야 한다 (정수를 넘기면 내용이 저장되지 않음)
		$oDocument = DocumentModel::getDocument($document_srl);
		$upd = new stdClass;
		$upd->document_srl = $document_srl;
		$upd->content = $content;
		$upd->title = $obj->title;
		if (!empty($obj->regdate))
		{
			$upd->regdate = $obj->regdate;
			$upd->last_update = $obj->regdate;
		}
		$oDocumentController->updateDocument($oDocument, $upd, true);

		// list_order 보정 (목록 맨 앞 노출) + 캐시 무효화
		$oDB->query('UPDATE documents SET list_order = ?, update_order = ? WHERE document_srl = ?', $new_order, $new_order, $document_srl);
		DocumentController::clearDocumentCache($document_srl);

		// 설교(110): 설교일을 확장변수(sermon_date)에도 저장 → 목록 날짜로 사용
		if ($module_srl === 110 && !empty($args->pubdate))
		{
			$sermon_date = preg_replace('/\D/', '', $args->pubdate);
			if (strlen($sermon_date) >= 8)
			{
				DocumentController::insertDocumentExtraVar($module_srl, $document_srl, 1, substr($sermon_date, 0, 8), 'sermon_date');
			}
		}

		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', $module_info->mid, 'document_srl', $document_srl));
		if (church_writeModel::isMissionBoard($module_srl, (string)($module_info->mid ?? '')))
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', 'p27'));
		}
	}

	/**
	 * 관리자: 기존 글 수정 (등록 폼과 동일 필드)
	 */
	function procChurchWriteUpdateDocument()
	{
		Context::setRequestMethod('JSON');

		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}

		$logged_info = Context::get('logged_info');
		$document_srl = (int)Context::get('target_srl');
		if ($document_srl < 1)
		{
			$document_srl = (int)Context::get('document_srl');
		}
		$module_srl = (int)Context::get('module_srl');
		$forms = church_writeModel::getBoardForms();

		if ($document_srl < 1 || !isset($forms[$module_srl]))
		{
			return new BaseObject(-1, '수정할 글을 찾을 수 없습니다.');
		}

		$mid = (string)($forms[$module_srl]['mid'] ?? '');
		if (!church_writeModel::isChurchAdmin($logged_info) && !church_writeModel::canWriteMissionBoard($logged_info, $module_srl, $mid))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$oDocument = DocumentModel::getDocument($document_srl);
		if (!$oDocument || !$oDocument->isExists() || (int)$oDocument->get('module_srl') !== $module_srl)
		{
			return new BaseObject(-1, '수정할 글을 찾을 수 없습니다.');
		}

		$args = Context::getRequestVars();
		$form = $forms[$module_srl];
		$this->validateRequiredFields($form['fields'], $args, $module_srl, true);

		$title = trim((string)($args->title ?? ''));
		if ($title === '')
		{
			return new BaseObject(-1, '제목을 입력해 주세요.');
		}
		if (!empty($args->subtitle))
		{
			$sub = trim((string)$args->subtitle);
			if ($sub !== '')
			{
				$title = $title . ' / ' . $sub;
			}
		}

		$file_urls = $this->processUploads($module_srl, $document_srl, $args);

		$board_mid = strtolower(trim((string)(ModuleModel::getModuleInfoByModuleSrl($module_srl)->mid ?? '')));
		if ($module_srl === 114)
		{
			$existing = church_writeModel::extractJuboImageUrls((string)$oDocument->getContent(false));
			$file_urls['jubo'] = array_merge($existing, $file_urls['jubo'] ?? []);
			if (empty($file_urls['jubo']))
			{
				return new BaseObject(-1, '주보 이미지가 없습니다. 이미지를 등록해 주세요.');
			}
		}
		if ($board_mid === 'dispatch_letter')
		{
			$existing = church_writeModel::extractDispatchLetterImageUrls((string)$oDocument->getContent(false));
			$file_urls['letter'] = array_merge($existing, $file_urls['letter'] ?? []);
			if (empty($file_urls['letter']['page1']) && empty($file_urls['letter']))
			{
				return new BaseObject(-1, '선교편지 이미지가 없습니다. 1페이지를 등록해 주세요.');
			}
		}
		if ($module_srl === 124 && empty($file_urls['photo']))
		{
			$prev = church_writeModel::extractEditFields($oDocument);
			if (!empty($prev['photo_url']))
			{
				$file_urls['photo'] = $prev['photo_url'];
			}
		}

		$content = $this->buildContent($module_srl, $document_srl, $args, $file_urls);

		$upd = new stdClass;
		$upd->document_srl = $document_srl;
		$upd->module_srl = $module_srl;
		$upd->title = $title;
		$upd->content = $content;
		$upd->status = $oDocument->get('status') ?: 'PUBLIC';
		$upd->commentStatus = 'ALLOW';
		$upd->lang_code = $oDocument->get('lang_code') ?: Context::getLangType();
		if (!empty($args->pubdate))
		{
			$upd->regdate = church_writeModel::regdateFromDate($args->pubdate);
			$upd->last_update = $upd->regdate;
		}
		elseif (!empty($args->reg_date))
		{
			$upd->regdate = church_writeModel::regdateFromDate($args->reg_date);
			$upd->last_update = $upd->regdate;
		}

		$oDocumentController = DocumentController::getInstance();
		$output = $oDocumentController->updateDocument($oDocument, $upd, true);
		if (!$output->toBool())
		{
			return $output;
		}

		// Rhymix 다국어/캐시 이슈 대비: 제목·본문을 DB에 한 번 더 확정
		$oDB = Rhymix\Framework\DB::getInstance();
		if (!empty($upd->regdate))
		{
			$oDB->query(
				'UPDATE documents SET title = ?, content = ?, regdate = ?, last_update = ? WHERE document_srl = ?',
				$title,
				$content,
				$upd->regdate,
				$upd->last_update ?? $upd->regdate,
				$document_srl
			);
		}
		else
		{
			$oDB->query(
				'UPDATE documents SET title = ?, content = ?, last_update = ? WHERE document_srl = ?',
				$title,
				$content,
				date('YmdHis'),
				$document_srl
			);
		}

		if ($module_srl === 110 && !empty($args->pubdate))
		{
			$sermon_date = preg_replace('/\D/', '', $args->pubdate);
			if (strlen($sermon_date) >= 8)
			{
				DocumentController::insertDocumentExtraVar($module_srl, $document_srl, 1, substr($sermon_date, 0, 8), 'sermon_date');
			}
		}

		DocumentController::clearDocumentCache($document_srl);

		$verify = DocumentModel::getDocument($document_srl, false, false);
		$saved_title = $verify && $verify->isExists() ? $verify->getTitleText() : '';
		if ($saved_title !== $title)
		{
			return new BaseObject(-1, '제목 저장에 실패했습니다. 다시 시도해 주세요.');
		}

		$this->add('document_srl', $document_srl);
		$this->add('saved', 1);
		$this->add('title', $saved_title);
		$this->setMessage('success_updated');
	}
	function procChurchWriteGetDocument()
	{
		Context::setRequestMethod('JSON');

		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}

		$logged_info = Context::get('logged_info');

		// document_srl 은 라우팅에 가로채일 수 있어 target_srl 우선
		$document_srl = (int)Context::get('target_srl');
		if ($document_srl < 1)
		{
			$document_srl = (int)Context::get('document_srl');
		}
		if ($document_srl < 1)
		{
			$document_srl = (int)Context::get('srl');
		}
		if ($document_srl < 1)
		{
			return new BaseObject(-1, '글을 찾을 수 없습니다.');
		}

		$oDocument = DocumentModel::getDocument($document_srl);
		if (!$oDocument || !$oDocument->isExists())
		{
			return new BaseObject(-1, '글을 찾을 수 없습니다.');
		}

		$module_srl = (int)$oDocument->get('module_srl');
		$forms = church_writeModel::getBoardForms();
		if (!isset($forms[$module_srl]))
		{
			return new BaseObject(-1, '이 게시판은 수정 폼이 없습니다.');
		}

		$mid = (string)($forms[$module_srl]['mid'] ?? '');
		if (!church_writeModel::isChurchAdmin($logged_info) && !church_writeModel::canWriteMissionBoard($logged_info, $module_srl, $mid))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$this->add('document_srl', $document_srl);
		$this->add('module_srl', $module_srl);
		$this->add('fields', church_writeModel::extractEditFields($oDocument));
	}

	/**
	 * 설교 영상 팝업이 열릴 때 해당 글의 조회수를 +1 한다.
	 * (팝업 방식이라 글 읽기 페이지를 거치지 않아 기본 조회수가 오르지 않으므로 별도 처리)
	 */
	function procChurchWriteHitDocument()
	{
		Context::setRequestMethod('JSON');
		$document_srl = (int)Context::get('srl');
		if (!$document_srl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$oDocument = DocumentModel::getDocument($document_srl);
		if (!$oDocument->isExists())
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		// 갤러리형 영상·파송선교 편지/영상 글만 허용
		$module_srl = (int)$oDocument->get('module_srl');
		$mid = '';
		$mi = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		if ($mi && !empty($mi->mid))
		{
			$mid = strtolower(trim((string)$mi->mid));
		}
		$allowed_srls = [110, 116, 118, 120];
		$allowed_mids = ['dispatch_letter', 'dispatch_video'];
		if (!in_array($module_srl, $allowed_srls, true) && !in_array($mid, $allowed_mids, true))
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$oDB = Rhymix\Framework\DB::getInstance();
		$oDB->query('UPDATE documents SET readed_count = readed_count + 1 WHERE document_srl = ?', $document_srl);
		DocumentController::clearDocumentCache($document_srl);

		$this->add('readed_count', (int)$oDocument->get('readed_count') + 1);
	}

	/**
	 * 관리자(dmc2241/사이트관리자)가 목록에서 선택한 게시물들을 일괄 삭제한다.
	 */
	function procChurchWriteDeleteDocuments()
	{
		Context::setRequestMethod('JSON');

		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}

		$logged_info = Context::get('logged_info');
		$srls_raw = (string)Context::get('srls');
		$srls = array_values(array_unique(array_filter(array_map('intval', explode(',', $srls_raw)))));
		if (!$srls)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		if (!church_writeModel::isChurchAdmin($logged_info))
		{
			foreach ($srls as $srl)
			{
				$doc = DocumentModel::getDocument($srl);
				$module_srl = $doc && $doc->isExists() ? (int)$doc->get('module_srl') : 0;
				if (!$module_srl || !church_writeModel::canWriteMissionBoard($logged_info, $module_srl))
				{
					throw new Rhymix\Framework\Exceptions\NotPermitted;
				}
			}
		}

		$oDocumentController = DocumentController::getInstance();
		$deleted = 0;
		foreach ($srls as $srl)
		{
			$output = $oDocumentController->deleteDocument($srl, true);
			if ($output->toBool())
			{
				$deleted++;
			}
		}

		$this->add('deleted', $deleted);
	}

	protected function validateRequiredFields(array $fields, $args, int $module_srl, bool $is_edit = false): void
	{
		foreach ($fields as $field)
		{
			$name = $field['name'];
			$type = $field['type'] ?? 'text';
			$required = !empty($field['required']);

			if ($module_srl === 120 && $name === 'youtube_url')
			{
				$required = empty(trim($args->video_url ?? ''));
			}
			if ($module_srl === 120 && $name === 'video_url')
			{
				continue;
			}

			// 수정 시 파일은 새로 올리지 않으면 기존 유지 → 필수 해제
			if ($is_edit && $type === 'file')
			{
				continue;
			}

			if (!$required)
			{
				continue;
			}

			if ($type === 'file')
			{
				if (!empty($field['multiple']))
				{
					if (empty($_FILES[$name]['name']) || !is_array($_FILES[$name]['name']) || !array_filter($_FILES[$name]['name']))
					{
						throw new Rhymix\Framework\Exception($field['label'] . '을(를) 등록해 주세요.');
					}
				}
				elseif (empty($_FILES[$name]['name']))
				{
					throw new Rhymix\Framework\Exception($field['label'] . '을(를) 등록해 주세요.');
				}
				continue;
			}

			if (!trim($args->{$name} ?? ''))
			{
				throw new Rhymix\Framework\Exception($field['label'] . '을(를) 입력해 주세요.');
			}
		}

		if ($module_srl === 120 && !trim($args->youtube_url ?? '') && !trim($args->video_url ?? ''))
		{
			throw new Rhymix\Framework\Exception('유튜브 URL 또는 MP4 URL 중 하나는 필수입니다.');
		}
	}

	protected function processUploads(int $module_srl, int $document_srl, $args): array
	{
		$result = [
			'images' => [],
			'jubo' => [],
			'letter' => [],
			'photo' => '',
		];

		$map = [
			'news_image' => 'news',
			'front_image' => 'front',
			'back_image' => 'back',
			'page1_image' => 'page1',
			'page2_image' => 'page2',
			'page3_image' => 'page3',
		];

		foreach ($map as $input => $kind)
		{
			if (empty($_FILES[$input]['name']))
			{
				continue;
			}
			$url = $this->uploadOne($module_srl, $document_srl, $_FILES[$input], $this->labelFilename($kind));
			if ($url)
			{
				if (strpos($kind, 'page') === 0)
				{
					$result['letter'][$kind] = $url;
				}
				else
				{
					$result['jubo'][$kind] = $url;
				}
			}
		}

		if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name']))
		{
			$cnt = count($_FILES['photos']['name']);
			for ($i = 0; $i < $cnt; $i++)
			{
				if (empty($_FILES['photos']['name'][$i]))
				{
					continue;
				}
				$file = [
					'name' => $_FILES['photos']['name'][$i],
					'type' => $_FILES['photos']['type'][$i],
					'tmp_name' => $_FILES['photos']['tmp_name'][$i],
					'error' => $_FILES['photos']['error'][$i],
					'size' => $_FILES['photos']['size'][$i],
				];
				$url = $this->uploadOne($module_srl, $document_srl, $file, $_FILES['photos']['name'][$i]);
				if ($url)
				{
					$result['images'][] = $url;
				}
			}
		}

		if (!empty($_FILES['photo']['name']))
		{
			$url = $this->uploadOne($module_srl, $document_srl, $_FILES['photo'], $_FILES['photo']['name']);
			if ($url)
			{
				$result['photo'] = $url;
			}
		}

		return $result;
	}

	protected function labelFilename(string $kind): string
	{
		$labels = [
			'news' => '교회소식.jpg',
			'front' => '앞면.jpg',
			'back' => '뒷면.jpg',
			'page1' => '편지1.jpg',
			'page2' => '편지2.jpg',
			'page3' => '편지3.jpg',
		];
		return $labels[$kind] ?? 'image.jpg';
	}

	protected function uploadOne(int $module_srl, int $document_srl, array $file, string $display_name): string
	{
		if (!empty($file['error']) || empty($file['tmp_name']))
		{
			return '';
		}

		// 주보(114)/행사사진(122)/새가족소개(124)/선교편지: 업로드 사진을 약 2MB(가로/세로 1600px)로 자동 리사이즈
		$info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		$mid = strtolower(trim((string)($info->mid ?? '')));
		if (in_array($module_srl, [114, 122, 124], true) || $mid === 'dispatch_letter')
		{
			$this->resizeUploadedImage($file);
		}

		$file['name'] = $display_name ?: $file['name'];
		$oFileController = FileController::getInstance();
		$output = $oFileController->insertFile($file, $module_srl, $document_srl, 0, true);
		if (!$output->toBool())
		{
			return '';
		}
		$file_srl = (int)$output->get('file_srl');
		$file_obj = FileModel::getFile($file_srl);
		if (!$file_obj)
		{
			return '';
		}
		return FileModel::getDirectFileUrl($file_obj->uploaded_filename);
	}

	/**
	 * 업로드된 이미지를 최대 변(1600px) / 최대 용량(2MB) 이내로 자동 리사이즈한다.
	 * tmp 파일을 그 자리에서 다시 인코딩하여 덮어쓴다.
	 */
	protected function resizeUploadedImage(array &$file): void
	{
		$tmp = $file['tmp_name'] ?? '';
		if (!$tmp || !is_file($tmp))
		{
			return;
		}

		$info = @getimagesize($tmp);
		if (!$info || empty($info[0]) || empty($info[1]))
		{
			return; // 이미지가 아니면 건너뜀
		}

		$w = (int)$info[0];
		$h = (int)$info[1];
		$type = (int)$info[2];
		$max_dim = 1600;
		$max_bytes = 2 * 1024 * 1024;

		$too_big = ($w > $max_dim || $h > $max_dim);
		$too_heavy = (filesize($tmp) > $max_bytes);
		if (!$too_big && !$too_heavy)
		{
			return; // 이미 충분히 작음
		}

		switch ($type)
		{
			case IMAGETYPE_JPEG:
				$src = @imagecreatefromjpeg($tmp);
				break;
			case IMAGETYPE_PNG:
				$src = @imagecreatefrompng($tmp);
				break;
			case IMAGETYPE_GIF:
				$src = @imagecreatefromgif($tmp);
				break;
			case IMAGETYPE_WEBP:
				$src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false;
				break;
			default:
				return; // 지원하지 않는 형식은 원본 유지
		}
		if (!$src)
		{
			return;
		}

		$ratio = $too_big ? min(1.0, $max_dim / max($w, $h)) : 1.0;
		$tw = max(1, (int)round($w * $ratio));
		$th = max(1, (int)round($h * $ratio));

		$dst = imagecreatetruecolor($tw, $th);
		if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF)
		{
			imagealphablending($dst, false);
			imagesavealpha($dst, true);
			$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
			imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
		}
		imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

		if ($type === IMAGETYPE_PNG)
		{
			imagepng($dst, $tmp, 6);
		}
		elseif ($type === IMAGETYPE_GIF)
		{
			imagegif($dst, $tmp);
		}
		elseif ($type === IMAGETYPE_WEBP && function_exists('imagewebp'))
		{
			imagewebp($dst, $tmp, 82);
		}
		else
		{
			// JPEG: 2MB 이하가 될 때까지 품질을 낮춰 재인코딩
			$quality = 85;
			imagejpeg($dst, $tmp, $quality);
			while (filesize($tmp) > $max_bytes && $quality > 50)
			{
				$quality -= 10;
				imagejpeg($dst, $tmp, $quality);
			}
		}

		imagedestroy($src);
		imagedestroy($dst);

		clearstatcache(true, $tmp);
		$file['size'] = filesize($tmp);
	}

	protected function buildContent(int $module_srl, int $document_srl, $args, array $file_urls): string
	{
		$info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		$mid = strtolower(trim((string)($info->mid ?? '')));

		if ($mid === 'dispatch_letter')
		{
			return church_writeModel::buildDispatchLetterContent($document_srl, $file_urls['letter'] ?? []);
		}
		if ($mid === 'dispatch_video')
		{
			$summary = trim((string)($args->summary ?? ''));
			if (function_exists('mb_substr'))
			{
				$summary = mb_substr($summary, 0, 100);
			}
			else
			{
				$summary = substr($summary, 0, 100);
			}
			$body = church_writeModel::buildVideoContent([
				'speaker' => '',
				'youtube_url' => $args->youtube_url ?? '',
				'video_url' => '',
				'summary' => '',
			]);
			if ($summary !== '')
			{
				$body .= '<p class="cd-doc-summary">' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</p>';
			}
			return $body;
		}

		switch ($module_srl)
		{
			case 110:
			case 116:
			case 118:
			case 120:
				return church_writeModel::buildVideoContent([
					'speaker' => $args->speaker ?? '',
					'youtube_url' => $args->youtube_url ?? '',
					'video_url' => $args->video_url ?? '',
					'summary' => $args->summary ?? '',
				]);

			case 114:
				return church_writeModel::buildJuboContent($document_srl, $file_urls['jubo'] ?? []);

			case 122:
				return church_writeModel::buildPictureContent($file_urls['images'] ?? [], $args->summary ?? '');

			case 124:
				$images = [];
				if (!empty($file_urls['photo']))
				{
					$images[] = $file_urls['photo'];
				}
				return church_writeModel::buildPictureContent($images, $args->summary ?? '');

			default:
				return '<p></p>';
		}
	}

	/* ===================== 파송선교 성도 응원 ===================== */

	protected function cheerCanModerate($logged_info): bool
	{
		return church_writeModel::isChurchAdmin($logged_info)
			|| church_writeModel::isMissionPageAdmin($logged_info);
	}

	function procChurchWriteCheerConfig()
	{
		Context::setResponseMethod('JSON');
		getModel('dmcadmin');
		$logged = Context::get('logged_info');
		$is_logged = (bool)Context::get('is_logged');
		$can_reply = false;
		$member_srl = 0;
		$nick = '';
		if ($is_logged && $logged)
		{
			$member_srl = (int)($logged->member_srl ?? 0);
			$nick = trim((string)($logged->user_name ?? ''));
			if ($nick === '')
			{
				$nick = (string)($logged->nick_name ?? '');
			}
			$can_reply = church_writeModel::isChurchAdmin($logged)
				|| church_writeModel::isMissionPageAdmin($logged);
		}
		$this->add('isLogged', $is_logged);
		$this->add('memberSrl', $member_srl);
		$this->add('nickName', $nick);
		$this->add('canReply', $can_reply);
		$this->add('canEditPrayer', $can_reply);
		$this->add('canWriteBoards', $can_reply);
		$this->add('csrf', $is_logged ? Rhymix\Framework\Session::createToken('') : '');
		$this->add('prayerLine', (string)(dmcadminModel::getDispatchMissionPageData()['prayer_line'] ?? ''));

		$write = [];
		foreach (['dispatch_letter', 'dispatch_video'] as $board_mid)
		{
			$srl = dmcadminModel::getModuleSrlByMid($board_mid);
			if ($srl < 1)
			{
				continue;
			}
			$cfg = church_writeModel::getClientConfig($srl, $logged, $board_mid);
			if ($cfg)
			{
				$write[$board_mid] = $cfg;
			}
		}
		$this->add('writeConfigs', $write);
	}

	function procChurchWriteDispatchFeed()
	{
		Context::setResponseMethod('JSON');
		getModel('dmcadmin');
		$logged = Context::get('logged_info');
		$can_write = church_writeModel::isChurchAdmin($logged) || church_writeModel::isMissionPageAdmin($logged);
		$letters = [];
		foreach (dmcadminModel::listDispatchBoardDocuments('dispatch_letter', 36) as $row)
		{
			$letters[] = [
				'document_srl' => $row['document_srl'],
				'title' => $row['title'],
				'date' => $row['date'],
				'images' => $row['images'],
				'views' => (int)($row['readed_count'] ?? 0),
				'can_edit' => $can_write,
			];
		}
		$videos = [];
		foreach (dmcadminModel::listDispatchBoardDocuments('dispatch_video', 48) as $row)
		{
			$videos[] = [
				'document_srl' => $row['document_srl'],
				'title' => $row['title'],
				'date' => $row['date'],
				'youtube_id' => $row['youtube_id'],
				'summary' => $row['summary'],
				'views' => (int)($row['readed_count'] ?? 0),
				'can_edit' => $can_write,
			];
		}
		$this->add('letters', $letters);
		$this->add('videos', $videos);
	}

	function procChurchWriteDispatchPrayerSave()
	{
		Context::setResponseMethod('JSON');
		getModel('dmcadmin');
		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}
		$logged = Context::get('logged_info');
		if (!church_writeModel::isChurchAdmin($logged) && !church_writeModel::isMissionPageAdmin($logged))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		$prayer = trim((string)Context::get('prayer_line'));
		if (function_exists('mb_strlen') ? mb_strlen($prayer) > 160 : strlen($prayer) > 480)
		{
			return new BaseObject(-1, '기도 제목은 160자 이내로 작성해 주세요.');
		}
		$output = dmcadminModel::updateDispatchPrayerLine($prayer);
		if (!$output->toBool())
		{
			return $output;
		}
		$this->add('prayer_line', $prayer);
		$this->add('message', '저장되었습니다.');
	}

	function procChurchWriteCheerList()
	{
		Context::setResponseMethod('JSON');
		getModel('dmcadmin');
		$logged = Context::get('logged_info');
		$viewer = (int)($logged->member_srl ?? 0);
		$can_mod = $this->cheerCanModerate($logged);
		$data = dmcadminModel::getDispatchCheerData();
		$out = [];
		foreach (array_reverse((array)$data['comments']) as $c)
		{
			if (!is_array($c))
			{
				continue;
			}
			$out[] = dmcadminModel::formatCheerCommentForClient($c, $viewer, $can_mod);
		}
		$this->add('comments', $out);
	}

	function procChurchWriteCheerAdd()
	{
		Context::setResponseMethod('JSON');
		getModel('dmcadmin');
		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}
		$logged = Context::get('logged_info');
		$body = trim((string)Context::get('body'));
		if ($body === '')
		{
			return new BaseObject(-1, '응원 내용을 입력해 주세요.');
		}
		if (function_exists('mb_strlen') ? mb_strlen($body) > 300 : strlen($body) > 900)
		{
			return new BaseObject(-1, '응원은 300자 이내로 작성해 주세요.');
		}

		$data = dmcadminModel::getDispatchCheerData();
		$member_srl = (int)$logged->member_srl;
		$today = date('Y-m-d');
		$today_count = 0;
		foreach ((array)$data['comments'] as $c)
		{
			if ((int)($c['member_srl'] ?? 0) === $member_srl && strncmp((string)($c['created'] ?? ''), $today, 10) === 0)
			{
				$today_count++;
			}
		}
		if ($today_count >= 10 && !church_writeModel::isChurchAdmin($logged))
		{
			return new BaseObject(-1, '하루 응원 작성 횟수(10회)를 초과했습니다.');
		}

		$reactions = [];
		foreach (dmcadminModel::cheerReactionKeys() as $k)
		{
			$reactions[$k] = [];
		}
		$display_name = trim((string)($logged->user_name ?? ''));
		if ($display_name === '')
		{
			$display_name = trim((string)($logged->nick_name ?? ''));
		}
		if ($display_name === '')
		{
			$display_name = '성도';
		}
		$data['comments'][] = [
			'id' => 'C' . date('YmdHis') . substr((string)mt_rand(1000, 9999), 0, 4),
			'member_srl' => $member_srl,
			'nick_name' => $display_name,
			'body' => $body,
			'created' => date('Y-m-d H:i'),
			'reactions' => $reactions,
			'reply' => null,
		];
		$output = dmcadminModel::saveDispatchCheerData($data);
		if (!$output->toBool())
		{
			return $output;
		}
		$this->add('message', '응원이 등록되었습니다.');
	}

	function procChurchWriteCheerReply()
	{
		Context::setResponseMethod('JSON');
		getModel('dmcadmin');
		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}
		$logged = Context::get('logged_info');
		if (!$this->cheerCanModerate($logged))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		$comment_id = trim((string)Context::get('comment_id'));
		$body = trim((string)Context::get('body'));
		if ($comment_id === '' || $body === '')
		{
			return new BaseObject(-1, '답글 내용을 입력해 주세요.');
		}
		if (function_exists('mb_strlen') ? mb_strlen($body) > 500 : strlen($body) > 1500)
		{
			return new BaseObject(-1, '답글은 500자 이내로 작성해 주세요.');
		}

		$data = dmcadminModel::getDispatchCheerData();
		$found = false;
		foreach ($data['comments'] as &$c)
		{
			if ((string)($c['id'] ?? '') !== $comment_id)
			{
				continue;
			}
			$reactions = [];
			foreach (dmcadminModel::cheerReactionKeys() as $k)
			{
				$reactions[$k] = [];
			}
			if (!empty($c['reply']['reactions']) && is_array($c['reply']['reactions']))
			{
				$reactions = $c['reply']['reactions'];
			}
			$reply_name = trim((string)($logged->user_name ?? ''));
			if ($reply_name === '')
			{
				$reply_name = trim((string)($logged->nick_name ?? ''));
			}
			if ($reply_name === '')
			{
				$reply_name = '선교사';
			}
			$c['reply'] = [
				'member_srl' => (int)$logged->member_srl,
				'nick_name' => $reply_name,
				'body' => $body,
				'created' => date('Y-m-d H:i'),
				'is_missionary' => true,
				'reactions' => $reactions,
			];
			$found = true;
			break;
		}
		unset($c);
		if (!$found)
		{
			return new BaseObject(-1, '응원을 찾을 수 없습니다.');
		}
		$output = dmcadminModel::saveDispatchCheerData($data);
		if (!$output->toBool())
		{
			return $output;
		}
		$this->add('message', '답글이 등록되었습니다.');
	}

	function procChurchWriteCheerReact()
	{
		Context::setResponseMethod('JSON');
		getModel('dmcadmin');
		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}
		$logged = Context::get('logged_info');
		$member_srl = (int)$logged->member_srl;
		$comment_id = trim((string)Context::get('comment_id'));
		$target = trim((string)Context::get('target'));
		$reaction = trim((string)Context::get('reaction'));
		if ($comment_id === '' || !in_array($target, ['comment', 'reply'], true) || !in_array($reaction, dmcadminModel::cheerReactionKeys(), true))
		{
			return new BaseObject(-1, '잘못된 요청입니다.');
		}

		$data = dmcadminModel::getDispatchCheerData();
		$found = false;
		foreach ($data['comments'] as &$c)
		{
			if ((string)($c['id'] ?? '') !== $comment_id)
			{
				continue;
			}
			if ($target === 'reply')
			{
				if (empty($c['reply']) || !is_array($c['reply']))
				{
					return new BaseObject(-1, '답글이 없습니다.');
				}
				if (!isset($c['reply']['reactions']) || !is_array($c['reply']['reactions']))
				{
					$c['reply']['reactions'] = [];
				}
				$bucket = &$c['reply']['reactions'];
			}
			else
			{
				if (!isset($c['reactions']) || !is_array($c['reactions']))
				{
					$c['reactions'] = [];
				}
				$bucket = &$c['reactions'];
			}

			$had_same = in_array($member_srl, array_map('intval', (array)($bucket[$reaction] ?? [])), true);
			foreach (dmcadminModel::cheerReactionKeys() as $k)
			{
				$list = array_values(array_unique(array_map('intval', (array)($bucket[$k] ?? []))));
				$list = array_values(array_filter($list, static function ($s) use ($member_srl) {
					return $s !== $member_srl;
				}));
				$bucket[$k] = $list;
			}
			if (!$had_same)
			{
				$bucket[$reaction][] = $member_srl;
				$bucket[$reaction] = array_values(array_unique(array_map('intval', $bucket[$reaction])));
			}
			$found = true;
			break;
		}
		unset($c);

		if (!$found)
		{
			return new BaseObject(-1, '응원을 찾을 수 없습니다.');
		}
		$output = dmcadminModel::saveDispatchCheerData($data);
		if (!$output->toBool())
		{
			return $output;
		}
		$this->add('message', 'ok');
	}

	function procChurchWriteCheerDelete()
	{
		Context::setResponseMethod('JSON');
		getModel('dmcadmin');
		if (!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			throw new Rhymix\Framework\Exception('msg_security_violation');
		}
		$logged = Context::get('logged_info');
		$comment_id = trim((string)Context::get('comment_id'));
		$mode = trim((string)Context::get('mode'));
		if ($comment_id === '')
		{
			return new BaseObject(-1, '잘못된 요청입니다.');
		}
		$can_mod = $this->cheerCanModerate($logged);
		$member_srl = (int)$logged->member_srl;

		$data = dmcadminModel::getDispatchCheerData();
		$found = false;
		$next = [];
		foreach ((array)$data['comments'] as $c)
		{
			if (!is_array($c) || (string)($c['id'] ?? '') !== $comment_id)
			{
				$next[] = $c;
				continue;
			}
			$found = true;
			if ($mode === 'reply')
			{
				if (!$can_mod && (int)($c['reply']['member_srl'] ?? 0) !== $member_srl)
				{
					throw new Rhymix\Framework\Exceptions\NotPermitted;
				}
				$c['reply'] = null;
				$next[] = $c;
			}
			else
			{
				if (!$can_mod && (int)($c['member_srl'] ?? 0) !== $member_srl)
				{
					throw new Rhymix\Framework\Exceptions\NotPermitted;
				}
				/* 삭제: 목록에서 제외 */
			}
		}
		if (!$found)
		{
			return new BaseObject(-1, '응원을 찾을 수 없습니다.');
		}
		$data['comments'] = $next;
		$output = dmcadminModel::saveDispatchCheerData($data);
		if (!$output->toBool())
		{
			return $output;
		}
		$this->add('message', '삭제되었습니다.');
	}
}

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
		if (!$is_public && !church_writeModel::isChurchAdmin($logged_info))
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
		if (!church_writeModel::isChurchAdmin($logged_info))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

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

		if ($module_srl === 114)
		{
			$existing = church_writeModel::extractJuboImageUrls((string)$oDocument->getContent(false));
			$file_urls['jubo'] = array_merge($existing, $file_urls['jubo'] ?? []);
			if (empty($file_urls['jubo']))
			{
				return new BaseObject(-1, '주보 이미지가 없습니다. 이미지를 등록해 주세요.');
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
		if (!church_writeModel::isChurchAdmin($logged_info))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

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

		// 갤러리형 영상 게시판(설교110/성가대116/브니엘118/교회행사120) 글만 허용
		if (!in_array((int)$oDocument->get('module_srl'), [110, 116, 118, 120], true))
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
		if (!church_writeModel::isChurchAdmin($logged_info))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$srls_raw = (string)Context::get('srls');
		$srls = array_values(array_unique(array_filter(array_map('intval', explode(',', $srls_raw)))));
		if (!$srls)
		{
			return new BaseObject(-1, 'msg_invalid_request');
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
			'photo' => '',
		];

		$map = [
			'news_image' => 'news',
			'front_image' => 'front',
			'back_image' => 'back',
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
				$result['jubo'][$kind] = $url;
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
		$labels = ['news' => '교회소식.jpg', 'front' => '앞면.jpg', 'back' => '뒷면.jpg'];
		return $labels[$kind] ?? 'image.jpg';
	}

	protected function uploadOne(int $module_srl, int $document_srl, array $file, string $display_name): string
	{
		if (!empty($file['error']) || empty($file['tmp_name']))
		{
			return '';
		}

		// 주보(114)/행사사진(122)/새가족소개(124): 업로드 사진을 약 2MB(가로/세로 1600px)로 자동 리사이즈
		if (in_array($module_srl, [114, 122, 124], true))
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
}

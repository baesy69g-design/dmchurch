<?php
/**
 * 오시는길(p12) 구형 Daum 지도(http) → HTTPS 지도 embed 교체
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$mid = 'p12';
$module_srl = dmcadminModel::getPageModuleSrl($mid);
if ($module_srl < 1)
{
	fwrite(STDERR, "page module not found: {$mid}\n");
	exit(1);
}

$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
$content = (string)($module_info->content ?? '');

if ($content === '')
{
	fwrite(STDERR, "empty page content\n");
	exit(1);
}

$map_html = <<<'HTML'
<div class="church-map-embed">
<iframe
	title="동명교회 오시는길"
	src="https://maps.google.com/maps?q=%EC%84%9C%EC%9A%B8+%EB%8F%99%EB%8C%80%EB%AC%B8%EA%B5%AC+%EB%8B%B5%EC%8B%AD%EB%A6%AC%EB%A1%9C+136+%EB%8F%99%EB%AA%85%EA%B5%90%ED%9A%8C&amp;hl=ko&amp;z=16&amp;output=embed"
	width="730"
	height="420"
	style="border:0;max-width:100%;display:block;margin:0 auto"
	loading="lazy"
	referrerpolicy="no-referrer-when-downgrade"
	allowfullscreen></iframe>
</div>

HTML;

$patterns = [
	'@<!--\s*\*?\s*Daum 지도.*?daum\.roughmap\.Lander\(\{.*?\}\)\.render\(\);\s*</script>@is',
	'@<div id="daumRoughmapContainer[^"]*"[^>]*></div>.*?daum\.roughmap\.Lander\(\{.*?\}\)\.render\(\);\s*</script>@is',
];

$new_content = $content;
$replaced = false;
foreach ($patterns as $pattern)
{
	$tmp = preg_replace($pattern, $map_html, $new_content, 1, $count);
	if ($count > 0 && is_string($tmp))
	{
		$new_content = $tmp;
		$replaced = true;
		break;
	}
}

if (!$replaced)
{
	if (strpos($new_content, 'church-map-embed') !== false)
	{
		echo "already fixed: {$mid}\n";
		exit(0);
	}
	fwrite(STDERR, "daum map block not found in content\n");
	exit(1);
}

$new_content = str_replace('http://dmaps.daum.net/', 'https://dmaps.daum.net/', $new_content);

$output = dmcadminModel::updatePageModuleContent($module_srl, $new_content);
if (!$output->toBool())
{
	fwrite(STDERR, $output->getMessage() . "\n");
	exit(1);
}

dmcadminModel::clearPageModuleCache($module_srl, $mid);
echo "fixed map embed: {$mid}\n";

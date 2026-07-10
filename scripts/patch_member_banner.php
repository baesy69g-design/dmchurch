<?php
$layout = '/var/www/vhosts/localhost/html/layouts/xedition/layout.html';
$text = file_get_contents($layout);

if (strpos($text, '$_member_page') === false)
{
	$text = str_replace(
		"{@ \$sub_header_title = 'Membership'}",
		"{@ \$_member_page = true}\n\t\t{@ \$sub_header_title = '회원'}",
		$text
	);
	$text = str_replace(
		"{@ \$sub_header_title = 'Search'}",
		"{@ \$sub_header_title = '검색'}{@ \$_member_page = false}",
		$text
	);
	echo "member title patched\n";
}
else
{
	echo "member title already patched\n";
}

$needle = "{@ \$church_sub_top_banner_url = dmcadminModel::getSubTopBannerUrlForLayout(\$mid)}";
$insert = $needle . "\n\t\t<block cond=\"!\$church_sub_top_banner_url && !empty(\$_member_page)\">\n\t\t{@ \$church_sub_top_banner_url = dmcadminModel::getMemberPageBannerUrl()}\n\t\t</block>";
if (strpos($text, 'getMemberPageBannerUrl') === false)
{
	if (strpos($text, $needle) === false)
	{
		fwrite(STDERR, "banner anchor not found\n");
		exit(1);
	}
	$text = str_replace($needle, $insert, $text);
	echo "member banner patched\n";
}
else
{
	echo "member banner already patched\n";
}

file_put_contents($layout, $text);
echo "done\n";

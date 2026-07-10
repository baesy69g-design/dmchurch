<?php
$path = '/var/www/vhosts/localhost/html/layouts/xedition/layout.html';
$text = file_get_contents($path);
$text = str_replace('{$lang->cmd_modify_password}', '암호변경', $text);
file_put_contents($path, $text);
echo "password label -> 암호변경 (" . substr_count($text, '암호변경') . ")\n";

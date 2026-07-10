<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
$user_id = $argv[1] ?? 'baesy69';
$password = $argv[2] ?? 'dkagh@6918';
$out = $argv[3] ?? '/tmp/sess.txt';
getController('member')->doLogin($user_id, $password, false);
file_put_contents($out, session_name() . '=' . session_id());

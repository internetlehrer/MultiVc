<?php
#ini_set('display_errors', 1);
#ini_set('error_reporting', 5);

$gotoUrl = 'https://';
$gotoUrl .= $_SERVER['SERVER_NAME'];
$gotoUrl .= ':' . $_SERVER['SERVER_PORT'];
$gotoUrl .= str_replace(['editProperties', 'configure'], 'authorizeWebexIntegration', rawurldecode($_GET['state']));
$gotoUrl .= '&code=' . filter_var($_GET['code'], FILTER_SANITIZE_ENCODED);

header('Status: 303 See Other', false, 303);
header('Location:' . $gotoUrl);
exit;

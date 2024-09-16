<?php
#ini_set('display_errors', 1);
#ini_set('error_reporting', 5);
if (isset($_GET['error'])) {
    $error = 'error=' . $_GET['error'];
    if (isset($_GET['error_description'])) {
        $error .= '; error_description=' . $_GET['error_description'];
    }
    die($error);
}
$hasHttps = null !== $_SERVER['HTTPS'];
$withScheme = $hasHttps ? 'https://' : 'http://';
$hasDefaultPort = in_array((int)$_SERVER['SERVER_PORT'], [80, 443]);
$withPort = $hasDefaultPort ? '' : ':' . $_SERVER['SERVER_PORT'];

$gotoUrl = $withScheme;
$gotoUrl .= $_SERVER['SERVER_NAME'];
$gotoUrl .= $withPort;
$gotoUrl .= str_replace(['editProperties', 'configure'], 'authorizeWebexIntegration', rawurldecode($_GET['state']));
$gotoUrl .= '&code=' . filter_var($_GET['code'], FILTER_SANITIZE_ENCODED);

header('Status: 303 See Other', false, 303);
header('Location:' . $gotoUrl);
exit;
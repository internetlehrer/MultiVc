<?php
ini_set('display_errors', 1);
ini_set('error_reporting', 5);

require_once(__DIR__ . '/classes/class.ilMultiVcInit4Guestlink.php');

$join = JoinMeetingByGuestLink::init();
echo $join;

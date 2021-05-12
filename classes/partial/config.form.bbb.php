<?php
$ti = new ilTextInputGUI($pl->txt("obj_ids_special"), "frmObjIdsSpecial");
$ti->setRequired(false);
$ti->setMaxLength(1024);
$ti->setSize(60);
$ti->setInfo($pl->txt("obj_ids_special_info"));
$combo->addSubItem($ti);


$ti = new ilTextInputGUI($pl->txt("svr_public_url"), "svr_public_url");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(60);
$ti->setInfo($pl->txt("info_svr_public_url"));
$combo->addSubItem($ti);

$ti = new ilTextInputGUI($pl->txt("svr_private_url"), "svr_private_url");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(60);
$ti->setInfo($pl->txt("info_svr_private_url"));
$combo->addSubItem($ti);

// Password unreadable
$pi = new ilPasswordInputGUI($pl->txt("svr_salt"), "svr_salt");
$pi->setSkipSyntaxCheck(true);
$pi->setRequired(true);
$pi->setMaxLength(256);
$pi->setSize(6);
$pi->setInfo($pl->txt("info_svr_salt"));
$pi->setRetype(true);
$combo->addSubItem($pi);

// LOGO
$ti = new ilTextInputGUI($pl->txt("logo_url"), "logo");
$ti->setInfo($pl->txt("logo_url_info"));
$ti->setRequired(false);
$ti->setMaxLength(256);
$ti->setSize(60);
$combo->addSubItem($ti);

// CUSTOM STYLE
$ti = new ilTextInputGUI($pl->txt("custom_style"), "style");
$ti->setInfo($pl->txt("custom_style_info"));
$ti->setRequired(false);
$ti->setSize(60);
$combo->addSubItem($ti);

$ti = new ilTextInputGUI($pl->txt("max_participants"), "max_participants");
$ti->setMaxLength(3);
$ti->setSize(6);
$ti->setInfo($pl->txt("info_max_participants"));
$combo->addSubItem($ti);

$ti = new ilTextInputGUI($pl->txt("add_presentation_url"), "add_presentation_url");
$ti->setMaxLength(256);
$ti->setSize(6);
//$ti->setValidationRegexp('%^https://.*%');
$ti->setInfo($pl->txt("info_add_presentation_url"));
$combo->addSubItem($ti);

$cb = new ilCheckboxInputGUI($pl->txt("add_welcome_text"), "add_welcome_text");
$cb->setRequired(false);
$cb->setInfo($pl->txt("info_add_welcome_text"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("disable_sip"), "disable_sip");
$cb->setRequired(false);
$cb->setInfo($pl->txt("disable_sip_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("hide_username_logs"), "hide_username_logs");
$cb->setRequired(false);
$cb->setInfo($pl->txt("hide_username_logs_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("moderated_choose"), "cb_moderated_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_choose_info") . " " . $pl->txt("config_help_begin") . " " . $pl->txt("moderated_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("moderated_default"), "cb_moderated_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_default_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("private_chat_choose"), "private_chat_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("private_chat_choose_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("private_chat_default"), "private_chat_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("private_chat_default_info"));
$combo->addSubItem($cb);

// RECORDING
$cb = new ilCheckboxInputGUI($pl->txt("recording_choose"), "recording_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("recording_choose_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("recording_default"), "recording_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("recording_default_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("recording_only_for_moderated_rooms_default"), "recording_only_for_moderated_rooms_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("recording_only_for_moderated_rooms_default_info"));
$combo->addSubItem($cb);


$cb = new ilCheckboxInputGUI($pl->txt("cam_only_for_moderator_choose"), "cam_only_for_moderator_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("cam_only_for_moderator_choose_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("cam_only_for_moderator_default"), "cam_only_for_moderator_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("cam_only_for_moderator_default_info"));
$combo->addSubItem($cb);

// LOCK SETTINGS DISABLE CAM
$cb = new ilCheckboxInputGUI($pl->txt("lock_disable_cam_choose"), "lock_disable_cam");
$cb->setInfo($pl->txt("lock_disable_cam_choose_info"));
$cb->setRequired(false);
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("lock_disable_cam_default"), "lock_disable_cam_default");
$cb->setInfo($pl->txt("lock_disable_cam_default_info"));
$cb->setRequired(false);
$combo->addSubItem($cb);

// guestlink
$cb = new ilCheckboxInputGUI($pl->txt("guestlink_choose"), "guestlink_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("guestlink_choose_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("guestlink_default"), "guestlink_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("guestlink_default_info"));
$combo->addSubItem($cb);

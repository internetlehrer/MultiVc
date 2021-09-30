<?php

$sm = new ilMultiSelectInputGUI($pl->txt("assigned_roles"), 'assigned_roles');
$sm->setInfo($pl->txt("assigned_roles_info"));
#$sm->enableSelectAll(true);
$sm->setWidth('100');
$sm->setWidthUnit('%');
$sm->setHeight('200');
// $sm->setRequired(true);
$sm->setOptions($this->object->getAssignableGlobalRoles());
$combo->addSubItem($sm);

$ti = new ilTextInputGUI($pl->txt("om_svr_public_url"), "svr_public_url");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(60);
$ti->setInfo($pl->txt("info_om_svr_public_url"));
$combo->addSubItem($ti);

$ti = new ilTextInputGUI($pl->txt("om_svr_public_port"), "svr_public_port");
$ti->setRequired(true);
$ti->setMaxLength(5);
$ti->setSize(6);
$ti->setInfo($pl->txt("info_svr_public_port"));
$combo->addSubItem($ti);

$ti = new ilTextInputGUI($pl->txt("om_svr_username"), "svr_username");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(6);
$ti->setInfo($pl->txt("info_svr_username"));
$combo->addSubItem($ti);
/*
// Password - readable
$ti = new ilTextInputGUI($pl->txt("om_svr_userpass"), "svr_salt");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(6);
$ti->setInfo($pl->txt("info_svr_salt_om"));
$combo->addSubItem($ti);
*/

// Password - unreadable
$pi = new ilPasswordInputGUI($pl->txt("om_svr_userpass"), "svr_salt");
$pi->setSkipSyntaxCheck(true);
$pi->setRequired(true);
$pi->setMaxLength(256);
$pi->setSize(6);
$pi->setInfo($pl->txt("info_svr_salt_om"));
$pi->setRetype(true);

$combo->addSubItem($pi);

$ti = new ilTextInputGUI($pl->txt("max_participants"), "max_participants");
//$ti->setRequired(true);
$ti->setMaxLength(3);
$ti->setSize(6);
$ti->setInfo($pl->txt("info_max_participants"));
$combo->addSubItem($ti);


$cb = new ilCheckboxInputGUI($pl->txt("moderated_choose"), "cb_moderated_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_choose_info") . " " . $pl->txt("config_help_begin") . " " . $pl->txt("moderated_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("moderated_default"), "cb_moderated_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_default_info"));
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


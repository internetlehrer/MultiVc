<?php

/*
$ti = new ilTextInputGUI($pl->txt("obj_ids_special"), "frmObjIdsSpecial");
$ti->setRequired(false);
$ti->setMaxLength(1024);
$ti->setSize(60);
$ti->setInfo($pl->txt("obj_ids_special_info"));
$combo->addSubItem($ti);
*/
if(!(ilApiMultiVC::setPluginIniSet()['non_role_based_vc'] ?? 0)) {
    $sm = new ilMultiSelectInputGUI($pl->txt("assigned_roles"), 'assigned_roles');
    $sm->setInfo($pl->txt("assigned_roles_info"));
    #$sm->enableSelectAll(true);
    $sm->setWidth('100');
    $sm->setWidthUnit('%');
    $sm->setHeight('200');
    // $sm->setRequired(true);
    $sm->setOptions($this->object->getAssignableGlobalRoles());
    $combo->addSubItem($sm);
}

$ti = new ilTextInputGUI($pl->txt("teams_tenant_id"), "svr_public_url");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(60);
$ti->setInfo($pl->txt("teams_tenant_id_info"));
$combo->addSubItem($ti);


$ti = new ilTextInputGUI($pl->txt("teams_client_id"), "svr_username");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(60);
$ti->setInfo($pl->txt("teams_client_id_info"));
$combo->addSubItem($ti);

// Password unreadable
$pi = new ilPasswordInputGUI($pl->txt("teams_client_secret"), "svr_salt");
$pi->setSkipSyntaxCheck(true);
$pi->setRequired(true);
$pi->setMaxLength(256);
$pi->setSize(6);
$pi->setInfo($pl->txt("teams_client_secret_info"));
$pi->setRetype(false);
$combo->addSubItem($pi);

$cb = new ilCheckboxInputGUI($pl->txt("moderated_choose"), "cb_moderated_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_choose_info") . " " . $pl->txt("config_help_begin") . " " . $pl->txt("moderated_teams_info") );
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("moderated_default"), "cb_moderated_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_default_info"));
$combo->addSubItem($cb);

// guestlink
//$cb = new ilCheckboxInputGUI($pl->txt("guestlink_choose"), "guestlink_choose");
//$cb->setRequired(false);
//$cb->setInfo($pl->txt("guestlink_choose_info"));
//$combo->addSubItem($cb);
//
//$cb = new ilCheckboxInputGUI($pl->txt("guestlink_default"), "guestlink_default");
//$cb->setRequired(false);
//$cb->setInfo($pl->txt("guestlink_default_info"));
//$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("chat_choose"), "private_chat_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("chat_choose_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("chat_default"), "private_chat_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("chat_default_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("cam_mic_only_for_moderator_choose"), "cam_only_for_moderator_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("cam_mic_only_for_moderator_choose_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("cam_mic_only_for_moderator_default"), "cam_only_for_moderator_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("cam_mic_only_for_moderator_default_info"));
$combo->addSubItem($cb);

// RECORDING
$cb = new ilCheckboxInputGUI($pl->txt("recording_choose"), "recording_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("recording_choose_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("recording_default"), "recording_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("recording_autostart"));
$combo->addSubItem($cb);

//later
//$cb = new ilCheckboxInputGUI($pl->txt("recording_only_for_moderated_rooms_default"), "recording_only_for_moderated_rooms_default");
//$cb->setRequired(false);
//$cb->setInfo($pl->txt("recording_only_for_moderated_rooms_default_info"));
//$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("hide_username_logs"), "hide_username_logs");
$cb->setRequired(false);
$cb->setInfo($pl->txt("hide_username_logs_info"));
$combo->addSubItem($cb);



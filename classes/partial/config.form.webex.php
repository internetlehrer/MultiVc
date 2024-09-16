<?php

$wbxIntegAuthMethod = !(bool) $wbxIntegAuthMethod ? $this->object->getAuthMethod() : $wbxIntegAuthMethod;
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

$ti = new ilTextInputGUI($pl->txt("webex_site_url"), "svr_public_url");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(60);
$ti->setInfo($pl->txt("info_webex_site_url"));
$combo->addSubItem($ti);


$ti = new ilTextInputGUI($pl->txt("webex_client_id"), "svr_username");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(60);
$ti->setInfo($pl->txt("info_webex_client_id"));
$combo->addSubItem($ti);

// Password unreadable
$pi = new ilPasswordInputGUI($pl->txt("webex_client_secret"), "svr_salt");
$pi->setSkipSyntaxCheck(true);
$pi->setRequired(true);
$pi->setMaxLength(256);
$pi->setSize(6);
$pi->setInfo($pl->txt("info_webex_client_secret"));
$pi->setRetype(false);
$combo->addSubItem($pi);

$redirectUri = ILIAS_HTTP_PATH . '/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/server.php';
$ti = new ilNonEditableValueGUI($pl->txt("webex_redirect_uri"), "webex_redirect_uri");
$ti->setValue($redirectUri);
$ti->setInfo($pl->txt("info_webex_redirect_uri"));
$combo->addSubItem($ti);

//
$cb = new ilCheckboxInputGUI($pl->txt("moderated_choose"), "cb_moderated_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_choose_info") . " " . $pl->txt("config_help_begin") . " " . $pl->txt("moderated_info") . '<br />' . $pl->txt("moderated_webex"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("moderated_default"), "cb_moderated_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_default_info"));
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


/*
$cb = new ilCheckboxInputGUI($pl->txt("webex_logout_user_choose"), "extra_cmd_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("webex_logout_user_choose_info"));
$combo->addSubItem($cb);
*/

#$cb = new ilHiddenInputGUI("extra_cmd_default");
/*
$cb = new ilCheckboxInputGUI($pl->txt("webex_logout_user_default"), "extra_cmd_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("webex_logout_user_default_info"));
$combo->addSubItem($cb);
*/

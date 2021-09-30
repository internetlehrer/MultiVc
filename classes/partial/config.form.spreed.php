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

$ti = new ilTextInputGUI($pl->txt("obj_ids_special"), "frmObjIdsSpecial");
$ti->setRequired(false);
$ti->setMaxLength(1024);
$ti->setSize(60);
$ti->setInfo($pl->txt("obj_ids_special_info"));
$combo->addSubItem($ti);

// spreed_url (text)
$ti = new ilTextInputGUI($pl->txt("url"), "frmSpreedUrl");
$ti->setRequired(true);
$ti->setMaxLength(256);
$ti->setSize(60);
$ti->setInfo($pl->txt("url_info"));
$combo->addSubItem($ti);

//
$cb = new ilCheckboxInputGUI($pl->txt("protected"), "cb_protected");
$cb->setRequired(false);
$cb->setInfo($pl->txt("protected_info") . " " . $ilSetting->get('inst_id',0));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("moderated_choose"), "cb_moderated_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_choose_info") . " " . $pl->txt("config_help_begin") . " " . $pl->txt("moderated_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("moderated_default"), "cb_moderated_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("moderated_default_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("btn_settings_choose"), "cb_btn_settings_choose");
$cb->setRequired(false);
$settingsText = $pl->txt("btn_settings_choose_info");
if ($this->object->get_protected() == false) $settingsText .= " " . $pl->txt("btn_settings_not_protected_info");
$settingsText .= " " . $pl->txt("config_help_begin") . " " . $pl->txt("btn_settings_info");
$cb->setInfo($settingsText);
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("btn_settings_default"), "cb_btn_settings_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("btn_settings_default_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("btn_chat_choose"), "cb_btn_chat_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("btn_chat_choose_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("btn_chat_default"), "cb_btn_chat_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("btn_chat_default_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("with_chat_choose"), "cb_with_chat_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("with_chat_choose_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("with_chat_default"), "cb_with_chat_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("with_chat_default_info") . " " . $pl->txt("config_help_begin") . " " . $pl->txt("with_chat_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("btn_locationshare_choose"), "cb_btn_locationshare_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("btn_locationshare_choose_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("btn_locationshare_default"), "cb_btn_locationshare_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("btn_locationshare_default_info"));
$combo->addSubItem($cb);



//
$cb = new ilCheckboxInputGUI($pl->txt("member_btn_fileupload_choose"), "cb_member_btn_fileupload_choose");
$cb->setRequired(false);
$cb->setInfo($pl->txt("member_btn_fileupload_choose_info") . " " . $pl->txt("config_help_begin") . " " . $pl->txt("member_btn_fileupload_info"));
$combo->addSubItem($cb);

//
$cb = new ilCheckboxInputGUI($pl->txt("member_btn_fileupload_default"), "cb_member_btn_fileupload_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("member_btn_fileupload_default_info"));
$combo->addSubItem($cb);

$cb = new ilCheckboxInputGUI($pl->txt("fa_expand_default"), "cb_fa_expand_default");
$cb->setRequired(false);
$cb->setInfo($pl->txt("fa_expand_default_info"));
$combo->addSubItem($cb);

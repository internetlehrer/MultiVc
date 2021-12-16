<?php

$actUserChanged = new ilHiddenInputGUI('act_owner_changed');
$combo->addSubItem($actUserChanged);

if( !(ilApiMultiVC::setPluginIniSet()['non_role_based_vc'] ?? 0) ) {
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


/*
$actUserChanged = new ilCheckboxInputGUI($pl->txt("delete_sess_user_changed"), 'act_owner_changed');
$actUserChanged->setInfo($pl->txt("delete_sess_user_changed_info"));
$combo->addSubItem($actUserChanged);
*/

$select = new ilSelectInputGUI($pl->txt("max_participants"), "max_participants");
$select->setOptions([
    20 => 20,
    30 => 30,
    50 => 50,
    100 => 100,
    200 => 200,
    500 => 500
]);
$select->setInfo($pl->txt("info_max_participants_webinar"));
$combo->addSubItem($select);


if( isset($_GET['configureNewMultiVcConn']) ) {
    $listTokenUser = new ilHiddenInputGUI('list_token_user');
    $combo->addSubItem($listTokenUser);
    $ul = new ilHiddenInputGUI("new_token_user_email");
    $combo->addSubItem($ul);

} else {

    $listTokenUser = new ilNonEditableValueGUI($pl->txt('list_token_user'), 'list_token_user');
    $listTokenUser->setInfo($pl->txt('list_token_user_info'));
    $listTokenUser->setValue($lng->txt('no_items'));
    $combo->addSubItem($listTokenUser);

// User name, login, email filter
    $ul = new ilTextInputGUI($pl->txt("add_token_user"), "new_token_user_email");
    $ul->setInfo($pl->txt("ext_provider_token_user_info"));
    $ul->setDataSource($this->dic->ctrl()->getLinkTarget(
        $this,
        "addUserAutoComplete",
        "",
        true
    ));
    $ul->setSize(20);
    $ul->setSubmitFormOnEnter(false);
    $ul->setDisableHtmlAutoComplete(false);
    $combo->addSubItem($ul);
}
// elem will be converted into type button. Its value keeps json, see getValues() and src/js/modal.conf.edudip.js
$btnEditToken = new ilHiddenInputGUI('btn_edit_token_user');
$combo->addSubItem($btnEditToken);

$newTokenUserAccessToken = new ilHiddenInputGUI('new_token_user_access_token');
$combo->addSubItem($newTokenUserAccessToken);

$edudipTokenUser = new ilHiddenInputGUI('token_user');
$combo->addSubItem($edudipTokenUser);


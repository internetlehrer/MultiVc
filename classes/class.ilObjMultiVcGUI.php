<?php

use ILIAS\DI\Container;

include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");
include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilApiMultiVC.php";
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilApiBBB.php";
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilApiOM.php";


/**
* User  class for MultiVc repository object.
*
* User  classes process GET and POST parameter and call
* application classes to fulfill certain tasks.
*
* @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
*
* $Id$
*
* Integration into control structure:
* - The GUI class is called by ilRepositoryGUI
* - GUI classes used by this class are ilPermissionGUI (provides the rbac
*   screens) and ilInfoScreenGUI (handles the info screen).
*
* @ilCtrl_isCalledBy ilObjMultiVcGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjMultiVcGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilRepositorySearchGUI
*
*/
class ilObjMultiVcGUI extends ilObjectPluginGUI
{
	/** @var ilObjMultiVc $object */
	public $object;
	/** @var ilLanguage $lng */
	public $lng;
    /** @var Container $dic */
	private $dic;
	/** @var ilPropertyFormGUI $form */
	private $form;


	function __construct($a_ref_id = 0, $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0)
    {
        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;

        parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);

        $this->dic = $DIC;



        if( $this->object instanceof ilObjMultiVc ) {
            $this->object->fillEmptyPasswordsBBBVCR();
        }
    }


    /**
	* Initialisation
	*/
	protected function afterConstructor()
	{
		// anything needed after object has been constructed
		// - Spreed: append my_id GET parameter to each request
		//   $ilCtrl->saveParameter($this, array("my_id"));
		//$this->deactivateCreationForm(ilObject2GUI::CFORM_IMPORT);
		//$this->deactivateCreationForm(ilObject2GUI::CFORM_CLONE);
	}

	/**
	* Get type.
	*/
	final function getType()
	{
		return "xmvc";
	}

    /**
     * Handles all commmands of this class, centralizes permission checks
     * @param $cmd
     * @return mixed
     * @throws ilCtrlException
     * @throws ilDatabaseException
     * @throws ilObjectException
     * @throws ilTemplateException
     * @throws Exception
     */
	function performCommand($cmd)
	{
	    global $DIC; /** @var Container $DIC */
		$next_class = $this->ctrl->getNextClass($this);
		switch($next_class)
		{
			case 'ilcommonactiondispatchergui':
				require_once 'Services/Object/classes/class.ilCommonActionDispatcherGUI.php';
				$gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
				return $this->ctrl->forwardCommand($gui);
				break;
		}

		switch ($cmd)
		{
            case 'confirmedDelete':
			case "editProperties":		// list all commands that need write permission here
			case "updateProperties":
				$this->checkPermission("write");
				$this->$cmd();
				break;

            case 'userLog':
            case "applyFilterUserLog":
            case "resetFilterUserLog":
			case "downloadUserLog":
                $this->checkPermission('write');
                $this->initUserLogTableGUI($cmd);
                break;

            case 'confirmDeleteRecords':
                $this->confirmDeleteRecords();
                break;
            case "deleteRecords":
                $this->checkPermission("read");
                if( $this->deleteRecords() ) {
                    ilUtil::sendSuccess($DIC->language()->txt("msg_obj_deleted"), true);
                    $DIC->ctrl()->redirect($this, 'showContent');
                } else {
                    ilUtil::sendFailure($DIC->language()->txt("msg_no_objs_deleted"), true);
                    $DIC->ctrl()->redirect($this, 'showContent');
                }
                break;

			case "showContent":			// list all commands that need read permission here
				$this->checkPermission("read");
				$this->$cmd();
				break;
		}
	}

    /**
     * @throws Exception
     */
	private function initTabContent()
    {
        $this->tabs->activateTab('content');

        if( !ilMultiVcConfig::getInstance($this->object->getConnId())->getHideUsernameInLogs()
            && $this->dic->access()->checkAccess("write", "", $this->object->getRefId())
        ) {
            $this->tabs->addSubTab("showContent", $this->txt('meeting'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'showContent'));
            $this->tabs->addSubTab("userLog", $this->txt('user_log'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'userLog'));
            $this->tabs->activateSubTab('showContent');
        }
    }

    /**
     * @throws Exception
     */
    public function initUserLogTableGUI($cmd)
    {
        if ( ilMultiVcConfig::getInstance($this->object->getConnId())->getHideUsernameInLogs()
            || !$this->dic->access()->checkAccess("write", "", $this->object->getRefId())
        ) {
            $this->dic->ctrl()->redirect($this, '');
        }

        $this->tabs->addSubTab("showContent", $this->txt('meeting'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'showContent'));
        $this->tabs->addSubTab("userLog", $this->txt('user_log'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'userLog'));
        $this->tabs->activateTab('content');
        $this->tabs->activateSubTab('userLog');

        require_once dirname(__FILE__) . '/class.ilMultiVcUserLogTableGUI.php';
        $userLogTableGui = new ilMultiVcUserLogTableGUI($this, $cmd);

        $this->dic->ui()->mainTemplate()->setContent($userLogTableGui->getHTML());

		if( $cmd === 'downloadUserLog' ) {
			$userLogTableGui->downloadCsv();
		}
    }

    /**
     * init create form
     * @param  $a_new_type
     * @return ilPropertyFormGUI
     */
	public function initCreateForm($a_new_type)
	{
        require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilObjMultiVc.php");
		$form = parent::initCreateForm($a_new_type);

        // MultiVcConn selection
        $combo = new ilSelectInputGUI($this->txt("conn_id"), 'conn_id');
        $combo->setRequired(true);
        $combo->setOptions(ilMultiVcConfig::_getAvailableMultiVcConn(true));
        //$combo->setInfo($pl->txt('info_platform_chg_reset_data'));
        $form->addItem($combo);

		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$form->addItem($cb);

		return $form;
	}

    /**
     * @param ilObject $newObj
     * @global $DIC
     */
	public function afterSave(ilObject $newObj)
	{
	    global $DIC; /** @var Container $DIC */
        $ilCtrl = $DIC['ilCtrl'];
        $ilUser = $DIC['ilUser'];



		$form = $this->initCreateForm('xmvc');
		$form->checkInput();

		$newObj->createRoom($form->getInput("online"), $form->getInput("conn_id"));
		$newObj->fillEmptyPasswordsBBBVCR();
		//var_dump($newObj); exit;
		parent::afterSave($newObj);
	}

	/**
	* After object has been created -> jump to this command
	*/
	function getAfterCreationCmd()
	{
		return "editProperties";
	}

	/**
	* Get standard command
	*/
	function getStandardCmd()
	{
		return "showContent";
	}

    /**
     * Show tabs
     */
	function setTabs()
	{
        global $DIC; /** @var Container $DIC */
        $ilTabs = $DIC['ilTabs'];
        $ilCtrl = $DIC['ilCtrl'];
        $ilAccess = $DIC['ilAccess'];

		// tab for the "show content" command
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("content", $this->txt("meeting"), $ilCtrl->getLinkTarget($this, "showContent"));
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		// standard permission tab
		$this->addPermissionTab();
	}


	/**
	* Edit Properties. This commands uses the form class to display an input form.
	*/
	function editProperties()
	{
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];
        $ilTabs = $DIC['ilTabs'];

		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
        if( $this->hasChoosePermission('admin') ) {
            ilUtil::sendQuestion($this->lng->txt('rep_robj_xmvc_sysadmin_perm_choose_all'));
        }
		$tpl->setContent($this->form->getHTML());
	}

    /**
     * @param $item
     * @param $choose
     * @return ilCheckboxInputGUI|ilHiddenInputGUI
     */
	function formItem($item) {
		if ( $this->hasChoosePermission($item) ) {
			$cb = new ilCheckboxInputGUI($this->lng->txt("rep_robj_xmvc_".$item), "cb_".$item);
			$cb->setInfo($this->lng->txt("rep_robj_xmvc_".$item."_info"));
		} else {
			$cb = new ilHiddenInputGUI("cb_".$item);
		}
		return $cb;
	}

	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
        global $DIC; /** @var Container $DIC */
        $lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();

        //$pl = $this->getPluginObject();

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		// title
		$ti = new ilTextInputGUI($this->lng->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);

		// description
		$ta = new ilTextAreaInputGUI($this->lng->txt("description"), "desc");
		$this->form->addItem($ta);

        // // MultiVcConn selection
        // $combo = new ilSelectInputGUI($this->txt("conn_id"), 'conn_id');
        // $combo->setRequired(true);
        // $combo->setOptions(ilMultiVcConfig::_getAvailableMultiVcConn());
        // $combo->setOptions([
            // $this->object->getConnId() => ilMultiVcConfig::_getMultiVcConnData()[$this->object->getConnId()]['title']
        // ]);
        // //$combo->setOptions(ilMultiVcConfig::_getAvailableMultiVcConn());
        // $combo->setInfo($this->txt('info_selected_conn'));
        // $this->form->addItem($combo);

        // ConnID
        $info = new ilNonEditableValueGUI($this->txt("conn_id"));
        $info->setValue(ilMultiVcConfig::_getMultiVcConnData()[$this->object->getConnId()]['title']);
        $this->form->addItem($info);


        // SpecialID
        $info = new ilNonEditableValueGUI($this->lng->txt("object_id"));
        $info->setValue($this->object->getId());
        $this->form->addItem($info);


        // online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);

		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
		$settings = ilMultiVcConfig::getInstance($this->object->getConnId());

        $this->form->addItem($this->formItem("moderated"));

        $this->form->addItem($this->formItem("btn_settings"));

        $this->form->addItem($this->formItem("btn_chat"));

        $this->form->addItem($this->formItem("with_chat"));

        $this->form->addItem($this->formItem("btn_locationshare"));

        $this->form->addItem($this->formItem("member_btn_fileupload"));

        $this->form->addItem($this->formItem("private_chat"));

        $this->form->addItem($this->formItem("cam_only_for_moderator"));

        $this->form->addItem($this->formItem("guestlink"));

        $this->form->addItem($this->formItem("recording"));

		$this->form->addCommandButton("updateProperties", $this->lng->txt("save"));
		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}

    /**
     * @return bool
     */
	private function isRecordChooseAvailable()
    {
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        switch( true ) {
            case $settings->isRecordChoose() && !$settings->isRecordOnlyForModeratedRoomsDefault():
            case $settings->isRecordChoose() && $settings->isRecordOnlyForModeratedRoomsDefault() && $this->object->get_moderated():
                return true;
            default:
                return false;

        }
    }

    private function checkRecordChooseValue(bool $moderated, bool $recording): bool
    {
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        switch( true ) {
            case !$settings->isRecordChoose() && !$settings->isRecordDefault():
            case $settings->isRecordChoose() && $settings->isRecordOnlyForModeratedRoomsDefault() && !$moderated:
                return false;
            case $settings->isRecordChoose() && !$settings->isRecordDefault() && !$settings->isRecordOnlyForModeratedRoomsDefault():
            case $settings->isRecordChoose() && $settings->isRecordOnlyForModeratedRoomsDefault() && $moderated:
                return $recording;
            case !$settings->isRecordChoose() && $settings->isRecordDefault() && $settings->isRecordOnlyForModeratedRoomsDefault() && $moderated:
            case !$settings->isRecordChoose() && $settings->isRecordDefault() && !$settings->isRecordOnlyForModeratedRoomsDefault():
                return true;
        }
        return false;
    }

    /**
     * @param string $field
     * @return bool
     */
    private function hasChoosePermission(string $field)
    {
		$isAdmin = false;
        // if( isset($this->dic->rbac()->review()->getRolesByFilter(0, $this->dic->user()->getId(), 'Administrator')[0]) ) {
            // $isAdmin = true; //check config
        // }

        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());

	    switch ($field) {
			case 'admin':
				$state = $isAdmin;
				break;
            case 'moderated':
                $state = $settings->isObjConfig('moderatedChoose') && ($settings->get_moderatedChoose() || $isAdmin);
                break;
            case 'btn_settings':
                $state = $settings->isObjConfig('btnSettingsChoose') && ($settings->get_btnSettingsChoose() || $isAdmin);
                break;
            case 'btn_chat':
                $state = $settings->isObjConfig('btnChatChoose') && ($settings->get_btnChatChoose() || $isAdmin);
                break;
            case 'with_chat':
                $state = $settings->isObjConfig('withChatChoose') && ($settings->get_withChatChoose() || $isAdmin);
                break;
            case 'btn_locationshare':
                $state = $settings->isObjConfig('btnLocationshareChoose') && ($settings->get_btnLocationshareChoose() || $isAdmin);
                break;
            case 'member_btn_fileupload':
                $state = $settings->isObjConfig('memberBtnFileuploadChoose') && ($settings->get_memberBtnFileuploadChoose() || $isAdmin);
                break;
            case 'cam_only_for_moderator':
                $state = $settings->isObjConfig('camOnlyForModeratorChoose') && ($settings->isCamOnlyForModeratorChoose() || $isAdmin);
                break;
            case 'private_chat':
                $state = $settings->isObjConfig('privateChatChoose') && ($settings->isPrivateChatChoose() || $isAdmin);
                break;
            case 'guestlink':
                $state = $settings->isObjConfig('guestlinkChoose') && ($settings->isGuestlinkChoose() || $isAdmin);
                break;
            case 'recording':
                $state = $settings->isObjConfig('recordChoose') && ($this->isRecordChooseAvailable() || $isAdmin);
                break;
            default:
                $state = false;
        }
	    return $state;
    }

	/**
	* Get values for edit properties form
	*/
	function getPropertiesValues()
	{
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["online"] = $this->object->getOnline();
		$values["cb_moderated"] = $this->object->get_moderated();
		$values["cb_btn_settings"] = $this->object->get_btnSettings();
		$values["cb_btn_chat"] = $this->object->get_btnChat();
		$values["cb_with_chat"] = $this->object->get_withChat();
		$values["cb_btn_locationshare"] = $this->object->get_btnLocationshare();
		$values["cb_member_btn_fileupload"] = $this->object->get_memberBtnFileupload();
		$values["cb_fa_expand"] = $this->object->get_faExpand();
		$values["attendeepwd"] = $this->object->getAttendeePwd();
		$values["moderatorpwd"] = $this->object->getModeratorPwd();
        $values["cb_private_chat"] = $this->object->isPrivateChat();
        $values["cb_recording"] = $this->object->isRecordingAllowed();
        $values["cb_cam_only_for_moderator"] = $this->object->isCamOnlyForModerator();
        $values["conn_id"] = $this->object->getConnId();
        $values["cb_guestlink"] = $this->object->isGuestlink();

		$this->form->setValuesByArray($values);

	}

	/**
	* Update properties
	*/
	public function updateProperties()
	{
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];
        $lng = $DIC['lng'];
        $ilCtrl = $DIC['ilCtrl'];

		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setOnline($this->form->getInput("online"));
			if( $this->hasChoosePermission('moderated') ) {
                $this->object->set_moderated($this->object->ilIntToBool($this->form->getInput("cb_moderated")));
            }
            if( $this->hasChoosePermission('btn_settings') ) {
                $this->object->set_btnSettings($this->object->ilIntToBool($this->form->getInput("cb_btn_settings")));
            }
            if( $this->hasChoosePermission('btn_chat') ) {
                $this->object->set_btnChat($this->object->ilIntToBool($this->form->getInput("cb_btn_chat")));
            }
            if( $this->hasChoosePermission('with_chat') ) {
                $this->object->set_withChat($this->object->ilIntToBool($this->form->getInput("cb_with_chat")));
            }
            if( $this->hasChoosePermission('btn_locationshare') ) {
                $this->object->set_btnLocationshare($this->object->ilIntToBool($this->form->getInput("cb_btn_locationshare")));
            }
            if( $this->hasChoosePermission('member_btn_fileupload') ) {
                $this->object->set_memberBtnFileupload($this->object->ilIntToBool($this->form->getInput("cb_member_btn_fileupload")));
            }
            if( $this->hasChoosePermission('fa_expand') ) {
                $this->object->set_faExpand($this->object->ilIntToBool($this->form->getInput("cb_fa_expand")));
            }
            if( $this->hasChoosePermission('private_chat') ) {
                $this->object->setPrivateChat( (bool)$this->form->getInput("cb_private_chat") );
            }
            if( $this->hasChoosePermission('cam_only_for_moderator') ) {
                $this->object->setCamOnlyForModerator( (bool)$this->form->getInput("cb_cam_only_for_moderator") );
            }
            if( $this->hasChoosePermission('guestlink') ) {
                $this->object->setGuestlink( (bool)$this->form->getInput("cb_guestlink") );
            }
            if( $this->hasChoosePermission('moderated') ) {
                $this->object->setRecord( $this->checkRecordChooseValue( (bool)$this->form->getInput("cb_moderated"), (bool)$this->form->getInput("cb_recording")) );
            }
            // $this->object->setConnId( (int)$this->form->getInput("conn_id") );

			$returnVal = $this->object->update();
			$vc = ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent();
			if( $vc === 'bbb' ) {
                $this->object->fillEmptyPasswordsBBBVCR();
            } elseif( $vc === 'om' ) {
                $om = new ilApiOM($this);
                $this->prepareRoomOM($om);
            }

			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}

	public function confirmedDelete() {
	    $this->object->doDelete();
    }

    function getBuddyPicture() {
        //http://stackoverflow.com/questions/3967515/how-to-convert-image-to-base64-encoding
        global $DIC; /** @var Container $DIC */
        $ilUser = $DIC['ilUser'];

        $user_image = substr($ilUser->getPersonalPicturePath($a_size = "xsmall", $a_force_pic = true),2);
        if (substr($user_image,0,2) == './') $user_image = substr($user_image,2);
        try {
            $path = ILIAS_HTTP_PATH.'/'.$user_image;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            // $base64 = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAABlCAYAAACGLCeXAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAABf3SURBVHhe7V1Zb1tJduZzHoI85RcESIBkECDB5CkvAQZZHpNfEMwgyMzTdI/7YZaeDNLuRhJgFu/yvmi3JVm7xEUiKYqiKErcRHEVd1LcSS3W4q3nyzl1eSmSphbbssem7wU+1a1TVaeWr6punbpXRdUPfvADKGhfKAS3ORSC2xwKwW0OheA2h0Jwm0MhuM2hENzmUAhucygEtzkUgtscCsFtDoXgNodCcJtDIbjNoRDc5lAIbnMoBLc5FILbHKpnz55BQftCIbjNoRDc5lDpneWWAQraAyp3dLfmefr0Kfb392s42D9oiKzg44PKFd2redZCXvzlpX/Gdy/8E/7sF3+Ff/zFv9TCssko8vk81gJhrAeDSESCWPOFsf9kG+lMoRavkk9Bb7YhFvIjX9muyUM+D7Q6HXzBEJ7sNXaccDRcu89tpCh8vyH8JHg9HuyJ+wMsLFjqwvYwPaXG3sEzbJVySBc3JfnTAySSybp47QuVs45gp8+OHw7/DH9x/jv46y//Bv/w2fdqYZv5BB71D0E9MwvTvBELejXu3X4At8uJqYkpXLl2E8XtXbiWZjA6/hir/ji0U1N48KAbxcoODJppTGl0mNfPYGJyBv19PTDq9bjb/Qi9PXcp7gR+++tfY2x0ErP6WTzs64RGrcavSfZ4dAJGi53KcQCTZR5zVIaxR4/waGgEM6T3PuXxhMqYjQfQ3dWLVSrT6noE6uEB3Ot6iNsdVzA5PoT+cQ3uXL+MnoEhmPRaDJA7MjmFa5cuYGh4Ej33O5EpVmp1fp/Y29tDLBZDqVRqkJudWQTCJZjtGURSW/BFKwhFykhndxBJbsEf38Te/lPkijvYfvLqwFAZPIejbH7ehL//7+/iT77/R/jjf/tTfOdf/1ZM1SKcev3AowFYjdMIJTdgMxsw/HgEdpsNRoMZXZ33kcxvIh5yIRKLYmBwGGr1DGyONZF+cc6AaSJ4kfIwmJYRDbpgXV7B3XtdmDXoYJydQWdXN/QzBhiNBvR23yV3Dl3d3dDpTdja4zI+xdTYMAaHRmFbtGFp2Urx1ejtH8Qu52E2wmw2QaeeFmU1zupgW1nF5NgQdBoNRqZmMNTfDa3eCCvFHRoawuDYOOXVCaPZApvNVWuL941MJgOfz4dAIFCT5TMV9GtiGNJFMW5MYd6aQp8mgRVPFncHgugdCcO8VsQuzYg/veBCOrfToJOhWgw+qXkq6SAsV76PiZ9+D49+9HfQ3foNtreq09p7RDYVg0Y3I6bWVuFnhQWTAd5QpGXY+0YikUCQHn1+vx8HB2e39lFZQ4dT9P7eLta0fTRq97BdLsJvGKmFNS/AFHz4EARbgocEK2g/qHrmFDu4naEqbb6eSaLg44KKn628RFfQnlDxn2+//VZBm0IQrFzteykEt/lVm6Jb2VEKzh7c3k+ePHktvEma3d1dsWEiCH758mXLSArOHpVKRWwanRbb29sol8stw47D1taWILmO4G3c7LgGzbQGs7NGOB0r6L/3AHPLK1BPTsOgn4HFsoT7d25ipL8XK77oK4VXcDKYYDZfll1xxFIlpHMVhONFJLNl2D1JpLObiKdLCEbz4q1bjWBKs5EpIRDcgDeexYong3iyiESmgsRGETZ3WqSLkGyfCN7cfIXgHRi0U3jU34dr1x9AR4TOTGuhM+ixYLbQ/RjUujnMGXUY7OqBOxh/pfAKTka5XBLu4koI6jk/5qxBaExB2L0JeENJ6Ex+zFjD8AcSSBJ5HJ8J5jSrvgQsthAW3VEYFtfhcEWhWQjA6U/BshzDpN6DpZUI8ls7Il0Twa0LpOBsUSwWxXv1Qr6AHLnyPbu1+wLfF1AoFMRbJib4MEyKy/cc3krOLqOBYJ63m3dBjkLzfN8MXkw8fXogHvIHB0+r7jHYP5AWIU3y+sUJY49B5WW8ElaVN2B3D7t7u+QS2G0Vh0HxWso/cjQQzCvp3//+96eCbES//PZlLW09uBOwXCavRuARaC5YM7igh6AVIo2ERtlubYTs0KNmh+93yH2H4Lxa1eVDArfdkQRvlgoIRxPYpEUBf6KTSmdQoulllxqQp5nKZgUlmjqioTDKlTKevSAdAS++3ZNmAtbJbrte8kz1IeNYgvmlu85gwrzJCJfTjhntHIZ6uqE3L0A3PYnJiQk8Gp2EUWPA+Oggoqk8vo2uKwR/QDiW4KNQLuaxubMr4p80RSsE/2HxRgQzDskkggmH/kaCORN54VUDLbrq/adahFXBizBR+Ca5CKurWD14YdZK/rbg534r+YeEtyZYkHvMCH754gVe0LP5hXAZz/HiOaHmf4HnzxnPRRp2G0Ayljd0iOrqvF4m5DLRB1Q5hlxRqh/Xsb7inwrOgGACE1n1y5AJfvHykEgJTHCjTJDLYDLryZVlz1oTWS/jWaFGcH0F9w9X4vt7jZX/FPDWBD+n0VSgFfWLanoenS9fnjBFt0CNtFemb0neADa5aIQ2y5sr1+44jZn29gQ/O4DD7sS8QUeuA/rZWWTL0nfWrJNd5Xo3F9virUitx1sTzFMzv7VIxqOIJ1LIpBIob0vfWSsEv9vrPRFM6app66EQ/O6v90awnLYeCsHv/novBLMdLEEh+H1fyghu8+u9EnyUHawQ/G4u3iN4TwSzq4zg93kxX0ze+yGYR281bT1kgjmT+o2L48CbFa1krwuxCXLC++ePGfyNlvz+u1V4Pd6aYIYwler8DJlgaS+aUd2Ppqnl6K3Kxm1KISc9DEH4MyKc3eq25FGdgSt2UN2Llr4Aoftqr//U8PYj+IRF1kuxF33GLxuqRNbL6uXKy4ZDnAHB0jQt+2XIBMsvG2ojmUh7s5cNTHKVaCb3OIKpYgdyBauVVAh+TYIrpTzsLheKuRIymRSSyRSi0Si0YyPwx9I1gplY95ofI1qjWBTwCLYuLopvdtccdpjNFiQTKQTDEXFSjm3FDptRD5t9FW7yx9cjiMTjsFvNWF0LIBpPIR70QmMwIxwKIBJeR3g9ilw2hYVFG9Z8fngJoWAQu3Il9z/NN0mMNyY4GlxDIBLGzMQMlp2L0M5YkErE4LE5sf1UGnnyCO4Z1eA/Lg8gl8vh6W4FWo0GM4Z5aManYHOuYWlOh4HRSbgsFsyarTBTZ/D5vZhSa6EdH8f0zAzcy8uYmJyC1kjE+tyY0M5iqK8T84sOLOq0mJkzYU4/A7VajftdDwXJtYpS/ZQR/JoEy1MxT9GFXFb64K4qY9QIphHMq75cPkdTLk/RVRw1RTcttA6n6Fen4npZ7X1w0+pZmaLPgOBjv+gQJDcuspi4RoLfYJH19PhFlnDlSoopWiFYENOKzFaQiTxpFc1TdISezW6PV9htz5/uwaifxYxGjcGhcazTMzQei2F5yQa704VoKIKl+QWsJ5I10sUIrh/FRN4ri6z6LzrqV9AMqp8ygt+Q4JM+uuNF1qpnDZlsVjyDX+xvw7RghWHWRAuhdQz2dmJhyQ2jepqe5XZo6Jm+YFyANxiqI7h5BBPq/IfyI0awYge/uxHMdrB4BmeJXPHsPQM7WPno7tQ4A4IJxxDMU/TWZuWQ0BMWWc0Es6yZ4FPtZFUXW/JOlkLwGxPM6Y4meG+rTPbpAsaGR/Bw4DEcZOe63A6op4wI+nzwuuywLDmxtOxALOCFlexf79oa2bhBmPR6eNxeeN0erLlWYJq3wDy/iJXlFbKtg3CR6yFb3Ga10WOA7GaKZ6Fn+fLSCs0YGbKv4/CSubVkdaCU34CLdAf9axibmIbFugSLTgeL3YPVVTsGOwcQSaQRC4Xgdjngcq3BuUr5uldpvaDFqtsJh8NFcVJYXpiD07lK+pwwLyzQvQuzs/MwzWqwthqg+jlJ5xqS6QzVl8pjs2LRsgTrsl2U0ba0SGVcRqmYh4fMOatlAW6Kr6PyWK1UP2qj8tYO3PYlzJE5ueKwYyNXEGm5PAYyF2dMi1iq6ikWUpifXxF1cttspGsVDvfa2RF83Ah+drBHDWCnxVQcPreLKmNDjBre7fKSLR1AaH0dkXAEc+ZFlAoFhGjRZVmwIOD3IRGLIhiIIRrwwx8MIkQLMPsSEUPP8HAkgUQ0grU1DzxOD9bpmb1O5BhM89QhvNjZLsPr9cHjpXBXAOlYGEuLS1h2cOO7oSe7OeIjnZT3/LwZLpsdvImyurKMYDgEu82BKJG5SvFDfionyZaojKFogsrkg8vhhp9IEfJFIsXhocamRqeysUyvJbIDEQwPPBKHo/pJx5w47HQFZhPZ+URAeWsT9hUHFubnqH1iRNgSdWAKW/MjGksjGY9Q/i5BZDKTE2m5PEHqFLxoNZEeL3XE0mYRToqnN5rgc3mo7dawuOw8G4IZxxG8v7eDTeqNsXgcIWpsny+AMDVqubIJ76oPmVQSHip4gFbN0XhM7EpVcmn4/Otwc+9f9VJFaSR615BKpagzUOPTqGN9cSI4mUyIHbR8sYJoJEzhYXio4edNZiTiUerxLupM1AHWKSyWaJi+PgWcwQgm9xiCn5FZxP+4NjQ6gYDbTdO1HcuWOSxardRb7XBYrPA4aHpddWF0egqLJCtnk7DSytputlKvtWLNF8IyTc0emtq1s3rMzS7AYjKJc6q1ag1madUdoxGtGR+idCtELk2HBrM4otjJU+yUGhOUP0+5hXylZUO0K86A4OOn6OaXDbxwarXIEguq57Sgqt7XZKSn3g6WXheeYAdTxZSXDRLemuDT2MHSTpa8m3UMwZSmnlxZxqgnU7KDjzeTZKLlSip28DsawWwHvyRyayOYCa4jVyJYIvlYgut2spi8Y18XKjtZNZxIMDdys6xSKuL5S5ng4xdZ0gkxp0OJ9LaSvS6KpYI4iaBYLNSQJbPpTVAo5Bv0fEzg17MnEhym1Wc05INRp4eN7L0lMikMZI8adGRLLtJCyWwm2BBPkImiM8Gz5qB76RdYWGeJGrtc5oYvYGurIrC5WW7wy3Hqwxl8n89nRdgqLcLctCJmfyaTpnsnPB43KpUyEomYCPOTzctpYrGICA8G/ZKOXFb0Zi4PzxjsngTuXJVKifLLCR2ZfBGFklSm7e1N4bZCriDFSW7kyYRbRTqdFP50tiTqkOJ/7alrjyJZE7wRxPd8JAafocX3DD6LJFLYgT6gQ5R/cKMpXz42YyNfRjpXQoEILZey2EjYEVuX2oX/pehEgrNUwGgsRiZJApXtLSJah1xpE5H1oJBHIhHEybyJkblSyBawvDiPdH6rRjCPpvPnv8IPf/ifNdK8Ph/u378v7ll27doV3Lt3R/hdLgd+9av/wuXLlyS9pJ8b6csvf4ErVy4LMkOhAH75yy9x6dJF0VhM9Ndfn8fvfvdboePx40H8/Oc/Q0fHVeFPkEnF5Xk89BhazST0ZI+a52YwNj6B0s5+9VvFxot/+UStnhIEZ4i0VH4bVmdQ6NvZ2aK6x3HhynUxUliWJHOvq6cPK54E1amCJXcMGs10rZPpFuOYnJwQnZA7XGaDTMEokR7KU8eRCFtPUF3Wi9Q5JJ18KpAxNI0vxv4clugqApEUBtUrGDVEKV1BbH5MLm/j/wb5ALUANpIO2HT/g+u9ZKW4uGOdguCjIE/FjOMWWdwAw8OP0dXVWSN4dGxM2K58z7ILFy9hfGJc+O32ZYyOjggjPpfLkG0bFSP28uWL6OvrEWRHo2HRAbq7u8Qo40a8efM6OjsfCB06nQYXL15Af3+f8MfjCTw7eIKA1w87mVa8o6Q3zopfmNnee1qltPFikrjz8IwRimXwcFSH5ZUVoW+bOnqcbOy5OQOsq9II5VljcPARTenSiHf64qR/jgbAutClscRhMOjJHg9RvWikbWyIEZjMlLApRlsF2QKN6PLh7MUEW5IDuDj471hMDpKtX6KBtIF4qiBOtMvk8ujTreM3fT7xW1YbMRd0w7/D/95ZhM0VIR1nQPBJdnD9VHwU5Aq1gjx1N8dp5a+X1ftZB0+53NCnBY9chlz+en3HTdH1qC8PT5myX+iqknoceKaQzvaSsLX1ar5yeWsy4ZfzOhOCpWeW7JfxOgT/oSFOiCPwvXw0lBxWLudQzEVQqXa0YjGDXCYmZg45zoeIBoKbf1BYQXtBIbjNoWqeXs8SPE1/3OCNGj535NB/WlPrQwBzoOLNjPqdpbMEP4s/Rsi7Y63C3heeP2/c2WsV5yRwOhVvAtRv+Z0FxHYiubz6Y1OJj7blBz6Dz7nkD/BkPx+XWywVxT2vFMU5mLTKFP9gtSMdOspxpONx+cDRnZqf0+zsbCObzYpVL/t5xVwfznnx/yrxp0O88OCP7vl4Xt5U2N9/dctTKrdUNs5758kuCkVamVYXLfyzf6xTrIy35eP2d8TRvhyf/VskZ/3yblKpwudEl4Wf48jxntSdcrvbdOIt81J5UiA7PIpNcndJ9oTKIsCn6JKOfHkHG2Sjb2+T3idUr+IG0rlN8YpW1qHiTLnAcsb14INIy4RKmc2DbVpBZigxn0BeEnK5ocQODDVcgRq5QuGsj8ENGg6vi1/VZPuRdfAmAv8QI9+zjH9SVfZzZ2BbMRaLSnqr+kMhlvHqlfMsCb9kS2+JDhEOh4WfdaTTaRHOGyXs5zRcFpZnMhvIkv0o26Jb3DAt6s16OS53OCaW65QvlKplpvqTTt7s4HhyHslkkjqC1E78fpo7GdeV/Zl8ReTH/q1N0ilW61R2whbplnRK/gq1I/uZl1w5g+CGjYgsUvyK+J3mjWxZxOOOulHYRiRN7U428maFBlImjHiSTLxqOViHijMVjdkCPDKyVNEEVSZFDZQiIuKJJOJEAFeIiUkkUmJTIh5LUCXSdM9bjxIx3KtlMBESGv2twiVwWF6EsV4miP08MvmeZRyPyZLJ4zRcZslPMwProPhyHes75HGQysPl4r3oojCjGsvYGhyP3Vyey7whZhUhF35um5zQI+viM8bktM3gMmcLRcTJLGO3OZy3J4WJVz0YXLRNLkX1l9pF1qHihuCMW4Ebq1nGjZdtkjVDTsdxPwTw1yBvgnSa0Vrnhw7mlaGSp8izRDweby1PvCrnrUSOf1pweflj+VZyBa9CJf/q9FmCf+S4lTwQILm/UcZxzxI+v09yqV4KfFDZ7XY4nU4Bl8t1Ksjxj4LD4WgpPw4Op4PQJCM9bwM7g+rHaBXe7uB6q/idrsViOVMsLCxILunW6bQYn5jArE4HtUYLrXoaGo0GarUWOrUGMyTnb4InJiehIZnJaIRGqxMf1Gm1FD47K+453tT0NKan1eLfRDnNFKVhv15vwOTEOKXTCr0akk1PT9XiTFD+Ot0M5uZM1XfYnw5U3NhaahgGN8hpIMc/CrJOjYYbWi0IHh8dxejoGMb5f36JqFHy8zvS0eFhjHE4ESTCiJDRsXGMkHyY4jA5UtxJET48PCr0TdA9h7FsampauAyOOzQ0RGF0PzKCkRGKL8ImRLk+NagGBwfx+PFjgWFu1FNAjn8UuIEldxA9PT3o6+tHL7ldXd3of9hPsl709/WRXEJnZxcGHj0S911dXeT24mF/v0hXH6+nu7sa/pDC+9Db20t6e2vhnZ2dwj84OCCFifA+Ebeb8u6mMnB5OF5f72Ganu4e9Pc/pLDuaj7kf8h+LrsUr5vK2EdxuGxCXs23m8rD9eH303wvdD64j15y7965I+XFMkrfTXrlMg5QGVlPlwjvp7BuKYzKxWXhMnM4631IbcNpRb4U3kP1qukZeCTksp/L10VxmVeG6t69e+ILiwcPHrw27lNFHgg0yUkf4969u+jo6MCVy5dx6dJl3LhxQ+Dq1Wu4dfMmbtJ9x/UbuE64Tf7r5GfZrVu3cO3qVYorxeE010gP67pBcW/cuFV1Oe114TI66J513blzG7dv38ZN1kn+u+Tn8Fs3b+DKlasiXgfp76imu3TxEm5R/NuUr6xLxlVRDume41zvuFbzi/KIOB218sphnP+NG1weyqtDKhfLr1/vELK7d+/gBpevpuOqCOP7u8QJp712TfLfpo7CLuMalf8qyWt6qG4dHVLdbzCq8ZhXhurixYvU+JdwmUh4XVxitEjLMhkXLlxo8L9riPoQYewquAjV119/jW+++eZMUa/z/Pnz+Oqrr/DN1+wneVN+X5Oc4yt4N1CdO3cOX3zxxTvDT37yE3AeUj7n8Pnnn+Ozzz4X8h//+MfC//lnn1XlksthrXS9Cc6dI/cd1/FDxblz5/D/Qy+Xc0HIIDYAAAAASUVORK5CYII=";
            return $base64;
        }  catch (Exception $e) {
            return "";
        }
    }

    /**
     * Show content
     * @throws Exception
     */
	function showContent(){
        global $DIC; /** @var Container $DIC */
        $ilTabs = $DIC['ilTabs'];

        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
        //$ilTabs->clearTargets(); //no display of tabs
        $this->initTabContent();
        #$ilTabs->activateTab("content");//necessary...
        $showContent = ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent();
        switch($showContent) {
            case 'spreed':
                $this->showContentSpreed();
                break;
            case 'bbb':
                $this->showContentBBB();
                break;
            case 'om':
                $this->showContentOM();
                break;
        }
        //ilMultiVcConfig::getInstance()->getShowContent() === 'spreed' ? $this->showContentSpreed() : $this->showContentBBB();
	}

	private function showContentOM()
    {
        global $DIC; /** @var Container $DIC */

        $om = new ilApiOM($this);
        $this->prepareRoomOM($om);

        /*
        if( !$this->object->getRoomId() )
        {
            $roomId = $om->createRoom();
            $this->object->updateRoomId($roomId);
        }
        */

        //var_dump($om->getRecordings()); exit;

        switch (true) {
            case !($om instanceof ilApiOM) || !ilObjMultiVcAccess::checkConnAvailability($this->obj_id):
                $this->showContentUnavailable();
                break;
            case isset($_GET['startOM']) && (int)$_GET['startOM'] === 10:
            case isset($_GET['startOM']) && (int)$_GET['startOM'] === 1 && !$om->isMeetingStartable():
                $this->showContentWindowClose();
                break;
            case isset($_GET['startOM']) && (int)$_GET['startOM'] === 1 && $om->isMeetingStartable():
                $this->redirectToPlatformByUrl($om->getOmRoomUrl());
                break;
            case null !== $om->getPluginIniSet('max_concurrent_users'):
                //$this->showContentConcurrent($om);
                $this->showContentDefault($om);
                break;
            default:
                $this->showContentDefault($om);
                break;
        }
    }

	private function showContentBBB() {
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];

        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        if( (bool)strlen($hint = trim($settings->getHint())) ) {
            ilUtil::sendQuestion($hint);
        }

        try {
            $bbb = new ilApiBBB($this);
        } catch (Exception $e) {
            $bbb = new StdClass();
        }

        switch(true) {
            case !($bbb instanceof ilApiBBB) || !ilObjMultiVcAccess::checkConnAvailability($this->obj_id):
                $this->showContentUnavailable();
                break;
            case isset($_GET['windowBBB']) && (int)filter_var($_GET['windowBBB'], FILTER_SANITIZE_NUMBER_INT) === 1 && $bbb->isMeetingStartable():
                $this->showContentWindowRedirect();
                break;
            case isset($_GET['startBBB']) && (int)filter_var($_GET['startBBB'], FILTER_SANITIZE_NUMBER_INT) === 10:
            case isset($_GET['startBBB']) && (int)filter_var($_GET['startBBB'], FILTER_SANITIZE_NUMBER_INT) === 1 && !$bbb->isMeetingStartable():
                $this->showContentWindowClose();
                break;
            case isset($_GET['startBBB']) && (int)filter_var($_GET['startBBB'], FILTER_SANITIZE_NUMBER_INT) === 1 && $bbb->isMeetingStartable():
                $bbb->addConcurrent();
                $bbb->logMaxConcurrent();
                $this->redirectToPlatformByUrl($bbb->getUrlJoinMeeting(), $bbb);
                break;
            case null !== $bbb->getPluginIniSet('max_concurrent_users'):
                $this->showContentConcurrent($bbb);
                break;
            default:
                $this->showContentDefault($bbb);;
                break;
        }


    }

    private function showContentConcurrent(ilApiBBB $bbb) {
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];

        //$tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/src/css/three-dots.css');
        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/bbb/tpl.show_content_concurrent.html", true, true);

        if ($this->object->get_moderated() == true) {
            if ( $bbb->isUserModerator() ) {
                $my_tpl->setVariable("INFOTOP", $this->txt('info_top_moderator_bbb'));
            } else {
                $my_tpl->setVariable("INFOTOP", $this->txt('info_top_moderated_m_bbb'));
            }
        } else {
            $my_tpl->setVariable("INFOTOP", $this->txt('info_top_not_moderated_bbb'));
        }

        // INFO CONCURRENT USER / BUTTON CHECK AVAILABILITY / BUTTON JOIN MEETING
        $meetingRunningTxt = $this->lng->txt('rep_robj_xmvc_meeting_running_txt');
        if( ($availableUsers = $bbb->getMaxAvailableJoins()) > 0) {
            $info_concurrent = $this->lng->txt('rep_robj_xmvc_info_concurrent_users_available') . $availableUsers;
        } else {
            ilUtil::sendFailure($this->lng->txt('rep_robj_xmvc_info_concurrent_users_none'));
            //$info_concurrent = $this->lng->txt('rep_robj_xmvc_info_concurrent_users_none');
        }
        $my_tpl->setVariable('INFO_CONCURRENT', $info_concurrent);

        $my_tpl->setVariable("JOINCONTENT", $this->getJoinContent($bbb));
        $my_tpl->setVariable("MEETING_RUNNING", $this->txt('meeting_running'));
        $my_tpl->setVariable("INFOBOTTOM", $this->txt('info_bottom'));
        $my_tpl->setVariable("infoRequirements", $this->txt('info_requirements_bbb'));
        if( $bbb->isUserModerator() && $this->object->get_moderated() && $this->object->isGuestlink() ) {
            $my_tpl->setVariable("HEADLINE_GUESTLINK", $this->txt('guestlink'));
            $my_tpl->setVariable("userInviteInfo", $this->txt('user_invite_info'));
            $my_tpl->setVariable("userInviteUrl", $bbb->getInviteUserUrl());
        } else {
            $my_tpl->setVariable("HIDE_GUESTLINK", 'hidden');
        }

        // RECORDINGS
        if( $this->object->isRecordingAllowed() ) {
            $my_tpl->setVariable("RECORDINGS", $this->getShowRecordings($bbb));
        }

        $tpl->setContent($my_tpl->get());

    }

    /**
     * @param ilApiBBB|ilApiOM $vcObj
     * @throws ilTemplateException
     */
    private function showContentDefault($vcObj) {
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];

        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/bbb/tpl.show_content_default.html", true, true);

        $apiPostFix = strtolower(str_replace('ilApi', '', get_class($vcObj)));

        if( $this->object->get_moderated() ) {
            if ( $vcObj->isUserModerator() ) {
                $my_tpl->setVariable("INFOTOP", $this->txt('info_top_moderator_bbb'));
            } else {
                $my_tpl->setVariable("INFOTOP", $this->txt('info_top_moderated_m_bbb'));
            }
        } else {
            $my_tpl->setVariable("INFOTOP", $this->txt('info_top_not_moderated_bbb'));
        }

        $my_tpl->setVariable("JOINCONTENT", $this->getJoinContent($vcObj));
        $my_tpl->setVariable("MEETING_RUNNING", $this->txt('meeting_running'));
        $my_tpl->setVariable("INFOBOTTOM", $this->txt('info_bottom'));
        $my_tpl->setVariable("infoRequirements", $this->txt('info_requirements_'. $apiPostFix));
        if( $vcObj instanceof ilApiBBB && $vcObj->isUserModerator() && $this->object->get_moderated() && $this->object->isGuestlink() ) {
            $my_tpl->setVariable("HEADLINE_GUESTLINK", $this->txt('guestlink'));
            $my_tpl->setVariable("userInviteInfo", $this->txt('user_invite_info'));
            $my_tpl->setVariable("userInviteUrl", $vcObj->getInviteUserUrl());
        } else {
            $my_tpl->setVariable("HIDE_GUESTLINK", 'hidden');
        }

        // RECORDINGS
        if( $this->object->isRecordingAllowed() && $vcObj->isUserModerator() ) {
            $my_tpl->setVariable("HEADLINE_RECORDING", $this->txt('recording'));
            $my_tpl->setVariable("RECORDINGS", $this->getShowRecordings($vcObj));
        } else {
            $my_tpl->setVariable("HIDE_RECORDINGS", 'hidden');
        }

        $tpl->setContent($my_tpl->get());
    }

    private function showContentWindowClose() {
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];

	    $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/bbb/tpl.window_close.html", true, true);
        $my_tpl->setVariable('LINK_CLOSE',$this->lng->txt('rep_robj_xmvc_tab_close'));
        $tpl->setContent($my_tpl->get());
    }

    private function showContentWindowRedirect() {
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];

        $redirectUrl = str_replace('windowBBB', 'startBBB', $DIC->http()->request()->getUri() );
        // $tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/src/css/three-dots.css');
        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/bbb/tpl.window_redirect.html", true, true);
        $my_tpl->setVariable("REDIRECTMSG", $this->lng->txt('rep_robj_xmvc_redirect_msg'));
        $my_tpl->setVariable("REDIRECTURL", $redirectUrl);
        $tpl->setContent($my_tpl->get());
    }

    private function showContentUnavailable() {
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];

        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/bbb/tpl.unavailable.html", true, true);

        $my_tpl->setVariable("UNAVAILABLE", $this->lng->txt('rep_robj_xmvc_service_unavailable'));

        $tpl->setContent($my_tpl->get());
    }

    private function showContentSpreed(){
        global $DIC; /** @var Container $DIC */
        $tpl = $DIC['tpl'];
        $ilUser = $DIC['ilUser'];
        $ilSetting = $DIC->settings();
        $rbacreview = $DIC->rbac()->review();
        $ilAccess = $DIC->access();

        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/tpl.Spreedclient.html", true, true);
        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
		$settings = ilMultiVcConfig::getInstance($this->object->getConnId());
		$cmdURL = $settings->get_spreedUrl().'/il'.$ilSetting->get('inst_id',0).$this->object->getId();
		$specialObjectRights = false;
		$adminRights = false;
		$neededCourseFound = true;
		//check if $this->object->getId() is for special use
		// $settings->set_objIdsSpecial(266);
		// $tmpTxt = $settings->get_objIdsSpecial();
		if ($settings->get_objIdsSpecial() != '') {
			$ArObjIdsSpecial = [];
			$rawIds = explode (",", $settings->get_objIdsSpecial());
			foreach ($rawIds as $id) {
				$id = trim ($id);
				if (is_numeric ($id)) array_push ($ArObjIdsSpecial, $id);
			}
			if (in_array ($this->object->getId(), $ArObjIdsSpecial)) {
				//get course_id/grp_id and add to $cmdURL
				// $courseId = 0;
				//first check URL
				// if (is_numeric ($_GET["crs_id"])) $courseId = $_GET["crs_id"];
				//second check referrer
				// if ($courseId === 0) {
					// $referer = $_SERVER["HTTP_REFERER"];
					// $refIdPosition = strpos ($referer, "ref_id");
					// if ($refIdPosition !== false) {
						// $tmpStr = substr ($referer, $refIdPosition + 7);
						// $courseId = substr ($tmpStr, 0, strpos ($tmpStr, "&"));
						// $courseId = intval ($courseId);
					// }
					// if ($courseId === 0) {
						// $crsPosition = strpos ($referer, "crs_");
						// if ($crsPosition !== false)
						// {
							// $tmpStr = substr ($referer, $crsPosition + 4);
							// $courseId = substr ($tmpStr, 0, strpos ($tmpStr, "&"));
							// $courseId = intval ($courseId);
						// }
					// }
				// }
				//third check: single course of user

				//get $courseId for ref_id of spreed-object
				$refId = $_GET["ref_id"];
				$courseId = $this->object->getCourseRefIdForSpreedObjectRefId($refId);

				//check if user is in course and his/her role
				if ($courseId === 0) {
					$neededCourseFound = false;
				} else {
					$userRoles = $rbacreview->assignedRoles ($ilUser->getId());
					include_once("./Modules/Course/classes/class.ilObjCourse.php");
					$course = new ilObjCourse ($courseId);
					if (
						in_array($course->getDefaultMemberRole(),$userRoles) ||
						in_array($course->getDefaultTutorRole(),$userRoles) ||
						in_array($course->getDefaultAdminRole(),$userRoles)
					) {
						$cmdURL .= $courseId.$refId;
						$specialObjectRights = true;
					}
					if (
						in_array($course->getDefaultTutorRole(),$userRoles) ||
						in_array($course->getDefaultAdminRole(),$userRoles)
					) {
						$adminRights = true;
					}
				}
			}
		}
		if ($specialObjectRights == false && $ilAccess->checkAccess("write", "", $this->object->getRefId())) {
			$adminRights = true;
		}
//+ course/grpid wenn getId in $settings->getObj....
		//$my_tpl->setVariable("HOST","192.168.0.124");//$settings->getHost);

		//$host = '192.168.0.124/api/v1/config';//$settings->getHost;
		// $ch = curl_init();
		// curl_setopt($ch, CURLOPT_URL, $host);
		// curl_setopt($ch, CURLOPT_HEADER, 0);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		// $spreedConfig = curl_exec($ch);
		// curl_close($ch);
		// $my_tpl->setVariable("SPREEDCONFIG", $spreedConfig);
		$my_tpl->setVariable("DISPLAYNAME", $ilUser->getFirstname(). ' '. $ilUser->getLastname());
		$my_tpl->setVariable("BUDDYPICTURE", $this->getBuddyPicture());

		$ilnone = '[';
		if ($settings->get_protected()		 		== true) $ilnone .= "'.roombar',";
		if ($this->object->get_btnSettings()		== false) $ilnone .= "'.btn-settings',";
		if ($this->object->get_btnChat() 			== false && $adminRights == false) $ilnone .= "'.btn-chat',";
		if ($this->object->get_btnLocationshare()	== false) $ilnone .= "'.btn-locationshare',";
		if ($this->object->get_faExpand() 			== false) $ilnone .= "'.fa-expand',";
		if ($this->object->get_memberBtnFileupload() == false && $adminRights == false) $ilnone .= "'.btn-fileupload',";
		$ilnone .= ']';
		$my_tpl->setVariable("ILNONEAR",str_replace(",]" , "]" , $ilnone));

		$ildev = "ui";
		if ($this->object->get_withChat() == true) $ildev .= " withChat";
		if ($this->object->get_moderated() == true) {
			if ($adminRights == true) {
				$ildev .= " withBuddylist";
				$my_tpl->setVariable("INFOTOP", $this->lng->txt('rep_robj_xmvc_info_top_moderator_spreed'));
			} else {
				$ilnone .= "'.buddylist',";
				$my_tpl->setVariable("INFOTOP", $this->lng->txt('rep_robj_xmvc_info_top_moderated_m_spreed'));
			}
		} else {
			$my_tpl->setVariable("INFOTOP", $this->lng->txt('rep_robj_xmvc_info_top_not_moderated_spreed'));
		}
		$my_tpl->setVariable("ILDEFAULT",$ildev);

		$my_tpl->setVariable("cmdURL", $cmdURL);
		$my_tpl->setVariable("windowStarted", $this->lng->txt('rep_robj_xmvc_window_started'));
		$my_tpl->setVariable("windowClosed", $this->lng->txt('rep_robj_xmvc_window_closed'));
		$my_tpl->setVariable("startWindow", $this->lng->txt('rep_robj_xmvc_start_window'));
		$my_tpl->setVariable("INFOBOTTOM", $this->lng->txt('rep_robj_xmvc_info_bottom'));
		$my_tpl->setVariable("infoRequirements", $this->lng->txt('rep_robj_xmvc_info_requirements_spreed'));
		$my_tpl->setVariable("infoRequirementsNotOk", $this->lng->txt('rep_robj_xmvc_info_requirements_not_ok'));
		$my_tpl->setVariable("infoRequirementsPartly", $this->lng->txt('rep_robj_xmvc_info_requirements_partly'));
		$tpl->setContent($my_tpl->get());
	}

    /**
     * @param string $url
     * @param ilApiBBB|ilApiOM|null $vcObj
     * @throws Exception
     */
    private function redirectToPlatformByUrl(string $url, ?ilApiBBB $vcObj = null): void {
        //echo $url; exit;
        if( !is_null($vcObj) && $vcObj instanceof ilApiBBB ) {
            $this->object->setUserLog('bbb', $vcObj);
        }
        header('Status: 303 See Other', false, 303);
        header('Location:' . $url);
        exit;
    }

    /**
     * @param ilApiBBB|ilApiOM $vcObj
     * @return string
     * @throws ilTemplateException
     */
	private function getJoinContent($vcObj)
    {
        global $DIC; /** @var Container $DIC */

        $my_tpl = function($partial) {
            return new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/partial/tpl." . $partial . ".html", true, true);
        };


        $showBtn = (
            ( !$this->object->get_moderated() && $vcObj->isValidAppointmentUser() ) ||
            //( $this->object->get_moderated() && $bbb->hasSessionObject() && $bbb->isValidAppointmentUser() ) ||
            ( $this->object->get_moderated() && ($vcObj->isUserModerator() || $vcObj->isUserAdmin()) ) ||
            ( $this->object->get_moderated() && $vcObj->isMeetingRunning() && $vcObj->isModeratorPresent() && $vcObj->isValidAppointmentUser() )
        );

        $showAdmInfoAppointment = $vcObj->hasSessionObject() && ($vcObj->isUserModerator() || $vcObj->isUserAdmin());



        if( $showBtn )
        {
            $vcType = strtoupper(ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent());
            $tpl = $my_tpl('join_btn');
            $joinBtnText = $this->lng->txt('rep_robj_xmvc_btntext_join_meeting');
            //$joinBtnUrl = $DIC->http()->request()->getUri() . '&amp;start' . $vcType . '=1';
            $rqUri = $DIC->http()->request()->getUri();
            $joinBtnUrl = ILIAS_HTTP_PATH . '/' . substr($rqUri, strpos($rqUri, 'ilias.php')) . '&amp;start' . $vcType . '=1';
            $tpl->setVariable("JOINBTNURL", $joinBtnUrl);
            $tpl->setVariable("JOINBTNTEXT", $joinBtnText);
        } else {
            $tpl = $my_tpl('wait_msg');
            $tpl->setVariable("WAITMSG", $this->lng->txt('rep_robj_xmvc_wait_join_meeting'));
        }

        $content = $tpl->get();

        if( $showAdmInfoAppointment ) {
            $tpl = $my_tpl('adm_info_appointment');
            $tpl->setVariable("ADM_INFO", $this->lng->txt('rep_robj_xmvc_adm_info_appointment'));
            $content .= $tpl->get();
        }

        return $content;






    }

    /**
     * @param ilApiBBB|ilApiOM $vcObj
     * @param array $getRecId
     * @param bool $returnRawData
     * @return array|string
     * @throws ilPluginException
     */
    private function getShowRecordings( $vcObj, array $getRecId = [], bool $returnRawData = false )
    {
        global $DIC; /** @var Container $DIC */

        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());

        $recData = $vcObj->getRecordings();

        if( (bool)sizeof($recData) && (bool)sizeof($getRecId) ) {
            $tmpData = [];
            foreach( $recData as $key => $data ) {
                if( false !== array_search($key, $getRecId) ) {
                    $tmpData[] = $data;
                }
            }
            $recData = $tmpData;
        }

        if( $returnRawData ) {
            return $recData;
        }

        $lng = (object)['txt' => function($var) { return $this->getPlugin()->txt($var); } ];
        //var_dump($this->getPlugin()->txt('start')); exit;
        //$pl = $this->;

        require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcRecordingsTableGUI.php");
        $table = new ilMultiVcRecordingsTableGUI($this);
        $table->initColumns($this->getPlugin());
        $table->setRowTemplate('tpl.recordings_table_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $table->setData($table->addRowSelector($recData));
        $table->setFormAction($DIC->ctrl()->getFormAction($this));
        $table->addCommandButton('confirmDeleteRecords', $DIC->language()->txt('delete'));
        return $table->getHTML();
    }

    private function confirmDeleteRecords()
    {
        global $DIC; /** @var Container $DIC */
        $ilTabs = $DIC['ilTabs'];

        include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
        $ilTabs->activateTab("content");//necessary...

        if ( !isset($_POST['rec_id']) || !(bool)sizeof($_POST['rec_id'])) {
            ilUtil::sendFailure($this->lng->txt('select_one'));
            //$DIC->ctrl()->redirect($this, 'showContent');
            $this->showContent();
            return false;
        }

        include_once("Services/Utilities/classes/class.ilConfirmationGUI.php");
        $c_gui = new ilConfirmationGUI();

        // set confirm/cancel commands
        $c_gui->setFormAction($this->ctrl->getFormAction($this, "showContent"));
        $c_gui->setHeaderText($this->lng->txt("info_delete_sure"));
        $c_gui->setCancel($this->lng->txt("cancel"), "showContent");
        $c_gui->setConfirm($this->lng->txt("confirm"), "deleteRecords");

        // add items to delete
        //include_once('Modules/Course/classes/class.ilCourseFile.php');
        $vcObj = ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent() === 'bbb' ? new ilApiBBB($this) : new ilApiOM($this);
        $records = $this->getShowRecordings($vcObj, $_POST['rec_id'], true);
        foreach ($_POST["rec_id"] as $recId) {
            $key = array_search($recId, $records);
            //$file = new ilCourseFile($file_id);
            $cGuiItemContent = $records[$key]['startTime'] . ' - ' . $records[$key]['endTime'] . ' &nbsp; ' . $records[$key]['playback'];
            $c_gui->addItem("rec_id[]", $recId, $cGuiItemContent);
        }

        $this->tpl->setContent($c_gui->getHTML());
    }

    private function deleteRecords(): bool
    {
        $success = false;


        try {
            $vcObj = ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent() === 'bbb' ? new ilApiBBB($this) : new ilApiOM($this);
            //$bbb = new ilApiBBB($this);
        } catch (Exception $e) {
            $vcObj = new StdClass();
        }

        if( !($vcObj instanceof ilApiBBB) && !($vcObj instanceof ilApiOM) ) {
            return $success;
        }

        if(  $this->object->isRecordingAllowed() && isset($_POST) && isset($_POST['rec_id']) ) {
            foreach ($_POST['rec_id'] as $recId) {
                $vcObj->deleteRecord($recId);
                if( !$success ) {
                    $success = true;
                }
            } // EOF foreach ($_POST['rec_id'] as $item)
            return $success;
        }
        return $success;
    }

    private function prepareRoomOM(ilApiOM $om)
    {
        if( !($roomId = $this->object->getRoomId()) ) {
            $roomId = $om->createRoom();
            $this->object->updateRoomId($roomId);
        } else {
            // only proc if debug is true in plugin.ini
            if( !!(bool)$om->getPluginIniSet('debug') ) {
                $rVal = $om->updateRoom($roomId);
                // if $roomId doesn't exist on omServer, we'll get a new one
                if( $rVal !== $roomId ) {
                    $this->object->updateRoomId($rVal);
                }
            }
        }
    }

}

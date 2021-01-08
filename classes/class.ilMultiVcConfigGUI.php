<?php

use ILIAS\DI\Container;

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
include_once("Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
require_once dirname(__FILE__) . '/class.ilMultiVcUtil.php';

/**
 * MultiVc configuration user interface class
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 *
 * @ilCtrl_Calls ilMultiVcConfigGUI: ilCommonActionDispatcherGUI
 */
class ilMultiVcConfigGUI extends ilPluginConfigGUI
{
	const ASTERISK_PW = '******';
	/** @var ilMultiVcConfig $object */
	private $object;
	/**
	 * @var ilPropertyFormGUI
	 */
	private $form;

	/** @var Container $dic */
	private $dic;


	public function __construct()
	{
		global $DIC; /** @var Container $DIC */
		$this->dic = $DIC;

	}

	/**
	 * Handles all commmands, default is "configure"
	 * @param $cmd
	 * @throws Exception
	 */
	function performCommand($cmd)
	{
		global $DIC; /** @var Container $DIC */
		$tpl = $DIC->ui()->mainTemplate();
		$ilCtrl = $DIC->ctrl();
		$cmd = $ilCtrl->getCmd($this);

		switch ($cmd)
		{
			case 'selectNewMultiVcConn':
 				$this->initTabs('edit_type');
				$this->$cmd();
				break;
			case 'configureNewMultiVcConn':
				$this->object = new ilMultiVcConfig();
				$ilCtrl->setParameter($this, 'configureNewMultiVcConn', $_POST['showcontent']);
				$this->initTabs('edit_type');
				$this->$cmd();
				break;
			case 'editMultiVcConn':
			case 'deleteMultiVcConn':
				$this->object = new ilMultiVcConfig($_GET['conn_id']);
				if( isset($_GET['configureNewMultiVcConn']) ) {
					$ilCtrl->setParameter($this, 'configureNewMultiVcConn', $_GET['configureNewMultiVcConn']);
				}
				$ilCtrl->setParameter($this, 'conn_id', $_GET['conn_id']);
				$this->initTabs('edit_type');
				$this->$cmd();
				break;
			case "save":
				if( isset($_GET['configureNewMultiVcConn']) ) {
					$ilCtrl->setParameter($this, 'configureNewMultiVcConn', $_GET['configureNewMultiVcConn']);
				}
				$this->initTabs('edit_type');
				$this->$cmd();
				break;
			case "configure":
				$this->initTabs();
				$this->$cmd();
				break;
			case "overviewUses":
				$this->initTabs();
				$this->initOverviewUsesTableGUI($cmd);
				break;
			case "userLog":
			case "applyFilterUserLog":
			case "resetFilterUserLog":
			case "downloadUserLog":
				$this->initTabs();
				$this->initUserLogTableGUI($cmd);
				break;
			default:
				$this->initTabs();
				if (!$cmd)
				{
					$cmd = "configure";
				}
				$this->$cmd();
				break;

		}
	}

	/**
	 * Init Tabs
	 *
	 * @param string	mode ('edit_type' or '')
	 */
	function initTabs($a_mode = "")
	{
		global $DIC; /** @var Container $DIC */
		$ilCtrl = $DIC->ctrl();
		$ilTabs = $DIC->tabs();
		$lng = $DIC->language();

		switch ($a_mode)
		{
			case "edit_type":
				$ilTabs->clearTargets();
				$ilTabs->setBackTarget(
					$this->plugin_object->txt('configure'),
					$ilCtrl->getLinkTarget($this, 'configure')
				);
				break;

			default:
				$ilTabs->addTab("configure",
					$this->plugin_object->txt('configure'),
					$ilCtrl->getLinkTarget($this, 'configure')
				);
				$ilTabs->addTab("overview_uses",
					$this->plugin_object->txt('overview_uses'),
					$ilCtrl->getLinkTarget($this, 'overviewUses')
				);
				$ilTabs->addTab("report_log_max",
					$this->plugin_object->txt('report_log_max'),
					$ilCtrl->getLinkTarget($this, 'reportLogMax')
				);
				$ilTabs->addTab("user_log",
					$this->plugin_object->txt('user_log'),
					$ilCtrl->getLinkTarget($this, 'userLog')
				);
				break;
		}
	}

	/**
	 * Configure screen
	 */
	function configure()
	{
		global $DIC; /** @var Container $DIC */
		$tpl = $DIC->ui()->mainTemplate();
		$ilTabs = $DIC->tabs();
		$ilTabs->activateTab('configure');

		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConnTableGUI.php');
		$table_gui = new ilMultiVcConnTableGUI($this, 'configure');
		$table_gui->init($this);
		$tpl->setContent($table_gui->getHTML());
	}

	private function createMulitVcConn()
	{
		global $DIC; /** @var Container $DIC */
		$ilCtrl = $DIC->ctrl();
		$tpl = $DIC->ui()->mainTemplate();
		//$ilTabs = $DIC->tabs();
		//$ilTabs->activateTab('configure');

		$ilCtrl->redirect($this, "selectNewMultiVcConn");
		/*
		$this->object = ilMultiVcConfig::getInstance();
		$this->object->setDefaultValues();
		$this->object->setShowContent('bbb');
		$this->initConfigurationForm('createMulitVcConn');
		$this->getValues();
		$tpl->setContent($this->form->getHTML());

		/*
		$this->object->create();
		$ilCtrl->setParameter($this, 'conn_id', $this->object->getConnId());
		$ilCtrl->redirect($this, "editMultiVcConn");
		*/
	}

	private function selectNewMultiVcConn()
	{
		global $DIC; /** @var Container $DIC */
		$ilCtrl = $DIC->ctrl();
		$tpl = $DIC->ui()->mainTemplate();
		//$ilTabs = $DIC->tabs();
		//$ilTabs->activateTab('configure');

		$this->object = ilMultiVcConfig::getInstance();
		$this->object->setDefaultValues();
		$this->object->setShowContent('bbb');
		$this->initConfigurationForm('selectNewMultiVcConn');
		$this->getValues();
		$tpl->setContent($this->form->getHTML());

		/*
		$this->object->create();
		$ilCtrl->setParameter($this, 'conn_id', $this->object->getConnId());
		$ilCtrl->redirect($this, "editMultiVcConn");
		*/
	}

	private function configureNewMultiVcConn()
	{
		global $DIC; /** @var Container $DIC */
		$ilCtrl = $DIC->ctrl();
		//$ilCtrl->setParameter($this, 'multiVcConn', $_POST['showcontent']);
		$ilCtrl->redirect($this, "editMultiVcConn");
	}

	private function editMultiVcConn()
	{
		global $DIC; /** @var Container $DIC */
		$tpl = $DIC->ui()->mainTemplate();
		$ilTabs = $DIC->tabs();
		$ilTabs->activateTab('configure');

		$this->object = ilMultiVcConfig::getInstance($_GET['conn_id']);
		$cmd = isset($_GET['configureNewMultiVcConn']) ? 'configureNewMultiVcConn' : '';
		$this->initConfigurationForm($cmd);
		$this->getValues();
		$tpl->setContent($this->form->getHTML());
	}

	private function deleteMultiVcConn()
	{
		global $DIC; /** @var Container $DIC */
		$ilCtrl = $DIC->ctrl();
		$tpl = $DIC->ui()->mainTemplate();
		$lng = $DIC->language();
		$pl = $this->getPluginObject();
		$this->object = ilMultiVcConfig::getInstance($_GET['conn_id']);

		require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');

		$gui = new ilConfirmationGUI();
		$gui->setFormAction($ilCtrl->getFormAction($this));
		$gui->setHeaderText($pl->txt('delete_conn'));
		$gui->addItem('conn_id', $this->object->getConnId(), $this->object->getTitle());
		$gui->setConfirm($pl->txt('delete'), 'deleteMultiVcConnConfirmed');
		$gui->setCancel($lng->txt('cancel'), 'configure');

		$tpl->setContent($gui->getHTML());
	}

	private function deleteMultiVcConnConfirmed()
	{
		global $DIC; /** @var Container $DIC */
		$ilCtrl = $DIC->ctrl();
		$this->object = ilMultiVcConfig::getInstance($_GET['conn_id']);
		$pl = $this->getPluginObject();

		ilMultiVcConfig::_deleteMultiVcConn($_GET['conn_id']);
		ilUtil::sendSuccess($pl->txt('conn_deleted'), true);
		$ilCtrl->redirect($this, 'configure');
	}

	private function getFormItemsForPlatformSpreed(ilSelectInputGUI $combo)
	{
		global $DIC; /** @var Container $DIC */
		$lng = $DIC->language();
		$ilCtrl = $DIC->ctrl();
		$ilDB = $DIC->database();
		$ilSetting = $DIC->settings();

		$pl = $this->getPluginObject();

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

		return $combo;
	}

	private function getFormItemsForPlatformBBB(ilSelectInputGUI $combo)
	{
		global $DIC; /** @var Container $DIC */
		$lng = $DIC->language();
		$ilCtrl = $DIC->ctrl();
		$ilDB = $DIC->database();
		$ilSetting = $DIC->settings();

		$pl = $this->getPluginObject();

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

		// guestlink
		$cb = new ilCheckboxInputGUI($pl->txt("guestlink_choose"), "guestlink_choose");
		$cb->setRequired(false);
		$cb->setInfo($pl->txt("guestlink_choose_info"));
		$combo->addSubItem($cb);

		$cb = new ilCheckboxInputGUI($pl->txt("guestlink_default"), "guestlink_default");
		$cb->setRequired(false);
		$cb->setInfo($pl->txt("guestlink_default_info"));
		$combo->addSubItem($cb);

		return $combo;
	}

	private function getFormItemsForPlatformOM(ilSelectInputGUI $combo)
	{
		global $DIC; /** @var Container $DIC */
		$lng = $DIC->language();
		$ilCtrl = $DIC->ctrl();
		$ilDB = $DIC->database();
		$ilSetting = $DIC->settings();

		$pl = $this->getPluginObject();

		$ti = new ilTextInputGUI($pl->txt("om_svr_public_url"), "svr_public_url");
		$ti->setRequired(true);
		$ti->setMaxLength(256);
		$ti->setSize(60);
		$ti->setInfo($pl->txt("info_svr_public_url"));
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

		/*
		$cb = new ilCheckboxInputGUI($pl->txt("private_chat_choose"), "private_chat_choose");
		//$cb->setRequired(false);
		$cb->setInfo($pl->txt("private_chat_choose_info"));
		$combo->addSubItem($cb);


		$cb = new ilCheckboxInputGUI($pl->txt("private_chat_default"), "private_chat_default");
		$cb->setRequired(false);
		$cb->setInfo($pl->txt("private_chat_default_info"));
		$combo->addSubItem($cb);


		//
		$cb = new ilCheckboxInputGUI($pl->txt("om_with_chat_choose"), "cb_with_chat_choose");
		$cb->setRequired(false);
		$cb->setInfo($pl->txt("om_with_chat_choose_info"));
		$combo->addSubItem($cb);

		//
		$cb = new ilCheckboxInputGUI($pl->txt("om_with_chat_default"), "cb_with_chat_default");
		$cb->setRequired(false);
		$cb->setInfo($pl->txt("om_with_chat_default_info") . " " . $pl->txt("config_help_begin") . " " . $pl->txt("with_chat_info"));
		$combo->addSubItem($cb);
		*/


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


		/*
		$cb = new ilCheckboxInputGUI($pl->txt("cam_only_for_moderator_choose"), "cam_only_for_moderator_choose");
		$cb->setRequired(false);
		$cb->setInfo($pl->txt("cam_only_for_moderator_choose_info"));
		$combo->addSubItem($cb);

		$cb = new ilCheckboxInputGUI($pl->txt("cam_only_for_moderator_default"), "cam_only_for_moderator_default");
		$cb->setRequired(false);
		$cb->setInfo($pl->txt("cam_only_for_moderator_default_info"));
		$combo->addSubItem($cb);
		*/
		return $combo;
	}

	private function initConfigurationFormByPlatform(string $platform, string $cmd = '')
	{
		global $DIC; /** @var Container $DIC */
		$lng = $DIC->language();
		$ilCtrl = $DIC->ctrl();
		$ilSetting = $DIC->settings();
		$ilTpl = $DIC->ui()->mainTemplate();

		$pl = $this->getPluginObject();

		$combo = new ilSelectInputGUI($pl->txt("showcontent"), 'showcontent');
		$combo->setRequired(true);
		if( $cmd ===  'configureNewMultiVcConn' || isset($_GET['configureNewMultiVcConn']) ) { // editMultiVcConn configureNewMultiVcConn
			$combo->setOptions([$platform => ilMultiVcConfig::AVAILABLE_VC_CONN[$_GET['configureNewMultiVcConn']]]);
		} else {
			$combo->setOptions(ilMultiVcConfig::AVAILABLE_VC_CONN);
		}

		$combo->setHideSubForm(false, '=== \'' . $this->object->getShowContent() . '\'' );
		$combo->setInfo($pl->txt('info_platform_chg_reset_data'));
		if( $cmd === 'selectNewMultiVcConn' ) { // isset( $_POST['cmd']['createMultiVcConn'])
			//var_dump($_POST); exit;
			$this->form->addItem($combo);
			return $this->form;
		}

		$ti = new ilTextInputGUI($pl->txt("title"), "title");
		$ti->setRequired(true);
		$ti->setMaxLength(256);
		$ti->setSize(60);
		$ti->setInfo($pl->txt("info_title"));
		$combo->addSubItem($ti);

		// availability
		$item = new ilSelectInputGUI($this->plugin_object->txt('conf_availability'), 'cb_availability');
		$item->setOptions (
			array(
				ilMultiVcConfig::AVAILABILITY_CREATE => $this->plugin_object->txt('conf_availability_' . ilMultiVcConfig::AVAILABILITY_CREATE),
				ilMultiVcConfig::AVAILABILITY_EXISTING => $this->plugin_object->txt('conf_availability_' . ilMultiVcConfig::AVAILABILITY_EXISTING),
				ilMultiVcConfig::AVAILABILITY_NONE => $this->plugin_object->txt('conf_availability_' . ilMultiVcConfig::AVAILABILITY_NONE)
			)
		);
		$item->setInfo($this->plugin_object->txt('info_availability'));
		$item->setRequired(true);
		$combo->addSubItem($item);

		// Hint TextArea
		$ti = new ilTextInputGUI($pl->txt("hint"), "hint");
		$ti->setInfo($pl->txt("info_hint"));
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

		$cmd = 'getFormItemsForPlatform' . strtoupper($platform);
		$combo = $this->$cmd($combo);
		$this->form->addItem($combo);

		return $this->form;
	}

	/**
	 * Init configuration form.
	 *
	 * @param string $cmd
	 * @return object form object
	 */
	public function initConfigurationForm(string $cmd = '')
	{
		global $DIC; /** @var Container $DIC */
		$lng = $DIC->language();
		$ilCtrl = $DIC->ctrl();
		$ilDB = $DIC->database();
		$ilSetting = $DIC->settings();


		$pl = $this->getPluginObject();
		$this->getPluginObject()->includeClass('class.ilMultiVcConfig.php');
		//$this->object = ilMultiVcConfig::getInstance($connId);

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();


		$this->form->setTitle($pl->txt("plugin_configuration"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
		if( $cmd === 'selectNewMultiVcConn' ) {
			$this->form->addCommandButton("configureNewMultiVcConn", $pl->txt("configure_add"));
		} elseif( $cmd ===  'configureNewMultiVcConn' || isset($ilCtrl->getParameterArray($this)['configureNewMultiVcConn']) ) {
			$this->form->addCommandButton("save", $lng->txt("create"));
		} else {
			$this->form->addCommandButton("save", $lng->txt("save"));
		}
		$this->form->addCommandButton("configure", $lng->txt("cancel"));


		############################################################################################################################
		if( !($this->object instanceof ilMultiVcConfig) ) {
			$this->object = ilMultiVcConfig::getInstance();
			//$this->object->setDefaultValues();
		}
		if( null === $this->object->getShowContent() ) {
			if( isset($_POST['showcontent']) ) {
				$plattform = $_POST['showcontent'];
			} else {
				$plattform = $ilCtrl->getParameterArray($this)['configureNewMultiVcConn'];
			}
			$this->object->setShowContent($plattform);
		}
		$this->form = $this->initConfigurationFormByPlatform($this->object->getShowContent(), $cmd);
		if( $cmd === 'selectNewMultiVcConn' ) {
			return $this->form;
		}
		############################################################################################################################
		$defField = function($name, $value) {
			$field = new ilHiddenInputGUI($name);
			$field->setValue($value);
			return $field;
		};


		$formFieldItems = $this->form->getInputItemsRecursive();
		$formHasField = [];
		/** @var ilSelectInputGUI|ilTextInputGUI|ilCheckboxInputGUI $item */
		foreach ($formFieldItems as $key => $item) {
			$formHasField[] = $item->getPostVar();
		}
		foreach ( $this->getDefaultFieldAndValues() as $name => $value ) {
			if( false === array_search($name, $formHasField) ) {
				$this->form->addItem( $defField($name, $value) );
			}
		}


		return $this->form;
	}

	public function getValues()
	{
		$values["conn_id"] = $this->object->getConnId();
		$values["title"] = $this->object->getTitle();
		$values["hint"] = $this->object->getHint();
		$values["cb_availability"] = $this->object->getAvailability();
		$values["frmSpreedUrl"] = $this->object->get_spreedUrl();
		$values["frmObjIdsSpecial"] = $this->object->get_objIdsSpecial();
		$values["cb_protected"] = $this->object->get_protected();
		$values["cb_moderated_choose"] = $this->object->get_moderatedChoose();
		$values["cb_moderated_default"] = $this->object->get_moderatedDefault();
		$values["cb_btn_settings_choose"] = $this->object->get_btnSettingsChoose();
		$values["cb_btn_settings_default"] = $this->object->get_btnSettingsDefault();
		$values["cb_btn_chat_choose"] = $this->object->get_btnChatChoose();
		$values["cb_btn_chat_default"] = $this->object->get_btnChatDefault();
		$values["cb_with_chat_choose"] = $this->object->get_withChatChoose();
		$values["cb_with_chat_default"] = $this->object->get_withChatDefault();
		$values["cb_btn_locationshare_choose"] = $this->object->get_btnLocationshareChoose();
		$values["cb_btn_locationshare_default"] = $this->object->get_btnLocationshareDefault();
		$values["cb_member_btn_fileupload_choose"] = $this->object->get_memberBtnFileuploadChoose();
		$values["cb_member_btn_fileupload_default"] = $this->object->get_memberBtnFileuploadDefault();
		$values["cb_fa_expand_default"] = $this->object->get_faExpandDefault();
		$values["svr_public_url"] = $this->object->getSvrPublicUrl();
		$values["svr_public_port"] = $this->object->getSvrPublicPort();
		$values["svr_private_url"] = $this->object->getSvrPrivateUrl();
		$values["svr_private_port"] = $this->object->getSvrPrivatePort();
		$values["svr_salt"] = $this->object->getSvrSalt();
		$values["svr_username"] = $this->object->getSvrUsername();
		$values["max_participants"] = $this->object->getMaxParticipants();
		$values["showcontent"] = $this->object->getShowContent();
		$values["private_chat_choose"] = $this->object->isPrivateChatChoose();
		$values["private_chat_default"] = $this->object->isPrivateChatDefault();
		$values["recording_choose"] = $this->object->isRecordChoose();
		$values["recording_default"] = $this->object->isRecordDefault();
		$values["recording_only_for_moderated_rooms_default"] = $this->object->isRecordOnlyForModeratedRoomsDefault();
		$values["cam_only_for_moderator_choose"] = $this->object->isCamOnlyForModeratorChoose();
		$values["cam_only_for_moderator_default"] = $this->object->isCamOnlyForModeratorDefault();
		$values["guestlink_choose"] = $this->object->isGuestlinkChoose();
		$values["guestlink_default"] = $this->object->isGuestlinkDefault();
		$values["add_presentation_url"] = $this->object->getAddPresentationUrl();
		$values["add_welcome_text"] = $this->object->issetAddWelcomeText();
		$values["disable_sip"] = $this->object->getDisableSip();
		$values["hide_username_logs"] = $this->object->getHideUsernameInLogs();

		//$values["recording_only_for_moderator_choose"] = $this->object->isRecordOnlyForModeratorChoose();

		if( $this->object->hasInitialDbEntry() ) {
			$values["svr_salt"] = $values["svr_salt_retype"] = self::ASTERISK_PW;
		}

		$this->form->setValuesByArray($values);
	}

	/**
	 * Save form input
	 *
	 */
	public function save()
	{
		global $DIC; /** @var Container $DIC */
		$lng = $DIC->language();
		$ilCtrl = $DIC->ctrl();
		$tpl = $DIC->ui()->mainTemplate();

		$pl = $this->getPluginObject();

		//$this->object = ilMultiVcConfig::getInstance($_GET['conn_id']);
		$form = $this->initConfigurationForm();

		$platformChanged = false;
		if( $_POST["showcontent"] !== $this->object->getShowContent() ) {
			$platformChanged = true;
			$formFieldItems = $form->getInputItemsRecursive();
			/** @var ilSelectInputGUI|ilTextInputGUI|ilCheckboxInputGUI $item */
			foreach ($formFieldItems as $key => $item) {
				$item->setRequired(false);
				$form->removeItemByPostVar($item->getPostVar());
				$form->addItem($item);
			}
		}

		$urlCheck = true;
		/** @var  ilTextInputGUI $field */
		$field = $form->getItemByPostVar('add_presentation_url');
		if( (bool)strlen(filter_var($_POST['add_presentation_url'], FILTER_SANITIZE_URL)) ) {
			$field->setValidationRegexp('%^https://.*%');
			if( !$field->checkInput() ) {
				$urlCheck = false;
				ilUtil::sendFailure($lng->txt("form_input_not_valid"));
			}
		}
		
		if ( $urlCheck && $form->checkInput() )
		{
			if( !$this->object->hasInitialDbEntry() && $platformChanged )
			{
				$this->object->setDefaultValues();
			} else {
		
				$this->object->setConnId(!!(bool)($connId = $form->getInput("conn_id")) ? $connId : null);
				$this->object->setTitle($form->getInput("title"));
				$this->object->setHint((string)ilMultiVUtil::removeUnsafeChars($form->getInput("hint")) );
				$this->object->setAvailability((int)$form->getInput("cb_availability"));
				$this->object->set_spreedUrl($form->getInput("frmSpreedUrl"));
				$this->object->set_objIdsSpecial($form->getInput("frmObjIdsSpecial"));
				$this->object->set_protected($form->getInput("cb_protected"));
				$this->object->set_moderatedChoose($form->getInput("cb_moderated_choose"));
				$this->object->set_moderatedDefault($form->getInput("cb_moderated_default"));
				$this->object->set_btnSettingsChoose($form->getInput("cb_btn_settings_choose"));
				$this->object->set_btnSettingsDefault($form->getInput("cb_btn_settings_default"));
				$this->object->set_btnChatChoose($form->getInput("cb_btn_chat_choose"));
				$this->object->set_btnChatDefault($form->getInput("cb_btn_chat_default"));
				$this->object->set_withChatChoose($form->getInput("cb_with_chat_choose"));
				$this->object->set_withChatDefault($form->getInput("cb_with_chat_default"));
				$this->object->set_btnLocationshareChoose($form->getInput("cb_btn_locationshare_choose"));
				$this->object->set_btnLocationshareDefault($form->getInput("cb_btn_locationshare_default"));
				$this->object->set_memberBtnFileuploadChoose($form->getInput("cb_member_btn_fileupload_choose"));
				$this->object->set_memberBtnFileuploadDefault($form->getInput("cb_member_btn_fileupload_default"));
				$this->object->set_faExpandDefault($form->getInput("cb_fa_expand_default"));
				$this->object->setSvrPublicUrl($form->getInput("svr_public_url"));
				$this->object->setSvrPublicPort((int)$form->getInput("svr_public_port"));
				$this->object->setSvrPrivateUrl($form->getInput("svr_private_url"));
				$this->object->setSvrPrivatePort((int)$form->getInput("svr_private_port"));
				$this->object->setSvrUsername($form->getInput("svr_username"));
				$this->object->setMaxParticipants((int)$form->getInput("max_participants"));
				$this->object->setPrivateChatChoose( (bool)$form->getInput("private_chat_choose") );
				$this->object->setPrivateChatDefault( (bool)$form->getInput("private_chat_default") );
				$this->object->setRecordChoose( (bool)$form->getInput("recording_choose") );
				$this->object->setRecordDefault( (bool)$form->getInput("recording_default") );
				$this->object->setRecordOnlyForModeratedRoomsDefault( (bool)$form->getInput("recording_only_for_moderated_rooms_default") );
				//$this->object->setRecordOnlyForModeratorChoose( (bool)$form->getInput("recording_only_for_moderator_choose") );
				//$this->object->setRecordOnlyForModeratorDefault( (bool)$form->getInput("recording_only_for_moderated_rooms_default") );
				$this->object->setCamOnlyForModeratorChoose( (bool)$form->getInput("cam_only_for_moderator_choose") );
				$this->object->setCamOnlyForModeratorDefault( (bool)$form->getInput("cam_only_for_moderator_default") );
				$this->object->setGuestlinkChoose( (bool)$form->getInput("guestlink_choose") );
				$this->object->setGuestlinkDefault( (bool)$form->getInput("guestlink_default") );
				$this->object->setAddPresentationUrl( $form->getInput("add_presentation_url") );
				$this->object->setAddWelcomeText( $form->getInput("add_welcome_text") );
				$this->object->setDisableSip( $form->getInput("disable_sip") );
				$this->object->setHideUsernameInLogs( $form->getInput("hide_username_logs") );

				if ($form->getInput("showcontent") != "spreed") {
					if( $form->getInput('svr_salt') !== self::ASTERISK_PW ) {
						//var_dump([$form->getInput('svr_salt'), self::ASTERISK_PW]); exit;
						$this->object->setSvrSalt($form->getInput("svr_salt"));
					} else {
						$this->object->keepSvrSalt();
					}
				} else {
					$this->object->setSvrSalt('');
				}

			}

			$this->object->setShowContent($form->getInput("showcontent"));
			//var_dump($this->object->getSvrPrivateUrl());
			$this->object->save((bool)$form->getInput("conn_id"));
			//var_dump($this->object); exit;


			require_once "Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilObjMultiVc.php";
			ilObjMultiVc::getInstance()->fillEmptyPasswordsBBBVCR();
			ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
			$ilCtrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}

	public function getDefaultFieldAndValues()
	{
		$values = [];
		$values['conn_id'] = $this->object->getConnId();;
		$values['title'] = $this->object->getTitle();
		$values['cb_availability'] = $this->object->getAvailability();
		$values['hint'] = $this->object->getHint();
		$values["frmSpreedUrl"] ='';
		$values["frmObjIdsSpecial"] = $this->object->get_objIdsSpecial();
		$values["cb_protected"] = 1;
		$values["cb_moderated_choose"] = 1;
		$values["cb_moderated_default"] = 1;
		$values["cb_btn_settings_choose"] = 0;
		$values["cb_btn_settings_default"] = 0;
		$values["cb_btn_chat_choose"] = 0;
		$values["cb_btn_chat_default"] = 0;
		$values["cb_with_chat_choose"] = 0;
		$values["cb_with_chat_default"] = 1;
		$values["cb_btn_locationshare_choose"] = 0;
		$values["cb_btn_locationshare_default"] = 0;
		$values["cb_member_btn_fileupload_choose"] = 0;
		$values["cb_member_btn_fileupload_default"] = 0;
		$values["cb_fa_expand_default"] = 0;
		$values["svr_public_url"] ='';
		$values["svr_public_port"] ='';
		$values["svr_private_url"] = '';
		$values["svr_private_port"] ='';
		$values["svr_salt"] ='';
		$values["svr_username"] ='';
		$values["max_participants"] = 20;
		$values["showcontent"] = 'spreed';
		$values["private_chat_choose"] = 0;
		$values["private_chat_default"] = 1;
		$values["recording_choose"] = 0;
		$values["recording_default"] = 0;
		$values["recording_only_for_moderated_rooms_default"] = 1;
		$values["cam_only_for_moderator_choose"] = 0;
		$values["cam_only_for_moderator_default"] = 0;
		$values["guestlink_choose"] = 0;
		$values["guestlink_default"] = 0;
		$values["add_presentation_url"] = 'https://';
		$values["add_welcome_text"] = 0;
		$values["disable_sip"] = 0;
		$values["hide_username_logs"] = 1;

		return $values;
	}


	// Max Users

	public function reportLogMax($html = true)
	{
		global $DIC; /** @var Container $DIC */
		$tpl = $DIC->ui()->mainTemplate();
		$ilTabs = $DIC->tabs();

		$ilTabs->activateTab('report_log_max');

		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcReportLogMaxTableGUI.php');
		$table_gui = new ilMultiVcReportLogMaxTableGUI($this, 'reportLogMax');
		$table_gui->init($this);
		$tpl->setContent($table_gui->getHTML());
		if( !$html ) {
			$table_gui->downloadCsv();
		}
	}

	public function downloadCsv() {
		$this->reportLogMax(false);
	}

	public function initOverviewUsesTableGUI(string $cmd, $html = true)
	{
		global $DIC; /** @var Container $DIC */
		$tpl = $DIC->ui()->mainTemplate();
		$ilTabs = $DIC->tabs();
		$guiClass = 'ilMultiVcOverviewUsesTableGUI';

		$ilTabs->activateTab('overview_uses');

		require_once(dirname(__FILE__) . '/class.ilMultiVcOverviewUsesTableGUI.php');

		$rows = ilMultiVcConfig::_getMultiVcConnOverviewUses();
		foreach ($rows as $key => $row) {
			$rows[$key]['parentLink'] = ilLink::_getLink($row['parentRefId']);
			$rows[$key]['link'] = ilLink::_getLink($row['xmvcRefId']);
		} // EOF foreach ($rows as $key => $row)
		#var_dump($rows); exit;

		$table_gui = new ilMultiVcOverviewUsesTableGUI($this, $cmd);
		$table_gui->setData($rows);
		$table_gui->init($this);
		$tpl->setContent($table_gui->getHTML());

		if( !$html ) {
			#$table_gui->downloadCsv();
		}
	}

	// private function createRowFromUses($conn)
	// {
		// global $DIC; /** @var Container $DIC */
		// $row = [];
		// foreach ($conn['uses'] as $data) {
			// $allReferences = ilObject::_getAllReferences($data['obj_id']);
			// foreach ($allReferences as $key => $refId) {
				// $path = array_reverse($DIC->repositoryTree()->getPathFull($refId));
				// $keys = array_keys($path);
				// $parent = $path[$keys[1]];
				// $item = $path[$keys[0]];
				// $isInTrash = false;
				// if( ilObject::_isInTrash($refId) )  {
					// $parent = $item;
					// $item = [
						// 'ref_id' => $refId,
						// // Todo: Title from DB?
						// 'title' => ilMultiVcConfig::_getObjTitleByObjId(ilObject::_lookupObjId($refId)), # ilObjectFactory::getInstanceByRefId($refId)->getTitle(),
					// ];
					// $isInTrash = true;
				// }
				// $numReferences = sizeof($allReferences);
				// $hasReferences = 1 < $numReferences;
				// $isLink = false;
				// /*
				// if( $hasReferences ) {
					// $isLink = false; // (int)(ilObjectFactory::getInstanceByObjId($item['obj_id'])->getRefId()) !== (int)$item['ref_id'];
				// }
				// */
				// #var_dump([$parent, $item, $isLink, $allReferences]);
				// #exit;
				// $row[$refId] = [
					// 'connTitle' => $conn['title'],
					// 'refId' => $refId,
					// 'title' => $item['title'],
					// 'link'	=> ilLink::_getLink($item['ref_id']),
					// 'isInTrash' => $isInTrash, #ilObject::_isInTrash($refId),
					// 'hasReferences' => $hasReferences,
					// 'numReferences' => $numReferences,
					// 'isLink' => $isLink,
					// 'parent' => [
						// 'title' => $parent['title'],
						// 'link'	=> ilLink::_getLink($parent['ref_id']),
						// 'ref_id' => $parent['ref_id'],
					// ]
				// ];
			// } // EOF foreach (ilObject::_getAllReferences($data['obj_id']) as $allReference)
		// } // EOF foreach ($uses as $data)
		// #var_dump($row); exit;
		// #var_dump($uses); exit;
		// return $row;
	// }

	public function confirmDeleteUsesMultiVcConn() {
		global $DIC; /** @var Container $DIC */

		$DIC->tabs()->activateTab('overview_uses');

		$item_ref_id = isset($_GET['item_ref_id']) ? (int)filter_var($_GET['item_ref_id'], FILTER_SANITIZE_NUMBER_INT) : '';
		$parent_ref_id = isset($_GET['parent_ref_id']) ? (int)filter_var($_GET['parent_ref_id'], FILTER_SANITIZE_NUMBER_INT) : '';
		$itemType = (bool)$item_ref_id ? ilObject::_lookupType($item_ref_id, true) : 'undefined';

		if( !(bool)$item_ref_id || !(bool)$parent_ref_id || $itemType !== $this->getPluginObject()->getId() ) {
			$this->returnFailure($DIC->language()->txt('select_one'));
		}

		$c_gui = new ilConfirmationGUI();

		// set confirm/cancel commands
		$c_gui->setFormAction($DIC->ctrl()->getFormAction($this, "overviewUses"));
		$c_gui->setHeaderText($DIC->language()->txt("rep_robj_xmvc_info_delete_vc_sure"));
		$c_gui->setCancel($DIC->language()->txt("cancel"), "overviewUses");
		$c_gui->setConfirm($DIC->language()->txt("confirm"), "deleteUsesMultiVcConn");

		// add items to delete
		//include_once('Modules/Course/classes/class.ilCourseFile.php');
		$cGuiItemContent = filter_var($_GET['cGuiItemContent'], FILTER_SANITIZE_STRING);
		$c_gui->addItem("item_ref_id", $item_ref_id, $cGuiItemContent);
		$c_gui->addHiddenItem('parent_ref_id', $parent_ref_id);
		$DIC->ui()->mainTemplate()->setContent($c_gui->getHTML());

	}

	public function deleteUsesMultiVcConn()
	{
		global $DIC; /** @var Container $DIC */

		$item_ref_id = isset($_POST['item_ref_id']) ? (int)filter_var($_POST['item_ref_id'], FILTER_SANITIZE_NUMBER_INT) : '';
		$parent_ref_id = isset($_POST['parent_ref_id']) ? (int)filter_var($_POST['parent_ref_id'], FILTER_SANITIZE_NUMBER_INT) : '';
		$itemType = (bool)$item_ref_id ? ilObject::_lookupType($item_ref_id, true) : 'undefined';

		if( !(bool)$item_ref_id || !(bool)$parent_ref_id || $itemType !== $this->getPluginObject()->getId() ) {
			$this->returnFailure($DIC->language()->txt('select_one'));
		}

		try {
			if( ilObject::_isInTrash($item_ref_id) || !$DIC->settings()->get('enable_trash') ) {
				ilRepUtil::removeObjectsFromSystem([$item_ref_id]);
			} else {
				ilRepUtil::deleteObjects($parent_ref_id, [$item_ref_id]);
			}
			ilUtil::sendSuccess($DIC->language()->txt('deleted'));
		} catch (ilRepositoryException $e) {
			ilUtil::sendFailure($DIC->language()->txt('not_deleted'));
		}

		$DIC->ctrl()->redirect($this, 'overviewUses');
	}

	/**
	 * @param string $cmd
	 * @throws Exception
	 */
	public function initUserLogTableGUI(string $cmd) {

		$tpl = $this->dic->ui()->mainTemplate();
		$ilTabs = $this->dic->tabs();

		$ilTabs->activateTab('user_log');

		require_once dirname(__FILE__) . '/class.ilMultiVcUserLogTableGUI.php';
		$userLogTableGui = new ilMultiVcUserLogTableGUI($this, $cmd);
		//$userLogTableGui->init();
		$tpl->setContent($userLogTableGui->getHTML());

		if( $cmd === 'downloadUserLog' ) {
			$userLogTableGui->downloadCsv();
		}
	}


	private function returnFailure($txt = 'error', $redirect = true, $gui = 'overviewUses') {
		global $DIC; /** @var Container $DIC */

		ilUtil::sendFailure($txt);
		$redirect
			? $DIC->ctrl()->redirect($this, $gui)
			: $this->initTableGUI($gui);
		return false;
	}


}
?>

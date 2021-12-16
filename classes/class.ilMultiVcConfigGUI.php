<?php

use ILIAS\DI\Container;

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
include_once("Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
require_once dirname(__FILE__) . '/class.ilMultiVcUtil.php';
require_once __DIR__ . '/class.ilApiMultiVC.php';
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
				$ilCtrl->setParameter($this, 'integration_auth_method', $_POST['integration_auth_method']);
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
				$ilCtrl->setParameter($this, 'integration_auth_method', $_GET['integration_auth_method']);
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
			case 'update_token_user':
				$this->initTabs();
				$this->updateTokenUser();
				break;
			case 'delete_token_user':
				$this->initTabs();
				$this->deleteTokenUser();
				break;
			case 'authorizeWebexIntegration':
				$this->initTabs();
				$this->authorizeWebexIntegration();
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
			case 'addUserAutoComplete':
				$this->$cmd();
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

				// ONLY BIGBLUEBUTTON
				$iniSet = ilApiMultiVC::setPluginIniSet();# $this->object instanceof ilMultiVcConfig ? ilApiMultiVC::setPluginIniSet($this->object) : [];
				$vcTypesAvailable = $iniSet['vc_types_available'] ?? ['vc_types_available' => []];
				$vcTypesAvailable = !is_array($vcTypesAvailable) ? [$vcTypesAvailable] : $vcTypesAvailable;
				if( in_array('BigBlueButton', $vcTypesAvailable) ) {
					$ilTabs->addTab("report_log_max",
						$this->plugin_object->txt('report_log_max'),
						$ilCtrl->getLinkTarget($this, 'reportLogMax')
					);
					$ilTabs->addTab("user_log",
						$this->plugin_object->txt('user_log_bbb'),
						$ilCtrl->getLinkTarget($this, 'userLog')
					);
				}
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
		$html = $table_gui->getHTML();
		if( $table_gui->isWebex() ) {
			$tpl->addJavaScript( './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/webex/js/modal.integration.js' );
			$html .= file_get_contents(dirname(__FILE__) . '/../templates/webex/html/modal.integration.html');
			#var_dump($modal); exit;
		}
		$tpl->setContent($html);
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

	private function initConfigurationFormByPlatform(string $platform, string $cmd = '')
	{
		global $DIC; /** @var Container $DIC */
		$lng = $DIC->language();
		$ilCtrl = $DIC->ctrl();
		$ilSetting = $DIC->settings();
		$ilTpl = $DIC->ui()->mainTemplate();

		$iniSet = $this->object instanceof ilMultiVcConfig ? ilApiMultiVC::setPluginIniSet($this->object) : [];
		$vcTypesAvailable = $iniSet['vc_types_available'] ?? ['vc_types_available' => []];
		$vcTypesAvailable = !is_array($vcTypesAvailable) ? [$vcTypesAvailable] : $vcTypesAvailable;
		$filteredVcTypes = [];
		$triggerScript = false;
		$i = 0;
		foreach( ilMultiVcConfig::AVAILABLE_VC_CONN as $vcKey => $vcType ) {
			if( in_array($vcType, $vcTypesAvailable) ) {
				$filteredVcTypes[$vcKey] = $vcType;
				if( $vcType === 'Webex' ) {
					$triggerScript = $i;
				}
				$i++;
			}
		}



		$pl = $this->getPluginObject();

		$combo = new ilSelectInputGUI($pl->txt("showcontent"), 'showcontent');
		$combo->setRequired(true);
		if( $cmd ===  'configureNewMultiVcConn' || isset($_GET['configureNewMultiVcConn']) ) { // editMultiVcConn configureNewMultiVcConn
			$combo->setOptions([$platform => ilMultiVcConfig::AVAILABLE_VC_CONN[$_GET['configureNewMultiVcConn']]]);
		} elseif( (bool)(strlen($this->object->getTitle())) ) {
			$combo->setOptions([$platform => ilMultiVcConfig::AVAILABLE_VC_CONN[$this->object->getShowContent()]]);
		} else {
			$combo->setOptions($filteredVcTypes);
		}
		$combo->setInfo($pl->txt('info_platform_chg_reset_data'));


		#$wbxAdmInteg = (bool)ilApiMultiVC::init()->getPluginIniSet('wbx_adm_integrations') ?? false;
		#$combo->setHideSubForm(false, '=== \'' . $this->object->getShowContent() . '\'' );

		// 1st form page to create new conn
		if( $cmd === 'selectNewMultiVcConn' ) { // isset( $_POST['cmd']['createMultiVcConn'])
			$ilTpl->addOnLoadCode('$(\'#il_prop_cont_integration_auth_method\', document).hide();');
			if( false !== $triggerScript ) {
				$combo->addCustomAttribute('onchange="if(this.selectedIndex===' . $triggerScript . '){$(\'#il_prop_cont_integration_auth_method\', document).show();} else {$(\'#il_prop_cont_integration_auth_method\', document).hide();}"');
				if( count($vcTypesAvailable) === 1 ) {
					$ilTpl->addOnLoadCode('$(\'#showcontent\', document).trigger(\'change\');');
				}
			}
			$this->form->addItem($combo);

			#if( $wbxAdmInteg ) {
			$select = new ilSelectInputGUI($pl->txt('integration_auth_method'), 'integration_auth_method');
			$select->setOptions(ilMultiVcConfig::INTEGRATION_AUTH_METHODS);
			$select->setInfo(
				$pl->txt('integration_auth_methods_info') . ' ' .
				$pl->txt('integration_user_auth_scopes_info') . '<br />' .
				$pl->txt('integration_admin_auth_scopes_info')
			);
				$this->form->addItem($select);
			/*} else {
				$ih = new ilHiddenInputGUI('api');
				$this->form->addItem($ih);
			}*/
			return $this->form;
		}

		// 2nd form page to create new conn || edit conn
		if( $platform === 'webex' ) {
			#var_dump($ilCtrl->getParameterArray($this)); exit;
			$wbxIntegAuthMethod = !(bool)(strlen($postIntegAuthMethod = $_POST['integration_auth_method']))? $ilCtrl->getParameterArray($this)['integration_auth_method'] : $postIntegAuthMethod;
			$wbxIntegAuthMethod = !(bool)(strlen($wbxIntegAuthMethod)) ? $this->object->getAuthMethod() : $wbxIntegAuthMethod;
			$select = new ilSelectInputGUI($pl->txt('integration_auth_method'), 'integration_auth_method');
			$select->setOptions([$wbxIntegAuthMethod => ilMultiVcConfig::INTEGRATION_AUTH_METHODS[$wbxIntegAuthMethod]]);
			$select->setInfo(
				$pl->txt('integration_' . $wbxIntegAuthMethod . '_auth_method_info') . ' ' .
				$pl->txt('integration_user_auth_scopes_info') .
				(($wbxIntegAuthMethod === 'admin') ? ', ' . $pl->txt('integration_admin_auth_scopes_info') : '')
			);
			#$select->setInfo($pl->txt('integration_' . $wbxIntegAuthMethod . '_auth_method_info'));
			$combo->addSubItem($select);
		} else {
			$ih = new ilHiddenInputGUI('integration_auth_method');
			$combo->addSubItem($ih);
		}

		if( $platform === 'edudip' && !isset($_GET['configureNewMultiVcConn']) ) {
			$this->dic->ui()->mainTemplate()->addJavaScript( ILIAS_HTTP_PATH . '/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/src/js/modal.config.edudip.js' );
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


		// Platform specific form items
		include_once __DIR__ . '/partial/config.form.' . strtolower($platform) . '.php';

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
		$this->form->setId('plugin_configuration');
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
		#echo '<pre>'; var_dump($this->getDefaultFieldAndValues($this->object->getShowContent())); exit;
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
		// MaxDuration
		if( in_array($this->object->getShowContent(), ilMultiVcConfig::VC_RELATED_FUNCTION['maxDuration']) ) {
			$maxDurationDecimal = (int)$this->object->getMaxDuration() / 60;
			list($values["max_duration"]['hh']) = explode('.', $maxDurationDecimal);
			$values["max_duration"]['mm'] = round(($maxDurationDecimal - $values["max_duration"]['hh']) * 60);
		} else {
			$values["max_duration"] = 0;
		}

		// globalAssignedRoles
		$nonRoleBasedVc = ilApiMultiVC::setPluginIniSet()['non_role_based_vc'] ?? 0;
		if( !(bool)$nonRoleBasedVc && in_array($this->object->getShowContent(), ilMultiVcConfig::VC_RELATED_FUNCTION['globalAssignedRoles']) ) {
			$values["assigned_roles"] = $this->object->getAssignedRoles();
		}
		$values["showcontent"] = $this->object->getShowContent();
		$values["private_chat_choose"] = $this->object->isPrivateChatChoose();
		$values["private_chat_default"] = $this->object->isPrivateChatDefault();
		$values["recording_choose"] = $this->object->isRecordChoose();
		$values["recording_default"] = $this->object->isRecordDefault();
		$values["recording_only_for_moderated_rooms_default"] = $this->object->isRecordOnlyForModeratedRoomsDefault();
		$values["pub_recs_choose"] = $this->object->getPubRecsChoose();
		$values["pub_recs_default"] = $this->object->getPubRecsDefault();
		$values["show_hint_pub_recs"] = $this->object->getShowHintPubRecs();
		$values["hide_recs_until_date"] = substr($this->object->getHideRecsUntilDate(), 0, 10);
		$values["cam_only_for_moderator_choose"] = $this->object->isCamOnlyForModeratorChoose();
		$values["cam_only_for_moderator_default"] = $this->object->isCamOnlyForModeratorDefault();
		$values["lock_disable_cam"] = $this->object->getLockDisableCamChoose();
		$values["lock_disable_cam_default"] = $this->object->getLockDisableCamDefault();
		$values["guestlink_choose"] = $this->object->isGuestlinkChoose();
		$values["guestlink_default"] = $this->object->isGuestlinkDefault();
		$values["add_presentation_url"] = $this->object->getAddPresentationUrl();
		$values["add_welcome_text"] = $this->object->issetAddWelcomeText();
		$values["disable_sip"] = $this->object->getDisableSip();
		$values["hide_username_logs"] = $this->object->getHideUsernameInLogs();
		#$values["api"] = $this->object->getApi();
		$values["integration_auth_method"] = $this->object->getAuthMethod();
		$values["extra_cmd_choose"] = $this->object->getExtraCmdChoose();
		$values["extra_cmd_default"] = $this->object->getExtraCmdDefault();
		$values["style"] = $this->object->getStyle();
		$values["logo"] = $this->object->getLogo();

		$tokenUser = [];
		if( null !== $string = $this->object->getAccessToken() ) {
			$token = json_decode($string, 1);
			if( is_array($token) ) {
				foreach ( array_keys($token) as $email ) {
					$tokenUser[] = $email;
				}
				#$tokenUser = ilUtil::sortArray($tokenUser, 0);
			}
		}

		$values["token_user"] = json_encode($tokenUser);
		#$values["refresh_token"] = $this->object->getRefreshToken();

		// Edudip Button
		$btnEditTokenVal = [
			'formBtnAddToken' => $this->dic->language()->txt('rep_robj_xmvc_add_token'),
			'modalBtnStore' => $this->dic->language()->txt('save'),
			'modalBtnDelete' => $this->dic->language()->txt('delete'),
			'modalBtnAbort' => $this->dic->language()->txt('cancel'),
			'modalAddTokenTitle' => $this->dic->language()->txt('rep_robj_xmvc_add_token'),
			'modalDeleteTokenTitle' => $this->dic->language()->txt('rep_robj_xmvc_delete_token'),


		];
		$values['btn_edit_token_user'] = json_encode($btnEditTokenVal);

		//$values["recording_only_for_moderator_choose"] = $this->object->isRecordOnlyForModeratorChoose();

		if( $this->object->hasInitialDbEntry() ) {
			$values["svr_salt"] = $values["svr_salt_retype"] = self::ASTERISK_PW;
		}

		$this->form->setValuesByArray($values);
	}


	public function updateTokenUser()
	{
		$form = $this->initConfigurationForm();
		if ( $form->checkInput() ) {
			$hasMinTokenLen = strlen($form->getInput('new_token_user_access_token')) >= 8;
			$connId = $form->getInput('conn_id');
			$tokens = $this->object->getTokenFromDb($connId, 'access');
			$storedTokens = (bool)strlen($tokens) ? json_decode($tokens, 1) : [];

			$newTokenUserAccessToken = $form->getInput('new_token_user_access_token');
			$newTokenUserEmail = $form->getInput('new_token_user_email');
			$validEmail = (bool)sizeof(ilObjUser::getUserLoginsByEmail($newTokenUserEmail));
			if( $hasMinTokenLen && $validEmail && !array_key_exists($newTokenUserEmail, $storedTokens) ) {
				$storedTokens[$newTokenUserEmail] = $newTokenUserAccessToken;
				$_POST['token_user'] = json_encode($storedTokens);
				ilUtil::sendSuccess($this->dic->language()->txt("rep_robj_xmvc_token_stored"), true);
			} else {
				$_POST['token_user'] = json_encode($storedTokens);
				ilUtil::sendQuestion($this->dic->language()->txt("rep_robj_xmvc_token_not_stored"), true);
			}
			$this->save(false);
		}
	}

	public function deleteTokenUser()
	{
		$form = $this->initConfigurationForm();
		if ( $form->checkInput() ) {
			$connId = $form->getInput('conn_id');
			$tokens = $this->object->getTokenFromDb($connId, 'access');
			#echo '<per>'; var_dump($tokens);; exit;
			$storedTokens = (bool)strlen($tokens) ? json_decode($tokens, 1) : [];
			#echo '<per>'; var_dump($storedTokens); exit;

			$deleteTokenUserEmail = $form->getInput('new_token_user_email');
			if( array_key_exists($deleteTokenUserEmail, $storedTokens) ) {
				unset($storedTokens[$deleteTokenUserEmail]);
				$_POST['token_user'] = json_encode($storedTokens);
				ilUtil::sendSuccess($this->dic->language()->txt("rep_robj_xmvc_token_deleted"), true);
			} else {
				ilUtil::sendQuestion($this->dic->language()->txt("rep_robj_xmvc_token_not_deleted"), true);
			}
			$this->save(false);
		}
	}


	/**
	 * Save form input
	 * @param bool $redirect
	 */
	public function save(bool $redirect = true)
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

		// BBB CHECK URL 2 ADD PRESENTATION
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

		// WEBEX CHECK SITE-URL
		if( 'webex' === filter_var($_POST['showcontent'], FILTER_SANITIZE_STRING) ) {
			$svrPublicUrl = filter_var($_POST['svr_public_url'], FILTER_SANITIZE_URL);
			$regEx = "%(^(https://|http://|//)|(webex.com).*$)%";
			if( (bool)preg_match($regEx, $svrPublicUrl, $match) )  {
				$urlCheck = false;
				/** @var  ilTextInputGUI $field */
				$field = $form->getItemByPostVar('svr_public_url');
				$field->setValidationRegexp('%^$%');
				if( !$field->checkInput() ) {
					ilUtil::sendFailure($lng->txt("form_input_not_valid"));
				}
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
				// Max Duration
				if( !in_array($this->object->getShowContent(), ilMultiVcConfig::VC_RELATED_FUNCTION['maxDuration']) ) {
					$maxDuration = 0;
				} else {
					/** @var array $valDuration */
					$valDuration = $form->getInput("max_duration");
					$maxDuration = (int)$valDuration['hh'] * 60 + (int)$valDuration['mm'];
				}
				$this->object->setMaxDuration((int)$maxDuration);
				$this->object->setPrivateChatChoose( (bool)$form->getInput("private_chat_choose") );
				$this->object->setPrivateChatDefault( (bool)$form->getInput("private_chat_default") );
				$this->object->setRecordChoose( (bool)$form->getInput("recording_choose") );
				$this->object->setRecordDefault( (bool)$form->getInput("recording_default") );
				$this->object->setRecordOnlyForModeratedRoomsDefault( (bool)$form->getInput("recording_only_for_moderated_rooms_default") );
				$this->object->setPubRecsChoose( (bool)$form->getInput("pub_recs_choose") );
				$this->object->setPubRecsDefault( (bool)$form->getInput("pub_recs_default") );
				$this->object->setShowHintPubRecs( (bool)$form->getInput("show_hint_pub_recs") );
				$this->object->setHideRecsUntilDate( $form->getInput("hide_recs_until_date") );
				//$this->object->setRecordOnlyForModeratorChoose( (bool)$form->getInput("recording_only_for_moderator_choose") );
				//$this->object->setRecordOnlyForModeratorDefault( (bool)$form->getInput("recording_only_for_moderated_rooms_default") );
				$this->object->setCamOnlyForModeratorChoose( (bool)$form->getInput("cam_only_for_moderator_choose") );
				$this->object->setCamOnlyForModeratorDefault( (bool)$form->getInput("cam_only_for_moderator_default") );
				$this->object->setLockDisableCamChoose( (bool)$form->getInput("lock_disable_cam") );
				$this->object->setLockDisableCamDefault( (bool)$form->getInput("lock_disable_cam_default") );
				$this->object->setGuestlinkChoose( (bool)$form->getInput("guestlink_choose") );
				$this->object->setGuestlinkDefault( (bool)$form->getInput("guestlink_default") );
				$this->object->setAddPresentationUrl( $form->getInput("add_presentation_url") );
				$this->object->setAddWelcomeText( $form->getInput("add_welcome_text") );
				$this->object->setDisableSip( $form->getInput("disable_sip") );
				$this->object->setHideUsernameInLogs( $form->getInput("hide_username_logs") );
				#$this->object->setApi( $form->getInput("api") );
				$this->object->setAuthMethod( $form->getInput("integration_auth_method") );
				if( is_null($this->object->getConnId()) ) {
					$token = [
							'access_token' => null,
							'refresh_token' => null
						];
				} elseif( false !== array_search($this->dic->ctrl()->getCmd(), ['update_token_user', 'delete_token_user']) && false !== array_search($this->object->getShowContent(), $this->object::ADMIN_DEFINED_TOKEN_VC) ) {
					$token = [
						'access_token' => $form->getInput("token_user"),
						'refresh_token' => null
					];
				} else {
					$token = $this->object->getTokenFromDb($connId);
				}
				$this->object->setAccessToken($token['access_token']);
				$this->object->setRefreshToken($token['refresh_token']);

				/*
				$this->object->setAccessToken(false === array_search($this->object->getShowContent(), $this->object::ADMIN_DEFINED_TOKEN_VC)
						? $form->getInput("access_token")
						: $form->getInput("token_user")
				);
				$this->object->setRefreshToken( $form->getInput("refresh_token") );
				*/
				$this->object->setExtraCmdChoose( (bool)$form->getInput("extra_cmd_choose") );
				$this->object->setExtraCmdDefault( (bool)$form->getInput("extra_cmd_default") );
				$this->object->setStyle( $form->getInput("style") );
				$this->object->setLogo( $form->getInput("logo") );

				$nonRoleBasedVc = ilApiMultiVC::setPluginIniSet()['non_role_based_vc'] ?? 0;
				if( !(bool)$nonRoleBasedVc && in_array($this->object->getShowContent(), ilMultiVcConfig::VC_RELATED_FUNCTION['globalAssignedRoles']) )
				{
					$this->object->setAssignedRoles($form->getInput("assigned_roles"));
				}

				if ($form->getInput("showcontent") != "spreed") {
					if( isset($_GET['configureNewMultiVcConn']) || $form->getInput('svr_salt') !== self::ASTERISK_PW ) {
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
			$this->object->save((bool)$form->getInput("conn_id"));

			require_once "Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilObjMultiVc.php";
			ilObjMultiVc::getInstance()->fillEmptyPasswordsBBBVCR();
			if( $redirect ) {
				ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
				$ilCtrl->redirect($this, "configure");
			} else {
				$ilCtrl->setParameter($this, 'conn_id', $_GET['conn_id']);
				$ilCtrl->redirect($this, 'editMultiVcConn');
			}
		}

		$form->setValuesByPost();
		$tpl->setContent($form->getHtml());
	}

	public function getDefaultFieldAndValues(): array
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
		#echo $platform; exit;

		// MaxDuration
		if( in_array($this->object->getShowContent(), ilMultiVcConfig::VC_RELATED_FUNCTION['maxDuration']) ) {
			$values["max_duration"]['hh'] =
			$values["max_duration"]['mm'] = 0;
		} else {
			$values["max_duration"] = 0;
		}

		// globalAssignedRoles
		$nonRoleBasedVc = ilApiMultiVC::setPluginIniSet()['non_role_based_vc'] ?? 0;
		if( !(bool)$nonRoleBasedVc && in_array($this->object->getShowContent(), ilMultiVcConfig::VC_RELATED_FUNCTION['globalAssignedRoles']) ) {
			$values["assigned_roles"] = $this->object->getAssignedRoles();
		}
		#$adminRoleId = array_search('Administrator', $this->object->getAssignableGlobalRoles());
		#$values["assigned_roles"] = $adminRoleId;


		$values["showcontent"] = 'spreed';
		$values["private_chat_choose"] = 0;
		$values["private_chat_default"] = 1;
		$values["recording_choose"] = 0;
		$values["recording_default"] = 0;
		$values["recording_only_for_moderated_rooms_default"] = 1;
		$values["pub_recs_choose"] = 0;
		$values["pub_recs_default"] = 0;
		$values["show_hint_pub_recs"] = 0;
		$values["hide_recs_until_date"] = '';
		$values["cam_only_for_moderator_choose"] = 0;
		$values["cam_only_for_moderator_default"] = 0;
		$values["lock_disable_cam"] = 0;
		$values["lock_disable_cam_default"] = 0;
		$values["guestlink_choose"] = 0;
		$values["guestlink_default"] = 0;
		$values["add_presentation_url"] = 'https://';
		$values["add_welcome_text"] = 0;
		$values["disable_sip"] = 0;
		$values["hide_username_logs"] = 1;
		$values["api"] = '';
		$values["integration_auth_method"] = '';
		$values["access_token"] = $this->object->getAccessToken();
		$values["refresh_token"] = $this->object->getRefreshToken();
		$values["token_user"] = $this->object->getAccessToken();
		$values["style"] = $this->object->getStyle();
		$values["logo"] = $this->object->getLogo();


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

			if( (bool)$row['isInTrash'] ) {
				if( ilObject::_isInTrash($row['xmvcRefId']) )  {
					$row['parentRefId'] = $row['xmvcRefId'];
				} else {
					$row['isInTrash'] = 0;
				}
			}
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
			if( !$DIC->settings()->get('enable_trash') ) {
				ilRepUtil::deleteObjects($item_ref_id, [$item_ref_id]);
			} elseif( ilObject::_isInTrash($item_ref_id) ) {
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


	#################################################################################################
	#### EDUDIP
	#################################################################################################
	/**
	 * Show auto complete results
	 */
	public function addUserAutoComplete()
	{
		if( !(bool)strlen($term = filter_var($_REQUEST['term'], FILTER_SANITIZE_STRING)) ) {
			exit();
		}
		include_once './Services/User/classes/class.ilUserAutoComplete.php';
		$auto = new ilUserAutoComplete();
		#$auto->addUserAccessFilterCallable([$this,'filterUserIdsByRbacOrPositionOfCurrentUser']);
		$auto->setSearchFields(array('login','firstname','lastname','email', 'second_email'));
		$auto->setResultField('email');
		$auto->enableFieldSearchableCheck(false);
		$auto->setMoreLinkAvailable(true);

		/*
		if (($_REQUEST['fetchall'])) {
			$auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
		}
		*/

		echo $auto->getList($term);
		exit();
	}

	#################################################################################################
	#### Webex
	#################################################################################################

	public function authorizeWebexIntegration()
	{
		require_once(__DIR__ . '/class.ilApiWebexIntegration.php');
		ilApiWebexIntegration::init($this);
	}



}
?>

<?php

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
 * @ilCtrl_Calls ilObjMultiVcGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilRepositorySearchGUI, ilLearningProgressGUI
 *
 */
class ilObjMultiVcGUI extends ilObjectPluginGUI
{
    public const START_TYPE = [
        'WEBEX' => 'window', #'start', #'window', #
        'EDUDIP' => 'start',
        'BBB' => 'start',
        'OM' => 'start',
        'TEAMS' => 'start'
    ];

    public ?ilObject $object = null;
    protected ?ilMultiVcConfig $xmvcConfig = null;

    public bool $isBBB = false;

    public bool $isWebex = false;

    public bool $isAdminScope = false;

    public bool $isEdudip = false;

    public bool $isTeams = false;

    /** @var null|ilApiBBB|ilApiWebex|ilApiEdudip|ilApiOM $vcObj  */
    public $vcObj = null;

    public ?string $platform = null;

    public string $sessType = 'meeting';

    public ilLanguage $lng;

    private ILIAS\DI\Container $dic;

    private ilPropertyFormGUI $form;

    private bool $checkNotifcationMail = false;


    /**
     * ilObjMultiVcGUI constructor.
     * @throws ilObjectException
     * @throws ilObjectNotFoundException
     */
    public function __construct(int $a_ref_id = 0, int $a_id_type = self::REPOSITORY_NODE_ID, int $a_parent_node_id = 0)
    {
        global $DIC;
        //        $this->component_factory = $DIC["component.factory"];
        //        $this->component_repository = $DIC["component.repository"];
        parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);

        $this->dic = $DIC;

        $this->dic->language()->loadLanguageModule('rep_robj_xmvc');

        $this->platform = $this->object instanceof ilObjMultiVc ? ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent() : $this->platform; #

        $this->xmvcConfig = $this->object instanceof ilObjMultiVc ? ilMultiVcConfig::getInstance($this->object->getConnId()) : $this->xmvcConfig;

        $cmd = $this->dic->ctrl()->getCmd();
        $isXmvcObj = $this->object instanceof ilObjMultiVc;
        $initVc = false;
        $checkAuthUser = false;

        switch($this->platform) { # ilMultiVcConfig::_getMultiVcConnData()[ilObjMultiVc::getInstance()->getConnId()]['show_content']
            case 'bbb':
                $this->isBBB = true;
                if($initVc = $isXmvcObj) {
                    $this->object->fillEmptyPasswordsBBBVCR();
                }
                break;
            case 'webex':
                $this->isWebex = true;
                $this->isAdminScope = (ilMultiVcConfig::getInstance($this->object->getConnId())->getAuthMethod() === 'admin');
                $checkAuthUser =
                $initVc = $isXmvcObj;
                break;
            case 'edudip':
                $this->isEdudip = true;
                $checkAuthUser =
                $initVc = $isXmvcObj;
                $this->sessType = 'webinar';
                #$this->object::langMeeting2Webinar();
                break;
            case 'teams':
                $this->isTeams = true;
                $checkAuthUser =
                $initVc = $isXmvcObj;
                break;
            default:
                break;
        }

        if($checkAuthUser) {
            $ownerEmail = $this->object->getOwnersEmail();
            $userEmail = $this->dic->user()->getEmail();
            if(sizeof(ilObjUser::_getLocalAccountsForEmail($ownerEmail)) > 1 && $ownerEmail === $userEmail && $cmd === 'editProperties') {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('question', $this->dic->language()->txt('rep_robj_xmvc_owners_email_not_unique'), true);
            }
        }

        if($checkAuthUser && !$this->object->isOwnerAuthUser()) {
            if($this->isWebex && !$this->isAdminScope) {
                $this->resetAccessRefreshToken(false);
            }
            $this->object->makeOwnerToAuthUser();
            if($this->dic->user()->getId() === $this->object->getOwner()) {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('info', $this->dic->language()->txt('rep_robj_xmvc_info_set_owner'), true);
            }
        }

        if($initVc) {
            $api = ilMultiVcConfig::AVAILABLE_XMVC_API[$this->platform];
            $this->vcObj = new $api($this);

            // run BBB Record Task
            if($this->isBBB) {
                $this->object->runBBBRecTask('delete', $this->vcObj);
            }
            // Edudip users gets their token from plugin settings
            #$cmd = $this->dic->ctrl()->getCmd();
            $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
            $permission = $this->dic->access()->checkAccessOfUser($this->dic->user()->getId(), 'read', 'showContent', $this->object->getRefId());
            if($permission && $this->isEdudip && $cmd !== 'updateProperties') {
                #$this->dic->user()->getEmail()
                if(($accessToken = $settings->getTokenUser($this->object->getAuthUser())) != '') {
                    $this->object->setAccessToken($accessToken);
                } elseif($this->object->isUserOwner()) {
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('question', $this->dic->language()->txt('rep_robj_xmvc_error_admin_no_access_token'), true);
                }
            }
        }
    }

    public function getVcObj(): ilApiBBB|ilApiEdudip|ilApiOM|ilApiWebex|ilApiTeams
    {
        $class = ilMultiVcConfig::AVAILABLE_XMVC_API[$this->platform];
        return $this->vcObj ?? $this->vcObj = new $class($this);
    }

    /**
     * Initialisation
     */
    protected function afterConstructor(): void
    {
        // anything needed after object has been constructed
        //   $ilCtrl->saveParameter($this, array("my_id"));
        //$this->deactivateCreationForm(ilObject2GUI::CFORM_IMPORT);
        //$this->deactivateCreationForm(ilObject2GUI::CFORM_CLONE);
    }



    /**
     * Get type.
     */
    final public function getType(): string
    {
        return "xmvc";
    }

    /**
     * Handles all commmands of this class, centralizes permission checks
     * @throws ilCtrlException
     * @throws ilDatabaseException
     * @throws ilObjectException
     * @throws ilTemplateException
     * @throws Exception
     */
    public function performCommand(string $cmd): void
    {
        $next_class = $this->ctrl->getNextClass($this);
        switch($next_class) {
            case 'ilcommonactiondispatchergui':
                $gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
                $this->ctrl->forwardCommand($gui);
                return;
                break;
        }

        switch ($cmd) {
            case 'confirmedDelete':
            case "editProperties":		// list all commands that need write permission here
            case "checkMailNotification":
            case 'checkWebexIntegrationAuthorization':
            case 'initEmailVerifyAuthUser':
            case 'checkRequestVerifyAuthUser':
            case "updateProperties":
            case 'resetAccessRefreshToken':
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

            case 'meeting_create':
            case 'delete_scheduled_meeting':
            case 'meeting_relate':
            case 'scheduledMeetings':
            case 'syncScheduledMeetings':
            case "applyFilterScheduledMeetings":
            case "applyFilterScheduledMeetingsKeepForm":
            case "resetFilterScheduledMeetings":
                $this->checkPermission('write');
                $this->initTableGUIScheduledMeetings($cmd);
                break;

            case 'confirmDeleteRecords':
                $this->confirmDeleteRecords();
                break;
            case "deleteRecords":
                $this->checkPermission("read");
                if($this->deleteRecords()) {
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt("rep_robj_xmvc_msg_obj_deleted"), true);
                } else {
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt("msg_no_objs_deleted"), true);
                }
                $this->dic->ctrl()->redirect($this, 'showContent');
                break;
            case 'setRecordAvailable':
                $this->checkPermission("read");
                $this->setRecordAvailable();
                break;
            case 'setRecordLocked':
                $this->checkPermission("read");
                $this->setRecordLocked();
                break;
            case "showContent":			// list all commands that need read permission here
                $this->checkPermission("read");
                $this->$cmd();
                break;
            case 'authorizeWebexIntegration':
                $this->checkPermission("write");
                #$this->initTabs();
                $this->authorizeWebexIntegration();
                break;
            case "editLPSettings":
            case "updateLPSettings":
                $this->checkPermission("edit_learning_progress");
                //$this->setSubTabs("learning_progress");
                $this->$cmd();
                break;
            case "lpUserResults":
                $this->checkPermission("read_learning_progress");
                $this->$cmd();
                break;
        }
    }

    /**
     * Show tabs
     */
    protected function setTabs(): void
    {
        $ilTabs = $this->dic->tabs();
        $ilCtrl = $this->dic->ctrl();
        $ilAccess = $this->dic->access();

        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        if($this->isWebex && strlen($hint = trim($settings->getHint()))) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('info', $hint, true);
        }

        // tab for the "show content" command
        if ($ilAccess->checkAccess("read", "", $this->object->getRefId())) {
            $ilTabs->addTab("content", $this->txt($this->sessType), $ilCtrl->getLinkTarget($this, "showContent"));
        }

        // SCHEDULED MEETINGS
        if ($this->object->isUserOwner() && $ilAccess->checkAccess("write", "", $this->object->getRefId())) {
            if(($this->isWebex || $this->isEdudip || $this->isTeams)) {
                $ilTabs->addTab("scheduledMeetings", $this->txt('scheduled_' . $this->sessType . 's'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'scheduledMeetings'));
            }
        }

        // standard info screen tab
        $this->addInfoTab();
        //        $this->tabs->addTab("infoScreen", $this->lng->txt("info_short"), $this->ctrl->getLinkTarget($this, "infoScreen"));


        // a "properties" tab
        if ($ilAccess->checkAccess("write", "", $this->object->getRefId())) {
            $ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
        }


        //
        //        if (ilLearningProgressAccess::checkAccess($this->ref_id)) {
        //
        //            $this->tabs->addTab(
        //                "learning_progress",
        //                $this->lng->txt("learning_progress"),
        //                $this->ctrl->getLinkTargetByClass(array('ilObjMultiVcGUI','illearningprogressgui'), '')
        //            );
        //        }




        if ($this->isTeams && ilObjUserTracking::_enabledLearningProgress()) {
            if ($this->checkPermissionBool("edit_learning_progress") || $this->checkPermissionBool("read_learning_progress")) {

                if ($this->object->getLPMode() == ilObjMultiVc::LP_ACTIVE && $this->checkPermissionBool("read_learning_progress")) {
                    if (ilObjUserTracking::_enabledUserRelatedData()) {
                        $this->tabs->addTab("learning_progress", $this->lng->txt('learning_progress'), $this->ctrl->getLinkTargetByClass(array('ilObjMultiVcGUI','ilLearningProgressGUI','ilLPListOfObjectsGUI')));
                        //$this->tabs->addSubTab("lp_user_results", $this->lng->txt('rep_robj_xmvc_user_results'), $this->ctrl->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'lpUserResults'));
                    } else {
                        $this->tabs->addTab("learning_progress", $this->lng->txt('learning_progress'), $this->ctrl->getLinkTargetByClass(array('ilObjMultiVcGUI','ilLearningProgressGUI', 'ilLPListOfObjectsGUI'), 'showObjectSummary'));
                    }
                } elseif ($this->checkPermissionBool("edit_learning_progress")) {
                    $this->tabs->addTab('learning_progress', $this->lng->txt('learning_progress'), $this->ctrl->getLinkTarget($this, 'editLPSettings'));

                }
                if ($this->checkPermissionBool("edit_learning_progress") && in_array($this->ctrl->getCmdClass(), array('illplistofobjectsgui','ilLPListOfObjectsGUI'))) { //'illearningprogressgui'
                    $this->tabs->addSubTab("lp_settings", $this->lng->txt('settings'), $this->ctrl->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'editLPSettings'));
                }
            } elseif (ilLearningProgressAccess::checkAccess($this->ref_id) && $this->object->getLPMode() == ilObjMultiVc::LP_ACTIVE) {
                $this->tabs->addTab(
                    "learning_progress",
                    $this->lng->txt("learning_progress"),
                    $this->ctrl->getLinkTargetByClass(array('ilObjMultiVcGUI','illearningprogressgui'), '')
                );
            }
        }

        // standard permission tab
        $this->addPermissionTab();
    }

    /**
     * @throws Exception
     */
    private function initTabContent()
    {
        $this->tabs->activateTab('content');

        if($this->object->isUserOwner()
            || $this->dic->access()->checkAccess("write", "", $this->object->getRefId())
            || false !== array_search(
                ($this->isBBB && ($parentObj = $this->vcObj->course ?? $this->vcObj->group) ? $parentObj->getDefaultAdminRole() : null),
                $this->dic->rbac()->review()->assignedRoles($this->dic->user()->getId())
            )
        ) {
            $this->tabs->addSubTab("showContent", $this->txt('meeting'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'showContent'));
            if(!ilMultiVcConfig::getInstance($this->object->getConnId())->getHideUsernameInLogs()) {
                $this->tabs->addSubTab("userLog", $this->txt('user_log'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'userLog'));
                $this->tabs->addSubTab("lp_user_results", $this->txt('user_results'), $this->ctrl->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'lpUserResults'));
            }

            /*
            if( false !== array_search(ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent(), ['webex', 'edudip']) ) {
                $this->tabs->addSubTab("scheduledMeetings", $this->txt('scheduled_meetings'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'scheduledMeetings'));
            }
            */
        }
    }

    private function addSubTab(string $activeParentTab, string $activeSubTab)
    {
        switch($activeParentTab) {
            case 'content':
                $this->tabs->addSubTab("showContent", $this->txt('meeting'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'showContent'));
                if(!ilMultiVcConfig::getInstance($this->object->getConnId())->getHideUsernameInLogs()) {
                    $this->tabs->addSubTab("userLog", $this->txt('user_log'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'userLog'));
                }

                #$this->tabs->addSubTab("userLog", $this->txt('user_log'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'userLog'));
                if(false !== array_search(ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent(), ['webex', 'edudip'])) {
                    $this->tabs->addSubTab("scheduledMeetings", $this->txt('scheduled_' . $this->sessType . 's'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'scheduledMeetings'));
                }
                #$this->tabs->addSubTab("scheduledMeetings", $this->txt('scheduled_meetings'), $this->dic->ctrl()->getLinkTargetByClass(array('ilObjMultiVcGUI'), 'scheduledMeetings'));
                break;
            default:
                break;
        }

        $this->tabs->activateTab($activeParentTab);
        $this->tabs->activateSubTab($activeSubTab);
    }


    /**
     * @throws Exception
     */
    public function initUserLogTableGUI(string $cmd)
    {
        if (ilMultiVcConfig::getInstance($this->object->getConnId())->getHideUsernameInLogs()
            || !$this->dic->access()->checkAccess("write", "", $this->object->getRefId())
        ) {
            $this->dic->ctrl()->redirect($this, '');
        }

        if ($this->isTeams) {
            $tSettings = ilMultiVcConfig::getInstance($this->object->getConnId());
            $meetingIds = ilApiTeams::getAttendanceReport($this->object->getId(), $this->object->getRefId(), $this->object->getLPMode(), $this->object->getLpTime(), $tSettings->getSvrUsername(), $tSettings->getSvrSalt(), $tSettings->getSvrPublicUrl());
        }
        $this->initTabContent();

        $this->tabs->activateSubTab('userLog');

        $userLogTableGui = new ilMultiVcUserLogTableGUI($this, $cmd);

        if($cmd === 'downloadUserLog') {
            $userLogTableGui->downloadCsv();
            #$this->dic->ctrl()->redirect($this, 'applyFilterUserLog');
        } else {
            $this->dic->ui()->mainTemplate()->setContent($userLogTableGui->getHTML());
        }


    }

    //    private function filterPostParam()
    //    {
    //        if( is_array($_POST) && count($_POST) ) {
    //            foreach (array_keys($_POST) as $postKey) {
    //                if( !is_array($_POST[$postKey]) || !isset($_POST[$postKey]['ref_id']) ) {
    //                    continue;
    //                }
    //                $_POST[$postKey] = array_replace($_POST[$postKey], filter_var_array($_POST[$postKey], [
    //                    'ref_id'    => FILTER_SANITIZE_NUMBER_INT,
    //                    'rel_id'    => FILTER_SANITIZE_STRING,
    //                    'rel_data'    => FILTER_UNSAFE_RAW,
    //                    'participants'    => FILTER_SANITIZE_STRING,
    //                    'start'    => FILTER_SANITIZE_STRING,
    //                    'end'    => FILTER_SANITIZE_STRING,
    //                    'timezone'    => FILTER_SANITIZE_STRING,
    //                    'user_id'    => FILTER_SANITIZE_NUMBER_INT,
    //                    'auth_user'    => FILTER_SANITIZE_STRING,
    //                    'recurrence'    => FILTER_SANITIZE_STRING,
    //                ]));
    //                #echo 'POSTKEY: ' . $postKey . '<br /><pre>';
    //                #var_dump($_POST[$postKey]);
    //            }
    //            #exit;
    //        }
    //    }

    public function setScheduleMeetingRequestParam(): void
    {
        $meetingDuration = $this->dic->http()->wrapper()->post()->retrieve('meeting_duration', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
        $duration = [
            'start' => $meetingDuration[0],
            'end' => $meetingDuration[1]
        ];
        $fmlDateDuration = $this->dic->http()->wrapper()->post()->retrieve('fml_date_duration', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
        $fmlDuration = [
            'start' => str_replace('<br>', '', $fmlDateDuration[0]),
            'end' => str_replace('<br>', '', $fmlDateDuration[1])
        ];

        $sess = [
            'fml_data_source' => $this->dic->http()->wrapper()->post()->retrieve('fml_data_source', $this->dic->refinery()->kindlyTo()->string()),
            'fml_date_duration' => $fmlDuration,
            'meeting_title' => $this->dic->http()->wrapper()->post()->retrieve('meeting_title', $this->dic->refinery()->kindlyTo()->string()),
            'meeting_agenda' => $this->dic->http()->wrapper()->post()->retrieve('meeting_agenda', $this->dic->refinery()->kindlyTo()->string()),
            'meeting_duration' => $duration
        ];
        //die(var_dump($this->dic->http()->wrapper()->post()).var_dump($sess));

        ilSession::set('scheduleMeetingRequestParam', $sess);
    }

    /**
     * @throws Exception
     */
    public function initTableGUIScheduledMeetings(string $cmd)
    {
        $keepForm = false;
        if ($cmd === "applyFilterScheduledMeetingsKeepForm") {
            $cmd = "applyFilterScheduledMeetings";
            $keepForm = true;
        }
        if (!$this->object->isUserOwner() || !$this->dic->access()->checkAccess("write", "", $this->object->getRefId())) {
            $this->dic->ctrl()->redirect($this, '');
        }

        //        $this->filterPostParam();

        if(
            $this->dic->user()->getId() !== $this->object->getOwner()
//            && (bool)sizeof($_POST)
            && $this->dic->http()->wrapper()->post()->has('start')
            && $cmd !== 'applyFilterScheduledMeetings'
            && $cmd !== 'resetFilterScheduledMeetings'
        ) {
            $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetings');
        }

        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());

        if($this->isWebex) {
            // check for auth credentials
            $isUserScope = !$isAdminScope = 'admin' === $settings->getAuthMethod();
            $issetToken = $isAdminScope ? strlen('' . $settings->getAccessToken()) > 0 : strlen('' . $this->object->getAccessToken()) > 0;
            $issetAuthUser = strlen($this->object->getAuthUser()) > 0;
            //            $redirect = (bool)sizeof($_POST) && (
            //                    $cmd === 'meeting_create' ||
            //                    ($cmd === 'applyFilterScheduledMeetings' && 'true' === $_POST['data_source'])
            //                );
            $redirect = $this->dic->http()->wrapper()->post()->has('data_source') && (
                $cmd === 'meeting_create' ||
                    ($cmd === 'applyFilterScheduledMeetings' && 'true' === $this->dic->http()->wrapper()->post()->retrieve('data_source', $this->dic->refinery()->kindlyTo()->string()))
            );
            $errMsg = '';

            if ($isUserScope && !$issetToken) {
                $errMsg = $this->dic->language()->txt('rep_robj_xmvc_error_object_no_access_token');
            }

            if ($isAdminScope && !$issetToken) {
                $errMsg = $this->dic->language()->txt('rep_robj_xmvc_error_admin_no_access_token');
            }

            if ($isAdminScope && !$issetAuthUser) {
                $errMsg = $this->dic->language()->txt('rep_robj_xmvc_error_object_no_host_email');
            }

            if (strlen($errMsg) > 0) {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('question', $errMsg, true);
            }

            if ($redirect && strlen($errMsg) > 0) {
                //                $_POST['keepCreateMeetingForm'] = true;
                //                ilSession::set('scheduleMeetingRequestParam', $this->dic->http()->wrapper()->post());//$_POST);
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error'), true);
                $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetingsKeepForm');
            }
        }

        // INIT TABLE GUI
        //        $this->getPlugin()->includeClass('class.ilMultiVcTableGUIScheduledMeetings.php');

        $tableGuiScheduledMeeting = new ilMultiVcTableGUIScheduledMeetings($this, $cmd);

        // VALIDATE FORM CREATE / RELATE / DELETE MEETING
        if(false !== array_search($cmd, ['meeting_create', 'meeting_relate', 'delete_scheduled_meeting'])) {
            //ilSession::set('scheduleMeetingRequestParam', $this->dic->http()->wrapper()->post());//$_POST);
            $this->setScheduleMeetingRequestParam();
            if(!$tableGuiScheduledMeeting->getMeetingPropertiesForm()->checkInput()) {
                //                $_POST['keepCreateMeetingForm'] = true;
                //                ilSession::set('scheduleMeetingRequestParam', $this->dic->http()->wrapper()->post());//$_POST);
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('rep_robj_xmvc_schedule_meeting_error'), true);
                $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetingsKeepForm');
            }
        }


        // CMD Delete Meeting
        if($cmd === 'delete_scheduled_meeting') {
            $arDeleteScheduledMeeting = $this->dic->http()->wrapper()->post()->retrieve('delete_scheduled_meeting', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
            if(
                strlen($arDeleteScheduledMeeting[0]) > 0 && //'rel_id'
                strlen($arDeleteScheduledMeeting[1]) > 0 && //'delete_local_only'
                strlen($start = $arDeleteScheduledMeeting[2]) > 0 && //'start'
                strlen($end = $arDeleteScheduledMeeting[3]) > 0 && //'end'
                strlen($timezone = $arDeleteScheduledMeeting[4]) > 0 //'timezone'
            ) {
                $refId = $this->ref_id;// (int) $arDeleteScheduledMeeting['ref_id'];
                $deleteLocalOnly = (bool) $arDeleteScheduledMeeting[1];
                $sessDeleted = false;
                $sessToDelete = $this->object->getScheduledMeetingsByDateRange($start, $end, $refId, $timezone);

                if(isset($sessToDelete[0]) && strlen($sessId = $sessToDelete[0]['rel_id']) > 0) {
                    $hostSessDeleted = $deleteLocalOnly || $this->vcObj->sessionDelete($sessId);
                    if($hostSessDeleted) {
                        $sessDeleted =
                            $this->object->deleteScheduledSession($refId, $start, $end, $timezone)
                            && $this->object->deleteStoredHostSessionByRelId($sessId);
                    }
                }

                if ($sessDeleted) {
                    if($this->isEdudip && $procId = $this->initNotificationMail($sessToDelete[0], 'delete')) {
                        ilSession::set('checkNotificationMail', $procId);
                    }
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->sessType . '_deleted'), true);
                } else {
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->sessType . '_not_deleted'), true);
                }
                $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetings');
            }
        }

        // CMD Create Meeting
        if($cmd === 'meeting_create') {
            $meetingDuration = $this->dic->http()->wrapper()->post()->retrieve('meeting_duration', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
            //            $start = new DateTime($_POST['meeting_duration']['start']);
            $start = new DateTime($meetingDuration[0]);//['start']);
            $dateTimeStart = $start->format('Y-m-d H:i:s');
            //            $end = new DateTime($_POST['meeting_duration']['end']);
            $end = new DateTime($meetingDuration[1]);//['end']);
            $dateTimeEnd = $end->format('Y-m-d H:i:s');
            $duration = $end->getTimestamp() - $start->getTimestamp();
            $duration = $duration / 60;

            // Check minimum duration of meeting
            if($duration < 10) {
                //                $_POST['keepCreateMeetingForm'] = true;
                //                ilSession::set('scheduleMeetingRequestParam', $this->dic->http()->wrapper()->post());//$_POST);
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('rep_robj_xmvc_schedule_meeting_duration_less_time'), true);
                $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetingsKeepForm');
            }

            // Meetings collision detection
            if(null !== $this->object->hasScheduledMeetingsCollision($this->ref_id, $dateTimeStart, $dateTimeEnd)) {
                //                $_POST['keepCreateMeetingForm'] = true;
                //                ilSession::set('scheduleMeetingRequestParam', $this->dic->http()->wrapper()->post());//$_POST);
                $this->setScheduleMeetingRequestParam();
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('rep_robj_xmvc_meeting_collision'), true);
                //                $this->initTableGUIScheduledMeetings("applyFilterScheduledMeetingsKeepForm");
                $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetingsKeepForm');
            }

            if($this->isWebex) {
                // Create meeting @Webex, store response locally
                // todo: uncomment for production
                $webexCreateMeetingData = $this->createWebexMeeting();

                if (!is_null($data = $this->object->saveWebexMeetingData($this->object->getRefId(), $webexCreateMeetingData, true))) {
                    #echo '<pre>'; var_dump($data); exit;
                    $data['host'] = $this->platform;
                    $data['rel_data'] = json_encode($data['rel_data']);
                    $data['type'] = '';
                    if($this->object->storeHostSession($this->object->getRefId(), [$data])) {
                        $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->sessType . '_created'), true);
                        $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetings');
                    }
                }
            } // EOF isWebex

            if($this->isTeams) {
                //make UTC
                $meetingTitle = $this->dic->http()->wrapper()->post()->retrieve('meeting_title', $this->dic->refinery()->kindlyTo()->string());
                $utcStart = new ilDateTime($dateTimeStart, IL_CAL_DATETIME, $this->dic->user()->getTimeZone());
                $utcEnd = new ilDateTime($dateTimeEnd, IL_CAL_DATETIME, $this->dic->user()->getTimeZone());
                $creationResult = $this->vcObj->sessionCreateTeams($meetingTitle, $utcStart, $utcEnd);
                #die(var_dump($creationResult));
                if (!is_null($data = $this->object->saveTeamsSessionData($this->object->getRefId(), $creationResult, true, true))) {
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt('rep_robj_xmvc_scheduled_meeting_created'), true);
                    $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetings');
                }
            }

            if($this->isEdudip) {
                $param = [
                    'title' => $this->dic->http()->wrapper()->post()->retrieve('meeting_title', $this->dic->refinery()->kindlyTo()->string()),
                    'dateStart' => $dateTimeStart,
                    'duration' => $duration,
                    'max_participants' => $this->xmvcConfig->getMaxParticipants()
                ];
                $creationResult = $this->vcObj->sessionCreate($param);

                $check = json_decode($creationResult, 1);
                #echo '<per>'; var_dump($check); exit;
                if(!isset($check['success']) || !$check['success']) {
                    //                    $_POST['keepCreateMeetingForm'] = true;
                    //                    ilSession::set('scheduleMeetingRequestParam', $this->dic->http()->wrapper()->post());//$_POST);
                    $checkError = "";
                    if (isset($check['error'])) {
                        $checkError = $check['error'];
                    }
                    if ($checkError == "") {
                        $checkError = $this->dic->language()->txt('error');
                    }
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $checkError, true);
                    $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetingsKeepForm');
                }


                if (!is_null($data = $this->object->saveEdudipSessionData($this->object->getRefId(), $creationResult, true, true))) {
                    if($procId = $this->initNotificationMail($data)) {
                        ilSession::set('checkNotificationMail', $procId);
                    }
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->sessType . '_created'), true);
                    $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetings');
                }
            } // EOF isEdudip
        } // EOF $cmd = create_meeting

        // CMD relate meeting
        if($cmd === 'meeting_relate') {
            $relateMeeting = $this->dic->http()->wrapper()->post()->retrieve('relate_meeting', $this->dic->refinery()->kindlyTo()->dictOf($this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string())));
            //var_dump($relateMeeting['start'][0]);exit;
            //$relateMeeting = $this->dic->http()->wrapper()->post()->retrieve('relate_meeting', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
            //            if(null !==  $this->object->hasScheduledMeetingsCollision($this->ref_id, $relateMeeting['start'], $relateMeeting['end'])) {
            if(null !== $this->object->hasScheduledMeetingsCollision($this->ref_id, $relateMeeting['start'][0], $relateMeeting['end'][0])) {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('rep_robj_xmvc_' . $this->sessType . '_collision'), true);
                $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetings');
            }
            $relateMeeting['rel_data'] = filter_var($relateMeeting['rel_data'][0], FILTER_UNSAFE_RAW);

            $fnSave = 'saveWebexMeetingData';
            if($this->isEdudip) {
                $fnSave = 'saveEdudipSessionData';
                $relateMeeting['rel_data'] = '{"webinar":' . $relateMeeting['rel_data'] . "}";
            }

            if(!is_null($data = $this->object->{$fnSave}($relateMeeting['ref_id'][0], $relateMeeting['rel_data'], true))) {
                // because we delete session @ provider
                if($this->isEdudip || $this->isWebex) {
                    //ToDo Check if necessary
                    //die(var_dump(json_decode($data['rel_data'], 1)));
                    #$this->object->deleteStoredHostSessionByRelId(json_decode($_POST['relate_meeting']['rel_data'], 1)['id']);
                    //$this->object->relateStoredHostSessionByRelId(json_decode($data['rel_data'], 1)['id'], $relateMeeting['rel_data']['ref_id']);
                }
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt('rep_robj_xmvc_' . $this->sessType . '_related_successful'), true);
                $this->dic->ctrl()->redirect($this, 'applyFilterScheduledMeetings');
            }
        }

        // Construct TableGui
        #$this->addSubTab('content', 'scheduledMeetings');
        $this->tabs->activateTab('scheduledMeetings');
        $this->dic->ui()->mainTemplate()->addJavaScript(ILIAS_HTTP_PATH . '/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/src/js/xmvcModal.js');
        $this->dic->ui()->mainTemplate()->setContent($tableGuiScheduledMeeting->getHtmlMeetingPropertiesAndOverview($keepForm));
        if($this->isEdudip && !empty(ilSession::get('checkNotificationMail'))) {
            $this->dic->ui()->mainTemplate()->addOnLoadCode($this->getJsNotificationMail());
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('info', $this->dic->language()->txt('rep_robj_xmvc_notification_sending'), false);
        }
    }

    /**
     * @throws ilPluginException
     */
    private function initNotificationMail(array $data, string $event = 'create'): ?string
    {
        //        $this->getPlugin()->includeClass('class.ilMultiVcMailNotification.php');
        $data['rel_data'] = json_decode($data['rel_data'], 1);
        $procId = null;

        $path = array_reverse($this->dic->repositoryTree()->getPathFull($this->object->getRefId()));
        $keys = array_keys($path);
        /** @var null|int $parent */
        $parent = null;
        foreach($keys as $key) {
            if(in_array($path[$key]['type'], ['crs', 'grp'])) {
                $parent = $path[$key];
                break;
            }
        }
        if(!$parent) {
            return null;
        }

        $members = $this->object->getContainerMembers((int) $parent['obj_id']);
        $procId = uniqid(date('U'));
        foreach ($members as $member) {
            switch(true) {
                case (bool) $member['admin'] || (bool) $member['tutor']:
                    $recipient = array_replace(
                        ilObjUser::_lookupName($member['usr_id']),
                        [
                            'user_id' => $member['usr_id'],
                            'email' => ilObjUser::_lookupEmail($member['usr_id']),
                        ]
                    );
                    $fullname = $recipient['title'] ? $recipient['title'] . ' ' : '';
                    $fullname .= $recipient['firstname'] . ' ' . $recipient['lastname'];

                    $data['rel_data']['start'] = ilDatePresentation::formatDate(
                        new ilDateTime(strtotime(
                            $data['rel_data']['start']
                        ), IL_CAL_UNIX)
                    );

                    $data['rel_data']['end'] = ilDatePresentation::formatDate(
                        new ilDateTime(strtotime(
                            $data['rel_data']['end']
                        ), IL_CAL_UNIX)
                    );

                    $message = $this->object->getNotificationTextPhrases(
                        $this->lng->txt('rep_robj_xmvc_webinar_notification_from'),
                        $this->lng->txt('rep_robj_xmvc_webinar_event_' . $event . 'd'),
                        $data['rel_data']['title'],
                        $data['rel_data']['start'] . ' - ' . $data['rel_data']['end'],
                        $fullname,
                        $event === 'delete' ? '' : ilLink::_getLink($this->ref_id),
                        'webinar'
                    );

                    $procId = $this->object->createNotificationEntry(
                        $data['obj_id'],
                        $data['rel_id'],
                        $data['user_id'],
                        $data['auth_user'],
                        $recipient['user_id'], #$recipient['email'],
                        json_encode($message),
                        $procId
                    );
                    break;
            }

        } // EOF foreach ($members as $member)
        return $procId;
    }

    /**
     * @throws ilPluginException
     */
    public function checkMailNotification(): void
    {
        $json = [];
        if(!empty($procId = $this->dic->http()->wrapper()->query()->retrieve('procId', $this->dic->refinery()->kindlyTo()->string()))) {
            if($this->object->storeNotificationStatusInProgress($procId)) {
                if(count($notifyEntries = $this->object->getNotificationEntry($procId, ilMultiVcMailNotification::PROC_IN_PROGRESS))) {
                    //                    $this->getPlugin()->includeClass('class.ilMultiVcMailNotification.php');
                    $mailer = new ilMultiVcMailNotification($this->object);
                    foreach ($notifyEntries as $neKey => $notifyEntry) {
                        if($mailer->sendMessage($notifyEntry)) {
                            $this->object->storeNotificationStatusById($neKey, $procId, ilMultiVcMailNotification::PROC_SUCCEEDED);
                            $json[$neKey] = true;
                        } else {
                            $this->object->storeNotificationStatusById($neKey, $procId, ilMultiVcMailNotification::PROC_FAILED);
                            $json[$neKey] = false;
                        }
                    } // EOF foreach ($notifyEntries as $notifyEntry)
                }

            }
        }
        $this->jsonResponse($json);
    }

    private function jsonResponse(array $json)
    {
        header('Content-Type: application/json', false, 200);
        echo json_encode($json);
        exit;
    }

    /**
     * @throws ilPluginException
     * @throws ilTemplateException
     */
    private function getJsNotificationMail(): string
    {
        $tpl = new ilTemplate('tpl.xhrTriggerMailNotification.js', true, true, $this->getPlugin()->getDirectory()); #ilPlugin::_getDirectory(IL_COMP_SERVICE, "Repository", "robj", 'CatLp')
        $procId = ilSession::get('checkNotificationMail');
        ilSession::clear('checkNotificationMail');
        $tpl->setVariable('LINK_TARGET', $this->dic->ctrl()->getLinkTarget($this, 'checkMailNotification') . '&procId=' . $procId);
        $tpl->setVariable('TXT_SENT', $this->dic->language()->txt('rep_robj_xmvc_notification_sent'));
        return html_entity_decode($tpl->get());
    }
    /**
     * init create form
     */
    protected function initCreateForm(string $new_type): ilPropertyFormGUI
    {
        $form = parent::initCreateForm($new_type);

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

    protected function afterSave(ilObject $new_object): void
    {
        global $DIC;

        $form = $this->initCreateForm('xmvc');
        $form->checkInput();

        $new_object->setAuthUser($DIC->user()->getEmail());
        $new_object->createRoom((int) $form->getInput("online"), $form->getInput("conn_id"));
        $new_object->fillEmptyPasswordsBBBVCR();
        //var_dump($newObj); exit;
        ilSession::set('createNewObj', true);
        ilSession::set('doNotShowResetedTokens', true);
        parent::afterSave($new_object);
    }

    /**
     * After object has been created -> jump to this command
     */
    public function getAfterCreationCmd(): string
    {
        return "editProperties";
        #return 'checkWebexIntegrationAuthorization';
    }

    public function checkWebexIntegrationAuthorization()
    {
        if((bool) ilSession::get('createNewObj')) {
            ilSession::clear('createNewObj');
            if($this->isWebex && !$this->isAdminScope) {
                $cmd = 'authorizeWebexIntegration';
                $this->dic->ctrl()->redirect($this, $cmd);
            }
        }
    }

    /**
     * Get standard command
     */
    public function getStandardCmd(): string
    {
        return "showContent";
    }

    /**
     * Edit Properties. This commands uses the form class to display an input form.
     */
    public function editProperties()
    {
        $this->checkWebexIntegrationAuthorization();

        $this->dic->tabs()->activateTab("properties");
        $this->initPropertiesForm();
        $this->getPropertiesValues();
        if($this->hasChoosePermission('admin')) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('question', $this->lng->txt('rep_robj_xmvc_sysadmin_perm_choose_all'));
        }
        $this->dic->ui()->mainTemplate()->setContent($this->form->getHTML());
    }

    public function initEmailVerifyAuthUser()
    {
        // todo: add mailer
        $mailSent = true;
        if($mailSent) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->lng->txt('rep_robj_xmvc_success_send_mail_verify_auth_user'), true);
        } else {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->lng->txt('rep_robj_xmvc_failure_send_mail_verify_auth_user'), true);
        }
        $this->dic->ctrl()->redirect($this, 'editProperties');
    }

    public function checkRequestVerifyAuthUser()
    {
        // todo: check request param email verification
        $check = (bool) $this->dic->http()->wrapper()->query()->retrieve('devVerifyAU', $this->dic->refinery()->kindlyTo()->int());
        if($check) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->lng->txt('rep_robj_xmvc_success_verify_auth_user'), true);
        } else {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->lng->txt('rep_robj_xmvc_failure_verify_auth_user'), true);
        }
        $this->dic->ctrl()->redirect($this, 'editProperties');
    }

    public function formItem(string $item): ilCheckboxInputGUI|ilHiddenInputGUI
    {
        $text = "rep_robj_xmvc_" . $item;
        $info = $text . "_info";
        if($this->isTeams) {
            if ($item == 'private_chat') {
                $text = "rep_robj_xmvc_chat";
                $info = $text . "_info";
            } elseif ($item == 'cam_only_for_moderator') {
                $text = "rep_robj_xmvc_cam_mic_only_for_moderator";
                $info = $text . "_info";
            } elseif ($item == 'recording') {
                $info = $text . '_autostart';
            } elseif ($item == 'moderated') {
                $info = $text . '_teams_info';
            }
        }
        if ($this->hasChoosePermission($item)) {
            $cb = new ilCheckboxInputGUI($this->lng->txt($text), "cb_" . $item);
            $cb->setInfo($this->lng->txt($info));
        } else {
            $cb = new ilHiddenInputGUI("cb_" . $item);
        }
        return $cb;
    }

    /**
     * Init  form.
     *
     */
    public function initPropertiesForm()
    {
        global $DIC;
        $lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();

        //$pl = $this->getPluginObject();

        $this->form = new ilPropertyFormGUI();

        // title
        $ti = new ilTextInputGUI($this->lng->txt("title"), "title");
        $ti->setRequired(true);
        $this->form->addItem($ti);

        // description
        $ta = new ilTextAreaInputGUI($this->lng->txt("description"), "desc");
        $this->form->addItem($ta);

        // TileImage
        $this->form = $this->dic->object()->commonSettings()->legacyForm($this->form, $this->object)->addTileImage();

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


        $cbModr = $this->formItem("moderated");
        $this->form->addItem($cbModr);

        if($this->isBBB && ($this->xmvcConfig->isRecordChoose() || $this->xmvcConfig->isRecordDefault()) && $this->xmvcConfig->isRecordOnlyForModeratedRoomsDefault() && $cbModr->getType() !== 'hidden') {
            $info = new ilNonEditableValueGUI($this->lng->txt("hint"));
            $info->setValue($this->txt("recording_only_for_moderated_rooms_info"));
            $this->form->addItem($info);
        }

        $cbGuestLink = $this->formItem("guestlink");
        if($this->isBBB) {
            if($cbModr->getType() === 'hidden') {
                if ($this->object->get_moderated()) {
                    $info = new ilNonEditableValueGUI($this->lng->txt("hint"));
                    $info->setValue($this->txt("moderated"));
                    $this->form->addItem($info);

                    $this->form->addItem($cbGuestLink);
                }
            } else {
                if($cbGuestLink->getType() !== 'hidden') {
                    $cbModr->addSubItem($cbGuestLink);
                }
            }
        } else {
            $this->form->addItem($cbGuestLink);
        }


        if($this->isBBB && $this->object->get_moderated() && ($cbGuestLink->getType() !== 'hidden' || $this->xmvcConfig->isGuestlinkDefault())) {
            $parentItem = null;
            if ($cbModr->getType() !== 'hidden') {
                $parentItem = $cbModr;
            }
            if ($cbGuestLink->getType() !== 'hidden') {
                $parentItem = $cbGuestLink;
            }
            $guestPass = new ilTextInputGUI($this->lng->txt('rep_robj_xmvc_bbb_guest_password'), 'access_token');
            $guestPass->setInfo($this->lng->txt('rep_robj_xmvc_bbb_guest_password_info'));
            $guestPass->setDisableHtmlAutoComplete(true);
            if ($parentItem) {
                $parentItem->addSubItem($guestPass);
            } else {
                $this->form->addItem($guestPass);
            }

            $cbSecretExpires = new ilCheckboxInputGUI($this->lng->txt('rep_robj_xmvc_bbb_secret_expires'), 'secret_expires');
            $cbSecretExpires->setInfo($this->lng->txt('rep_robj_xmvc_bbb_secret_expires_info'));
            #$cbSecretExpires->setAdditionalAttributes('onchange="if(!this.checked){$(\'#il_prop_cont_secret_expiration_date\', document).hide(\'fast\');} else {$(\'#il_prop_cont_secret_expiration_date\', document).show(\'fast\');}"');
            if ($parentItem) {
                $parentItem->addSubItem($cbSecretExpires);
            } else {
                $this->form->addItem($cbSecretExpires);
            }

            $inputSecretExpirationDate = new ilDateTimeInputGUI($this->lng->txt('rep_robj_xmvc_bbb_secret_expiration_date'), 'secret_expiration_date');
            $inputSecretExpirationDate->setInfo($this->lng->txt('rep_robj_xmvc_bbb_secret_expiration_date_info'));
            $cbSecretExpires->addSubItem($inputSecretExpirationDate);
        }

        $cbRecordings = $this->formItem("recording");
        if($this->isBBB && $this->xmvcConfig->isRecordOnlyForModeratedRoomsDefault()) {
            if($cbModr->getType() === 'hidden') {
                if ($this->object->get_moderated()) {
                    $this->form->addItem($cbRecordings);
                }
            } else {
                if($cbRecordings->getType() !== 'hidden') {//  $cbRecordings->getType() !== 'hidden'|| $this->xmvcConfig->isRecordDefault()
                    $cbModr->addSubItem($cbRecordings);
                }
            }
        } else {
            $this->form->addItem($cbRecordings);
        }

        // if ($this->isBBB && $this->object->isRecordingAllowed() ) {

        if($this->isBBB && $this->xmvcConfig->getPubRecsChoose()) {
            $cbPubRecs = $this->formItem("pub_recs");
            if($cbRecordings->getType() == 'hidden') {
                $this->form->addItem($cbPubRecs);
            } else {
                $cbRecordings->addSubItem($cbPubRecs);
            }
        } elseif($this->isBBB && $this->xmvcConfig->getPubRecsDefault()) {
            $nePubRecs = new ilNonEditableValueGUI($this->lng->txt('rep_robj_xmvc_pub_recs'), 'pub_recs');
            $nePubRecs->setValue($this->lng->txt('rep_robj_xmvc_pub_recs_default_for_object'));
            if($this->object->isRecordingAllowed() && !$this->xmvcConfig->isRecordChoose()) {
                $this->form->addItem($nePubRecs);
            } elseif($this->xmvcConfig->isRecordChoose()) {
                $cbRecordings->addSubItem($nePubRecs);
            }
        }
        // }

        $this->form->addItem($this->formItem("btn_settings"));

        $this->form->addItem($this->formItem("btn_chat"));

        $this->form->addItem($this->formItem("with_chat"));

        $this->form->addItem($this->formItem("btn_locationshare"));

        $this->form->addItem($this->formItem("member_btn_fileupload"));

        $this->form->addItem($this->formItem("private_chat"));

        $this->form->addItem($this->formItem("cam_only_for_moderator"));

        $this->form->addItem($this->formItem("lock_disable_cam"));

        // If it's Webex add specific form elements
        if ('webex' !== $this->xmvcConfig->getShowContent() || !$this->hasChoosePermission('extra_cmd')) {
            $extra = new ilHiddenInputGUI("cb_extra_cmd");
        } else {
            $extra = new ilSelectInputGUI($this->lng->txt("rep_robj_xmvc_webex_user_logout"), "cb_extra_cmd");
            $extra->setInfo($this->lng->txt("rep_robj_xmvc_webex_logout_user_choose_info"));
            $extra->setOptions([
                1 => $this->lng->txt("rep_robj_xmvc_webex_user_do_logout"),
                0 => $this->lng->txt("rep_robj_xmvc_webex_user_do_not_logout"),
                2 => $this->lng->txt("rep_robj_xmvc_webex_user_choose_logout")
            ]);
            #resetAccessRefreshToken
        }
        $this->form->addItem($extra);

        $hostMail = new ilHiddenInputGUI('auth_user');

        if($this->isWebex) {
            $hostMailInfo = $this->object->isUserOwner() || $this->vcObj->isUserAdmin()
                ? $this->lng->txt('rep_robj_xmvc_webex_host_email_info')
                : $this->lng->txt('rep_robj_xmvc_webex_host_email_hidden_info');
        }
        if($this->isEdudip) {
            $hostMailInfo = $this->object->isUserOwner() || $this->vcObj->isUserAdmin()
                ? $this->lng->txt('rep_robj_xmvc_edudip_host_email_info')
                : $this->lng->txt('rep_robj_xmvc_edudip_host_email_hidden_info');
        }

        // Webex Integration Set Authorization
        if($this->isWebex) {
            if($this->object->isUserOwner()) {
                if ('user' === $this->xmvcConfig->getAuthMethod()) {
                    $authLinks = [];
                    if (!(bool) $this->object->getAccessToken()) {
                        $hrefSetAuth = $this->dic->ctrl()->getLinkTarget($this, 'authorizeWebexIntegration');
                        $authLinks[] = '<a href="' . $hrefSetAuth . '" target="_blank" class="btn btn-default btn-sm">' . $this->lng->txt("rep_robj_xmvc_authorize") . '</a>';
                    }
                    // Webex Integration ReSet Authorization
                    if ((bool) $this->object->getAccessToken()) {
                        $hrefResetAuth = $this->dic->ctrl()->getLinkTarget($this, 'resetAccessRefreshToken');
                        $authLinks[] = '<a href="' . $hrefResetAuth . '" class="btn btn-danger btn-sm">' . $this->lng->txt("rep_robj_xmvc_reset_authorization") . '</a>';
                    }

                    // FormElem Webex Integration Authorization
                    $authInteg = new ilNonEditableValueGUI('Integration', 'auth_integration', true);
                    $authInteg->setValue(implode('&nbsp;', $authLinks));
                    $authInteg->setInfo($this->lng->txt("rep_robj_xmvc_webex_integration_auth_info"));
                    $this->form->addItem($authInteg);
                }
            }

            $hostMail = new ilNonEditableValueGUI($this->lng->txt('rep_robj_xmvc_webex_host_email'), 'auth_user');
            $hostMail->setInfo($hostMailInfo);
        } // EOF if ( 'webex' === $this->xmvcConfig->getShowContent() )

        // Edudip Host Email
        if($this->isEdudip) {
            $hostMail = new ilNonEditableValueGUI($this->lng->txt('rep_robj_xmvc_edudip_host_email'), 'auth_user');
            $hostMail->setInfo($hostMailInfo);
        } // EOF

        // Add Host Email Webex / Edudip
        $this->form->addItem($hostMail);

        $this->form->addCommandButton("updateProperties", $this->lng->txt("save"));
        $this->form->setTitle($this->txt("edit_properties"));
        $this->form->setFormAction($ilCtrl->getFormAction($this));
    }

    /**
     * Get values for edit properties form
     * @throws ilObjectException
     */
    public function getPropertiesValues()
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
        $values["cb_pub_recs"] = $this->object->getPubRecs();
        $values["cb_cam_only_for_moderator"] = $this->object->isCamOnlyForModerator();
        $values["cb_lock_disable_cam"] = $this->object->getLockDisableCam();
        $values["conn_id"] = $this->object->getConnId();
        $values["cb_guestlink"] = $this->object->isGuestlink();
        $values["cb_extra_cmd"] = $this->object->getExtraCmd();
        $values["auth_user"] = ($this->isWebex || $this->isEdudip) && ($this->object->isUserOwner() || $this->vcObj->isUserAdmin())
            ? $this->object->getAuthUser() ?? $this->object->getOwnersEmail()
            : $this->lng->txt('rep_robj_xmvc_of_owner_prefix') . ' ' . $this->object->getOwnersName();
        #$values["auth_user"] = $this->object->getAuthUser() ?? $this->object->getOwnersEmail();
        if($this->isBBB && $this->object->isGuestlink()) {
            $values["access_token"] = rawurldecode($this->object->getAccessToken());
            $secretExpiration = $this->object->getSecretExpiration();
            $values["secret_expires"] = (bool) $secretExpiration && (bool) $values["access_token"];
            $values["secret_expiration_date"] = (string) $secretExpiration;
            // if( !(bool)$secretExpiration ) {
            // $this->dic->ui()->mainTemplate()->addOnLoadCode('$(\'#il_prop_cont_secret_expiration_date\', document).hide();');
            // }
        }

        $this->form->setValuesByArray($values);

    }

    /**
     * Update properties
     */
    public function updateProperties()
    {
        $ilCtrl = $this->dic->ctrl();

        $this->initPropertiesForm();
        if ($this->form->checkInput()) {
            $this->dic->object()->commonSettings()->legacyForm($this->form, $this->object)->saveTileImage();
            $this->object->setTitle($this->form->getInput("title"));
            $this->object->setDescription($this->form->getInput("desc"));
            $this->object->setOnline($this->form->getInput("online"));
            if($this->hasChoosePermission('moderated')) {
                $this->object->set_moderated($this->object->ilIntToBool((int) $this->form->getInput("cb_moderated")));
            }
            if($this->hasChoosePermission('btn_settings')) {
                $this->object->set_btnSettings($this->object->ilIntToBool((int) $this->form->getInput("cb_btn_settings")));
            }
            if($this->hasChoosePermission('btn_chat')) {
                $this->object->set_btnChat($this->object->ilIntToBool((int) $this->form->getInput("cb_btn_chat")));
            }
            if($this->hasChoosePermission('with_chat')) {
                $this->object->set_withChat($this->object->ilIntToBool((int) $this->form->getInput("cb_with_chat")));
            }
            if($this->hasChoosePermission('btn_locationshare')) {
                $this->object->set_btnLocationshare($this->object->ilIntToBool((int) $this->form->getInput("cb_btn_locationshare")));
            }
            if($this->hasChoosePermission('member_btn_fileupload')) {
                $this->object->set_memberBtnFileupload($this->object->ilIntToBool((int) $this->form->getInput("cb_member_btn_fileupload")));
            }
            if($this->hasChoosePermission('fa_expand')) {
                $this->object->set_faExpand($this->object->ilIntToBool((int) $this->form->getInput("cb_fa_expand")));
            }
            if($this->hasChoosePermission('private_chat')) {
                $this->object->setPrivateChat((bool) $this->form->getInput("cb_private_chat"));
            }
            if($this->hasChoosePermission('cam_only_for_moderator')) {
                $this->object->setCamOnlyForModerator((bool) $this->form->getInput("cb_cam_only_for_moderator"));
            }
            if($this->hasChoosePermission('lock_disable_cam')) {
                $this->object->setLockDisableCam((bool) $this->form->getInput("cb_lock_disable_cam"));
            }
            if($this->hasChoosePermission('guestlink')) {
                $this->object->setGuestlink((bool) $this->form->getInput("cb_guestlink"));
            }
            if($this->hasChoosePermission('extra_cmd')) {
                $this->object->setExtraCmd($this->form->getInput("cb_extra_cmd"));
            }
            if($this->hasChoosePermission('recording')) {
                $this->object->setRecord($this->checkRecordChooseValue((bool) $this->form->getInput("cb_moderated"), (bool) $this->form->getInput("cb_recording")));
            }
            if($this->hasChoosePermission('pub_recs')) {
                $this->object->setPubRecs((bool) $this->form->getInput("cb_pub_recs"));
            }
            // $this->object->setConnId( (int)$this->form->getInput("conn_id") );

            if($this->isBBB && $this->object->get_moderated() === false) {
                $this->object->setGuestlink(false);
            }


            // Webex/Edudip User Auth (AdminScope)
            $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
            if('admin' === $settings->getAuthMethod() && ('webex' === $settings->getShowContent() || 'edudip' === $settings->getShowContent())) {
                $oldAuthUser = $this->object->getAuthUser();
                $this->object->setAuthUser($this->form->getInput("auth_user"));
            }

            if($this->isBBB && $this->object->isGuestlink()) {
                $this->object->setAccessToken(filter_var(trim($this->form->getInput("access_token")), FILTER_SANITIZE_ENCODED));
                $secretExpiration = (bool) $this->form->getInput("secret_expires") && $this->object->getAccessToken() ? $this->form->getInput("secret_expiration_date") : 0;
                $this->object->setSecretExpiration($secretExpiration);
            }

            $returnVal = $this->object->update();
            $vc = ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent();
            if($vc === 'bbb') {
                $this->object->fillEmptyPasswordsBBBVCR();
            } elseif($vc === 'om') {
                $om = new ilApiOM($this);
                $this->prepareRoomOM($om);
            }

            $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt("msg_obj_modified"), true);
            $ilCtrl->redirect($this, "editProperties");
        } else {
            #$this->dic->ui()->mainTemplate()->setMessage('failure', $lng->txt("form_input_not_valid"), true);
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt("form_input_not_valid"), true);
            $this->dic->ctrl()->redirect($this, 'editProperties');
        }

        $this->form->setValuesByPost();
        $this->dic->ui()->mainTemplate()->setContent($this->form->getHtml());
    }


    private function isRecordChooseAvailable(): bool
    {
        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        switch(true) {
            case $settings->isRecordChoose() && !$settings->isRecordOnlyForModeratedRoomsDefault():
            case $settings->isRecordChoose() && $settings->isRecordOnlyForModeratedRoomsDefault() && (($this->xmvcConfig->get_moderatedChoose()) || $this->xmvcConfig->get_moderatedDefault())://$this->object->get_moderated() &&
                return true;
            default:
                return false;

        }
    }

    private function checkRecordChooseValue(bool $moderated, bool $recording): bool
    {
        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        switch(true) {
            case !$settings->isRecordChoose() && !$settings->isRecordDefault():
            case $settings->isRecordChoose() && $settings->isRecordOnlyForModeratedRoomsDefault() && !$moderated:
                return false;
            case $settings->isRecordChoose() && !$settings->isRecordDefault() && !$settings->isRecordOnlyForModeratedRoomsDefault():
            case $settings->isRecordChoose() && $settings->isRecordDefault() && !$settings->isRecordOnlyForModeratedRoomsDefault():
            case $settings->isRecordChoose() && $settings->isRecordOnlyForModeratedRoomsDefault() && $moderated:
                return $recording;
            case !$settings->isRecordChoose() && $settings->isRecordDefault() && $settings->isRecordOnlyForModeratedRoomsDefault() && $moderated:
            case !$settings->isRecordChoose() && $settings->isRecordDefault() && !$settings->isRecordOnlyForModeratedRoomsDefault():
                return true;
        }
        return false;
    }

    private function hasChoosePermission(string $field): bool
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
            case 'lock_disable_cam':
                $state = $settings->isObjConfig('lockDisableCam') && ($settings->getLockDisableCamChoose() || $isAdmin);
                break;
            case 'private_chat':
                $state = $settings->isObjConfig('privateChatChoose') && ($settings->isPrivateChatChoose() || $isAdmin);
                break;
            case 'guestlink':
                $state = $settings->isObjConfig('guestlinkChoose') && ($settings->isGuestlinkChoose() || $isAdmin);
                break;
            case 'extra_cmd':
                $state = $settings->isObjConfig('extraCmd') && ($settings->getExtraCmdChoose() || $isAdmin);
                break;
            case 'recording':
                $state = $settings->isObjConfig('recordChoose') && ($this->isRecordChooseAvailable() || $isAdmin);
                break;
            case 'pub_recs':
                $state = $settings->isObjConfig('pubRecs') && ($this->isRecordChooseAvailable() || $settings->getPubRecsDefault() || $isAdmin);
                break;
            default:
                $state = false;
        }
        return $state;
    }

    public function resetAccessRefreshToken(bool $redirect = true)
    {
        $this->object->resetAccessRefreshToken();
        if((bool) ilSession::get('doNotShowResetedTokens')) {
            ilSession::clear('doNotShowResetedTokens');
        } else {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt("rep_robj_xmvc_reseted_tokens"), true);
        }
        if($redirect) {
            $this->dic->ctrl()->redirect($this, "editProperties");
        }
    }

    public function confirmedDelete(): void
    {
        $this->object->doDelete();
    }

    public function getBuddyPicture(): string
    {
        //http://stackoverflow.com/questions/3967515/how-to-convert-image-to-base64-encoding
        global $DIC;
        $ilUser = $DIC->user();

        $user_image = substr($ilUser->getPersonalPicturePath($a_size = "xsmall", $a_force_pic = true), 2);
        if (substr($user_image, 0, 2) == './') {
            $user_image = substr($user_image, 2);
        }
        try {
            $path = ILIAS_HTTP_PATH . '/' . $user_image;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            // $base64 = "data:image/png;base64,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";
            return $base64;
        } catch (Exception $e) {
            return "";
        }
    }

    public function getUiCompMsgBox(string $type = 'info', string $msg = '', bool $rendered = true): string|ILIAS\UI\Component\MessageBox\MessageBox
    {
        $msgBox = $this->dic->ui()->factory()->messageBox()->$type($msg);
        $renderedMsgBox = $this->dic->ui()->renderer()->render($msgBox);
        return !$rendered ? $msgBox : $renderedMsgBox;
    }

    /**
     * Show content
     * @throws Exception
     */
    public function showContent()
    {
        $this->dic->ui()->mainTemplate()->setPermanentLink($this->getType(), $this->ref_id);
        //$ilTabs->clearTargets(); //no display of tabs
        $this->initTabContent();
        $this->tabs->activateSubTab('showContent');
        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        $showContent = $settings->getShowContent();
        switch($showContent) {
            case 'bbb':
                $this->showContentBBB();
                break;
            case 'om':
                $this->showContentOM();
                break;
            case 'webex':
                $this->showContentWebex();
                break;
            case 'edudip':
                $this->showContentEdudip();
                break;
            case 'teams':
                $this->showContentTeams();
                break;
        }
    }

    /**
     * @throws ilTemplateException
     * @throws Exception
     */
    private function showContentWebex()
    {
        try {
            $webex = new ilApiWebex($this);
        } catch (Exception $e) {
            $webex = new StdClass();
        }

        $isWebex = $webex instanceof ilApiWebex;
        $isAdminOrModerator = $isWebex && ($webex->isUserModerator() || $webex->isUserAdmin());
        $sess =
        $participants = null;

        $relId = null;
        if ($this->dic->http()->wrapper()->query()->has('rel_id')) {
            $this->dic->http()->wrapper()->query()->retrieve('rel_id', $this->dic->refinery()->kindlyTo()->string());
        }

        if (is_null($relId)) {
            $upcomingSession = $this->object->getScheduledMeetingsByDateFrom(
                date('Y-m-d H:i:s'),
                $this->object->getRefId()
            );
        } else {
            $upcomingSession = $this->object->getScheduledMeetingByRelId($relId);
        }

        if(isset($upcomingSession[0])) {
            $upcomingSession[0]['ref_id'] = $this->object->getRefId();
            $sess = $upcomingSession[0];
            $participants = json_decode('' . $upcomingSession[0]['participants'], 1);
        }

        $userId = $this->dic->user()->getId();
        $sessRelData = !is_null($sess) ? json_decode($sess['rel_data'], 1) : [];

        $userIsInvitedModerator = isset($participants['moderator'][$userId]);
        $userIsInvitedAttendee = isset($participants['attendee'][$userId]);
        $userIsCoHost = $userIsInvitedAttendee && $participants['attendee'][$userId]['coHost'];
        $isNewUser = !$userIsInvitedModerator && !$userIsInvitedAttendee;

        if((bool) ($startSession = $this->vcObj->isMeetingStartable())) {
            switch (true) {
                case $this->dic->http()->wrapper()->query()->has('startWEBEX') && $this->dic->http()->wrapper()->query()->retrieve('startWEBEX', $this->dic->refinery()->kindlyTo()->int()) === 1:
                case $this->dic->http()->wrapper()->query()->has('windowWEBEX') && $this->dic->http()->wrapper()->query()->retrieve('windowWEBEX', $this->dic->refinery()->kindlyTo()->int()) === 1:
                    $startSession = true;
                    break;
                default:
                    $startSession = false;
                    break;
            }
        }

        if($startSession) {
            $hostSessData = $this->vcObj->sessionGet($sess['rel_id']);
            $success = json_decode($hostSessData, 1)['success'];
            if(!$success) {
                $referer = $this->dic->http()->request()->getServerParams()['HTTP_REFERER'];
                #$query = parse_url($referer, PHP_URL_QUERY);
                parse_str(parse_url($referer, PHP_URL_QUERY), $cmd);
                $cmd = $cmd['cmd'] ?? 'showContent';
                $cmd = $cmd !== 'showContent' ? 'scheduledMeetings' : $cmd;
                #$this->dic->ui()->mainTemplate()->setMessage('failure', $this->getPlugin()->txt('error_start_meeting'), true);
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->getPlugin()->txt('error_start_meeting'), true);
                $this->dic->ctrl()->redirect($this, $cmd);
            }
        }

        // ADD MODERATOR TO SESSION
        if(!is_null($sess) && $isNewUser && $isAdminOrModerator && $sessRelData['hostEmail'] === $this->dic->user()->getEmail() && $startSession
        ) {
            if($moderatorResult = $this->vcObj->sessionModeratorAdd($sess['rel_id'], $this->dic->user()->getFirstname(), $this->dic->user()->getLastname(), $this->dic->user()->getEmail())) {
                $this->object->saveWebexSessionModerator($sess['ref_id'], $sess['rel_id'], $userId, $moderatorResult, false, $sess['user_id']);
                $upcomingSession = !$relId
                    ? $this->object->getScheduledMeetingsByDateFrom(
                        date('Y-m-d H:i:s'),
                        $this->object->getRefId()
                    )
                    : $this->object->getScheduledMeetingByRelId($relId);

                if(isset($upcomingSession[0])) {
                    $sess = $upcomingSession[0];
                    $participants = json_decode($upcomingSession[0]['participants'], 1);
                    $userIsInvitedModerator = true;
                }

                # FURTHERMORE REFRESH TOKENS
                if($newTokens = json_decode($this->vcObj->refreshAccessToken($this->isAdminScope), 1)) {
                    if(isset($newTokens['access_token'])) {
                        if ($this->isAdminScope) {
                            $config = ilMultiVcConfig::getInstance($this->object->getConnId());
                            $config->setAccessToken($newTokens['access_token']);
                            $config->setRefreshToken($newTokens['refresh_token']);
                            $config->save();
                        } else {
                            $this->object->setAccessToken($newTokens['access_token']);
                            $this->object->setRefreshToken($newTokens['refresh_token']);
                            $this->object->updateAccessRefreshToken();
                        }
                    }
                }

            }
        }


        // ADD ATTENDEE (tutor as coHost)
        #if( !$userIsInvitedAttendee && !$isAdminOrModerator && !is_null($sess) && $startSession) {
        elseif(!$userIsInvitedAttendee && !$userIsInvitedModerator && !is_null($sess) && $startSession) {
            $email = !$isAdminOrModerator
                ? date('YmdHis') . '.' . uniqid() . '@example.com'
                : $this->dic->user()->getEmail();

            $httpResponse = $this->vcObj->sessionParticipantAdd(
                $upcomingSession[0]['rel_id'],
                $this->dic->user()->getFirstname(),
                $this->dic->user()->getLastname(),
                $email,
                $isAdminOrModerator
            );
            $userResult = json_decode($httpResponse, 1);

            if(isset($userResult['success']) && (bool) $userResult['success']) {
                $entry = $this->object->saveWebexSessionParticipant(
                    $upcomingSession[0]['ref_id'],
                    $upcomingSession[0]['rel_id'],
                    $upcomingSession[0]['user_id'],
                    $httpResponse
                );
                $userIsInvitedAttendee = true;
                $userIsCoHost = $userResult['coHost'];
                #echo '<pre>'; var_dump( [$userIsCoHost, $userResult]); exit();
            }
        }

        /*
        if( !$relId ) {
            $data = $this->object->getWebexMeetingByRefIdAndDateTime($this->ref_id, null, ilObjMultiVc::MEETING_TIME_AHEAD);
        } else {
            $data = $this->object->getScheduledMeetingByRelId($relId)[0];
            $data['rel_data'] = json_decode($data['rel_data']);
        }
        */
        $data = $this->object->getScheduledMeetingsByDateFrom(
            date('Y-m-d H:i:s'),
            $this->object->getRefId()
        );
        try {
            $data = $data[0];
            #$data = $this->object->getScheduledSessionByRefIdAndDateTime($this->ref_id, null); # $upcomingSession[0]; #
            $data['rel_data'] = json_decode($data['rel_data']);
        } catch (Exception $e) {
        }
        #echo '<pre>'; var_dump($data); exit;
        // INFORM TO SCHEDULE A SESSION IF NONE EXISTING
        /*
        if( $this->object->isUserOwner() && null === $data ) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('question',
                $this->dic->language()->txt("rep_robj_xmvc_require_schedule_new_meeting") . ' ' .
                $this->dic->language()->txt("rep_robj_xmvc_scheduled_" . $this->sessType . "_time_ahead") .
                ': ' . ilObjMultiVc::MEETING_TIME_AHEAD .
                ' ' . $this->dic->language()->txt('seconds'),
                true
            );
        }
        */

        // JOIN USER / WELCOME BACK USER
        switch(true) {
            case !($webex instanceof ilApiWebex) || !ilObjMultiVcAccess::checkConnAvailability($this->obj_id):
                $this->showContentUnavailable();
                break;
            case $this->dic->http()->wrapper()->query()->has('windowWEBEX') && $this->dic->http()->wrapper()->query()->retrieve('windowWEBEX', $this->dic->refinery()->kindlyTo()->int()) === 1 && $webex->isMeetingStartable() && !$userIsCoHost:
                //                $this->showContentWindowWebex(ilMultiVcConfig::getInstance($this->object->getConnId())->getSvrPublicUrl(), $data); //before 8
                $this->showContentWindowWebex(strstr($data['rel_data']->webLink, 'j.php', true), $data);
                break;

            case $this->dic->http()->wrapper()->query()->has('startWEBEX') && $this->dic->http()->wrapper()->query()->retrieve('startWEBEX', $this->dic->refinery()->kindlyTo()->int()) === 10:
            case $this->dic->http()->wrapper()->query()->has('startWEBEX') && $this->dic->http()->wrapper()->query()->retrieve('startWEBEX', $this->dic->refinery()->kindlyTo()->int()) === 1 && !$webex->isMeetingStartable():
                $this->showContentWindowClose();
                break;

            case $this->dic->http()->wrapper()->query()->has('windowWEBEX') && $this->dic->http()->wrapper()->query()->retrieve('windowWEBEX', $this->dic->refinery()->kindlyTo()->int()) === 1 && $webex->isMeetingStartable() && $userIsCoHost:
            case $this->dic->http()->wrapper()->query()->has('startWEBEX') && $this->dic->http()->wrapper()->query()->retrieve('startWEBEX', $this->dic->refinery()->kindlyTo()->int()) === 1 && $webex->isMeetingStartable():
                $this->dic->ui()->mainTemplate()->addOnLoadCode('$("body", document).hide();');
                $launchApp = !$isAdminOrModerator ? 'true' : 'false';
                #$this->redirectToPlatformByUrl($data['rel_data']->webLink . '&launchApp=' . $launchApp, $webex);
                $this->redirectToPlatformByUrl($data['rel_data']->webLink . '&launchApp=' . $launchApp, $webex);
                break;

            default:
                $this->showContentDefault($webex);
                break;
        }
    }

    /**
     * @throws ilTemplateException
     * @throws Exception
     */
    private function showContentEdudip()
    {
        try {
            $this->vcObj = $edudip = new ilApiEdudip($this);
        } catch (Exception $e) {
            $edudip = new StdClass();
        }

        $isEdudip = $this->isEdudip = $edudip instanceof ilApiEdudip;

        $isAdminOrModerator = $isEdudip && ($edudip->isUserModerator() || $edudip->isUserAdmin());

        $sess =
        $participants = null;

        $relId = null;
        if ($this->dic->http()->wrapper()->query()->has('rel_id')) {
            $this->dic->http()->wrapper()->query()->retrieve('rel_id', $this->dic->refinery()->kindlyTo()->string());
        }

        if (is_null($relId)) {
            $upcomingSession = $this->object->getScheduledMeetingsByDateFrom(
                date('Y-m-d H:i:s'),
                $this->object->getRefId()
            );
        } else {
            $upcomingSession = $this->object->getScheduledMeetingByRelId($relId);
        }


        if(isset($upcomingSession[0])) {
            $upcomingSession[0]['ref_id'] = $this->object->getRefId();
            $sess = $upcomingSession[0];
            $participants = json_decode($upcomingSession[0]['participants'], 1);
        }

        $userId = $this->dic->user()->getId();
        $userIsInvitedModerator = isset($participants['moderator'][$userId]);
        $userIsInvitedAttendee = isset($participants['attendee'][$userId]);
        $isNewUser = !$userIsInvitedModerator && !$userIsInvitedAttendee;
        $startSession = $this->vcObj->isMeetingStartable()
            && $this->dic->http()->wrapper()->query()->has('startEDUDIP')
            && $this->dic->http()->wrapper()->query()->retrieve('startEDUDIP', $this->dic->refinery()->kindlyTo()->int()) === 1;
        #$isAdminOrModerator = $isAdminOrModerator && isset($participants['moderator']) ? false : true;

        if($startSession) {
            $hostSessData = $this->vcObj->sessionGet($sess['rel_id']);
            $success = json_decode($hostSessData, 1)['success'];
            if(!$success) {
                $referer = $this->dic->http()->request()->getServerParams()['HTTP_REFERER'];
                $query = parse_url($referer, PHP_URL_QUERY);
                parse_str($query, $cmd);
                $cmd = $cmd['cmd'] ?? 'showContent';
                $cmd = $cmd !== 'showContent' ? 'scheduledMeetings' : $cmd;
                #$this->dic->ui()->mainTemplate()->setMessage('failure', $this->getPlugin()->txt('error_start_webinar'), true);
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->getPlugin()->txt('error_start_webinar'), true);
                $this->dic->ctrl()->redirect($this, $cmd);
            }
        }

        // ADD MODERATOR TO NEXT SESSION
        // && $this->object->isUserOwner() prevents adding coModerators, then nonOwner Modr&Admins will be attendees
        if(!is_null($sess) && $isAdminOrModerator && $isNewUser && $startSession
        ) {
            if($moderatorResult = $this->vcObj->sessionModeratorAdd($sess['rel_id'], $this->dic->user()->getFirstname(), $this->dic->user()->getLastname(), $this->dic->user()->getEmail())) {
                // ability to create sessions by nonOwner Modr&Admins
                $this->object->saveEdudipSessionModerator($sess['ref_id'], $sess['rel_id'], $userId, $moderatorResult, false, $sess['user_id']);
                $upcomingSession = !$relId
                    ? $this->object->getScheduledMeetingsByDateFrom(
                        date('Y-m-d H:i:s'),
                        $this->object->getRefId()
                    )
                    : $this->object->getScheduledMeetingByRelId($relId);

                if(isset($upcomingSession[0])) {
                    $upcomingSession[0]['ref_id'] = $this->object->getRefId();
                    $sess = $upcomingSession[0];
                    $participants = json_decode($upcomingSession[0]['participants'], 1);
                }
            }
        }

        if($isAdminOrModerator && !is_null($participants) && isset($participants['moderator'][$userId])) {
            $this->vcObj->setWebLink($participants['moderator'][$userId]['webLink']);
        } elseif(isset($participants['attendee'][$userId])) {
            $this->vcObj->setWebLink($participants['attendee'][$userId]['webLink']);
        } elseif($startSession && !is_null($sess) && !isset($participants['attendee'][$userId])) {
            $httpResponse = $this->vcObj->sessionParticipantAdd(
                $upcomingSession[0]['rel_id'],
                $upcomingSession[0]['start'],
                $this->dic->user()->getFirstname(),
                $this->dic->user()->getLastname()
            );
            $userResult = json_decode($httpResponse, 1);

            if(isset($userResult['success']) && (bool) $userResult['success']) {
                $entry = $this->object->saveEdudipSessionParticipant(
                    $upcomingSession[0]['ref_id'],
                    $upcomingSession[0]['rel_id'],
                    $upcomingSession[0]['user_id'],
                    $httpResponse,
                    true
                );

                $participants = json_decode($entry['participants'], 1);
                $attendee = $participants['attendee'][$userId];
                $this->vcObj->setWebLink($attendee['webLink']);
            }
        }

        $data = $this->object->getScheduledSessionByRefIdAndDateTime($this->ref_id, null);
        #$data = $this->object->getScheduledSessionByRefIdAndDateTime($this->ref_id, null, ilObjMultiVc::MEETING_TIME_AHEAD);

        switch(true) {
            case !ilObjMultiVcAccess::checkConnAvailability($this->obj_id):
                $this->showContentUnavailable();
                break;

            case $this->dic->http()->wrapper()->query()->has('startEDUDIP')
            && $this->dic->http()->wrapper()->query()->retrieve('startEDUDIP', $this->dic->refinery()->kindlyTo()->int()) === 10:
            case $this->dic->http()->wrapper()->query()->has('startEDUDIP')
            && $this->dic->http()->wrapper()->query()->retrieve('startEDUDIP', $this->dic->refinery()->kindlyTo()->int()) === 1
            && !$this->vcObj->isMeetingStartable():
                $this->showContentWindowClose();
                break;

            case $this->dic->http()->wrapper()->query()->has('startEDUDIP')
            && $this->dic->http()->wrapper()->query()->retrieve('startEDUDIP', $this->dic->refinery()->kindlyTo()->int()) === 1 && $this->vcObj->isMeetingStartable():
                $this->redirectToPlatformByUrl($this->vcObj->getWebLink(), $edudip);
                break;

            default:
                $this->showContentDefault($edudip);
                break;
        }
    }

    /**
     * @throws ilTemplateException
     * @throws Exception
     */
    private function showContentTeams()
    {
        try {
            $teams = new ilApiTeams($this);
        } catch (Exception $e) {
            $teams = new StdClass();
        }

        $isTeams = $teams instanceof ilApiTeams;
        $isAdminOrModerator = $isTeams && ($teams->isUserModerator() || $teams->isUserAdmin());
        $sess =
        $participants = null;

        $relId = null;
        if ($this->dic->http()->wrapper()->query()->has('rel_id')) {
            $relId = $this->dic->http()->wrapper()->query()->retrieve('rel_id', $this->dic->refinery()->kindlyTo()->string());
        }

        if (is_null($relId)) {
            $upcomingSession = $this->object->getScheduledMeetingsByDateFrom(
                date('Y-m-d H:i:s'),
                $this->object->getRefId(),
                'UTC'
            );
        } else {
            $upcomingSession = $this->object->getScheduledMeetingByRelId($relId);
        }

        if(isset($upcomingSession[0])) {
            $upcomingSession[0]['ref_id'] = $this->object->getRefId();
            $sess = $upcomingSession[0];
            $participants = json_decode('' . $upcomingSession[0]['participants'], 1);
        }

        $userId = $this->dic->user()->getId();
        $sessRelData = !is_null($sess) ? json_decode($sess['rel_data'], 1) : [];

        $userIsInvitedModerator = isset($participants['moderator'][$userId]);
        $userIsInvitedAttendee = isset($participants['attendee'][$userId]);
        $userIsCoHost = $userIsInvitedAttendee && $participants['attendee'][$userId]['coHost'];
        $isNewUser = !$userIsInvitedModerator && !$userIsInvitedAttendee;

        //        die(var_dump($this->vcObj->isMeetingStartable()));

        if((bool) ($startSession = $this->vcObj->isMeetingStartable())) {
            switch (true) {
                case $this->dic->http()->wrapper()->query()->has('startTEAMS') && $this->dic->http()->wrapper()->query()->retrieve('startTEAMS', $this->dic->refinery()->kindlyTo()->int()) === 1:
                case $this->dic->http()->wrapper()->query()->has('windowTEAMS') && $this->dic->http()->wrapper()->query()->retrieve('windowTEAMS', $this->dic->refinery()->kindlyTo()->int()) === 1:
                    $startSession = true;
                    break;
                default:
                    $startSession = false;
                    break;
            }
        }

        if($startSession) {
            $hostSessData = $this->vcObj->sessionGet($sess['rel_id']); //ToDo CheckSession
            $success = true;//json_decode($hostSessData, 1)['success'];
            if(!$success) {
                $referer = $this->dic->http()->request()->getServerParams()['HTTP_REFERER'];
                #$query = parse_url($referer, PHP_URL_QUERY);
                parse_str(parse_url($referer, PHP_URL_QUERY), $cmd);
                $cmd = $cmd['cmd'] ?? 'showContent';
                $cmd = $cmd !== 'showContent' ? 'scheduledMeetings' : $cmd;
                #$this->dic->ui()->mainTemplate()->setMessage('failure', $this->getPlugin()->txt('error_start_meeting'), true);
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->getPlugin()->txt('error_start_meeting'), true);
                $this->dic->ctrl()->redirect($this, $cmd);
            }
        }

        // ADD MODERATOR TO SESSION
        if(!is_null($sess) && $isNewUser && $isAdminOrModerator && $startSession
        ) {
            if($moderatorResult = $this->vcObj->sessionModeratorAdd($sess['rel_id'], $this->dic->user()->getFirstname(), $this->dic->user()->getLastname(), $this->dic->user()->getEmail())) {
                $this->object->saveWebexSessionModerator($sess['ref_id'], $sess['rel_id'], $userId, $moderatorResult, false, $sess['user_id']);
                $upcomingSession = !$relId
                    ? $this->object->getScheduledMeetingsByDateFrom(
                        date('Y-m-d H:i:s'),
                        $this->object->getRefId()
                    )
                    : $this->object->getScheduledMeetingByRelId($relId);

                if(isset($upcomingSession[0])) {
                    $sess = $upcomingSession[0];
                    $participants = json_decode($upcomingSession[0]['participants'], 1);
                    $userIsInvitedModerator = true;
                }

                # FURTHERMORE REFRESH TOKENS
                // if($newTokens = json_decode($this->vcObj->refreshAccessToken($this->isAdminScope), 1)) {
                //     if(isset($newTokens['access_token'])) {
                //         if ($this->isAdminScope) {
                //             $config = ilMultiVcConfig::getInstance($this->object->getConnId());
                //             $config->setAccessToken($newTokens['access_token']);
                //             $config->setRefreshToken($newTokens['refresh_token']);
                //             $config->save();
                //         } else {
                //             $this->object->setAccessToken($newTokens['access_token']);
                //             $this->object->setRefreshToken($newTokens['refresh_token']);
                //             $this->object->updateAccessRefreshToken();
                //         }
                //     }
                // }

            }
        }


        // ADD ATTENDEE (tutor as coHost)
        #if( !$userIsInvitedAttendee && !$isAdminOrModerator && !is_null($sess) && $startSession) {
        elseif(!$userIsInvitedAttendee && !$userIsInvitedModerator && !is_null($sess) && $startSession) {
            $email = !$isAdminOrModerator
                ? date('YmdHis') . '.' . uniqid() . '@example.com'
                : $this->dic->user()->getEmail();

            $httpResponse = $this->vcObj->sessionParticipantAdd(
                $upcomingSession[0]['rel_id'],
                $this->dic->user()->getFirstname(),
                $this->dic->user()->getLastname(),
                $email,
                $isAdminOrModerator
            );
            $userResult = json_decode($httpResponse, 1);

            if(isset($userResult['success']) && (bool) $userResult['success']) {
                $entry = $this->object->saveTeamsSessionParticipant(
                    $upcomingSession[0]['ref_id'],
                    $upcomingSession[0]['rel_id'],
                    $upcomingSession[0]['user_id'],
                    $httpResponse
                );
                $userIsInvitedAttendee = true;
                $userIsCoHost = $userResult['coHost'];
                #echo '<pre>'; var_dump( [$userIsCoHost, $userResult]); exit();
            }
        }

        /*
        if( !$relId ) {
            $data = $this->object->getWebexMeetingByRefIdAndDateTime($this->ref_id, null, ilObjMultiVc::MEETING_TIME_AHEAD);
        } else {
            $data = $this->object->getScheduledMeetingByRelId($relId)[0];
            $data['rel_data'] = json_decode($data['rel_data']);
        }
        */
        $data = $this->object->getScheduledMeetingsByDateFrom(
            date('Y-m-d H:i:s'),
            $this->object->getRefId(),
            "UTC"
        );
        try {
            $data = $data[0];
            #$data = $this->object->getScheduledSessionByRefIdAndDateTime($this->ref_id, null); # $upcomingSession[0]; #
            $data['rel_data'] = json_decode($data['rel_data']);
        } catch (Exception $e) {
        }
        //        echo '<pre>'; var_dump($data); exit;
        // INFORM TO SCHEDULE A SESSION IF NONE EXISTING
        /*
        if( $this->object->isUserOwner() && null === $data ) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('question',
                $this->dic->language()->txt("rep_robj_xmvc_require_schedule_new_meeting") . ' ' .
                $this->dic->language()->txt("rep_robj_xmvc_scheduled_" . $this->sessType . "_time_ahead") .
                ': ' . ilObjMultiVc::MEETING_TIME_AHEAD .
                ' ' . $this->dic->language()->txt('seconds'),
                true
            );
        }
        */

        // JOIN USER / WELCOME BACK USER
        switch(true) {
            case !($teams instanceof ilApiTeams) || !ilObjMultiVcAccess::checkConnAvailability($this->obj_id):
                $this->showContentUnavailable();
                break;
            case $this->dic->http()->wrapper()->query()->has('windowTEAMS') && $this->dic->http()->wrapper()->query()->retrieve('windowTEAMS', $this->dic->refinery()->kindlyTo()->int()) === 1 && $teams->isMeetingStartable() && !$userIsCoHost:
                //die('window');//$this->showContentWindowTeams($data['rel_data']->joinUrl);
                break;

            case $this->dic->http()->wrapper()->query()->has('startTEAMS') && $this->dic->http()->wrapper()->query()->retrieve('startTEAMS', $this->dic->refinery()->kindlyTo()->int()) === 10:
            case $this->dic->http()->wrapper()->query()->has('startTEAMS') && $this->dic->http()->wrapper()->query()->retrieve('startTEAMS', $this->dic->refinery()->kindlyTo()->int()) === 1 && !$teams->isMeetingStartable():
                $this->showContentWindowClose();
                break;

            case $this->dic->http()->wrapper()->query()->has('windowTEAMS') && $this->dic->http()->wrapper()->query()->retrieve('windowTEAMS', $this->dic->refinery()->kindlyTo()->int()) === 1 && $teams->isMeetingStartable() && $userIsCoHost:
            case $this->dic->http()->wrapper()->query()->has('startTEAMS') && $this->dic->http()->wrapper()->query()->retrieve('startTEAMS', $this->dic->refinery()->kindlyTo()->int()) === 1 && $teams->isMeetingStartable():
                $this->dic->ui()->mainTemplate()->addOnLoadCode('$("body", document).hide();');
                $launchApp = !$isAdminOrModerator ? 'true' : 'false';
                $this->redirectToPlatformByUrl($data['rel_data']->joinUrl, $teams);
                break;

            default:
                $this->showContentDefault($teams);
                break;
        }
    }

    /**
     * @throws ilTemplateException
     */
    private function showContentOM()
    {
        global $DIC;

        $om = new ilApiOM($this);
        $this->prepareRoomOM($om);

        switch (true) {
            case !($om instanceof ilApiOM) || !ilObjMultiVcAccess::checkConnAvailability($this->obj_id):
                $this->showContentUnavailable();
                break;
            case $this->dic->http()->wrapper()->query()->has('startOM') && $this->dic->http()->wrapper()->query()->retrieve('startOM', $this->dic->refinery()->kindlyTo()->int()) === 10:
            case $this->dic->http()->wrapper()->query()->has('startOM') && $this->dic->http()->wrapper()->query()->retrieve('startOM', $this->dic->refinery()->kindlyTo()->int()) === 1 && !$om->isMeetingStartable():
                $this->showContentWindowClose();
                break;
            case $this->dic->http()->wrapper()->query()->has('startOM') && $this->dic->http()->wrapper()->query()->retrieve('startOM', $this->dic->refinery()->kindlyTo()->int()) === 1 && $om->isMeetingStartable():
                $this->redirectToPlatformByUrl($om->getOmRoomUrl());
                break;
            case null !== $om->getPluginIniSet('max_concurrent_users'):
            default:
                $this->showContentDefault($om);
                break;
        }
    }

    private function showContentBBB()
    {

        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        if((bool) strlen($hint = trim($settings->getHint()))) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('question', $hint);
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
            case $this->dic->http()->wrapper()->query()->has('windowBBB') && $this->dic->http()->wrapper()->query()->retrieve('windowBBB', $this->dic->refinery()->kindlyTo()->int()) === 1 && $bbb->isMeetingStartable():
                $this->showContentWindowRedirect();
                break;
            case $this->dic->http()->wrapper()->query()->has('startBBB') && $this->dic->http()->wrapper()->query()->retrieve('startBBB', $this->dic->refinery()->kindlyTo()->int()) === 10:
            case $this->dic->http()->wrapper()->query()->has('startBBB') && $this->dic->http()->wrapper()->query()->retrieve('startBBB', $this->dic->refinery()->kindlyTo()->int()) === 1 && !$bbb->isMeetingStartable():
                $this->showContentWindowClose();
                break;
            case $this->dic->http()->wrapper()->query()->has('startBBB') && $this->dic->http()->wrapper()->query()->retrieve('startBBB', $this->dic->refinery()->kindlyTo()->int()) === 1 && $bbb->isMeetingStartable():
                $bbb->addConcurrent();
                $bbb->logMaxConcurrent();
                $this->redirectToPlatformByUrl($bbb->getUrlJoinMeeting(), $bbb);
                break;
            default:
                $withConcurrent = null !== $bbb->getPluginIniSet('max_concurrent_users');
                $this->showContentDefault($bbb, $withConcurrent);
                break;
        }


    }

    /**
     * @throws ilTemplateException
     * @throws Exception
     */
    private function showContentDefault(ilApiBBB|ilApiEdudip|ilApiOM|ilApiWebex|ilApiTeams|StdClass $vcObj, bool $withConcurrent = false)
    {
        $tpl = $this->dic->ui()->mainTemplate();//['tpl'];
        $tpl->addCss("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.show_content_default.css");

        $tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.show_content_default.js", true, 3);

        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.show_content_default.html", true, true);


        $apiPostFix = strtolower(str_replace('ilApi', '', get_class($vcObj)));

        $my_tpl->setVariable('HEADLINE_WELCOME', $this->txt('headline_welcome_' . $this->sessType));

        if($this->isBBB || $vcObj instanceof ilApiOM) {
            if ($this->object->get_moderated()) {
                if ($vcObj->isUserModerator()) {
                    $my_tpl->setVariable("INFOTOP", $this->txt('info_top_moderator_' . $this->platform));
                } else {
                    $my_tpl->setVariable("INFOTOP", $this->txt('info_top_moderated_m_bbb'));
                }
            } else {
                $my_tpl->setVariable("INFOTOP", $this->txt('info_top_not_moderated_bbb'));
            }
        }

        $my_tpl->setVariable('MEETING_RUNNUNG', $this->txt($this->sessType . '_running'));

        if($this->object->get_moderated() && $this->object->isRecordingAllowed() && ($vcObj->isMeetingRunning() || $vcObj->isUserModerator())) {
            $recWarning = $this->txt('recording_warning');
            $publishRecs = $this->object->getPubRecs() || $this->xmvcConfig->getPubRecsDefault();
            if($publishRecs) {
                $recWarning .= ' ' . $this->txt('pub_recs_default_for_object');
            }
            if(!$this->xmvcConfig->getShowHintPubRecs()) {
                $recWarning .= ' ' . $this->txt('hint_pub_recs');
            }
            /*
            if( $publishRecs ) {
                $recWarning .= ' ' . $this->txt('hint_availability_recs');
            }
            */

            $my_tpl->setVariable(
                "RECORDING_WARNING",
                $this->getUiCompMsgBox(
                    'info',
                    $recWarning
                )
            );
            $my_tpl->setVariable("UNHIDE_MSG_REC_ALLOWED", 'un');
        } else {
            $my_tpl->setVariable("UNHIDE_MSG_REC_ALLOWED", '');
        }


        if(!$withConcurrent) {
            $my_tpl->setVariable('CLASS_INFO_CONCURRENT', 'hidden');
            $my_tpl->setVariable('INFO_CONCURRENT', '');
        } else {
            if(($availableUsers = $vcObj->getMaxAvailableJoins()) > 0) {
                #$info_concurrent = $this->getUiCompMsgBox('info', $this->lng->txt('rep_robj_xmvc_info_concurrent_users_available') . $availableUsers, false);
                $info_concurrent = $this->lng->txt('rep_robj_xmvc_info_concurrent_users_available') . $availableUsers;
            } else {
                #$info_concurrent = $this->getUiCompMsgBox('info', $this->txt('info_concurrent_users_none'), false);
                $info_concurrent = $this->txt('info_concurrent_users_none');
            }
            $my_tpl->setVariable('CLASS_INFO_CONCURRENT', 'col-sm-12');
            #$my_tpl->setVariable('INFO_CONCURRENT', $this->dic->ui()->renderer()->render($info_concurrent));
            $my_tpl->setVariable('INFO_CONCURRENT', $info_concurrent);
        }

        $my_tpl->setVariable("JOINCONTENT", $this->getJoinContent($vcObj));
        $my_tpl->setVariable("MEETING_RUNNING", $this->txt('meeting_running'));
        $my_tpl->setVariable("HEADLINE_INFO_BOTTOM", $this->txt('info_bottom_headline'));
        $my_tpl->setVariable("INFOBOTTOM", $this->txt('info_bottom'));
        $my_tpl->setVariable("HEADLINE_INFO_REQUIREMENTS", $this->txt('info_requirements_headline'));
        $my_tpl->setVariable("INFO_REQUIREMENTS", $this->txt('info_requirements_' . $apiPostFix));

        // GUEST LINK
        $vcAllowedGuestLink = $vcObj instanceof ilApiBBB || $vcObj instanceof ilApiWebex;
        if($vcAllowedGuestLink && $vcObj->isUserModerator() && $this->object->isGuestlink()) {
            $my_tpl->setVariable("UNHIDE_GUESTLINK", 'un');
            #echo '<pre>'; var_dump($vcObj->isUserModerator()); exit;
            #if( $vcAllowedGuestLink && $vcObj->isUserModerator() && $this->object->get_moderated() && $this->object->isGuestlink() ) {
            // GASTLINK für Webex getScheduledSessionByRefIdAndDateTime
            if($this->isWebex) {
                $upcoming = $this->object->getScheduledMeetingsByDateFrom(date('Y-m-d H:i:s'), $this->object->getRefId());
                $guestLinkUrl = !is_null($upcoming) ? json_decode($upcoming[0]['rel_data'])->webLink : '';
            } else {
                $guestLinkUrl = $vcObj->getInviteUserUrl();
            }
            // Only Webex
            $isWebex = $apiPostFix === 'webex';
            $webexData = $this->object->getWebexMeetingByRefIdAndDateTime($this->ref_id, null, ilObjMultiVc::MEETING_TIME_AHEAD);
            if($isWebex && null !== $webexData) {
                $guestLinkUrl = $webexData['rel_data']->webLink . '&launchApp=true';
            }

            // Template
            $my_tpl->setVariable("HEADLINE_GUESTLINK", $this->txt('guestlink'));
            $my_tpl->setVariable("userInviteInfo", $this->txt('user_invite_info'));
            $my_tpl->setVariable("userInviteUrl", $guestLinkUrl);
            // if isset guestLinkPw
            if($this->isBBB && (bool) strlen($guestPw = trim($this->object->getAccessToken()))) {
                $pwExpired = $this->object->isSecretExpired();
                $my_tpl->setVariable("guestLinkPwInfo", $this->txt('guest_link_pw_info'));
                $my_tpl->setVariable("guestLinkPw", rawurldecode($guestPw));
                if($pwExpired) {
                    $msgPwExpired = $this->txt('bbb_secret_expired_msg');
                    $my_tpl->setVariable("guestLinkPwExpired", $this->getFormFieldMessage($msgPwExpired));
                    #$this->dic->ui()->mainTemplate()->setMessage('failure', $msgPwExpired);
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $msgPwExpired, true);
                }
            } else {
                $my_tpl->setVariable("guestLinkPwHidden", ' hidden');
            }
        } else {
            #$my_tpl->setVariable("HIDE_GUESTLINK", 'hidden');
        }


        // RECORDINGS
        if($this->isTeams == false && ($this->object->isRecordingAllowed() && $this->isBBB || $this->object->isRecordingAllowed() && $vcObj->isUserModerator())) {
            $my_tpl->setVariable("UNHIDE_RECORDINGS", 'un');
            $my_tpl->setVariable("HEADLINE_RECORDING", $this->txt('recording'));
            $my_tpl->setVariable("RECORDINGS", $this->getShowRecordings($vcObj));
        }

        // TABLE LIST MEETINGS (Webex & Edudip)
        if(false !== ($showListMeetings = array_search(get_class($vcObj), ['ilApiWebex', 'ilApiEdudip']))) {
            /*
            if( !$this->isEdudip ) {
                $currMeeting = $this->object->getWebexMeetingByRefIdAndDateTime($this->ref_id, null, ilObjMultiVc::MEETING_TIME_AHEAD);
            } else {
                $currMeeting = $this->object->getScheduledMeetingsByDateFrom(date('Y-m-d H:i:s'), $this->ref_id);
            }
            */
            $currMeeting = $this->object->getScheduledMeetingsByDateFrom(date('Y-m-d H:i:s'), $this->ref_id);
            #echo '<pre>'; var_dump($currMeeting); exit;
            #if( $showListMeetings = is_null($currMeeting) ) {
            #if( !is_null($currMeeting) && $this->object->checkAndSetMultiVcObjUserAsAuthUser($currMeeting['user_id'], $currMeeting['auth_user'] ) ) {
            if(!is_null($currMeeting)) {
                if($this->object->get_moderated() && $vcObj->isUserModerator()) {
                    $my_tpl->setVariable("INFOTOP", $this->txt('info_top_moderated_' . $this->platform));
                }
                $tblListMeetings = new ilMultiVcTableGUIListMeetings($this, 'showContent');
                $my_tpl->setVariable("UNHIDE_LIST_MEETINGS", 'un');
                $my_tpl->setVariable("HEADLINE_LIST_MEETINGS", $this->txt('scheduled_' . $this->sessType . 's'));
                $my_tpl->setVariable("LIST_MEETINGS", $tblListMeetings->getHTML());
            }
        }
        /*
        if( !$showListMeetings) {
            $my_tpl->setVariable("LIST_MEETINGS", '');
            $my_tpl->setVariable("HIDE_LIST_MEETINGS", 'hidden');
        }
        */
        $tpl->setContent($my_tpl->get());
    }

    private function showContentWindowClose()
    {
        $tpl = $this->dic->ui()->mainTemplate();
        $tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.window_close.js", true, 3);
        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.window_close.html", true, true);
        $my_tpl->setVariable('LINK_CLOSE', $this->lng->txt('rep_robj_xmvc_tab_close'));
        $tpl->setContent($my_tpl->get());
    }

    /**
     * @throws ilTemplateException
     */
    private function showContentWindowWebex(string $url, mixed $data)
    {
        $tpl = $this->dic->ui()->mainTemplate();
        #echo '<pre>'; var_dump($data['rel_data']); exit;
        $relData = $data['rel_data']; # json_decode($data['rel_data']);
        $participants = json_decode($data['participants'], 1);
        $attendee = $participants['attendee'][$this->dic->user()->getId()];

        list($ilUrl, $ilQuery) = explode('?', $this->dic->http()->request()->getUri());
        $backUrl = '';
        //        $rqUri = $this->dic->http()->request()->getUri();
        //        $backUrl = ILIAS_HTTP_PATH . '/' . substr($rqUri, strpos($rqUri, 'ilias.php')) . '&startWEBEX=10';

        $email = $attendee['email'];
        $tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/webex/tpl.window_join.js", true, 3);
        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/webex/tpl.window_join.html", true, true);
        $my_tpl->setVariable("SITEURL", $url);
        $my_tpl->setVariable("MEETINGKEY", $relData->meetingNumber);
        $my_tpl->setVariable("MEETINGPASSWORD", $relData->password);
        $my_tpl->setVariable("ATTENDEENAME", $this->dic->user()->getFullname());
        $my_tpl->setVariable("ATTENDEEEMAIL", $email);
        $my_tpl->setVariable("BACKURL", $backUrl);
        $tpl->setContent($my_tpl->get());
        //        $tpl->addOnLoadCode('$("body", document).hide();');
    }

    private function showContentWindowRedirect()
    {
        $tpl = $this->dic->ui()->mainTemplate();

        $redirectUrl = str_replace('windowBBB', 'startBBB', $this->dic->http()->request()->getUri());
        // $tpl->addCss('./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/src/css/three-dots.css');
        $tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.window_redirect.js", true, 3);
        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.window_redirect.html", true, true);
        $my_tpl->setVariable("REDIRECTMSG", $this->lng->txt('rep_robj_xmvc_redirect_msg'));
        $my_tpl->setVariable("REDIRECTURL", $redirectUrl);
        $tpl->setContent($my_tpl->get());
    }

    private function showContentUnavailable()
    {
        $tpl = $this->dic->ui()->mainTemplate();

        $my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.unavailable.html", true, true);

        $my_tpl->setVariable("UNAVAILABLE", $this->lng->txt('rep_robj_xmvc_service_unavailable'));

        $tpl->setContent($my_tpl->get());
    }


    /**
     * @throws Exception
     */
    private function redirectToPlatformByUrl(string $url, ilApiBBB|ilApiOM|ilApiWebex|ilApiEdudip|ilApiTeams|null $vcObj = null): void
    {
        if(!is_null($vcObj) && $vcObj instanceof ilApiBBB) {
            $this->object->setUserLog('bbb', $vcObj);
        }
        header('Status: 303 See Other', false, 303);
        header('Location:' . $url);
        exit;
    }

    /**
     * @throws ilTemplateException
     * @throws Exception
     */
    private function getJoinContent(ilApiBBB|ilApiOM|ilApiWebex|ilApiTeams $vcObj): string
    {
        $sessAuthUserIsValid = true;

        $showBtn = (
            (!$this->object->get_moderated() /* && $vcObj->isValidAppointmentUser() */) ||
            //( $this->object->get_moderated() && $bbb->hasSessionObject() && $bbb->isValidAppointmentUser() ) ||
            ($this->object->get_moderated() && ($vcObj->isUserModerator() || $vcObj->isUserAdmin())) ||
            ($this->object->get_moderated() && $vcObj->isMeetingRunning() && $vcObj->isModeratorPresent() /* && $vcObj->isValidAppointmentUser() */)
        );

        // Only Webex & Edudip
        $isWebex = get_class($vcObj) === 'ilApiWebex';
        $isEdudip = get_class($vcObj) === 'ilApiEdudip';
        $isTeams = get_class($vcObj) === 'ilApiTeams';
        $hasSessionProvider = false !== array_search(get_class($vcObj), ['ilApiWebex', 'ilApiEdudip', 'ilApiTeams']);
        $isModOrAdmin = $vcObj->isUserModerator() || $vcObj->isUserAdmin();
        #$webexData = $isWebex ? $this->object->getWebexMeetingByRefIdAndDateTime($this->ref_id, null, !$isModOrAdmin ? 0 : ilObjMultiVc::MEETING_TIME_AHEAD) : null;
        /*
        $sessData =
            $hasSessionProvider
                ? !$isEdudip
                    ? $this->object->getScheduledSessionByRefIdAndDateTime($this->ref_id, null, !$isModOrAdmin ? 0 : ilObjMultiVc::MEETING_TIME_AHEAD)
                    : $this->object->getScheduledMeetingsByDateFrom(date('Y-m-d H:i:s'), $this->ref_id)
                    #: $this->object->getScheduledMeetingsByDateRange(date('Y-m-d H:i:s'), date('Y-m-d H:i:s') + 60*60*24, $this->ref_id)
                : null;
        */
        $timezone = 'Europe/Berlin';
        if ($isTeams) {
            $timezone = 'UTC';
        }
        $sessData = $hasSessionProvider ? $this->object->getScheduledMeetingsByDateFrom(date('Y-m-d H:i:s'), $this->ref_id, $timezone) : null;
        #echo '<pre>'; var_dump($sessData); exit;
        #$showBtn = $isWebex ? null !== $webexData : $showBtn;
        $showBtn = $hasSessionProvider ? !is_null($sessData) : $showBtn;
        $showBtn = $hasSessionProvider && $showBtn && (!$isModOrAdmin && !$isEdudip && !$isTeams) ? $this->object->hasScheduledSessionModerator($this->ref_id, $sessData[0]['rel_id'], $sessData[0]['user_id']) : $showBtn;
        #echo '<pre>'; var_dump($showBtn); exit;
        $showJsIsMeetingRunning = false;
        if(!is_null($sessData) && $this->isWebex && !$isModOrAdmin
            && $showBtn = date('Y-m-d H:i:s') >= $sessData[0]['start']) {
            $showJsIsMeetingRunning = true;
            $showBtn = $vcObj->isMeetingRunning($sessData[0]['rel_id']);
        }

        // All VC
        if($showBtn) {

            $btnEvent = !$vcObj->isModeratorPresent()
            && ($vcObj->isUserModerator() || $vcObj->isUserAdmin() || !$this->object->get_moderated())
                ? 'start'
                : 'join';
            $tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.join_btn.html", true, true);
            $joinBtnText = $this->lng->txt('rep_robj_xmvc_btntext_' . $btnEvent . '_' . $this->sessType);
            $vcType = strtoupper(ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent());
            $startType = self::START_TYPE[$vcType];
            #echo '<pre>'; var_dump([$vcType, $startType]); exit;
            if($this->isWebex && $isModOrAdmin && $this->object->isUserOwner()) {
                $startType = 'start';
            }

            $joinBtnUrl = ILIAS_HTTP_PATH . '/' . $this->dic->ctrl()->getLinkTarget($this, 'showContent')
                . '&amp;' . $startType . $vcType . '=1';

            $tpl->setVariable("JOINBTNURL", $joinBtnUrl);
            $tpl->setVariable("JOINBTNTEXT", $joinBtnText);
        } else {
            $tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.wait_msg.html", true, true);
            if($isWebex) {
                if($showJsIsMeetingRunning) {
                    //                    $tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.xhrIsMeetingRunning.js",true, 2);
                    //$this->dic->ui()->mainTemplate()->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.xhrIsMeetingRunning.js", true, 2);
                }
                $tpl->setVariable("WAITMSG", str_replace('{br}', '<br />', $this->lng->txt('rep_robj_xmvc_wait_join_meeting_webex')));
            } elseif ($isEdudip) {
                $tpl->setVariable("WAITMSG", $this->lng->txt('rep_robj_xmvc_wait_join_meeting_edudip'));
            } elseif ($isTeams) {
                $tpl->setVariable("WAITMSG", str_replace('{br}', '<br />', $this->lng->txt('rep_robj_xmvc_wait_join_meeting_teams')));
            } else {
                $tpl->setVariable("WAITMSG", $this->lng->txt('rep_robj_xmvc_wait_join_meeting'));
            }
        }

        $content = $tpl->get();

        // if webex user show meeting password
        if($isWebex && !$isModOrAdmin && $showBtn) {
            //check
            $webexData = $isWebex ? $this->object->getWebexMeetingByRefIdAndDateTime($this->ref_id, null, !$isModOrAdmin ? 0 : ilObjMultiVc::MEETING_TIME_AHEAD) : null;
            $tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.meeting_password.html", true, true);
            $tpl->setVariable("MEETING_PASSWORD", $webexData['rel_data']->password);
            $content .= $tpl->get();
        }

        $showAdmInfoMeeting = $vcObj->hasSessionObject() && ($vcObj->isUserModerator() || $vcObj->isUserAdmin());
        if($showAdmInfoMeeting) {
            $tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/templates/default/tpl.adm_info_appointment.html", true, true);
            $tpl->setVariable("ADM_INFO", $this->lng->txt('rep_robj_xmvc_adm_info_appointment'));
            $content .= $tpl->get();
        }

        return $content;

    }

    /**
     * @throws ilPluginException
     */
    private function getShowRecordings(ilApiBBB|ilApiOM $vcObj, array $getRecId = [], bool $returnRawData = false): array|string
    {
        global $DIC;

        $settings = ilMultiVcConfig::getInstance($this->object->getConnId());

        $recData = $vcObj->getRecordings();

        if(count($recData) > 0 && count($getRecId) > 0) {
            $tmpData = [];
            foreach($recData as $key => $data) {
                if(in_array($key, $getRecId)) {
                    $tmpData[] = $data;
                }
            }
            $recData = $tmpData;
        }

        if($returnRawData) {
            return $recData;
        }

        $table = $this->isBBB
            ? new ilMultiVcTableGUIRecordingsBBB($this, $this->dic->ctrl()->getCmd())
            : new ilMultiVcRecordingsTableGUI($this, $this->dic->ctrl()->getCmd());
        $table->setData($table->addRowSelector($recData));
        $tblAppend = $this->isBBB && !($this->vcObj->isUserModerator() || $this->vcObj->isUserAdmin())
            ? $this->getUiCompMsgBox('info', $this->txt('hint_availability_recs'))
            : '';
        return $table->getHTML() . $tblAppend;
    }

    private function confirmDeleteRecords(): void
    {
        $this->tabs->activateTab("content");//necessary...

        $this->redirectIfNoRecordingsSelected();

        $c_gui = new ilConfirmationGUI();

        // set confirm/cancel commands
        $c_gui->setFormAction($this->ctrl->getFormAction($this, "showContent"));
        $c_gui->setHeaderText($this->lng->txt("info_delete_sure"));
        $c_gui->setCancel($this->lng->txt("cancel"), "showContent");
        $c_gui->setConfirm($this->lng->txt("confirm"), "deleteRecords");

        // add items to delete
        $vcObj = ilMultiVcConfig::getInstance($this->object->getConnId())->getShowContent() === 'bbb' ? new ilApiBBB($this) : new ilApiOM($this);
        $recIds = $this->dic->http()->wrapper()->post()->retrieve('rec_id', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
        $records = $this->getShowRecordings($vcObj, $recIds, true);
        foreach ($recIds as $recId) {
            $key = array_search($recId, $records);
            //$file = new ilCourseFile($file_id);
            #            die(var_dump($records[$key]));
            $records[$key]['START_TIME'] = new ilDateTime($records[$key]['START_TIME'], IL_CAL_UNIX);
            $records[$key]['END_TIME'] = new ilDateTime($records[$key]['END_TIME'], IL_CAL_UNIX);
            $cGuiItemContent = ilDatePresentation::formatDate($records[$key]['START_TIME']) . ' - ' . ilDatePresentation::formatDate($records[$key]['END_TIME']) . ' &nbsp; ' . $records[$key]['playback'];
            $c_gui->addItem("rec_id[]", $recId, $cGuiItemContent);
        }

        $this->tpl->setContent($c_gui->getHTML());
    }

    private function setRecordAvailable(): void
    {
        $this->redirectIfNoRecordingsSelected();
        #echo '<pre>'; var_dump($_POST["rec_id"]); exit;
        $recIds = $this->dic->http()->wrapper()->post()->retrieve('rec_id', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
        foreach ($recIds as $key => $recId) {
            $this->object->updateBBBRec($this->object->getRefId(), $recId, 1);
        }
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', count($recIds) . ' ' . $this->lng->txt('rep_robj_xmvc_recs_published'), true);
        $this->dic->ctrl()->redirect($this, 'showContent');
    }

    private function setRecordLocked(): void
    {
        $this->redirectIfNoRecordingsSelected();
        $recIds = $this->dic->http()->wrapper()->post()->retrieve('rec_id', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
        foreach ($recIds as $key => $recId) {
            $this->object->updateBBBRec($this->object->getRefId(), $recId, 0);
        }
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', count($recIds) . ' ' . $this->lng->txt('rep_robj_xmvc_recs_locked'), true);
        $this->dic->ctrl()->redirect($this, 'showContent');
    }

    private function redirectIfNoRecordingsSelected(): void
    {
        if (!$this->dic->http()->wrapper()->post()->has('rec_id') || count($this->dic->http()->wrapper()->post()->retrieve('rec_id', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()))) == 0) {//!(bool)sizeof($_POST['rec_id'])) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->lng->txt('select_one'), true);
            $this->dic->ctrl()->redirect($this, 'showContent');
            #$this->showContent();
        }
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

        if(!($vcObj instanceof ilApiBBB) && !($vcObj instanceof ilApiOM)) {
            return $success;
        }

        if($this->object->isRecordingAllowed() && $this->dic->http()->wrapper()->post()->has('rec_id')) {
            $recIds = $this->dic->http()->wrapper()->post()->retrieve('rec_id', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
            foreach ($recIds as $recId) {
                $recToDelete = $vcObj->deleteRecord($recId);
                if(!$success) {
                    $success = true;
                }
                if($recToDelete instanceof \BigBlueButton\Responses\DeleteRecordingsResponse && $recToDelete->isDeleted()) {
                    $this->object->deleteBBBRecById($this->ref_id, $recId);
                    #echo '<pre>'; var_dump($recDeleted); exit;
                } else {
                    $this->object->updateBBBRec($this->ref_id, $recId, 0, 'delete');
                }
            }
            return $success;
        }
        return $success;
    }

    public function getFormFieldMessage(string $msg, string $type = 'alert'): string
    {
        $tpl = new ilTemplate("tpl.property_form.html", true, true, "Services/Form");

        $tpl->setCurrentBlock($type);
        $tpl->setVariable("IMG_" . strtoupper($type), ilUtil::getImagePath("icon_" . $type . ".svg"));
        $tpl->setVariable("ALT_" . strtoupper($type), $this->dic->language()->txt($type));
        $tpl->setVariable("TXT_" . strtoupper($type), $msg);
        $tpl->parseCurrentBlock();
        $content = trim($tpl->get($type));

        return $content;
    }

    private function prepareRoomOM(ilApiOM $om)
    {
        $roomId = $this->object->getRoomId();
        if(!is_int($roomId) || $roomId === 0) {
            $roomId = $om->createRoom();
            $this->object->updateRoomId($roomId);
        } else {
            // only proc if debug is true in plugin.ini
            #if( !!(bool)$om->getPluginIniSet('debug') ) {
            $rVal = $om->updateRoom($roomId);
            if($rVal !== $roomId) {
                $this->object->updateRoomId($rVal);
            }
            #}
        }
    }

    #################################################################################################
    #### Webex
    #################################################################################################

    public function authorizeWebexIntegration()
    {
        ilApiWebexIntegration::init($this);
    }

    /**
     * @throws ilCurlConnectionException
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     * @throws Exception
     */
    private function createWebexMeeting(): bool|string
    {
        $meetingDuration = $this->dic->http()->wrapper()->post()->retrieve('meeting_duration', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()));
        //        $start = new DateTime(filter_var($_POST['meeting_duration']['start'], FILTER_SANITIZE_STRING));
        $start = new DateTime($meetingDuration[0]);//'start']);
        $dateTimeStart = $start->format('Y-m-d H:i:s');
        //        $end = new DateTime(filter_var($_POST['meeting_duration']['end'], FILTER_SANITIZE_STRING));
        $end = new DateTime($meetingDuration[1]);//['end']);
        $dateTimeEnd = $end->format('Y-m-d H:i:s');

        $vcObj = $this->vcObj ?? new ilApiWebex($this);
        $data = [
            'title' => $this->dic->http()->wrapper()->post()->retrieve('meeting_title', $this->dic->refinery()->kindlyTo()->string()),
            'agenda' => $this->dic->http()->wrapper()->post()->retrieve('meeting_agenda', $this->dic->refinery()->kindlyTo()->string()),
            'start' => $dateTimeStart,
            'end' => $dateTimeEnd,
        ];
        $response = $vcObj->sessionCreate($data);
        $response = json_decode($response, 1);
        $response["email"] = $response["hostEmail"];
        $response = json_encode($response);
        return ($response);
    }

    /**
     * Edit the learning progress settings
     */
    protected function editLPSettings()
    {
        $this->tabs_gui->activateTab('learning_progress');
        $this->tabs_gui->activateSubTab('lp_settings');

        $this->initFormLPSettings();
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * Init the form for Learning progress settings
     */
    protected function initFormLPSettings()
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('settings'));

        $rg = new ilRadioGroupInputGUI($this->txt('lp_mode'), 'lp_mode');
        $rg->setRequired(true);
        $rg->setValue($this->object->getLPMode());
        $ro = new ilRadioOption($this->txt('lp_inactive'), "0", $this->txt('lp_inactive_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->txt('lp_active'), "1", $this->txt('lp_active_info'));

        $ni = new ilNumberInputGUI($this->txt('lp_time'), 'lp_time');
        $ni->setMinValue(0);
        $ni->setMaxValue(100);
        $ni->setDecimals(0);
        $ni->setSize(3);
        $ni->setRequired(true);
        $ni->setValue($this->object->getLpTime());
        $ni->setInfo($this->txt('lp_time_info'));
        $ro->addSubItem($ni);

        $rg->addOption($ro);
        $form->addItem($rg);

        $form->addCommandButton('updateLPSettings', $this->lng->txt('save'));
        $this->form = $form;

    }

    /**
     * Update the LP settings
     */
    protected function updateLPSettings()
    {
        $this->tabs_gui->activateTab('learning_progress');
        $this->tabs_gui->activateSubTab('lp_settings');

        $this->initFormLPSettings();
        if (!$this->form->checkInput()) {
            $this->form->setValuesByPost();
            $this->tpl->setContent($this->form->getHTML());
            return;
        }

        $this->object->setLPMode((int) $this->form->getInput('lp_mode'));
        $this->object->setLpTime((int) $this->form->getInput('lp_time'));
        $this->object->update();
        $this->dic->ctrl()->redirect($this, 'editLPSettings');
    }

    protected function lpUserResults()
    {
        $this->initTabContent();
        //        $this->tabs_gui->activateTab('content'); //learning_progress
        $this->tabs_gui->activateSubTab('lp_user_results');

        $lpUserResultsGUI = new ilMultiVcLpUserResultsGUI($this);
        $this->dic->ui()->mainTemplate()->setContent($lpUserResultsGUI->getHTML());
    }
}

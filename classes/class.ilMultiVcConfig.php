<?php

use ILIAS\DI\Container;

/**
 * MultiVc configuration class
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 *
 */
class ilMultiVcConfig
{
    #region PROPERTIES
    public const PLUGIN_ID = 'xmvc';
    public const AVAILABLE_VC_CONN = [
        'bbb'		=> 'BigBlueButton',
        'edudip'     => 'Edudip',
        'om'		=> 'Openmeetings',
        'webex'     => 'Webex'
    ];
    public const AVAILABLE_XMVC_API = [
        'webex'     => 'ilApiWebex',
        'edudip'     => 'ilApiEdudip',
        'bbb'		=> 'ilApiBBB',
        'om'		=> 'ilApiOM'
    ];
    public const AVAILABLE_Webex_API = [
        'admin' => 'Admin Scopes',
        'integration' => 'User Scopes'
    ];
    public const INTEGRATION_AUTH_METHODS = [
        'admin' => 'Admin Scope',
        'user' => 'User Scope'
    ];
    public const AVAILABILITY_NONE = 0;  // Type is not longer available (error message)
    public const AVAILABILITY_EXISTING = 1; // Existing objects of the can be used, but no new created
    public const AVAILABILITY_CREATE = 2;  // New objects of this type can be created

    public const MEETING_LAYOUT_CUSTOM = 1;
    public const MEETING_LAYOUT_SMART = 2;
    public const MEETING_LAYOUT_PRESENTATION_FOCUS = 3;
    public const MEETING_LAYOUT_VIDEO_FOCUS = 4;

    public const ADMIN_DEFINED_TOKEN_VC = [
        'edudip'
    ];

    public const VC_RELATED_FUNCTION = [
        'globalAssignedRoles' => [
            'bbb', 'edudip', 'webex', 'om'
        ],
        'maxDuration' => [
            'bbb'
        ]
    ];

    /** @var Container $dic */
    private Container $dic;
    /** @var ilDBInterface $db */
    private ilDBInterface $db;

    private static ?ilMultiVcConfig $instance = null;

    /** @var int|null $conn_id */
    private ?int $conn_id = null;
    private string $title = '';
    private int $availability = 0;
    private string $hint = '';
    private string $objIdsSpecial = '';
    private bool $protected = true;
    private bool $moderatedChoose = false;
    private bool $moderatedDefault = true;
    private bool $btnSettingsChoose = false;
    private bool $btnSettingsDefault = false;
    private bool $btnChatChoose = false;
    private bool $btnChatDefault = false;
    private bool $withChatChoose = false;
    private bool $withChatDefault = true;
    private bool $btnLocationshareChoose = false;
    private bool $btnLocationshareDefault = false;
    private bool $memberBtnFileuploadChoose = false;
    private bool $memberBtnFileuploadDefault = false;
    private bool $faExpandDefault = false;
    private string $svrPublicUrl = '';
    private int $svrPublicPort = 443;
    private string $svrPrivateUrl = '';
    private ?int $svrPrivatePort = null;
    private ?string $svrSalt = null;
    private ?string $svrUsername = null;
    private int $maxParticipants = 0;
    private int $maxDuration = 0;
    private ?string $showContent = null;
    private bool $privateChatChoose = false;
    private bool $privateChatDefault = true;
    private bool $recordChoose = false;
    private bool $recordDefault = false;
    private bool $recordOnlyForModeratedRoomsDefault = true;
    private bool $pubRecsChoose = false;
    private bool $pubRecsDefault = false;
    private bool $showHintPubRecs = false;
    private ?string $hideRecsUntilDate = null;
    private bool $disableSip = false;
    private bool $hideUsernameInLogs = true;
    private bool $recordOnlyForModeratorChoose = false;
    private bool $recordOnlyForModeratorDefault = true;
    // eof todo

    private bool $camOnlyForModeratorChoose = false;
    private bool $camOnlyForModeratorDefault = false;
    private bool $lockDisableCamDefault = false;
    private bool $lockDisableCamChoose = false;
    private string $addPresentationUrl = '';
    private string $style = '';
    private string $logo = '';
    private bool $addWelcomeText = false;
    private array $moreOptions = [
        'camOnlyForModerator' => [ 'choose' => false, 'default' => false ],
        'privateChat' => [ 'choose' => false, 'default' => true ],
        'recording' => [ 'choose' => false, 'default' => false ],
        'recordingOnlyForModerator' => [ 'choose' => false, 'default' => true ],
    ];
    public object $option;

    private array $objConfigAvailSetting = [
        'bbb'   => [
            'moderatedChoose',
            'privateChatChoose',
            'recordChoose',
            'pubRecs',
            'camOnlyForModeratorChoose',
            'lockDisableCam',
            'guestlinkChoose'
        ],
        'webex'   => [
            'moderatedChoose',
            'privateChatChoose',
            'recordChoose',
            'camOnlyForModeratorChoose',
            'guestlinkChoose',
            'extraCmd'
        ],
        'edudip'   => [
            'moderatedChoose',
            'privateChatChoose',
            'recordChoose'
        ],
        'om'   => [
            'moderatedChoose',
            'privateChatChoose',
            'recordChoose'
        ],
    ];

    private bool $guestlink_choose = false;
    private bool $guestlink_default = false;
    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private ?string $tokenUser = null;
    private ?string $api = '';
    private ?string $authMethod = '';
    private bool $extraCmdDefault = false;
    private bool $extraCmdChoose = false;
    private ?array $assignedRoles = null;
    private int $meetingLayout = 2;
    #endregion PROPERTIES

    #region INIT READ WRITE

    /**
     * @param string $component VC e. g. bbb
     * @return array
     */
    public function getObjConfigAvailSetting(string $component = ''): array
    {
        if(!(bool)$component) {
            return $this->objConfigAvailSetting;
        }

        return $this->objConfigAvailSetting[$component];

    }

    /**
     * @param string $search
     * @param string $component
     * @return bool
     */
    public function isObjConfig(string $search): bool
    {
        return false !== array_search($search, $this->objConfigAvailSetting[$this->getShowContent()]);
    }


    /**
     * Constructor
     * @param int|null $connId
     */
    public function __construct(?int $connId = null)
    {
        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;
        $this->db = $DIC->database();

        if(!is_null($connId)) {
            $this->read($connId);
        }
    }

    /**
     * Get singleton instance
     *
     * @param int|null $connId
     * @return ilMultiVcConfig
     */
    public static function getInstance(?int $connId = null): self
    {
        if(self::$instance instanceof self && is_null($connId)) {
            return self::$instance;
        }
        return self::$instance = new ilMultiVcConfig($connId);
    }

    public function create(): void
    {
        $this->save(false);
    }


    /**
     * @param bool $update
     */
    public function save(bool $update = true): void
    {
        $ilDB = $this->db;

        if(!!(bool)$this->getConnId()) {
            if($this->hasPlatformChanged()) {
                $this->setDefaultValues();
            }
        }

        $a_data=array(
            'title'		                    => ['text', $this->getTitle()],
            'hint'		                    => ['text', $this->getHint()],
            'availability'		            => ['integer', (int)$this->getAvailability()],
            'obj_ids_special'				=> array('text',$this->get_objIdsSpecial()),
            'protected'						=> array('integer', $this->ilBoolToInt($this->get_protected())),
            'moderated_choose'				=> array('integer', $this->ilBoolToInt($this->get_moderatedChoose())),
            'moderated_default'				=> array('integer', $this->ilBoolToInt($this->get_moderatedDefault())),
            'btn_settings_choose'			=> array('integer', $this->ilBoolToInt($this->get_btnSettingsChoose())),
            'btn_settings_default'			=> array('integer', $this->ilBoolToInt($this->get_btnSettingsDefault())),
            'btn_chat_choose'				=> array('integer', $this->ilBoolToInt($this->get_btnChatChoose())),
            'btn_chat_default'				=> array('integer', $this->ilBoolToInt($this->get_btnChatDefault())),
            'with_chat_choose'				=> array('integer', $this->ilBoolToInt($this->get_withChatChoose())),
            'with_chat_default'				=> array('integer', $this->ilBoolToInt($this->get_withChatDefault())),
            'btn_locationshare_choose'		=> array('integer', $this->ilBoolToInt($this->get_btnLocationshareChoose())),
            'btn_locationshare_default'		=> array('integer', $this->ilBoolToInt($this->get_btnLocationshareDefault())),
            'member_btn_fileupload_choose'	=> array('integer', $this->ilBoolToInt($this->get_memberBtnFileuploadChoose())),
            'member_btn_fileupload_default'	=> array('integer', $this->ilBoolToInt($this->get_memberBtnFileuploadDefault())),
            'fa_expand_default'				=> array('integer', $this->ilBoolToInt($this->get_faExpandDefault())),
            'svrpublicurl'					=> ['string', $this->getSvrPublicUrl()],
            'svrpublicport'					=> ['integer', $this->getSvrPublicPort()],
            'svrprivateurl'					=> ['string', $this->getSvrPrivateUrl()],
            'svrprivateport'				=> ['integer', $this->getSvrPrivatePort()],
            'svrsalt'					    => ['string', $this->getSvrSalt()],
            'maxparticipants'			    => ['integer', $this->getMaxParticipants()],
            'max_duration'			        => ['integer', $this->getMaxDuration()],
            'showcontent'			        => ['string', $this->getShowContent()],
            'private_chat_choose'		    => ['integer', (int)$this->isPrivateChatChoose()],
            'private_chat_default'		    => ['integer', (int)$this->isPrivateChatDefault()],
            'recording_choose'		        => ['integer', (int)$this->isRecordChoose()],
            'recording_default'		        => ['integer', (int)$this->isRecordDefault()],
            'record_only_moderated_rooms' => ['integer', (int)$this->isRecordOnlyForModeratedRoomsDefault()],
            'pub_recs_choose' => ['integer', (int)$this->getPubRecsChoose()],
            'pub_recs_default' => ['integer', (int)$this->getPubRecsDefault()],
            'show_hint_pub_recs' => ['integer', (int)$this->getShowHintPubRecs()],
            'hide_recs_until_date' => ['datetime', $this->getHideRecsUntilDate()],
            'cam_only_moderator_choose' => ['integer', (int)$this->isCamOnlyForModeratorChoose()],
            'cam_only_moderator_default' => ['integer', (int)$this->isCamOnlyForModeratorDefault()],
            'lock_disable_cam' => ['integer', (int)$this->getLockDisableCamChoose()],
            'lock_disable_cam_default' => ['integer', (int)$this->getLockDisableCamDefault()],
            'svrUsername'		         => ['string', $this->getSvrUsername()],
            'guestlink_choose' => ['integer', (int)$this->isGuestlinkChoose()],
            'guestlink_default' => ['integer', (int)$this->isGuestlinkDefault()],
            'add_presentation_url' => ['string', $this->getAddPresentationUrl()],
            'add_welcome_text' => ['integer', (int)$this->issetAddWelcomeText()],
            'disable_sip' => ['integer', (int)$this->getDisableSip()],
            'hide_username_logs' => ['integer', (int)$this->getHideUsernameInLogs()],
            'access_token'  => ['string', $this->getAccessToken()],
            'refresh_token'  => ['string', $this->getRefreshToken()],
            #'api'  => ['string', $this->getApi()],
            'auth_method'  => ['string', $this->getAuthMethod()],
            'extra_cmd_choose' => ['integer', (int)$this->getExtraCmdChoose()],
            'extra_cmd_default' => ['integer', (int)$this->getExtraCmdDefault()],
            'style'				=> ['string', $this->getStyle()],
            'logo'				=> ['string', $this->getLogo()],
            'assigned_roles'	=> ['string', implode(',', $this->getAssignedRoles() ?? [])],
            'meeting_layout'    => ['integer', (int)$this->getMeetingLayout()],
            //'more_options'			        => ['string', json_encode($this->option)],
        );
        //var_dump($a_data); exit;

        $result = $ilDB->query("SELECT * FROM rep_robj_xmvc_conn");
        $numConn = $ilDB->numRows($result);

        if(!$update) {
            $result = $ilDB->query("SELECT MAX(id) id FROM rep_robj_xmvc_conn");
            $row = $ilDB->fetchObject($result);
            $connId = (bool)$numConn ? (int)$row->id + 1 : 1;
            $this->setConnId($connId);
        }
        if(!$update || $numConn === 0) {
            $a_data['id'] = array('integer', $this->getConnId());
            $ilDB->insert('rep_robj_xmvc_conn', $a_data);
        } else {
            $ilDB->update('rep_robj_xmvc_conn', $a_data, array('id' => array('integer', $this->getConnId())));
        }
    }

    public function keepSvrSalt(): void
    {
        $query = $this->db->query("SELECT svrsalt FROM rep_robj_xmvc_conn WHERE id = " . $this->getConnId());
        $row = $this->db->fetchObject($query);
        $this->svrSalt = $row->svrsalt;
    }

    private function hasPlatformChanged(): bool
    {
        if(is_null($this->getConnId())) {
            //return true;
        }
        $result = $this->db->query("SELECT showcontent FROM rep_robj_xmvc_conn where id =  " . $this->getConnId());
        $row = $this->db->fetchAssoc($result);
        $initialEntry = null === $row;
        switch(true) {
            case $initialEntry:
            case !$initialEntry && $row['showcontent'] === $this->getShowContent():
                $hasChanged = false;
                break;
            default:
                $hasChanged = true;
        }
        return $hasChanged;
    }

    public function hasInitialDbEntry(): bool
    {
        $result = $this->db->query("SELECT showcontent FROM rep_robj_xmvc_conn where id =  1");
        $row = $this->db->fetchAssoc($result);
        return !(null === $row);
    }

    public function setDefaultValues(array $exclude = ['obj_ids_special', 'showcontent']): void
    {
        $this->objIdsSpecial = (false !== array_search('obj_ids_special', $exclude)) ? $this->get_objIdsSpecial() : '';
        $this->protected = true;
        $this->moderatedChoose = false;
        $this->moderatedDefault = true;
        $this->btnSettingsChoose = false;
        $this->btnSettingsDefault = false;
        $this->btnChatChoose = false;
        $this->btnChatDefault = false;
        $this->withChatChoose = false;
        $this->withChatDefault = true;
        $this->btnLocationshareChoose = false;
        $this->btnLocationshareDefault = false;
        $this->memberBtnFileuploadChoose = false;
        $this->memberBtnFileuploadDefault = false;
        $this->faExpandDefault = false;
        $this->svrPublicUrl = '';
        $this->svrPublicPort = 443;
        $this->svrPrivateUrl = '';
        $this->svrPrivatePort = 443;
        $this->svrSalt = '';
        $this->svrUsername = '';
        $this->maxParticipants = 20;
        $this->maxDuration = 0;
        //$this->showContent
        $this->privateChatChoose = false;
        $this->privateChatDefault = true;
        $this->recordChoose = false;
        $this->recordDefault = false;
        $this->recordOnlyForModeratedRoomsDefault = true;
        $this->camOnlyForModeratorChoose = false;
        $this->camOnlyForModeratorDefault = false;
        $this->lockDisableCamChoose = false;
        $this->lockDisableCamDefault = false;
        $this->guestlink_choose = false;
        $this->guestlink_default = false;
        $this->api = '';
        $this->authMethod = 'user';
        $this->extraCmdChoose = false;
        $this->extraCmdDefault = false;
        $this->style =
        $this->logo = '';
        $this->assignedRoles = [];

    }

    /**
     * @param int $connId
     * @param string|null $type
     * @return array|string|null
     */
    public function getTokenFromDb(int $connId, string $type = null)
    {
        $result = $this->db->query("SELECT access_token, refresh_token FROM rep_robj_xmvc_conn WHERE" .
            " id =" . $this->db->quote($connId, 'integer'));
        while ($record = $this->db->fetchAssoc($result)) {
            return is_null($type) ? $record : $record[$type . '_token'];
        }
        return null;
    }

    /**
     * @param int $connId
     */
    public function read(int $connId): void
    {
        $ilDB = $this->db;
        $result = $ilDB->query("SELECT * FROM rep_robj_xmvc_conn WHERE id =" . $ilDB->quote($connId, 'integer'));
        while ($record = $ilDB->fetchAssoc($result)) {
            $this->setConnId($record["id"]);
            $this->setTitle($record["title"]);
            $this->setHint("" . $record["hint"]);
            $this->setAvailability((int)$record["availability"]);
            $this->set_objIdsSpecial($record["obj_ids_special"]);
            $this->set_protected($this->ilIntToBool($record["protected"]));
            $this->set_moderatedChoose($this->ilIntToBool($record["moderated_choose"]));
            $this->set_moderatedDefault($this->ilIntToBool($record["moderated_default"]));
            $this->set_btnSettingsChoose($this->ilIntToBool($record["btn_settings_choose"]));
            $this->set_btnSettingsDefault($this->ilIntToBool($record["btn_settings_default"]));
            $this->set_btnChatChoose($this->ilIntToBool($record["btn_chat_choose"]));
            $this->set_btnChatDefault($this->ilIntToBool($record["btn_chat_default"]));
            $this->set_withChatChoose($this->ilIntToBool($record["with_chat_choose"]));
            $this->set_withChatDefault($this->ilIntToBool($record["with_chat_default"]));
            $this->set_btnLocationshareChoose($this->ilIntToBool($record["btn_locationshare_choose"]));
            $this->set_btnLocationshareDefault($this->ilIntToBool($record["btn_locationshare_default"]));
            $this->set_memberBtnFileuploadChoose($this->ilIntToBool($record["member_btn_fileupload_choose"]));
            $this->set_memberBtnFileuploadDefault($this->ilIntToBool($record["member_btn_fileupload_default"]));
            $this->set_faExpandDefault($this->ilIntToBool($record["fa_expand_default"]));
            $this->setSvrPublicUrl($record["svrpublicurl"]);
            $this->setSvrPublicPort($record["svrpublicport"]);
            $this->setSvrPrivateUrl($record["svrprivateurl"]);
            $this->setSvrPrivatePort($record["svrprivateport"]);
            $this->setSvrSalt($record["svrsalt"]);
            $this->setMaxParticipants($record["maxparticipants"]);
            $this->setMaxDuration($record["max_duration"]);
            $this->setShowContent($record["showcontent"]);
            $this->setPrivateChatChoose((bool)$record["private_chat_choose"]);
            $this->setPrivateChatDefault((bool)$record["private_chat_default"]);
            $this->setRecordChoose((bool)$record["recording_choose"]);
            $this->setRecordDefault((bool)$record["recording_default"]);
            $this->setRecordOnlyForModeratedRoomsDefault((bool)$record["record_only_moderated_rooms"]);
            $this->setPubRecsChoose((bool)$record["pub_recs_choose"]);
            $this->setPubRecsDefault((bool)$record["pub_recs_default"]);
            $this->setShowHintPubRecs((bool)$record["show_hint_pub_recs"]);
            $this->setHideRecsUntilDate($record["hide_recs_until_date"]);
            $this->setCamOnlyForModeratorChoose((bool)$record["cam_only_moderator_choose"]);
            $this->setCamOnlyForModeratorDefault((bool)$record["cam_only_moderator_default"]);
            $this->setLockDisableCamChoose((bool)$record["lock_disable_cam"]);
            $this->setLockDisableCamDefault((bool)$record["lock_disable_cam_default"]);
            $this->setSvrUsername($record['svrusername']);
            $this->setGuestlinkChoose((bool)$record["guestlink_choose"]);
            $this->setGuestlinkDefault((bool)$record["guestlink_default"]);
            $this->setAddPresentationUrl($record["add_presentation_url"]);
            $this->setAddWelcomeText((bool)$record["add_welcome_text"]);
            $this->setDisableSip((bool)$record["disable_sip"]);
            $this->setHideUsernameInLogs((bool)$record["hide_username_logs"]);
            $this->setAccessToken($record["access_token"]);
            $this->setRefreshToken($record["refresh_token"]);
            #$this->setApi($record["api"]);
            $this->setAuthMethod($record["auth_method"]);
            $this->setExtraCmdChoose((bool)$record["extra_cmd_choose"]);
            $this->setExtraCmdDefault((bool)$record["extra_cmd_default"]);
            $this->setStyle((string)$record["style"]);
            $this->setLogo((string)$record["logo"]);
            $this->setAssignedRoles(explode(',', $record["assigned_roles"]));
            $this->setMeetingLayout((int)$record["meeting_layout"]);

            $this->setStoredOption($record);
        }
    }

    private function setStoredOption($options): void
    {
        $newOptions = [];
        foreach ($options as $option => $value) {
            if((string)$option === 'more_options') {
                $jsonArr = json_decode($options['more_options'], true);
                foreach($this->moreOptions as $moOpt => $moVal) {
                    if(isset($jsonArr[$moOpt])) {
                        $newOptions[$moOpt] = (object)$jsonArr[$moOpt];
                    } else {
                        $newOptions[$moOpt] = (object)$moVal;
                    }
                }
            } else {
                $newOptions[$option] = $value;
            }
        }
        $this->option = (object)$newOptions;
        //var_dump($this->option->cam->selected); exit;
    }

    #endregion INIT READ WRITE

    #region GETTER & SETTER
    /**
     * @return int|null
     */
    public function getConnId(): ?int
    {
        return $this->conn_id;
    }

    /**
     * @param int|null $conn_id
     */
    public function setConnId(?int $conn_id): void
    {
        $this->conn_id = $conn_id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getHint(): string
    {
        return $this->hint;
    }

    /**
     * @param string $hint
     */
    public function setHint(string $hint): void
    {
        $this->hint = $hint;
    }


    /**
     * @return int
     */
    public function getAvailability(): int
    {
        return $this->availability;
    }

    /**
     * @param int $availability
     */
    public function setAvailability(int $availability): void
    {
        $this->availability = $availability;
    }

    public function get_objIdsSpecial(): string
    {
        return $this->objIdsSpecial;
    }
    public function set_objIdsSpecial($a_objIdsSpecial): void
    {
        $this->objIdsSpecial = $a_objIdsSpecial;
    }

    public function get_protected(): bool
    {
        return $this->protected;
    }
    public function set_protected(bool $a_protected): void
    {
        $this->protected = $a_protected;
    }

    public function get_moderatedChoose(): bool
    {
        return $this->moderatedChoose;
    }
    public function set_moderatedChoose(bool $a_moderatedChoose): void
    {
        $this->moderatedChoose = $a_moderatedChoose;
    }

    public function get_moderatedDefault(): bool
    {
        return $this->moderatedDefault;
    }
    public function set_moderatedDefault(bool $a_moderatedDefault): void
    {
        $this->moderatedDefault = $a_moderatedDefault;
    }

    public function get_btnSettingsChoose(): bool
    {
        return $this->btnSettingsChoose;
    }
    public function set_btnSettingsChoose(bool $a_btnSettingsChoose): void
    {
        $this->btnSettingsChoose = $a_btnSettingsChoose;
    }

    public function get_btnSettingsDefault(): bool
    {
        return $this->btnSettingsDefault;
    }
    public function set_btnSettingsDefault(bool $a_btnSettingsDefault): void
    {
        $this->btnSettingsDefault = $a_btnSettingsDefault;
    }

    public function get_btnChatChoose(): bool
    {
        return $this->btnChatChoose;
    }
    public function set_btnChatChoose(bool $a_btnChatChoose): void
    {
        $this->btnChatChoose = $a_btnChatChoose;
    }

    public function get_btnChatDefault(): bool
    {
        return $this->btnChatDefault;
    }
    public function set_btnChatDefault(bool $a_btnChatDefault): void
    {
        $this->btnChatDefault = $a_btnChatDefault;
    }

    public function get_withChatChoose(): bool
    {
        return $this->withChatChoose;
    }
    public function set_withChatChoose(bool $a_withChatChoose): void
    {
        $this->withChatChoose = $a_withChatChoose;
    }

    public function get_withChatDefault(): bool
    {
        return $this->withChatDefault;
    }
    public function set_withChatDefault(bool $a_withChatDefault): void
    {
        $this->withChatDefault = $a_withChatDefault;
    }

    public function get_btnLocationshareChoose(): bool
    {
        return $this->btnLocationshareChoose;
    }
    public function set_btnLocationshareChoose(bool $a_btnLocationshareChoose): void
    {
        $this->btnLocationshareChoose = $a_btnLocationshareChoose;
    }

    public function get_btnLocationshareDefault(): bool
    {
        return $this->btnLocationshareDefault;
    }
    public function set_btnLocationshareDefault(bool $a_btnLocationshareDefault): void
    {
        $this->btnLocationshareDefault = $a_btnLocationshareDefault;
    }

    public function get_memberBtnFileuploadChoose(): bool
    {
        return $this->memberBtnFileuploadChoose;
    }
    public function set_memberBtnFileuploadChoose(bool $a_memberBtnFileuploadChoose): void
    {
        $this->memberBtnFileuploadChoose = $a_memberBtnFileuploadChoose;
    }

    public function get_memberBtnFileuploadDefault(): bool
    {
        return $this->memberBtnFileuploadDefault;
    }
    public function set_memberBtnFileuploadDefault(bool $a_memberBtnFileuploadDefault): void
    {
        $this->memberBtnFileuploadDefault = $a_memberBtnFileuploadDefault;
    }

    public function get_faExpandDefault(): bool
    {
        return $this->faExpandDefault;
    }
    public function set_faExpandDefault(bool $a_faExpandDefault): void
    {
        $this->faExpandDefault = $a_faExpandDefault;
    }


    public function ilBoolToInt(bool $a_val): int
    {
        if ($a_val == true) {
            return 1;
        }
        return 0;
    }
    public function ilIntToBool(int $a_val): bool
    {
        if ($a_val == 1) {
            return true;
        }
        return false;
    }


    /**
     * @return string
     */
    public function getSvrPublicUrl(): ?string
    {
        return $this->svrPublicUrl;
    }

    /**
     * @param string $svrPublicUrl
     */
    public function setSvrPublicUrl(string $svrPublicUrl): void
    {
        $this->svrPublicUrl = $svrPublicUrl;
    }

    /**
     * @return int
     */
    public function getSvrPublicPort(): ?int
    {
        return $this->svrPublicPort;
    }

    /**
     * @param int|null $svrPublicPort
     */
    public function setSvrPublicPort(?int $svrPublicPort): void
    {
        $this->svrPublicPort = $svrPublicPort;
    }

    /**
     * @return string
     */
    public function getSvrPrivateUrl(): ?string
    {
        return $this->svrPrivateUrl;
    }

    /**
     * @param string $svrPrivateUrl
     */
    public function setSvrPrivateUrl(string $svrPrivateUrl): void
    {
        $this->svrPrivateUrl = $svrPrivateUrl;
    }

    /**
     * @return int
     */
    public function getSvrPrivatePort(): ?int
    {
        return $this->svrPrivatePort;
    }

    /**
     * @param int $svrPrivatePort
     */
    public function setSvrPrivatePort(int $svrPrivatePort): void
    {
        $this->svrPrivatePort = $svrPrivatePort;
    }

    /**
     * @return array
     */
    public function getSvrProtocol(): array
    {
        $check = function ($state) {
            $cmd = 'getSvr' . ucfirst($state) . 'Url';
            $proto = '';
            switch (true) {
                case !(bool)strlen($this->$cmd()):
                    break;
                case substr($this->$cmd(), 0, 5) === 'https':
                    $proto = 'https';
                    break;
                case substr($this->$cmd(), 0, 4) === 'http':
                    $proto = 'http';
                    break;
            }
            return $proto;
        };

        return [
            'private' => $check('private'),
            'public' => $check('public')
        ];
    }

    /**
     * @return string
     */
    public function getSvrSalt(): ?string
    {
        return $this->svrSalt;
    }

    /**
     * @param string $svrSalt
     */
    public function setSvrSalt(string $svrSalt): void
    {
        $this->svrSalt = $svrSalt;
    }

    /**
     * @return string|null
     */
    public function getSvrUsername(): ?string
    {
        return $this->svrUsername;
    }

    /**
     * @param string|null $svrUsername
     */
    public function setSvrUsername(?string $svrUsername): void
    {
        $this->svrUsername = $svrUsername;
    }




    /**
     * @return int
     */
    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    /**
     * @param int|null $maxParticipants
     */
    public function setMaxParticipants(?int $maxParticipants): void
    {
        $this->maxParticipants = $maxParticipants;
    }

    /**
     * @return int
     */
    public function getMaxDuration(): int
    {
        return $this->maxDuration;
    }

    /**
     * @param int $maxDuration
     */
    public function setMaxDuration(int $maxDuration): void
    {
        $this->maxDuration = $maxDuration;
    }



    /**
     * @return string
     */
    public function getShowContent(): ?string
    {
        //echo $this->showContent; exit;
        return $this->showContent;
    }

    /**
     * @param string $showContent
     */
    public function setShowContent(string $showContent): void
    {
        $this->showContent = $showContent;
    }

    /**
     * @return bool
     */
    public function isPrivateChatChoose(): bool
    {
        return $this->privateChatChoose;
    }

    /**
     * @param bool $privateChatChoose
     */
    public function setPrivateChatChoose(bool $privateChatChoose): void
    {
        $this->privateChatChoose = $privateChatChoose;
    }

    /**
     * @return bool
     */
    public function isPrivateChatDefault(): bool
    {
        return $this->privateChatDefault;
    }

    /**
     * @param bool $privateChatDefault
     */
    public function setPrivateChatDefault(bool $privateChatDefault): void
    {
        $this->privateChatDefault = $privateChatDefault;
    }

    /**
     * @return bool
     */
    public function isRecordChoose(): bool
    {
        return $this->recordChoose;
    }

    /**
     * @param bool $recordChoose
     */
    public function setRecordChoose(bool $recordChoose): void
    {
        $this->recordChoose = $recordChoose;
    }

    /**
     * @return bool
     */
    public function isRecordDefault(): bool
    {
        return $this->recordDefault;
    }

    /**
     * @param bool $recordDefault
     */
    public function setRecordDefault(bool $recordDefault): void
    {
        $this->recordDefault = $recordDefault;
    }

    /**
     * @param bool $recordOnlyForModeratedRoomsDefault
     */
    public function setRecordOnlyForModeratedRoomsDefault(bool $recordOnlyForModeratedRoomsDefault): void
    {
        $this->recordOnlyForModeratedRoomsDefault = $recordOnlyForModeratedRoomsDefault;
    }

    /**
     * @return bool
     */
    public function isRecordOnlyForModeratedRoomsDefault(): bool
    {
        return $this->recordOnlyForModeratedRoomsDefault;
    }

    /**
     * @return bool
     */
    public function getPubRecsChoose(): bool
    {
        return $this->pubRecsChoose;
    }

    /**
     * @param bool $pubRecsChoose
     */
    public function setPubRecsChoose(bool $pubRecsChoose): void
    {
        $this->pubRecsChoose = $pubRecsChoose;
    }

    /**
     * @return bool
     */
    public function getPubRecsDefault(): bool
    {
        return $this->pubRecsDefault;
    }

    /**
     * @param bool $pubRecsDefault
     */
    public function setPubRecsDefault(bool $pubRecsDefault): void
    {
        $this->pubRecsDefault = $pubRecsDefault;
    }

    /**
     * @return bool
     */
    public function getShowHintPubRecs(): bool
    {
        return $this->showHintPubRecs;
    }

    /**
     * @param bool $showHintPubRecs
     */
    public function setShowHintPubRecs(bool $showHintPubRecs): void
    {
        $this->showHintPubRecs = $showHintPubRecs;
    }

    /**
     * @return string|null
     */
    public function getHideRecsUntilDate(): ?string
    {
        return $this->hideRecsUntilDate;
    }

    /**
     * @param string|null $hideRecsUntilDate
     */
    public function setHideRecsUntilDate(?string $hideRecsUntilDate): void
    {
        $this->hideRecsUntilDate = $hideRecsUntilDate;
    }




    /**
     * @return bool
     */
    public function isCamOnlyForModeratorChoose(): bool
    {
        return $this->camOnlyForModeratorChoose;
    }

    /**
     * @param bool $camOnlyForModeratorChoose
     */
    public function setCamOnlyForModeratorChoose(bool $camOnlyForModeratorChoose): void
    {
        $this->camOnlyForModeratorChoose = $camOnlyForModeratorChoose;
    }

    /**
     * @return bool
     */
    public function isCamOnlyForModeratorDefault(): bool
    {
        return $this->camOnlyForModeratorDefault;
    }
    /**
     * @param bool $camOnlyForModeratorDefault
     */
    public function setCamOnlyForModeratorDefault(bool $camOnlyForModeratorDefault): void
    {
        $this->camOnlyForModeratorDefault = $camOnlyForModeratorDefault;
    }

    /**
     * @return bool
     */
    public function getLockDisableCamDefault(): bool
    {
        return $this->lockDisableCamDefault;
    }

    /**
     * @param bool $lockDisableCamDefault
     */
    public function setLockDisableCamDefault(bool $lockDisableCamDefault): void
    {
        $this->lockDisableCamDefault = $lockDisableCamDefault;
    }

    /**
     * @return bool
     */
    public function getLockDisableCamChoose(): bool
    {
        return $this->lockDisableCamChoose;
    }

    /**
     * @param bool $lockDisableCamChoose
     */
    public function setLockDisableCamChoose(bool $lockDisableCamChoose): void
    {
        $this->lockDisableCamChoose = $lockDisableCamChoose;
    }

    /**
     * @return bool
     */
    public function isGuestlinkChoose(): bool
    {
        return $this->guestlink_choose;
    }

    /**
     * @param bool $guestlink_choose
     */
    public function setGuestlinkChoose(bool $guestlink_choose): void
    {
        $this->guestlink_choose = $guestlink_choose;
    }

    /**
     * @return bool
     */
    public function isGuestlinkDefault(): bool
    {
        return $this->guestlink_default;
    }

    /**
     * @param bool $guestlink_default
     */
    public function setGuestlinkDefault(bool $guestlink_default): void
    {
        $this->guestlink_default = $guestlink_default;
    }


    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * @param string|null $accessToken
     */
    public function setAccessToken(?string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * @param string|null $refreshToken
     */
    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @param string|null $user
     * @return array|string|null
     */
    public function getTokenUser(?string $user = null)
    {
        $array = json_decode($this->getAccessToken(), 1);
        $token = !is_null($user) && isset($array[$user]) ? $array[$user] : null;
        return is_null($user) ? $array : $token;
    }

    /**
     * @param string $user
     * @param string $token
     */
    public function setTokenUser(string $user, string $token): void
    {
        $tokenUser = $this->getTokenUser();
        $tokenUser[$user] = $token;
        $this->accessToken = json_encode($tokenUser);
    }


    /**
     * @return string|null
     */
    public function getApi(): ?string
    {
        return $this->api;
    }

    /**
     * @param string|null $api
     */
    public function setApi(?string $api): void
    {
        $this->api = $api;
    }

    /**
     * @return string|null
     */
    public function getAuthMethod(): ?string
    {
        return $this->authMethod;
    }

    /**
     * @param string|null $authMethod
     */
    public function setAuthMethod(?string $authMethod): void
    {
        $this->authMethod = $authMethod;
    }




    /**
     * @return bool
     */
    public function getExtraCmdDefault(): bool
    {
        return $this->extraCmdDefault;
    }

    /**
     * @param bool $extraCmdDefault
     */
    public function setExtraCmdDefault(bool $extraCmdDefault): void
    {
        $this->extraCmdDefault = $extraCmdDefault;
    }

    /**
     * @return bool
     */
    public function getExtraCmdChoose(): bool
    {
        return $this->extraCmdChoose;
    }

    /**
     * @param bool $extraCmdChoose
     */
    public function setExtraCmdChoose(bool $extraCmdChoose): void
    {
        $this->extraCmdChoose = $extraCmdChoose;
    }

    /**
     * @return string
     */
    public function getAddPresentationUrl(): string
    {
        return $this->addPresentationUrl;
    }

    /**
     * @param string $addPresentationUrl
     */
    public function setAddPresentationUrl(string $addPresentationUrl): void
    {
        $this->addPresentationUrl = $addPresentationUrl;
    }

    /**
     * @return bool
     */
    public function issetAddWelcomeText(): bool
    {
        return $this->addWelcomeText;
    }

    /**
     * @param bool $addWelcomeText
     */
    public function setAddWelcomeText(bool $addWelcomeText): void
    {
        $this->addWelcomeText = $addWelcomeText;
    }

    /**
     * @return bool
     */
    public function getDisableSip(): bool
    {
        return $this->disableSip;
    }

    /**
     * @param bool $disableSip
     * @return ilMultiVcConfig
     */
    public function setDisableSip(bool $disableSip): ilMultiVcConfig
    {
        $this->disableSip = $disableSip;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHideUsernameInLogs(): bool
    {
        return $this->hideUsernameInLogs;
    }

    /**
     * @param bool $hideUsernameInLogs
     * @return ilMultiVcConfig
     */
    public function setHideUsernameInLogs(bool $hideUsernameInLogs): ilMultiVcConfig
    {
        $this->hideUsernameInLogs = $hideUsernameInLogs;
        return $this;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style
     */
    public function setStyle(string $style): void
    {
        $this->style = $style;
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return $this->logo;
    }

    /**
     * @param string $logo
     */
    public function setLogo(string $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * @return array|null
     */
    public function getAssignedRoles(): ?array
    {
        return  $this->assignedRoles;
    }

    /**
     * @param array|string|null $assignedRoles
     */
    public function setAssignedRoles($assignedRoles): void
    {
        $this->assignedRoles = !is_array($assignedRoles) ? (array)$assignedRoles : $assignedRoles;
        /*if( !(int)$this->assignedRoles[0] ) {
            array_shift($this->assignedRoles);
        }*/
    }

    public function getAssignableGlobalRoles(): array
    {
        $globalRoles =
        $localRoles =
        $assignableRoles = [];
        foreach($this->dic->rbac()->review()->getGlobalRoles() as $roleId) {
            $title = ilObjRole::_lookupTitle($roleId);
            if(false !== array_search($title, ['Anonymous', 'Guest'])) {
                continue;
            }
            $globalRoles[$roleId] = ilObjRole::_getTranslation($title);
        }

        foreach (json_decode($this->getAllLocalRoles('il_crs_admin'), 1) as $localRole) {
            $localRoleId = ilObjRole::_getIdsForTitle($localRole['value'])[0];
            $localRoles[$localRoleId] = $localRole['label'];
        } // EOF foreach (json_decode($this->getAllLocalRoles(),1) as $item)

        return array_replace($globalRoles, $localRoles);
    }

    /**
     * @return int
     */
    public function getMeetingLayout(): int
    {
        return $this->meetingLayout;
    }

    /**
     * @param int $meetingLayout
     */
    public function setMeetingLayout(int $meetingLayout): void
    {
        $this->meetingLayout = $meetingLayout;
    }

    /**
     * @param string $a_str
     * @return false|string
     */
    private function getAllLocalRoles(string $a_str = '%')
    {
        global $DIC;

        $ilDB = $DIC->database();

        $query = "SELECT o1.title role,o3.title course_title, o2.title container FROM object_data o1
                    JOIN rbac_fa fa ON o1.obj_id = rol_id
                    JOIN tree t1 ON fa.parent =  t1.child
                    JOIN object_reference obr ON obr.ref_id = t1.parent
                    JOIN object_data o2 ON obr.obj_id = o2.obj_id
                    JOIN object_reference childbr ON childbr.ref_id = t1.child
                    JOIN object_data o3 ON childbr.obj_id = o3.obj_id
                    WHERE o1.type = 'role'
                    AND assign = 'y'
                    AND o1.title like '%il_crs_admin%'
                    AND fa.parent != 8
                    ORDER BY container,course_title,role ";

        $res = $ilDB->query($query);
        $counter = 0;
        $result = array();
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $result[$counter] = new stdClass();
            $result[$counter]->value = $row->role;
            $result[$counter]->label = $row->role . " (" . $row->container . " / " . $row->course_title . ")";
            ++$counter;
        }

        // if ($counter == 0) {
        // return [];//self::getListByObject($a_str);
        // }

        //        include_once './Services/JSON/classes/class.ilJsonUtil.php';
        //        return ilJsonUtil::encode($result);
        return json_encode($result);
    }

    #endregion GETTER & SETTER




    public static function _getMultiVcConnOverviewUses(): array
    {
        global $DIC; /** @var Container $DIC */
        $ilDB = $DIC->database();

        // Get Conn Title
        $query = "SELECT id, title from rep_robj_xmvc_conn";
        $result = $ilDB->query($query);
        $data0 = [];
        while($row = $ilDB->fetchAssoc($result)) {
            $data0[$row['id']] = $row;
        }
        // Get conn uses
        $query = "select object_reference.ref_id xmvcRefId, rep_robj_xmvc_data.conn_id xmvcConnId, rep_robj_xmvc_data.id as xmvcObjId," .
                " object_data.title xmvcObjTitle, not isnull(object_reference.deleted) as isInTrash, rep_robj_xmvc_data.is_online
                 FROM rep_robj_xmvc_data, object_data, object_reference
                 WHERE object_data.obj_id=rep_robj_xmvc_data.id
                 AND object_reference.obj_id=rep_robj_xmvc_data.id
                 ORDER by conn_id, xmvcObjTitle
                 ";
        $result = $ilDB->query($query);
        $data = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $row['connTitle'] = $data0[$row['xmvcConnId']]['title'];
            $data[$row['xmvcRefId']] = $row;
        }

        // Get repo data to conn uses
        $query = "select tree.child, tree.parent parentRefId, object_data.title parentTitle
                 FROM tree, object_data, object_reference
                 WHERE object_data.obj_id=object_reference.obj_id
                 AND object_reference.ref_id = tree.parent
                 AND " . $ilDB->in('tree.child', array_keys($data), false, 'integer'); # object_reference.ref_id in (272)
        $result = $ilDB->query($query);
        $data2 = [];
        while($row = $ilDB->fetchAssoc($result)) {
            $data2[$row['child']] = $row;
        }

        // merge all together
        $returnArr = [];
        foreach ($data as $refId => $row) {
            $returnArr[] = array_merge($data[$refId], $data2[$refId]);
        } // EOF foreach ($data as $datum)

        return $returnArr;
    }

    /**
     * Get basic data array of all types (without field definitions)
     */
    public static function _getMultiVcConnData(bool $a_extended = false, ?int $a_availability = null, string $operatorAvailability = '='): array
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT * FROM rep_robj_xmvc_conn";
        if (isset($a_availability)) {
            $query .= " WHERE availability" . $operatorAvailability . $ilDB->quote($a_availability, 'integer');
        }
        $query .= " ORDER BY title";
        $res = $ilDB->query($query);

        $data = array();
        while ($row = $ilDB->fetchAssoc($res)) {
            if ($a_extended) {
                $row['usages'] = self::_countUntrashedUsages($row['id']);
            }
            $row['conn_id'] = $row['id'];
            unset($row['id']);
            $data[$row['conn_id']] = $row;
        }
        return $data;
    }

    /**
     * Count the number of untrashed usages of a type
     */
    public static function _countUntrashedUsages(int $a_type_id): int
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT COUNT(*) untrashed FROM rep_robj_xmvc_data s"
            . " INNER JOIN object_reference r ON s.id = r.obj_id"
            . " WHERE r.deleted IS NULL "
            . " AND s.conn_id = " . $ilDB->quote($a_type_id, 'integer');

        $res = $ilDB->query($query);
        $row = $ilDB->fetchObject($res);
        return $row->untrashed;
    }

    public static function _getMultiVcConnUsesReferences(int $connId): array
    {
        global $DIC;
        $ilDB = $DIC->database();
        $data = [];

        $query = "SELECT id obj_id FROM rep_robj_xmvc_data s"
            . " INNER JOIN object_reference r ON s.id = r.obj_id"
            . " WHERE s.conn_id = " . $ilDB->quote($connId, 'integer');

        $res = $ilDB->query($query);

        while($row = $ilDB->fetchAssoc($res)) {
            $data[] = $row;
        }
        return $data;
    }

    public static function _getAvailableMultiVcConn(bool $onlyCreate = false, ?int $conn_id = null): array
    {
        global $DIC; /** @var Container $DIC */
        $operator = !$onlyCreate ? '<>' : '=';
        $availStatus = !$onlyCreate ? self::AVAILABILITY_NONE : self::AVAILABILITY_CREATE;
        $availItems = is_array($res = self::_getMultiVcConnData(false, $availStatus, $operator))
            ? $res
            : [];
        $list = [];
        #echo '<pre>'; var_dump($availItems); exit;
        foreach($availItems as $key => $val) {
            // we only check configs of defined VCs for globalAssignedRoles
            // For existing xmvcConfigs we do not check globalAssignedRoles before a vcConfig is updated.
            if(in_array($val['showcontent'], self::VC_RELATED_FUNCTION['globalAssignedRoles']) && (bool)$val['assigned_roles']) {
                $xmvcAssignedRoles = explode(',', $val['assigned_roles'] . ',x');
                array_pop($xmvcAssignedRoles);
                #exit;
                $continue = true;
                foreach ($xmvcAssignedRoles as $roleId) {
                    if($DIC->rbac()->review()->isAssigned($DIC->user()->getId(), $roleId)) {
                        $continue = false;
                        break;
                    }
                }
                if($continue) {
                    continue;
                }
            }
            $list[$val['conn_id']] = $val['title'];
        }
        return $list;
    }

    public static function _deleteMultiVcConn(int $conn_id): void
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM rep_robj_xmvc_conn " .
            "WHERE id = " . $ilDB->quote($conn_id, 'integer');
        $ilDB->manipulate($query);
    }

    //    static public function _getObjTitleByObjId(int $objId)
    //    {
    //        global $DIC; /** @var Container $DIC */
    //        $ilDB = $DIC->database();
    //
    //        $ilDB->query("SELECT title" .
    //            " FROM object_translation" .
    //            " WHERE " . $ilDB->quote($objId,"integer") .
    //            " AND lang_code=" . $DIC->user()->getCurrentLanguage()
    //        );
    //
    //        $row = $ilDB->fetchObject();
    //        return $row->title;
    //    }



    public static function removeUnsafeChars(string $value): string
    {
        $remove = ["\n", "\r", "\t", '"', '\'', "<?", "?>"];
        $value = str_replace($remove, ' ', $value);
        foreach (["/<[^>]*>/", "%<\/[^>]*>]%", "%[\s]{2,}%"] as $regEx) {
            $value = preg_replace($regEx, ' ', $value);
        } // EOF foreach as $regEx)
        return trim($value);
    }





}

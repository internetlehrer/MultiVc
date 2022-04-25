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
    const PLUGIN_ID = 'xmvc';
    const AVAILABLE_VC_CONN = [
        'bbb'		=> 'BigBlueButton',
        'edudip'     => 'Edudip',
        'om'		=> 'Openmeetings',
        'spreed'	=> 'Spreed',
        'webex'     => 'Webex',
    ];
    const AVAILABLE_XMVC_API = [
        'webex'     => 'ilApiWebex',
        'edudip'     => 'ilApiEdudip',
        'bbb'		=> 'ilApiBBB',
        'om'		=> 'ilApiOM'
    ];
    const AVAILABLE_Webex_API = [
        'admin' => 'Admin Scopes',
        'integration' => 'User Scopes'
    ];
    const INTEGRATION_AUTH_METHODS = [
        'admin' => 'Admin Scope',
        'user' => 'User Scope'
    ];
    const AVAILABILITY_NONE = 0;  // Type is not longer available (error message)
    const AVAILABILITY_EXISTING = 1; // Existing objects of the can be used, but no new created
    const AVAILABILITY_CREATE = 2;  // New objects of this type can be created

    const MEETING_LAYOUT_CUSTOM = 1;
    const MEETING_LAYOUT_SMART = 2;
    const MEETING_LAYOUT_PRESENTATION_FOCUS = 3;
    const MEETING_LAYOUT_VIDEO_FOCUS = 4;

    const ADMIN_DEFINED_TOKEN_VC = [
        'edudip'
    ];

    const VC_RELATED_FUNCTION = [
        'globalAssignedRoles' => [
            'bbb', 'edudip', 'webex', 'om', 'spreed'
        ],
        'maxDuration' => [
            'bbb'
        ]
    ];

    /** @var Container $dic */
    private $dic;
    /** @var ilDBInterface $db */
    private $db;

	private static $instance = null;

	/** @var int|null $conn_id */
    private $conn_id = null;
    /** @var string $title */
    private $title = '';
    /** @var int $availability */
    private $availability = 0;
    /** @var string $hint */
    private $hint = '';
	private $spreedUrl = '';
	private $objIdsSpecial = '';
	private $protected = true;
	private $moderatedChoose = false;
	private $moderatedDefault = true;
	private $btnSettingsChoose = false;
	private $btnSettingsDefault = false;
	private $btnChatChoose = false;
	private $btnChatDefault = false;
	private $withChatChoose = false;
	private $withChatDefault = true;
	private $btnLocationshareChoose = false;
	private $btnLocationshareDefault = false;
	private $memberBtnFileuploadChoose = false;
	private $memberBtnFileuploadDefault = false;
	private $faExpandDefault = false;
	/** @var string $svrPublicUrl */
	private $svrPublicUrl;
	/** @var int $svrPublicPort */
	private $svrPublicPort;
	/** @var string $svrPrivateUrl */
	private $svrPrivateUrl;
	/** @var int $svrPrivatePort */
	private $svrPrivatePort;
	/** @var string $svrSalt */
	private $svrSalt;
    /** @var string $svrUsername */
    private $svrUsername;
	/** @var int $maxParticipants */
	private $maxParticipants;

    /** @var int $maxDuration */
    private $maxDuration = 0;

	/** @var string $showContent */
	private $showContent;
	/** @var bool $privateChatChoose */
	private $privateChatChoose = false;
    /** @var bool $privateChatDefault */
    private $privateChatDefault = true;
    /** @var bool $recordChoose */
    private $recordChoose = false;
    /** @var bool $recordDefault */
    private $recordDefault = false;
    /** @var bool $recordOnlyForModeratedRoomsDefault */
    private $recordOnlyForModeratedRoomsDefault = true;
    /** @var bool $pubRecsChoose */
    private $pubRecsChoose = false;
    /** @var bool $pubRecsDefault */
    private $pubRecsDefault = false;
    /** @var bool $showHintPubRecs */
    private $showHintPubRecs = false;
    /** @var string|null $hideRecsUntilDate */
    private $hideRecsUntilDate = null;
    /** @var bool $disableSip */
    private $disableSip = false;
    /** @var bool $hideUsernameInLogs */
    private $hideUsernameInLogs = true;
    /** @var bool $recordOnlyForModeratorChoose */
    private $recordOnlyForModeratorChoose = false;
    /** @var bool $recordOnlyForModeratorDefault */
    private $recordOnlyForModeratorDefault = true;
    // eof todo

    /** @var bool $camOnlyForModeratorChoose */
    private $camOnlyForModeratorChoose = false;
    /** @var bool $camOnlyForModeratorDefault */
    private $camOnlyForModeratorDefault = false;

    /** @var $lockDisableCamDefault */
    private $lockDisableCamDefault = false;

    /** @var $lockDisableCamChoose */
    private $lockDisableCamChoose = false;

    /** @var string $addPresentationUrl */
    private $addPresentationUrl = '';

    /** @var string $style */
    private $style = '';

    /** @var string $logo */
    private $logo = '';


    /** @var bool $addWelcomeText */
    private $addWelcomeText = false;

	/** @var array $moreOptions */
	private $moreOptions = [
        'camOnlyForModerator' => [ 'choose' => false, 'default' => false ],
        'privateChat' => [ 'choose' => false, 'default' => true ],
        'recording' => [ 'choose' => false, 'default' => false ],
        'recordingOnlyForModerator' => [ 'choose' => false, 'default' => true ],
    ];
    /** @var object $option */
    public $option;

    /** @var array $objConfigAvailSetting */
    private $objConfigAvailSetting = [
        'bbb'   => [
            'moderatedChoose',
            'privateChatChoose',
            'recordChoose',
            'pubRecs',
            'camOnlyForModeratorChoose',
            'lockDisableCam',
            'guestlinkChoose'
        ],
        'spreed'=> [
            'moderatedChoose',
            'btnSettingsChoose',
            'btnChatChoose',
            'withChatChoose',
            'btnLocationshareChoose',
            'memberBtnFileuploadChoose',
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

    /** @var bool $guestlink_choose */
    private $guestlink_choose = false;

    /** @var bool $guestlink_default */
    private $guestlink_default = false;

    /** @var string|null $accessToken */
    private $accessToken;

    /** @var string|null $refreshToken */
    private $refreshToken;

    /** @var null|string $tokenUser */
    private $tokenUser = null;

    /** @var string|null $api */
    private $api = '';

    /** @var string|null $authMethod */
    private $authMethod = '';

    /** @var bool $extraCmdDefault */
    private $extraCmdDefault = false;

    /** @var bool $extraCmdChoose */
    private $extraCmdChoose = false;

    /** @var null|array $assignedRoles  */
    private $assignedRoles = null;

    /** @var int $meetingLayout */
    private $meetingLayout = 2;
    #endregion PROPERTIES

    #region INIT READ WRITE

    /**
     * @param string $component VC e. g. spreed | bbb
     * @return array
     */
    public function getObjConfigAvailSetting(string $component = ''): array
    {
        if( !(bool)$component )
        {
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

		if( !is_null($connId) ) {
            $this->read($connId);
        }
	}

    /**
     * Get singleton instance
     *
     * @param int|null $connId
     * @return ilMultiVcConfig
     */
	public static function getInstance(?int $connId = null)
	{
		if(self::$instance && is_null($connId))
		{
			return self::$instance;
		}
		return self::$instance = new ilMultiVcConfig($connId);
	}

	public function create()
    {
        $this->save(false);
    }


    /**
     * @param bool $update
     */
	public function save(bool $update = true)
	{
		global $ilDB;

		if( !!(bool)$this->getConnId() ) {
            if( $this->hasPlatformChanged() ) {
                $this->setDefaultValues();
            }
        }

		$a_data=array(
            'title'		                    => ['text', $this->getTitle()],
            'hint'		                    => ['text', $this->getHint()],
            'availability'		            => ['integer', (int)$this->getAvailability()],
			'spreed_url'					=> array('text',$this->get_spreedUrl()),
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
            'hide_recs_until_date' => ['string', $this->getHideRecsUntilDate()],
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

        if( !$update ) {
            $result = $ilDB->query("SELECT MAX(id) id FROM rep_robj_xmvc_conn");
            $row = $ilDB->fetchObject($result);
            $connId = (bool)$numConn ? (int)$row->id + 1 : 1;
            $this->setConnId($connId);
        }
		if( !$update || $numConn === 0){
		    $a_data['id'] = array('integer', $this->getConnId());
			$ilDB->insert('rep_robj_xmvc_conn', $a_data);
		} else {
			$ilDB->update('rep_robj_xmvc_conn', $a_data, array('id' => array('integer', $this->getConnId())));
		}
	}

    /**
     * @return string
     */
	public function keepSvrSalt() {
        $query = $this->db->query("SELECT svrsalt FROM rep_robj_xmvc_conn WHERE id = " . $this->getConnId());
        $row = $this->db->fetchObject($query);
        $this->svrSalt = $row->svrsalt;

    }

	private function hasPlatformChanged(): bool
    {
        if( is_null($this->getConnId()) ) {
            //return true;
        }
        $result = $this->db->query("SELECT showcontent FROM rep_robj_xmvc_conn where id =  " . $this->getConnId());
        $row = $this->db->fetchAssoc($result);
        $initialEntry = null === $row;
        switch(true){
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
        $this->spreedUrl = '';
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
    public function getTokenFromDb( int $connId, string $type = null )
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
	public function read(int $connId)
	{
		global $ilDB;
		$result = $ilDB->query("SELECT * FROM rep_robj_xmvc_conn WHERE id =" . $ilDB->quote($connId, 'integer'));
		while ($record = $ilDB->fetchAssoc($result)) {
		    $this->setConnId($record["id"]);
            $this->setTitle($record["title"]);
            $this->setHint("".$record["hint"]);
            $this->setAvailability((int)$record["availability"]);
			$this->set_spreedUrl($record["spreed_url"]);
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
            $this->setPrivateChatChoose( (bool)$record["private_chat_choose"] );
			$this->setPrivateChatDefault( (bool)$record["private_chat_default"] );
            $this->setRecordChoose( (bool)$record["recording_choose"] );
            $this->setRecordDefault( (bool)$record["recording_default"] );
            $this->setRecordOnlyForModeratedRoomsDefault( (bool)$record["record_only_moderated_rooms"] );
            $this->setPubRecsChoose( (bool)$record["pub_recs_choose"] );
            $this->setPubRecsDefault( (bool)$record["pub_recs_default"] );
            $this->setShowHintPubRecs( (bool)$record["show_hint_pub_recs"] );
            $this->setHideRecsUntilDate( $record["hide_recs_until_date"] );
            $this->setCamOnlyForModeratorChoose( (bool)$record["cam_only_moderator_choose"] );
            $this->setCamOnlyForModeratorDefault( (bool)$record["cam_only_moderator_default"] );
            $this->setLockDisableCamChoose( (bool)$record["lock_disable_cam"] );
            $this->setLockDisableCamDefault( (bool)$record["lock_disable_cam_default"] );
            $this->setSvrUsername($record['svrusername']);
            $this->setGuestlinkChoose( (bool)$record["guestlink_choose"] );
            $this->setGuestlinkDefault( (bool)$record["guestlink_default"] );
            $this->setAddPresentationUrl($record["add_presentation_url"]);
            $this->setAddWelcomeText( (bool)$record["add_welcome_text"] );
            $this->setDisableSip( (bool)$record["disable_sip"] );
            $this->setHideUsernameInLogs( (bool)$record["hide_username_logs"] );
            $this->setAccessToken($record["access_token"]);
            $this->setRefreshToken($record["refresh_token"]);
            #$this->setApi($record["api"]);
            $this->setAuthMethod($record["auth_method"]);
            $this->setExtraCmdChoose( (bool)$record["extra_cmd_choose"] );
            $this->setExtraCmdDefault( (bool)$record["extra_cmd_default"] );
            $this->setStyle( (string)$record["style"] );
            $this->setLogo( (string)$record["logo"] );
            $this->setAssignedRoles( explode(',', $record["assigned_roles"]) );
            $this->setMeetingLayout((int)$record["meeting_layout"]);

            $this->setStoredOption($record);
		}
    }

    private function setStoredOption($options): void {
	    $newOptions = [];
	    foreach ($options AS $option => $value) {
	        if( (string)$option === 'more_options' ) {
                $jsonArr = json_decode($options['more_options'], true);
                foreach( $this->moreOptions AS $moOpt => $moVal ) {
                    if( isset($jsonArr[$moOpt]) ) {
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

    public function get_spreedUrl()
    {
		return $this->spreedUrl;
	}
	public function set_spreedUrl($a_spreedUrl) {
		$this->spreedUrl = $a_spreedUrl;
	}

	public function get_objIdsSpecial() {
		return $this->objIdsSpecial;
	}
	public function set_objIdsSpecial($a_objIdsSpecial) {
		$this->objIdsSpecial = $a_objIdsSpecial;
	}

	public function get_protected() {
		return $this->protected;
	}
	public function set_protected($a_protected) {
		$this->protected = $a_protected;
	}

	public function get_moderatedChoose() {
		return $this->moderatedChoose;
	}
	public function set_moderatedChoose($a_moderatedChoose) {
		$this->moderatedChoose = $a_moderatedChoose;
	}

	public function get_moderatedDefault() {
		return $this->moderatedDefault;
	}
	public function set_moderatedDefault($a_moderatedDefault) {
		$this->moderatedDefault = $a_moderatedDefault;
	}

	public function get_btnSettingsChoose() {
		return $this->btnSettingsChoose;
	}
	public function set_btnSettingsChoose($a_btnSettingsChoose) {
		$this->btnSettingsChoose = $a_btnSettingsChoose;
	}

	public function get_btnSettingsDefault() {
		return $this->btnSettingsDefault;
	}
	public function set_btnSettingsDefault($a_btnSettingsDefault) {
		$this->btnSettingsDefault = $a_btnSettingsDefault;
	}

	public function get_btnChatChoose() {
		return $this->btnChatChoose;
	}
	public function set_btnChatChoose($a_btnChatChoose) {
		$this->btnChatChoose = $a_btnChatChoose;
	}

	public function get_btnChatDefault() {
		return $this->btnChatDefault;
	}
	public function set_btnChatDefault($a_btnChatDefault) {
		$this->btnChatDefault = $a_btnChatDefault;
	}

	public function get_withChatChoose() {
		return $this->withChatChoose;
	}
	public function set_withChatChoose($a_withChatChoose) {
		$this->withChatChoose = $a_withChatChoose;
	}

	public function get_withChatDefault() {
		return $this->withChatDefault;
	}
	public function set_withChatDefault($a_withChatDefault) {
		$this->withChatDefault = $a_withChatDefault;
	}

	public function get_btnLocationshareChoose() {
		return $this->btnLocationshareChoose;
	}
	public function set_btnLocationshareChoose($a_btnLocationshareChoose) {
		$this->btnLocationshareChoose = $a_btnLocationshareChoose;
	}

	public function get_btnLocationshareDefault() {
		return $this->btnLocationshareDefault;
	}
	public function set_btnLocationshareDefault($a_btnLocationshareDefault) {
		$this->btnLocationshareDefault = $a_btnLocationshareDefault;
	}

	public function get_memberBtnFileuploadChoose() {
		return $this->memberBtnFileuploadChoose;
	}
	public function set_memberBtnFileuploadChoose($a_memberBtnFileuploadChoose) {
		$this->memberBtnFileuploadChoose = $a_memberBtnFileuploadChoose;
	}

	public function get_memberBtnFileuploadDefault() {
		return $this->memberBtnFileuploadDefault;
	}
	public function set_memberBtnFileuploadDefault($a_memberBtnFileuploadDefault) {
		$this->memberBtnFileuploadDefault = $a_memberBtnFileuploadDefault;
	}

	public function get_faExpandDefault() {
		return $this->faExpandDefault;
	}
	public function set_faExpandDefault($a_faExpandDefault) {
		$this->faExpandDefault = $a_faExpandDefault;
	}

	
	function ilBoolToInt($a_val) {
		if ($a_val == true) return 1;
		return 0;
	}
	function ilIntToBool($a_val) {
		if ($a_val == 1) return true;
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
        $check = function($state) {
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
    public function getAssignedRoles()
    {
        return  $this->assignedRoles;
    }

    /**
     * @param array|string|null $assignedRoles
     */
    public function setAssignedRoles(?array $assignedRoles): void
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
            if( false !== array_search($title, ['Anonymous', 'Guest'])  ) {
                continue;
            }
            $globalRoles[$roleId] = ilObjRole::_getTranslation($title);
        }

        foreach (json_decode($this->getAllLocalRoles('il_crs_admin'),1) as $localRole) {
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
    public function setMeetingLayout(int $meetingLayout)
    {
        $this->meetingLayout = $meetingLayout;
    }

    private function getAllLocalRoles($a_str = '%')
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

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

        include_once './Services/JSON/classes/class.ilJsonUtil.php';
        return ilJsonUtil::encode($result);
    }

    #endregion GETTER & SETTER




    static public function _getMultiVcConnOverviewUses()
    {
        global $DIC; /** @var Container $DIC */
        $ilDB = $DIC->database();

        // Get Conn Title
        $query = "SELECT id, title from rep_robj_xmvc_conn";
        $result = $ilDB->query($query);
        $data0 = [];
        while( $row = $ilDB->fetchAssoc($result) ) {
            $data0[$row['id']] = $row;
        }
        // Get conn uses
        $query = "select object_reference.ref_id xmvcRefId, rep_robj_xmvc_data.conn_id xmvcConnId, rep_robj_xmvc_data.id as xmvcObjId,".
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
        while( $row = $ilDB->fetchAssoc($result) ) {
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
     *
     * @param boolean        get extended data ('usages')
     * @param null $a_availability
     * @param string $operatorAvailability
     * @return    array        array of assoc data arrays
     */
    static public function _getMultiVcConnData($a_extended = false, $a_availability = null, $operatorAvailability = '=')
    {
        global $ilDB;

        $query = "SELECT * FROM rep_robj_xmvc_conn";
        if (isset($a_availability)) {
            $query .= " WHERE availability" . $operatorAvailability . $ilDB->quote($a_availability, 'integer');
        }
        $query .= " ORDER BY title";
        $res = $ilDB->query($query);

        $data = array();
        while ($row = $ilDB->fetchAssoc($res))
        {
            if ($a_extended)
            {
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
     *
     * @var		integer		type_id
     * @return	integer		number of references
     */
    static function _countUntrashedUsages($a_type_id) {
        global $ilDB;

        $query = "SELECT COUNT(*) untrashed FROM rep_robj_xmvc_data s"
            . " INNER JOIN object_reference r ON s.id = r.obj_id"
            . " WHERE r.deleted IS NULL "
            . " AND s.conn_id = " . $ilDB->quote($a_type_id, 'integer');

        $res = $ilDB->query($query);
        $row = $ilDB->fetchObject($res);
        return $row->untrashed;
    }

    static function _getMultiVcConnUsesReferences(int $connId) {
        global $ilDB;
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

    static public function _getAvailableMultiVcConn(bool $onlyCreate = false, ?int $conn_id = null): array
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
            if( in_array($val['showcontent'], self::VC_RELATED_FUNCTION['globalAssignedRoles']) && (bool)$val['assigned_roles'] ) {
                $xmvcAssignedRoles = explode(',', $val['assigned_roles'] . ',x');
                array_pop($xmvcAssignedRoles);
                #exit;
                $continue = true;
                foreach ($xmvcAssignedRoles as $roleId ) {
                    if( $DIC->rbac()->review()->isAssigned($DIC->user()->getId(), $roleId) ) {
                        $continue = false;
                        break;
                    }
                }
                if( $continue ) {
                    continue;
                }
            }
            $list[$val['conn_id']] = $val['title'];
        }
        return $list;
    }

    static public function _deleteMultiVcConn(int $conn_id) {
        global $ilDB;

        $query = "DELETE FROM rep_robj_xmvc_conn " .
            "WHERE id = " . $ilDB->quote($conn_id, 'integer');
        $ilDB->manipulate($query);

        return true;
    }

    static public function _getObjTitleByObjId(int $objId)
    {
        global $DIC; /** @var Container $DIC */
        $ilDB = $DIC->database();

        $ilDB->query("SELECT title" .
            " FROM object_translation" .
            " WHERE " . $ilDB->quote($objId,"integer") .
            " AND lang_code=" . $DIC->user()->getCurrentLanguage()
        );

        $row = $ilDB->fetchObject();
        return $row->title;
    }









}

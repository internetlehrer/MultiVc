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
    const AVAILABLE_VC_CONN = [
        'bbb'		=> 'BigBlueButton',
        'spreed'	=> 'Spreed',
        'om'		=> 'Openmeetings'
    ];
    const AVAILABILITY_NONE = 0;  // Type is not longer available (error message)
    const AVAILABILITY_EXISTING = 1; // Existing objects of the can be used, but no new created
    const AVAILABILITY_CREATE = 2;  // New objects of this type can be created

    /** @var Container $dic */
    private $dic;
    /** @var ilDB $db */
    private $db;
	private static $instance = null;
	/** @var int|null $conn_id */
    private $conn_id = null;
    /** @var string $title */
    private $title = '';
    /** @var int $availability */
    private $availability = 0;
	private $spreedUrl = '';
	private $objIdsSpecial = '';
	private $protected = true;
	private $moderatedChoose = true;
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

    // todo
    /** @var bool $recordOnlyForModeratorChoose */
    private $recordOnlyForModeratorChoose = false;
    /** @var bool $recordOnlyForModeratorDefault */
    private $recordOnlyForModeratorDefault = true;
    // eof todo

    /** @var bool $camOnlyForModeratorChoose */
    private $camOnlyForModeratorChoose = false;
    /** @var bool $camOnlyForModeratorDefault */
    private $camOnlyForModeratorDefault = false;


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
            'camOnlyForModeratorChoose'
        ],
        'spreed'=> [
            'moderatedChoose',
            'btnSettingsChoose',
            'btnChatChoose',
            'withChatChoose',
            'btnLocationshareChoose',
            'memberBtnFileuploadChoose',
        ]
    ];

    /** @var bool $guestlink_choose */
    private $guestlink_choose = false;

    /** @var bool $guestlink_default */
    private $guestlink_default = false;


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
        return true; // false !== array_search($search, $this->objConfigAvailSetting[$this->getShowContent()]);
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
            'showcontent'			        => ['string', $this->getShowContent()],
            'private_chat_choose'		    => ['integer', (int)$this->isPrivateChatChoose()],
            'private_chat_default'		    => ['integer', (int)$this->isPrivateChatDefault()],
            'recording_choose'		        => ['integer', (int)$this->isRecordChoose()],
            'recording_default'		        => ['integer', (int)$this->isRecordDefault()],
            'record_only_moderated_rooms' => ['integer', (int)$this->isRecordOnlyForModeratedRoomsDefault()],
            'cam_only_moderator_choose' => ['integer', (int)$this->isCamOnlyForModeratorChoose()],
            'cam_only_moderator_default' => ['integer', (int)$this->isCamOnlyForModeratorDefault()],
            'svrUsername'		         => ['string', $this->getSvrUsername()],
            'guestlink_choose' => ['integer', (int)$this->isGuestlinkChoose()],
            'guestlink_default' => ['integer', (int)$this->isGuestlinkDefault()],
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
        $this->moderatedChoose = true;
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
        //$this->showContent
        $this->privateChatChoose = false;
        $this->privateChatDefault = true;
        $this->recordChoose = false;
        $this->recordDefault = false;
        $this->recordOnlyForModeratedRoomsDefault = true;
        $this->camOnlyForModeratorChoose = false;
        $this->camOnlyForModeratorDefault = false;
        $this->guestlink_choose = false;
        $this->guestlink_default = false;
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
			$this->setShowContent($record["showcontent"]);
            $this->setPrivateChatChoose( (bool)$record["private_chat_choose"] );
			$this->setPrivateChatDefault( (bool)$record["private_chat_default"] );
            $this->setRecordChoose( (bool)$record["recording_choose"] );
            $this->setRecordDefault( (bool)$record["recording_default"] );
            $this->setRecordOnlyForModeratedRoomsDefault( (bool)$record["record_only_moderated_rooms"] );
            $this->setCamOnlyForModeratorChoose( (bool)$record["cam_only_moderator_choose"] );
            $this->setCamOnlyForModeratorDefault( (bool)$record["cam_only_moderator_default"] );
            $this->setSvrUsername($record['svrusername']);
            $this->setGuestlinkChoose( (bool)$record["guestlink_choose"] );
            $this->setGuestlinkDefault( (bool)$record["guestlink_default"] );

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

    static public function _getAvailableMultiVcConn(bool $onlyCreate = false, ?int $conn_id = null): array
    {
        $operator = !$onlyCreate ? '<>' : '=';
        $availStatus = !$onlyCreate ? self::AVAILABILITY_NONE : self::AVAILABILITY_CREATE;
        $availItems = is_array($res = self::_getMultiVcConnData(false, $availStatus, $operator))
            ? $res
            : [];
        $list = [];
        foreach($availItems as $key => $val) {
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



    /*
    static public function fillEmptyShowContent()
    {
        global $DIC; /** @var Container $DIC */ /*

        $db = $DIC->database();

        $query = "SELECT id FROM rep_robj_xmvc_conn WHERE showcontent IS NULL";
        $result = $db->query($query);
        while( $row = $db->fetchAssoc($result) ) {
            $where = ['id' => ['integer', $row['id']]];
            $values = [
                'showcontent'			=> ['string', self::generateMembersPwd()],
                'moderatorpwd'			=> ['string', self::generateMembersPwd()]
            ];
            $db->update('rep_robj_xmvc_data', $values, $where);
        }
    }
    */








}

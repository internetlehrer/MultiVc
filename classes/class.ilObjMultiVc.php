<?php
/**
* Application class for MultiVc repository object.
*
* @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
*
* @version $Id$
*/

class ilObjMultiVc extends ilObjectPlugin implements ilLPStatusPluginInterface
{
    public const LP_INACTIVE = 0;
    public const LP_ACTIVE = 1;
    public const TABLE_XMVC_OBJECT = 'rep_robj_xmvc_data';

    public const TABLE_LOG_MAX_CONCURRENT = 'rep_robj_xmvc_log_max';

    public const TABLE_USER_LOG = 'rep_robj_xmvc_user_log';

    public const MEETING_TIME_AHEAD = 60 * 5;

    private ILIAS\DI\Container $dic;
    protected ilDBInterface $db;

    private bool $online = false;

    private ?int $token = null;
    private bool $moderated = true;
    private bool $btnSettings = false;
    private bool $btnChat = false;
    private bool $withChat = true;
    private bool $btnLocationshare = false;
    private bool $memberBtnFileupload = false;
    private bool $faExpand = false;
    private string $attendeePwd;
    private bool $privateChat;
    private bool $record;
    private bool $pubRecs = false;
    private bool $camOnlyForModerator = false;
    private bool $lockDisableCam = false;
    private bool $guestlink = false;
    /** @var int|bool $extraCmd */
    private $extraCmd = false;
    private string $moderatorPwd;
    private int $roomId;
    private int $connId;
    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private ?string $secretExpiration = null;
    private ?string $authUser = null;
    private ?string $authSecret = null;
    private int $lpMode = 0;
    private int $lpTime = 70;

    public object $option;

    private static ?self $instance = null;

    public function __construct(int $a_ref_id = 0)
    {
        global $DIC;

        parent::__construct($a_ref_id);

        $this->dic = $DIC;
        $this->db = $this->dic->database();
    }

    public static function getInstance(): self//ilObjMultiVc
    {
        //old return new self();
        global $DIC;

        if (self::$instance instanceof self) {
            return self::$instance;
        }

        /** @var ilComponentRepository $component_repository */
        $component_repository = $DIC['component.repository'];
        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC['component.factory'];

        $plugin_info = $component_repository->getComponentByTypeAndName('Services', 'Repository')->getPluginSlotById('robj')->getPluginById('xmvc');

        self::$instance = $component_factory->getPlugin($plugin_info->getId());

        return self::$instance;
    }


    /**
    * Get type.
    */
    protected function initType(): void
    {
        $this->setType("xmvc");
    }

    /**
    * Create object
    */

    public function doCreate(bool $clone_mode = false): void
    {
    }

    public function createRoom(int $online, int $conn_id)
    {
        $ilDB = $this->db;
        $this->setOnline($this->ilIntToBool((int) $online));
        $this->setConnId($conn_id);
        $settings = $this->setDefaultsByPluginConfig($conn_id, true);
        //var_dump($settings) ; exit;
        $a_data = array(
            'id' => array('integer', $this->getId()),
            'is_online' => array('integer', $this->ilBoolToInt($this->getOnline())),
            'token' => array('integer', $this->generate_token()),
            'moderated' => array('integer', $this->ilBoolToInt($settings->get_moderatedDefault())),
            'btn_settings' => array('integer', $this->ilBoolToInt($settings->get_btnSettingsDefault())),
            'btn_chat' => array('integer', $this->ilBoolToInt($settings->get_btnChatDefault())),
            'with_chat' => array('integer', $this->ilBoolToInt($settings->get_withChatDefault())),
            'btn_locationshare' => array('integer', $this->ilBoolToInt($settings->get_btnLocationshareDefault())),
            'member_btn_fileupload' => array('integer', $this->ilBoolToInt($settings->get_memberBtnFileuploadDefault())),
            'fa_expand' => array('integer', $this->ilBoolToInt($settings->get_faExpandDefault())),
            'attendeepwd' => array('string', $this->generateMembersPwd()),
            'moderatorpwd' => array('string', $this->generateMembersPwd()),
            'private_chat' => array('integer', (int) $this->isPrivateChat() ),
            'recording' => array('integer', (int) $this->isRecord()),
            'cam_only_for_moderator' => array('integer', (int) $this->isCamOnlyForModerator()),
            'lock_disable_cam' => array('integer', (int) $this->getLockDisableCam()),
            'conn_id' => array('integer', (int) $conn_id),
            'guestlink' => array('integer', (int) $settings->isGuestlinkDefault()),
            'extra_cmd' => array('integer', (int) $this->getExtraCmd()),
            'secret_expiration' => array('string', $this->getSecretExpiration()),
            #"auth_user" => ['string', $this->getAuthUser()],
        );
        $ilDB->insert('rep_robj_xmvc_data', $a_data);
        /*
        $authUser = $this->getAuthUser();
        $objUserCreateDefaultsEntry = array_replace([
            $authUser  => self::OBJ_USER_CREATE_DEFAULTS
        ], $this->getMultiVcObjUser());
        $this->setMultiVcObjUser($objUserCreateDefaultsEntry);
        $this->setMultiVcObjUser(['email' => $authUser], $this->getRefId());
        */
    }

    /**
    * Read data from db
    */
    public function doRead(): void
    {
        $ilDB = $this->db;

        $result = $ilDB->query("SELECT * FROM rep_robj_xmvc_data WHERE id = " . $ilDB->quote($this->getId(), "integer"));
        while ($record = $ilDB->fetchAssoc($result)) {
            $settings = new ilMultiVcConfig($record["conn_id"]);
            $this->option = $settings->option;
            $this->setPrivateChat($settings->isPrivateChatDefault());
            $this->setRecord($settings->isRecordDefault());
            $this->setCamOnlyForModerator($settings->isCamOnlyForModeratorDefault());
            $this->setOnline($this->ilIntToBool($record["is_online"]));
            $this->set_token($record["token"]);
            $this->set_moderated($this->ilIntToBool($record["moderated"]));
            $this->set_btnSettings($this->ilIntToBool($record["btn_settings"]));
            $this->set_btnChat($this->ilIntToBool($record["btn_chat"]));
            $this->set_withChat($this->ilIntToBool($record["with_chat"]));
            $this->set_btnLocationshare($this->ilIntToBool($record["btn_locationshare"]));
            $this->set_memberBtnFileupload($this->ilIntToBool($record["member_btn_fileupload"]));
            $this->set_faExpand($this->ilIntToBool($record["fa_expand"]));
            $this->setAttendeePwd($record["attendeepwd"]);
            $this->setModeratorPwd($record["moderatorpwd"]);
            $this->setPrivateChat((bool) $record["private_chat"]);
            $this->setRecord((bool) $record["recording"]);
            $this->setPubRecs((bool) $record["pub_recs"]);
            $this->setCamOnlyForModerator((bool) $record["cam_only_for_moderator"]);
            $this->setLockDisableCam((bool) $record["lock_disable_cam"]);
            $this->setRoomId((int) $record["rmid"]);
            $this->setConnId((int) $record["conn_id"]);
            $this->setAccessToken($record["access_token"]);
            $this->setRefreshToken($record["refresh_token"]);
            $this->setSecretExpiration($record["secret_expiration"]);
            $this->setAuthUser($record["auth_user"]);
            #$this->setAuthSecret( $record["auth_secret"] );
            $this->setGuestlink((bool) $record["guestlink"]);
            $this->setExtraCmd($record["extra_cmd"]);
            $this->setLPMode((int) $record["lp_mode"]);
            $this->setLpTime((int) $record["lp_time"]);
        }

        /*
        $hasWritePermission = $this->dic->access()->checkAccess("write", "", $this->getRefId());
        $objUser = $this->getMultiVcObjUser($this->getRefId());
        $issetObjUserEntry = isset($objUser['email']);
        if( !$issetObjUserEntry ) {
            $this->setMultiVcObjUser(['email' => $this->dic->user()->getEmail()], $this->getRefId());
        }
        if( $hasWritePermission && !$issetObjUserEntry ) {
            $objUserCreateDefaultsEntry = array_replace([
                $this->dic->user()->getEmail()  => self::OBJ_USER_CREATE_DEFAULTS
            ], $this->getMultiVcObjUser());
        }
        $objUser = $this->getMultiVcObjUser($this->getRefId());
        $authUser = isset($objUser['email']) ? $objUser['email'] : $this->dic->user()->getEmail();
        $this->setAuthUser( $authUser );
        */
    }

    /**
    * Update data
    */
    public function doUpdate(): void
    {
        $ilDB = $this->db;
        $a_data = array(
            // 'id'					=> array('integer', $this->getId()),
            'is_online' => array('integer', $this->ilBoolToInt($this->getOnline())),
            'token' => array('integer', $this->get_token()),
            'moderated' => array('integer', $this->ilBoolToInt($this->get_moderated())),
            'btn_settings' => array('integer', $this->ilBoolToInt($this->get_btnSettings())),
            'btn_chat' => array('integer', $this->ilBoolToInt($this->get_btnChat())),
            'with_chat' => array('integer', $this->ilBoolToInt($this->get_withChat())),
            'btn_locationshare' => array('integer', $this->ilBoolToInt($this->get_btnLocationshare())),
            'member_btn_fileupload' => array('integer', $this->ilBoolToInt($this->get_memberBtnFileupload())),
            'fa_expand' => array('integer', $this->ilBoolToInt($this->get_faExpand())),
            'attendeepwd' => ['string', $this->getAttendeePwd()],
            'moderatorpwd' => ['string', $this->getModeratorPwd()],
            'private_chat' => ['integer', (int) $this->isPrivateChat()],
            'recording' => ['integer', (int) $this->isRecord()],
            'pub_recs' => ['integer', (int) $this->getPubRecs()],
            'cam_only_for_moderator' => ['integer', (int) $this->isCamOnlyForModerator()],
            'lock_disable_cam' => ['integer', (int) $this->getLockDisableCam()],
            'conn_id' => ['integer', (int) $this->getConnId()],
            'access_token' => ['string', $this->getAccessToken()],
            'refresh_token' => ['string', $this->getRefreshToken()],
            'secret_expiration' => ['string', $this->getSecretExpiration()],
            'auth_user' => ['string', $this->getAuthUser()],
            'guestlink' => ['integer', (int) $this->isGuestlink()],
            'extra_cmd' => ['integer', (int) $this->getExtraCmd()],
            'lp_mode' => ['integer', $this->getLPMode()],
            'lp_time' => ['integer', $this->getLpTime()]
        );
        $ilDB->update('rep_robj_xmvc_data', $a_data, array('id' => array('integer', $this->getId())));

        /*
        $authUser = $this->getAuthUser();
        $objUserCreateDefaultsEntry = array_replace([
            $authUser  => self::OBJ_USER_CREATE_DEFAULTS
        ], $this->getMultiVcObjUser());
        $this->setMultiVcObjUser($objUserCreateDefaultsEntry);
        $this->setMultiVcObjUser(['email' => $authUser], $this->getRefId());
        $ilDB->update('rep_robj_xmvc_data', $a_data, array('id' => array('integer', $this->getId())));
        */
    }

    public function updateRoomId($roomId): void
    {
        $ilDB = $this->db;

        $this->setRoomId($roomId);
        $data = [
            'rmid' => ['integer', $this->roomId]
        ];
        $ilDB->update('rep_robj_xmvc_data', $data, array('id' => array('integer', $this->getId())));

    }

    public function updateConnId(int $connId): void
    {
        $ilDB = $this->db;

        $this->setConnId($connId);
        $data = [
            'conn_id' => ['integer', $this->connId]
        ];
        $ilDB->update('rep_robj_xmvc_data', $data, array('id' => array('integer', $this->getId())));
    }

    public function updateAccessRefreshToken(): void
    {
        $ilDB = $this->db;

        $data = [
            'access_token' => ['string', $this->accessToken],
            'refresh_token' => ['string', $this->refreshToken]
        ];
        $ilDB->update('rep_robj_xmvc_data', $data, array('id' => array('integer', $this->getId())));
    }

    public function resetAccessRefreshToken(): void
    {
        $ilDB = $this->db;

        $this->setAccessToken();
        $this->setRefreshToken();

        $data = [
            'access_token' => ['string', $this->accessToken],
            'refresh_token' => ['string', $this->refreshToken]
        ];
        $ilDB->update('rep_robj_xmvc_data', $data, array('id' => array('integer', $this->getId())));
    }

    /**
    * Delete data from db
    */
    public function doDelete(): void
    {
        $ilDB = $this->db;

        #$ilDB->manipulate("DELETE FROM rep_robj_xmvc_session WHERE obj_id = ".$ilDB->quote($this->getId(), "integer"));
        $ilDB->manipulate("DELETE FROM rep_robj_xmvc_schedule WHERE obj_id = " . $ilDB->quote($this->getId(), "integer"));
        $ilDB->manipulate("DELETE FROM rep_robj_xmvc_data WHERE id = " . $ilDB->quote($this->getId(), "integer"));
    }

    protected function doCloneObject(ilObject2 $new_obj, int $a_target_id, ?int $a_copy_id = 0): void
    {
        $this->doClone($new_obj, $a_target_id, $a_copy_id);
    }

    /**
     * Do Cloning
     */
    public function doClone(ilObject2 $new_obj, int $a_target_id, ?int $a_copy_id)
    {
        $ilDB = $this->db;
        $a_data = array(
            'id' => array('integer', $new_obj->getId()), // $a_target_id
            'is_online' => array('integer', $this->ilBoolToInt($this->getOnline())),
            'token' => array('integer', $this->generate_token()),
            'moderated' => array('integer', $this->ilBoolToInt($this->get_moderated())),
            'btn_settings' => array('integer', $this->ilBoolToInt($this->get_btnSettings())),
            'btn_chat' => array('integer', $this->ilBoolToInt($this->get_btnChat())),
            'with_chat' => array('integer', $this->ilBoolToInt($this->get_withChat())),
            'btn_locationshare' => array('integer', $this->ilBoolToInt($this->get_btnLocationshare())),
            'member_btn_fileupload' => array('integer', $this->ilBoolToInt($this->get_memberBtnFileupload())),
            'fa_expand' => array('integer', $this->ilBoolToInt($this->get_faExpand())),
            'attendeepwd' => ['string', $this->generateMembersPwd()],
            'moderatorpwd' => ['string', $this->generateMembersPwd()],
            'private_chat' => ['integer', (int) $this->isPrivateChat()],
            'recording' => ['integer', (int) $this->isRecord()],
            'pub_recs' => ['integer', (int) $this->getPubRecs()],
            'cam_only_for_moderator' => ['integer', (int) $this->isCamOnlyForModerator()],
            'lock_disable_cam' => ['integer', (int) $this->getLockDisableCam()],
            'conn_id' => ['integer', (int) $this->getConnId()],
            'guestlink' => ['integer', (int) $this->isGuestlink()],
            'extra_cmd' => ['integer', (int) $this->getExtraCmd()],
            'lp_mode' => ['integer', $this->getLPMode()],
            'lp_time' => ['integer', $this->getLpTime()]

            //'more_options'			=> ['string', json_encode($this->option)],
        );
        $ilDB->insert('rep_robj_xmvc_data', $a_data);
        // $new_obj->createRoom($this->getOnline());
        // $new_obj->doUpdate();
    }

    public function getMultiVcObjUser(?int $refId = null, ?int $userId = null): array
    {
        $db = $this->dic->database();
        $result = [];
        $userId = $db->quote($userId ?? $this->dic->user()->getId(), 'integer');
        $refId = $db->quote($refId ?? 0, 'integer');

        $sql = 'SELECT rel_data FROM rep_robj_xmvc_obj_user WHERE ref_id = ' . $refId .
            ' AND user_id = ' . $userId;
        $query = $db->query($sql);
        while($row = $db->fetchAssoc($query)) {
            $result = json_decode($row['rel_data'], true);
        }
        return $result;
    }

    public function setMultiVcObjUser(array $param, ?int $refId = null): bool
    {
        $db = $this->dic->database();
        $oldParam = $this->getMultiVcObjUser($refId);
        $newParam = [
            'rel_data' => ['string', json_encode(array_replace($oldParam, $param))]
        ];
        $where = [
            'ref_id' => ['integer', $refId ?? 0],
            'user_id' => ['integer' , $this->dic->user()->getId()]
        ];
        $result = false;
        if(!(bool) sizeof($oldParam)) {
            $result = $db->insert('rep_robj_xmvc_obj_user', array_merge($where, $newParam));
        } else {
            $result = $db->update('rep_robj_xmvc_obj_user', $newParam, $where);
        }
        return (bool) $result;
    }

    public function setMultiVcObjUserAccessToken(string $accessToken): bool
    {
        $param = $this->getMultiVcObjUser();
        $param[$this->getAuthUser()]['access_token'] = $accessToken;
        return $this->setMultiVcObjUser($param);
    }

    public function checkAndSetMultiVcObjUserAsAuthUser(int $userId, string $email, bool $setAuthUser = false, bool $setAccessToken = false): bool
    {
        #$authUser = $this->object->getAuthUser();
        $objectUser = $this->getMultiVcObjUser(0, $userId);
        $objectUserSet = isset($objectUser[$email]) ? $objectUser[$email] : null;
        switch(true) {
            case is_null($objectUserSet):
            case !($email === ilObjUser::_lookupEmail($userId)):
                return false;
            default:
                break;
        }

        /*
        if( !isset($objectUserSet['verified']) || !$objectUserSet['verified'] ||
            !isset($objectUserSet['access_token']) ||
            !(bool)strlen($objectUserSet['access_token']) ) {
            return false;
        }
        */
        if($setAuthUser) {
            $this->setAuthUser($email);
        }
        if($setAccessToken) {
            $this->setAccessToken($objectUserSet['access_token']);
        }
        return true;
    }

    //
    // Set/Get Methods for our MultiVc properties
    //

    public function setOnline(bool $a_val)
    {
        $this->online = $a_val;
    }

    public function getOnline(): bool
    {
        return $this->online;
    }

    public function get_moderated(): bool
    {
        return $this->moderated;
    }
    public function set_moderated($a_moderated)
    {
        $this->moderated = $a_moderated;
    }

    public function get_btnSettings(): bool
    {
        return $this->btnSettings;
    }
    public function set_btnSettings(bool $a_btnSettings)
    {
        $this->btnSettings = $a_btnSettings;
    }

    public function get_btnChat(): bool
    {
        return $this->btnChat;
    }
    public function set_btnChat(bool $a_btnChat)
    {
        $this->btnChat = $a_btnChat;
    }

    public function get_withChat(): bool
    {
        return $this->withChat;
    }
    public function set_withChat(bool $a_withChat)
    {
        $this->withChat = $a_withChat;
    }

    public function get_btnLocationshare(): bool
    {
        return $this->btnLocationshare;
    }
    public function set_btnLocationshare(bool $a_btnLocationshare)
    {
        $this->btnLocationshare = $a_btnLocationshare;
    }

    public function get_memberBtnFileupload(): bool
    {
        return $this->memberBtnFileupload;
    }
    public function set_memberBtnFileupload(bool $a_memberBtnFileupload)
    {
        $this->memberBtnFileupload = $a_memberBtnFileupload;
    }

    public function get_faExpand(): bool
    {
        return $this->faExpand;
    }
    public function set_faExpand(bool $a_faExpand)
    {
        $this->faExpand = $a_faExpand;
    }

    public function set_token(?int $a_val)
    {
        $this->token = $a_val;
    }
    public function get_token(): ?int
    {
        return $this->token;
    }
    public function generate_token(): int
    {
        $this->token = mt_rand(1000000000, 9999999999);
        return $this->token;
    }


    public function ilBoolToInt(bool $a_val): int
    {
        if ($a_val) {
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

    public function getAttendeePwd(): ?string
    {
        return $this->attendeePwd;
    }

    public function setAttendeePwd(?string $attendeePwd): void
    {
        $this->attendeePwd = $attendeePwd;
    }

    public function getModeratorPwd(): ?string
    {
        return $this->moderatorPwd;
    }

    public function setModeratorPwd(?string $moderatorPwd): void
    {
        $this->moderatorPwd = $moderatorPwd;
    }

    public function isPrivateChat(): bool
    {
        return $this->privateChat;
    }

    public function setPrivateChat(bool $privateChat): void
    {
        $this->privateChat = $privateChat;
    }

    public function isRecord(): bool
    {
        return $this->record;
    }

    public function isRecordingAllowed(): bool
    {
        return $this->record;
    }

    public function setRecord(bool $record): void
    {
        $this->record = $record;
    }

    public function getPubRecs(): bool
    {
        return $this->pubRecs;
    }

    public function setPubRecs(bool $pubRecs): void
    {
        $this->pubRecs = $pubRecs;
    }



    public function isCamOnlyForModerator(): bool
    {
        return $this->camOnlyForModerator;
    }

    public function setCamOnlyForModerator(bool $camOnlyForModerator): void
    {
        $this->camOnlyForModerator = $camOnlyForModerator;
    }

    public function getLockDisableCam(): bool
    {
        return $this->lockDisableCam;
    }

    public function setLockDisableCam(bool $lockDisableCam): void
    {
        $this->lockDisableCam = $lockDisableCam;
    }


    public function isGuestlink(): bool
    {
        return $this->guestlink;
    }

    public function setGuestlink(bool $guestlink): void
    {
        $this->guestlink = $guestlink;
    }


    public function getExtraCmd(): int
    {
        return $this->extraCmd;
    }

    public function setExtraCmd(int $extraCmd): void
    {
        $this->extraCmd = $extraCmd;
    }


    public function getMaxConcurrent(string $order = 'desc'): array
    {
        global $DIC;
        $sql = "SELECT * FROM " . self::TABLE_LOG_MAX_CONCURRENT . " ORDER BY year, month, day, hour " . $order;
        $query = $DIC->database()->query($sql);
        $data = [];
        while($row = $DIC->database()->fetchAssoc($query)) {
            $data[] = $row;
        }
        return $data;
    }

    private function getCurrentMaxConcurrent(): array
    {
        global $DIC;
        $defEntry = [
            'year' => date("Y"),
            'month' => date("m"),
            'day' => date("d"),
            'hour' => date("H"),
            'max_meetings' => 0,
            'max_users' => 0,
            'entries' => 0,
            'log' => serialize([])
        ];

        $values = [
            $defEntry['year'],
            $defEntry['month'],
            $defEntry['day'],
            $defEntry['hour'],
        ];

        $types = ['int','int','int','int'];

        $sql = "SELECT max_meetings, max_users, log FROM " . self::TABLE_LOG_MAX_CONCURRENT .
            " WHERE year = %s AND month = %s AND day = %s AND hour = %s";
        $query = $DIC->database()->queryF($sql, $types, $values);
        $entry = $DIC->database()->fetchAssoc($query);

        if ($entry == null) {
            return $defEntry;
        }
        if (!is_array(unserialize($entry['log']))) {
            $entry['log'] = $defEntry['log'];
        }
        return array_merge($defEntry, $entry, ['entries' => 1]);
    }

    public function saveMaxConcurrent(int $meetings, int $users, array $details): void
    {
        global $DIC;

        $currentMax = $this->getCurrentMaxConcurrent();
        $currentMax['max_meetings'] = $currentMax['max_meetings'] > $meetings ? $currentMax['max_meetings'] : $meetings;
        $currentMax['max_users'] = $currentMax['max_users'] > $users ? $currentMax['max_users'] : $users;

        // Logging Details
        $log = array_replace(
            unserialize($currentMax['log']),
            [$details['svrUrl'] => $details['allParentMeetingsParticipantsCount']]
        );

        $log = serialize($log);

        if(!(bool) $currentMax['entries']) {
            // insert
            $DIC->database()->insert(
                self::TABLE_LOG_MAX_CONCURRENT,
                array(
                    'year' => ['integer', $currentMax['year']],
                    'month' => ['integer', $currentMax['month']],
                    'day' => ['integer', $currentMax['day']],
                    'hour' => ['integer', $currentMax['hour']],
                    'max_meetings' => ['integer', $currentMax['max_meetings']],
                    'max_users' => ['integer', $currentMax['max_users']],
                    'log' => ['text', $log]
                )
            );
        } else {
            // update
            $DIC->database()->update(
                self::TABLE_LOG_MAX_CONCURRENT,
                array(
                    'max_meetings' => ['integer', $currentMax['max_meetings']],
                    'max_users' => ['integer', $currentMax['max_users']],
                    'log' => ['text', $log]
                ),
                array(
                    'year' => ['integer', $currentMax['year']],
                    'month' => ['integer', $currentMax['month']],
                    'day' => ['integer', $currentMax['day']],
                    'hour' => ['integer', $currentMax['hour']],
                )
            );
        }
        //var_dump([$currentMax, $this->getCurrentMaxConcurrent()]); exit;
    }

    public function getUserLog(?int $refId = null, ?int $dateFrom = null, ?int $dateTo = null, bool $getId = false): array
    {
        $select = "SELECT ref_id, user_id, display_name, is_moderator,join_time, meeting_id, leave_time";//entfernt: min(join_time)
        $from = " FROM " . self::TABLE_USER_LOG;
        $where = []; //[" WHERE ref_id = " . ($refId ?? '.')];
        if(!is_null($refId)) {
            $where[] = "ref_id = " . $refId;
        }
        if(!is_null($dateFrom)) {
            $where[] = "join_time >= " . $dateFrom;
        }
        if(!is_null($dateTo)) {
            $where[] = "join_time <= " . $dateTo;
        }
        $where = (bool) sizeof($where) ? " WHERE " . implode(' AND ', $where) : '';

        $filter = "";//" group by meeting_id order by display_name, meeting_id"; //entfernt display_name,

        $query = $select . $from . $where . $filter;
        $this->log->debug($query);
        $res = $this->db->query($query);
        $data = [];
        $meetings = [];
        $i = 1;
        while($row = $this->db->fetchAssoc($res)) {
            $start = '';
            if(isset(explode('-', $row['meeting_id'])[1])) {
                $start = substr(explode('-', $row['meeting_id'])[1], 0, -3);
            }
            $replace = [
                'start_time' => $start,
                'meeting_id' => $getId ? $row['meeting_id'] : ''
            ];
            $data[] = array_replace($row, $replace);
        }
        return  $data;
    }

    /**
     * @throws Exception
     */
    public function setUserLog(string $vcType, ilApiBBB|ilApiOM $vcObj): void
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));
        $values = [
            'ref_id' => ['integer', $this->getRefId()],
            'user_id' => ['integer', $this->dic->user()->getId()],
            'join_time' => ['integer', $dateTime->getTimestamp()]
        ];

        // BBB
        if($vcType === 'bbb') {
            $values = array_merge($values, [
                'display_name' => ['text', $vcObj->getDisplayName()],
                'is_moderator' => ['integer', (int) $vcObj->isUserModerator()],
                'meeting_id' => ['text', $vcObj->getMeetingIId()],
            ]);
        }

        $this->db->insert(self::TABLE_USER_LOG, $values);
    }

    public function generateMembersPwd(string $salt = '', int $length = 16): string
    {
        $chars = 'asdfghlkjqwerpoiutzxmcvbnASDFLKJGHQWERPOIUTZXCVBMN';
        $digits = '1234567890';
        $salt = $salt === '' ? $digits : $salt;
        $haystack = str_shuffle(str_repeat($digits . $chars . $salt, 3));
        return substr($haystack, rand(0, strlen($haystack) - 1 - $length), $length);
    }

    private function renewPasswordsBBBVCR(int $id)
    {
        global $DIC;

        $db = $DIC->database();

        $update['where'] = ['id' => ['integer', $id]];
        $update['values'] = [
            'attendeepwd' => ['string', $this->generateMembersPwd()],
            'moderatorpwd' => ['string', $this->generateMembersPwd()]
        ];

        $db->update('rep_robj_xmvc_data', $update['values'], $update['where']);
    }

    public function fillEmptyPasswordsBBBVCR()
    {
        global $DIC;

        $db = $DIC->database();

        $query = "SELECT id FROM rep_robj_xmvc_data WHERE moderatorpwd IS NULL OR attendeepwd IS NULL";
        $result = $db->query($query);
        $update = [];
        $i = 0;
        while($row = $db->fetchAssoc($result)) {
            $update[$i]['where'] = ['id' => ['integer', $row['id']]];
            $update[$i]['values'] = [
                'attendeepwd' => ['string', $this->generateMembersPwd()],
                'moderatorpwd' => ['string', $this->generateMembersPwd()]
            ];
            $i++;
        }

        if(sizeof($update) > 0) {
            foreach($update as $item) {
                $db->update('rep_robj_xmvc_data', $item['values'], $item['where']);
            }
        }
    }

    public function getRoomId(): int
    {
        return $this->roomId;
    }

    public function setRoomId(int $roomId): void
    {
        $this->roomId = $roomId;
    }

    public function getConnId(): ?int
    {
        return $this->connId;
    }

    public function setConnId(int $connId): void
    {
        $this->connId = $connId;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken = null): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken = null): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getSecretExpiration(): ?string
    {
        return $this->secretExpiration;
    }

    public function setSecretExpiration(?string $secretExpiration): void
    {
        $this->secretExpiration = $secretExpiration;
    }

    public function isSecretExpired($secretType = 'accessToken'): bool
    {
        $secretExpiration = $this->getSecretExpiration();
        $secretExpires = (bool) $secretExpiration;
        return $secretExpires && str_replace('-', '', $secretExpiration) < date('Ymd');
    }

    public function getAuthUser(): ?string
    {
        return $this->authUser;
    }

    public function setAuthUser(?string $authUser): void
    {
        $this->authUser = $authUser;
    }

    public function isOwnerAuthUser(): bool
    {
        $query = $this->db->query("SELECT auth_user FROM " . self::TABLE_XMVC_OBJECT . " WHERE id = " .
        $this->db->quote($this->getId(), 'integer'));
        $entry = $this->db->fetchAssoc($query);
        return isset($entry['auth_user']) && $entry['auth_user'] === ilObjUser::_lookupEmail($this->getOwner());
    }

    public function makeOwnerToAuthUser(bool $update = true)
    {
        $this->setAuthUser($this->getOwnersEmail());

        if($update) {
            $values = [
                'auth_user' => ['string', $this->getAuthUser()]
            ];
            $where = [
                'id' => ['integer', $this->getId()]
            ];
            $this->db->update(self::TABLE_XMVC_OBJECT, $values, $where);
        }
    }

    public function getOwnersEmail(): string
    {
        return ilObjUser::_lookupEmail($this->getOwner());
    }

    public function getOwnersName(): string
    {
        return ilObjUser::_lookupOwnerName($this->getOwner());
    }

    public function isUserOwner(): bool
    {
        return (int) $this->getOwner() === (int) $this->dic->user()->getId();
    }

    public function getUserForEmail(string $email, int $index = 0): ?int
    {
        if(sizeof($account = ilObjUser::_getLocalAccountsForEmail($email)) && isset(array_keys($account)[$index])) {
            return array_keys($account)[$index];
        }
        return null;
    }

    public function getAuthSecret(): ?string
    {
        return $this->authSecret;
    }

    public function setAuthSecret(?string $authSecret): void
    {
        $this->authSecret = $authSecret;
    }

    public function getLPMode(): int
    {
        return $this->lpMode;
    }

    public function setLPMode(int $a_lpMode): void
    {
        $this->lpMode = $a_lpMode;
    }

    public function getLpTime(): int
    {
        return $this->lpTime;
    }

    public function setLpTime(int $a_lpTime): void
    {
        $this->lpTime = $a_lpTime;
    }




    private function setDefaultsByPluginConfig(?int $connId, bool $getObject = false): ?ilMultiVcConfig
    {
        $settings = new ilMultiVcConfig($connId);
        $this->option = $settings->option;
        $this->setPrivateChat($settings->isPrivateChatDefault());
        $this->setRecord($settings->isRecordDefault());
        $this->setCamOnlyForModerator($settings->isCamOnlyForModeratorDefault());
        if($getObject) {
            return $settings;
        }
        return null;
    }

    public static function getMultiVcConnTitleAndTypeByObjId(int $objId): ?stdClass
    {
        global $DIC;
        $db = $DIC->database();

        $query = "SELECT rep_robj_xmvc_conn.title title, rep_robj_xmvc_conn.showcontent type FROM rep_robj_xmvc_data, rep_robj_xmvc_conn WHERE rep_robj_xmvc_conn.id = rep_robj_xmvc_data.conn_id AND rep_robj_xmvc_data.id = " . $db->quote($objId, 'integer');
        $result = $db->query($query);
        return $db->fetchObject($result);
    }

    //    static public function langMeeting2Webinar()
    //    {
    //        global $DIC; /**@var Container $DIC */
    //
    //        foreach($DIC->language()->global_cache->getTranslations()['rep_robj_xmvc'] as $key => $val) {
    //            $DIC->language()->text[$key] = str_replace(
    //                ['Meetings', 'Meeting', 'meeting', 'Webex'],
    //                ['Webinare', 'Webinar', 'webinar', 'Edudip'],
    //                $val
    //            );
    //            if( strtolower($DIC->user()->getCurrentLanguage()) !== 'de') {
    //                $DIC->language()->text[$key] = str_replace(
    //                    ['Webinare'],
    //                    ['Webinars'],
    //                    $val
    //                );
    //            }
    //        }
    //    }



    ####################################################################################################################
    #### SCHEDULED SESSIONS (MEETINGS / WEBINARS)
    ####################################################################################################################

    /**
     * string from/to e. g. date('Y-m-d H:i:s')
     */
    public function getScheduledMeetingsByDateRange(string $from, string $to, ?int $refId = null, ?string $timezone = 'Europe/Berlin'): ?array
    {
        $data = null;
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $sql = "SELECT *" .
            " FROM rep_robj_xmvc_schedule" .
            " WHERE start >= " . $this->db->quote($from, 'string') .
            " AND end <= " . $this->db->quote($to, 'string') .
            ((bool) $objId ? " AND obj_id = " . $this->db->quote($objId, 'integer') : '') .
            #(!is_null($refId) && (bool)$objId ? " AND ref_id = " . $this->db->quote($refId, 'integer') : '') .
            " ORDER BY start ASC";

        $result = $this->db->query($sql);

        while ($row = $this->db->fetchAssoc($result)) {
            $row['rel_data'] = json_decode($row['rel_data']);
            $row['rel_data']->wbxmvcRelatedMeeting = true;
            $row['rel_data'] = json_encode($row['rel_data']);
            $data[] = $row;
        }
        return $data;
    }

    /**
     * @throws Exception
     */
    public function getScheduledSessionByRefIdAndDateTime(int $refId, ?string $date = null, int $timeAhead = 0): ?array
    {
        $objId = ilObject::_lookupObjId($refId);

        $date = new DateTime($date ?? date("Y-m-d H:i:s"));
        $start = date("Y-m-d H:i:s", (int) $date->format('U') + $timeAhead);
        $end = date("Y-m-d H:i:s", (int) $date->format('U'));

        $data = null;
        $sql = "SELECT *" . #rel_data, start, end, user_id, auth_user
            " FROM rep_robj_xmvc_schedule" .
            " WHERE start <= " . $this->db->quote($start, 'text') .
            " AND end >= " . $this->db->quote($end, 'text') .
            " AND obj_id = " . $this->db->quote($objId, 'integer') .
            " ORDER BY end DESC" .
            " LIMIT 1";
        $result = $this->db->query($sql);
        while ($row = $this->db->fetchAssoc($result)) {
            $data = $row;
            if(isset($data['rel_data'])) {
                $data['rel_data'] = json_decode($data['rel_data'], 1);
            }
        }

        return $data;
    }

    public function getScheduledMeetingsByDateFrom(string $from, ?int $refId = null, ?string $timezone = 'Europe/Berlin'): ?array
    {
        if ($timezone == 'UTC') {
            $timeMod = new ilDateTime($from, IL_CAL_DATETIME, '');
            $from = $timeMod->get(IL_CAL_DATETIME, 'Y-m-d H:i:s', $timezone);
        }
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $data = null;

        $sql = "SELECT *" .
            " FROM rep_robj_xmvc_schedule" .
            " WHERE end > " . $this->db->quote($from, 'datetime') .
            ((bool) $objId ? " AND obj_id = " . $this->db->quote($objId, 'integer') : '') .
            #(!is_null($refId) ? " AND ref_id = " . $this->db->quote($refId, 'integer') : '') .
            " ORDER BY start ASC";

        $result = $this->db->query($sql);

        while ($row = $this->db->fetchAssoc($result)) {
            $row['rel_data'] = json_decode($row['rel_data']);
            $row['rel_data']->wbxmvcRelatedMeeting = true;
            $row['rel_data'] = json_encode($row['rel_data']);
            $data[] = $row;
        }
        return $data;
    }

    public function getScheduledMeetingByRelId(string $relId, ?int $refId = null, ?int $userId = null): ?array
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $data = null;

        $sql = "SELECT *" .
            " FROM rep_robj_xmvc_schedule" .
            " WHERE rel_id = " . $this->db->quote($relId, 'string') .
            ((bool) $objId ? " AND obj_id = " . $this->db->quote($objId, 'integer') : '') .
            #(!is_null($refId) ? " AND ref_id = " . $this->db->quote($refId, 'integer') : '') .
            (!is_null($userId) ? " AND user_id = " . $this->db->quote($userId, 'integer') : '') .
            " ORDER BY start ASC";

        $result = $this->db->query($sql);

        while ($row = $this->db->fetchAssoc($result)) {
            $data = is_null($data) ? [] : $data;
            $row['rel_data'] = json_decode($row['rel_data']);
            $row['rel_data']->wbxmvcRelatedMeeting = true;
            $row['rel_data'] = json_encode($row['rel_data']);
            $data[] = $row;
        }
        return $data;
    }

    public function deleteScheduledSession(int $refId, string $start, string $end, string $timeZone = 'Europe/Berlin'): bool
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $db = $this->dic->database();
        $db->manipulate(
            'DELETE FROM rep_robj_xmvc_schedule WHERE' .
            ' obj_id=' . $db->quote($objId, 'integer') .
            ' AND start=' . $db->quote($start, 'datetime') .
            ' AND end=' . $db->quote($end, 'datetime') .
            ' AND timezone=' . $db->quote($timeZone, 'text')
        );
        return true;
    }

    public function deleteScheduledSessionByRelId(string $relId): bool
    {
        $this->db->manipulate(
            'DELETE FROM rep_robj_xmvc_schedule WHERE'
            . '  rel_id=' . $this->db->quote($relId, 'string')
        );
        return true;
    }

    public function hasScheduledMeetingsCollision(int $refId, string $from, string $to): ?array
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $data = null;

        $sql = "SELECT *" .
            " FROM rep_robj_xmvc_schedule" .
            " WHERE " .
            " obj_id = " . $this->db->quote($objId, 'integer') .
            " AND ((start <= " . $this->db->quote($from, 'string') .
            " AND end > " . $this->db->quote($from, 'string') .
            ") OR (" .
            " start < " . $this->db->quote($to, 'string') .
            " AND end >= " . $this->db->quote($to, 'string') .
            " )) " .
            " ORDER BY start ASC";
        #var_dump( $sql );
        #exit(__FILE__ . __LINE__);
        $result = $this->db->query($sql);
        while ($row = $this->db->fetchAssoc($result)) {
            $data[] = $row;
        }
        #date_default_timezone_set($currTimeZone);
        return $data;
    }


    public function getScheduledSessionModerator(int $refId, string $relId, int $userId): ?array
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $sql = "SELECT participants" .
            " FROM rep_robj_xmvc_schedule" .
            " WHERE obj_id = " . $this->db->quote($objId, 'integer') .
            #" WHERE ref_id = " . $this->db->quote($refId, 'string') .
            " AND rel_id = " . $this->db->quote($relId, 'string') .
            #(!is_null($userId) ? " AND user_id = " . $this->db->quote($userId, 'integer') : '');
            " AND user_id = " . $this->db->quote($userId, 'integer');
        $result = $this->db->query($sql);
        while($row = $this->db->fetchAssoc($result)) {
            if((bool) strlen($row['participants'])) {
                $participants = json_decode($row['participants'], 1);
                return isset($participants['moderator']) ? $participants['moderator'] : null;
            }
        }
        #var_dump( $row ); exit;

        return null;
    }

    public function hasScheduledSessionModerator(int $refId, string $relId, int $userId): bool
    {
        return !is_null($this->getScheduledSessionModerator($refId, $relId, $userId));
    }

    public function getScheduledSessionAttendee(int $refId, int $relId, int $userId, ?int $partUserId = null): ?array
    {
        $objId = ilObject::_lookupObjId($refId);

        $sql = "SELECT participants" .
            " FROM rep_robj_xmvc_schedule" .
            " WHERE obj_id = " . $this->db->quote($objId, 'integer') .
            #" WHERE ref_id = " . $this->db->quote($refId, 'string') .
            " AND rel_id = " . $this->db->quote($relId, 'string') .
            " AND user_id = " . $this->db->quote($userId, 'integer');
        $result = $this->db->query($sql);
        while($row = $this->db->fetchAssoc($result)) {
            $json = $row['participants'];
            if (strlen($json) > 0) {
                $participants = json_decode($json, 1);
                $participant = isset($participants['attendee']) ? json_decode($participants['attendee'], 1) : null;

                if (!is_null($participant) && !is_null($partUserId) && isset($participant[$partUserId])) {
                    return $participant[$partUserId];
                } else {
                    return $participant;
                }
            }
        }

        return null;
    }



    ####################################################################################################################
    #### HOST SESSIONS
    ####################################################################################################################

    public function getStoredHostSessionsByDateRange(string $from, string $to, ?int $refId = null, ?string $timezone = 'Europe/Berlin'): ?array
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $data = [];

        $sql = "SELECT *" .
            " FROM rep_robj_xmvc_session" .
            " WHERE start >= " . $this->db->quote($from, 'string') .
            " AND end <= " . $this->db->quote($to, 'string') .
            ((bool) $objId ? " AND obj_id = " . $this->db->quote($objId, 'integer') : '') .
            #(!is_null($refId) ? " AND ref_id = " . $this->db->quote($refId, 'integer') : '') .
            " ORDER BY start ASC";
        #        die(var_dump($sql));
        $result = $this->db->query($sql);
        while ($row = $this->db->fetchAssoc($result)) {
            $row['rel_data'] = json_decode($row['rel_data']);
            $row['rel_data']->wbxmvcRelatedMeeting = false;
            $row['rel_data'] = json_encode($row['rel_data']);
            $data[] = $row;
        }
        return $data;
    }

    public function storeHostSession(int $refId, array $items): bool
    {
        #        echo '<pre>'; var_dump($items); echo '</pre>'; exit;
        $objId = ilObject::_lookupObjId($refId);
        #        $state = false;
        $db = $this->dic->database();
        $data2 = [];
        foreach ($items as $item) {
            $item = json_decode(json_encode($item), 1);
            $data = [
                'start' => ['datetime', date('Y-m-d H:i:s', strtotime($item['start']))],
                'end' => ['datetime', date('Y-m-d H:i:s', strtotime($item['end']))],
                'timezone' => ['text', $item['timezone']],
                'rel_data' => ['text', $item['rel_data']],
                'host' => ['text', $item['host']],
                'type' => ['text', $item['type']],
                'obj_id' => ['integer', $objId],
                'rel_id' => ['text', $item['rel_id']]
            ];
            $data2[] = $db->insert('rep_robj_xmvc_session', $data);

        } // EOF foreach ($items as $item)
        #die(var_dump($data2));
        return true;// $state;
    }

    public function deleteStoredHostSessionById(int $refId, ?string $relId = null): bool
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $this->db->manipulate(
            'DELETE FROM rep_robj_xmvc_session WHERE'
            . ' obj_id=' . $this->db->quote($objId, 'integer')
            . (is_null($relId) ? '' : ' AND rel_id=' . $this->db->quote($relId, 'string'))
        );
        return true;
    }

    public function deleteStoredHostSessionByRelId(string $relId): bool
    {
        $this->db->manipulate(
            'DELETE FROM rep_robj_xmvc_session WHERE'
            . '  rel_id=' . $this->db->quote($relId, 'string')
        );
        return true;
    }

    public function relateStoredHostSessionByRelId(string $relId, int $newRefId): void
    {
        $objId = ilObject::_lookupObjId($newRefId);

        $values = [ 'obj_id' => ['integer', $objId]];

        $where = [
            'rel_id' => ['string', $relId],
        ];

        $this->db->update('rep_robj_xmvc_session', $values, $where);
    }


    ####################################################################################################################
    #### Webex
    ####################################################################################################################

    /**
     * @throws Exception
     */
    public function getWebexMeetingByRefIdAndDateTime(int $refId, ?string $date = null, int $timeAhead = 0): ?array
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $date = new DateTime($date ?? date("Y-m-d H:i:s"));
        $start = date("Y-m-d H:i:s", (int) $date->format('U') + $timeAhead);
        $end = date("Y-m-d H:i:s", (int) $date->format('U'));

        $data = null;

        $sql = "SELECT *" . # rel_data, user_id, auth_user, start, end
            " FROM rep_robj_xmvc_schedule" .
            " WHERE start <= " . $this->db->quote($start, 'text') .
            " AND end >= " . $this->db->quote($end, 'text') .
            " AND obj_id = " . $this->db->quote($objId, 'integer') .
            #" AND ref_id = " . $this->db->quote($refId, 'integer') .
            " ORDER BY end DESC" .
            " LIMIT 1";
        $result = $this->db->query($sql);
        while ($row = $this->db->fetchAssoc($result)) {
            $data = $row;
            if(isset($data['rel_data'])) {
                $data['rel_data'] = json_decode($data['rel_data']);
            }
        }
        #date_default_timezone_set($currTimeZone);
        return $data;
    }

    /**
     * @throws Exception
     */
    public function saveWebexMeetingData(int $refId, string $data, bool $returnEntry = false): ?array
    {
        $objId = ilObject::_lookupObjId($refId);
        $dataObj = json_decode($data);
        $dataObj->ilCreateDate = date('Y-m-d H:i:s');
        $userId = $this->dic->user()->getId();
        $authUser = $dataObj->email; # $this->getAuthUser();
        $recurrence = '';
        if (isset($dataObj->recurrence)) {
            $recurrence = strpos($dataObj->recurrence, 'FREQ') === 0 ? explode('=', explode(';', $dataObj->recurrence)[0])[1] : '';
        }
        $db = $this->dic->database();

        $values = [
            #'ref_id'	=> ['integer', $refId],
            'obj_id' => ['integer', $objId],
            'start' => ['datetime', date('Y-m-d H:i:s', strtotime($dataObj->start))],
            'end' => ['datetime', date('Y-m-d H:i:s', strtotime($dataObj->end))],
            'timezone' => ['string', $dataObj->timezone],
            'recurrence' => ['string', $recurrence],
            'user_id' => ['integer', $userId],
            'auth_user' => ['string', $authUser],
            'rel_id' => ['string', $dataObj->id],
            'rel_data' => ['string', json_encode($dataObj)]
        ];

        $this->db->insert('rep_robj_xmvc_schedule', $values);

        if($returnEntry) {
            return $this->getWebexMeetingByRefIdAndDateTime($refId, $dataObj->start);
        }
        return null;
    }

    public function deleteWebexMeeting(int $refId, string $start, string $end, string $timeZone = 'Europe/Berlin'): bool
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        return (bool) $this->db->manipulate(
            'DELETE FROM rep_robj_xmvc_schedule WHERE' .
            ' obj_id=' . $this->db->quote($objId, 'integer') .
            #' ref_id=' . $db->quote($refId, 'integer') .
            ' AND start=' . $this->db->quote($start, 'text') .
            ' AND end=' . $this->db->quote($end, 'text') .
            ' AND timezone=' . $this->db->quote($timeZone, 'text')
        );
    }

    public function saveWebexSessionModerator(int $refId, string $relId, int $userId, string $data, bool $returnEntry = false, ?int $lookupUserId = null): array|bool|null
    {
        $objId = ilObject::_lookupObjId($refId);
        $lookupUserId = $lookupUserId ?? $userId;
        $dataArr = json_decode($data, true);
        if(null === $currEntry = $this->getScheduledMeetingByRelId($relId, $refId, $lookupUserId)) {
            #if( null === $currEntry = $this->getScheduledMeetingByRelId($relId, $refId, $userId) ) {
            return false;
        }
        $currValues = json_decode('' . $currEntry[0]['participants'], 1);
        $currValues['moderator'][$this->dic->user()->getId()] = $dataArr;

        $values = [ 'participants' => ['string', json_encode($currValues)]];

        $where = [
            #'ref_id'	=> ['integer', $refId],
            'obj_id' => ['integer', $objId],
            'rel_id' => ['string', $relId],
            'user_id' => ['integer', $lookupUserId],
            #'user_id'       => ['integer', $userId],
        ];

        $this->db->update('rep_robj_xmvc_schedule', $values, $where);

        if($returnEntry) {
            return $this->getScheduledMeetingByRelId($relId, $refId, $lookupUserId)[0];
        }
        return false;
    }

    public function saveWebexSessionParticipant(int $refId, string $relId, int $userId, string $data, bool $returnEntry = false): array|bool|null
    {
        $objId = ilObject::_lookupObjId($refId);
        $dataArr = json_decode($data, true);
        if(null === $currEntry = $this->getScheduledMeetingByRelId($relId, $refId, $userId)) {
            return false;
        }
        $currValues = json_decode($currEntry[0]['participants'], 1);
        $currValues['attendee'][$this->dic->user()->getId()] = [
            'id' => $dataArr['id'],
            'email' => $dataArr['email'],
            'displayName' => $dataArr['displayName'],
            'coHost' => $dataArr['coHost'],
            'meetingId' => $dataArr['meetingId'],
        ];
        $values = [ 'participants' => ['string', json_encode($currValues)]];

        $where = [
            'obj_id' => ['integer', $objId],
            #'ref_id'	=> ['integer', $refId],
            'rel_id' => ['string', $relId],
            'user_id' => ['integer', $userId],
        ];

        $this->db->update('rep_robj_xmvc_schedule', $values, $where);

        if($returnEntry) {
            return $this->getScheduledMeetingByRelId($relId, $refId, $userId)[0];
        }
        return false;
    }

    ####################################################################################################################
    #### Teams
    ####################################################################################################################



    public function saveTeamsSessionData(int $refId, array $data, bool $returnEntry = false, bool $addHostSessEntry = false): ?array
    {
        $objId = ilObject::_lookupObjId($refId);

        // die(var_dump($data));
        // $dataObj = json_decode($data, false);
        // $dataArr = json_decode($data, true);
        // $dataObj->ilCreateDate = date('Y-m-d H:i:s');
        // $webinar = $dataArr['webinar'];
        // $dataObj->id = $webinar['id'];
        // $date = $webinar['dates'][0];
        // $dataObj->start = $date['date'];
        // $dataObj->end = $date['date_end'];
        // $dataObj->timezone = $this->dic->user()->getTimeZone();
        // $dataObj->title = $webinar['title'];



        $userId = $this->dic->user()->getId();
        $authUser = $this->getAuthUser();

        $recurrence = ''; # strpos($dataObj->recurrence, 'FREQ') === 0 ? explode('=', explode(';', $dataObj->recurrence)[0])[1] : '';
        $db = $this->dic->database();

        $values = [
            #'ref_id'	=> ['integer', $refId],
            'obj_id' => ['integer', $objId],
            'start' => ['datetime', $data['start']],
            'end' => ['datetime', $data['end']],
            'timezone' => ['string', $data['timezone']],
            'recurrence' => ['string', $recurrence],
            'user_id' => ['integer', $userId],
            'auth_user' => ['string', $authUser],
            'rel_id' => ['string', $data['rel_id']],
            'rel_data' => ['string', $data['rel_data']]
        ];

        $this->db->insert('rep_robj_xmvc_schedule', $values);

        if($addHostSessEntry) {
            $values = [
                'obj_id' => ['integer', $objId],
                'start' => ['string', $data['start']],//todo Datetime
                'end' => ['string', $data['end']],
                'timezone' => ['string', $data['timezone']],
                'host' => ['string', 'teams'],
                'type' => ['string', 'meeting'],
                'rel_id' => ['string', $data['rel_id']],
                'rel_data' => ['string', $data['rel_data']]
            ];
            $this->db->insert('rep_robj_xmvc_session', $values);
        }

        if($returnEntry) {
            return $this->getScheduledMeetingByRelId($data['rel_id'], $refId, $userId)[0];
        }
        return null;
    }

    public function saveTeamsSessionParticipant(int $refId, int $relId, int $userId, string $data, bool $returnEntry = false): array|bool|null
    {
        $objId = ilObject::_lookupObjId($refId);

        $dataArr = json_decode($data, true);
        if(null === $currEntry = $this->getScheduledMeetingByRelId($relId, $refId, $userId)) {
            return false;
        }
        $currValues = json_decode($currEntry[0]['participants'], 1);
        $currValues['attendee'][$this->dic->user()->getId()] = array_merge(
            $dataArr['participant'],
            [
                // 'webLink'   => $dataArr['globalWebinarLink'],
                // 'email'     => $dataArr['called_param']['email']
            ]
        );
        $values = [ 'participants' => ['string', json_encode($currValues)]];

        $where = [
            'obj_id' => ['integer', $objId],
            'rel_id' => ['string', $relId],
            'user_id' => ['integer', $userId],
        ];

        $this->db->update('rep_robj_xmvc_schedule', $values, $where);

        if($returnEntry) {
            return $this->getScheduledMeetingByRelId($relId, $refId, $userId)[0];
        }
        return false;
    }


    ####################################################################################################################
    #### EDUDIP
    ####################################################################################################################


    public function saveEdudipSessionData(int $refId, string $data, bool $returnEntry = false, bool $addHostSessEntry = false): ?array
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $dataObj = json_decode($data, false);
        $dataArr = json_decode($data, true);
        $dataObj->ilCreateDate = date('Y-m-d H:i:s');
        $webinar = $dataArr['webinar'];
        $dataObj->id = $webinar['id'];
        $date = $webinar['dates'][0];
        $dataObj->start = $date['date'];
        $dataObj->end = $date['date_end'];
        $dataObj->timezone = $this->dic->user()->getTimeZone();
        $dataObj->title = $webinar['title'];

        $userId = $this->dic->user()->getId();
        $authUser = $this->getAuthUser();

        $recurrence = ''; # strpos($dataObj->recurrence, 'FREQ') === 0 ? explode('=', explode(';', $dataObj->recurrence)[0])[1] : '';
        $db = $this->dic->database();

        $values = [
            #'ref_id'	=> ['integer', $refId],
            'obj_id' => ['integer', $objId],
            'start' => ['datetime', $dataObj->start],
            'end' => ['datetime', $dataObj->end],
            'timezone' => ['string', $dataObj->timezone],
            'recurrence' => ['string', $recurrence],
            'user_id' => ['integer', $userId],
            'auth_user' => ['string', $authUser],
            'rel_id' => ['string', $dataObj->id],
            'rel_data' => ['string', json_encode($dataObj)]
        ];

        $this->db->insert('rep_robj_xmvc_schedule', $values);

        if($addHostSessEntry) {
            $values = [
                'obj_id' => ['integer', $objId],
                'start' => ['string', $dataObj->start],
                'end' => ['string', $dataObj->end],
                'timezone' => ['string', $dataObj->timezone],
                'host' => ['string', 'edudip'],
                'type' => ['string', 'webinar'],
                'rel_id' => ['string', $dataObj->id],
                'rel_data' => ['string', '{}']
            ];
            $this->db->insert('rep_robj_xmvc_session', $values);
        }

        if($returnEntry) {
            return $this->getScheduledMeetingByRelId($dataObj->id, $refId, $userId)[0];
        }
        return null;
    }

    public function saveEdudipSessionModerator(int $refId, int $relId, int $userId, string $data, bool $returnEntry = false, ?int $lookupUserId = null): array|bool|null
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);
        $lookupUserId = $lookupUserId ?? $userId;

        $dataArr = json_decode($data, true);
        $moderator = $dataArr['moderator'];
        $moderator['user_id'] = $userId;
        $moderator['webLink'] = $moderator['room_link'];
        if(null === $currEntry = $this->getScheduledMeetingByRelId($relId, $refId, $lookupUserId)) {
            #if( null === $currEntry = $this->getScheduledMeetingByRelId($relId, $refId, $userId) ) {
            return false;
        }
        $currValues = json_decode($currEntry[0]['participants'], 1);
        $currValues['moderator'][$userId] = $moderator;
        #$currValues['moderator'][$this->dic->user()->getId()] = $moderator;

        $values = [ 'participants' => ['string', json_encode($currValues)]];

        $where = [
            'obj_id' => ['integer', $objId],
            #'ref_id'	=> ['integer', $refId],
            'rel_id' => ['string', $relId],
            'user_id' => ['integer', $lookupUserId],
            #'user_id'       => ['integer', $userId],
        ];

        $this->db->update('rep_robj_xmvc_schedule', $values, $where);

        if($returnEntry) {
            return $this->getScheduledMeetingByRelId($relId, $refId, $lookupUserId)[0];
            #return $this->getScheduledMeetingByRelId($relId, $refId, $userId)[0];
        }
        return null;//?
    }

    public function saveEdudipSessionParticipant(int $refId, int $relId, int $userId, string $data, bool $returnEntry = false): array|bool|null
    {
        $objId = is_null($refId) ? null : ilObject::_lookupObjId($refId);

        $dataArr = json_decode($data, true);
        if(null === $currEntry = $this->getScheduledMeetingByRelId($relId, $refId, $userId)) {
            return false;
        }
        $currValues = json_decode($currEntry[0]['participants'], 1);
        $currValues['attendee'][$this->dic->user()->getId()] = array_merge(
            $dataArr['participant'],
            [
                'webLink' => $dataArr['globalWebinarLink'],
                'email' => $dataArr['called_param']['email']
            ]
        );
        $values = [ 'participants' => ['string', json_encode($currValues)]];

        $where = [
            'obj_id' => ['integer', $objId],
            #'ref_id'	=> ['integer', $refId],
            'rel_id' => ['string', $relId],
            'user_id' => ['integer', $userId],
        ];

        $this->db->update('rep_robj_xmvc_schedule', $values, $where);

        if($returnEntry) {
            return $this->getScheduledMeetingByRelId($relId, $refId, $userId)[0];
        }
        return false;
    }


    ####################################################################################################################
    #region MAIL NOTIFICATION
    ####################################################################################################################

    public function getNotificationEntry(?string $procId = null, int $status = ilMultiVcMailNotification::PROC_PENDING): array
    {
        $data = [];
        $where = [
            'proc_id = ' . $this->db->quote($procId, 'string'),
            'status = ' . $this->db->quote($status, 'integer'),
        ];

        $res = $this->db->query("SELECT * FROM rep_robj_xmvc_notify WHERE " . implode(' AND ', $where));
        while ($row = $this->db->fetchAssoc($res)) {
            $data[$row['id']] = $row;
        }

        return $data;
    }

    public function createNotificationEntry(int $objId, int $relId, int $userId, string $authUser, string $recipient, string $message, string $procId): ?string
    {

        $log = [__FUNCTION__ => date('Y-m-d H:i:s')];
        $data = [
            'id' => ['integer', $this->db->nextId('rep_robj_xmvc_notify')],
            'obj_id' => ['integer', $objId], # ilObject::_lookupObjId($refId)
            'rel_id' => ['string', $relId],
            'user_id' => ['integer', $userId],
            'auth_user' => ['string', $authUser],
            'recipient' => ['string', $recipient],
            'status' => ['integer', ilMultiVcMailNotification::PROC_PENDING],
            'proc_id' => ['string', $procId],
            'log' => ['string', json_encode($log)],
            'message' => ['string', $message],
        ];
        if(empty($id = $this->db->insert('rep_robj_xmvc_notify', $data))) {
            return null;
        }
        return $procId; #$data['id'];
    }

    public function storeNotificationStatusInProgress(string $procId): bool # int $refId, int $relId, int $userId, string $authUser,
    {
        $where = [
            'status' => ['integer', ilMultiVcMailNotification::PROC_PENDING],
            'proc_id' => ['string', $procId],
        ];

        $data = [
            'status' => ['integer', ilMultiVcMailNotification::PROC_IN_PROGRESS],
            'updated' => ['datetime', date('Y-m-d H:i:s')], #$this->db->now()
        ];

        for(;;) {
            if(empty($this->db->update('rep_robj_xmvc_notify', $data, $where))) {
                break;
            }
        }
        return true;
    }

    public function storeNotificationStatusById(int $id, string $procId, int $status, ?array $log = null): bool
    {
        $where = [
            'id' => ['integer', $id],
            'proc_id' => ['string', $procId],
        ];

        $data = [
            'status' => ['integer', $status],
        ];
        if($log) {
            $data['log'] = ['string', json_encode($log)];
        }

        if(empty($this->db->update('rep_robj_xmvc_notify', $data, $where))) {
            return false;
        }
        return true;
    }

    public function getContainerMembers(int $objId, ?string $role = null): array
    {
        $data = [];
        $selector = '*';
        $where = [
            'obj_id = ' . $this->db->quote($objId, 'integer'),
        ];

        $res = $this->db->query("SELECT * FROM obj_members WHERE " . implode(' AND ', $where));
        while ($row = $this->db->fetchAssoc($res)) {
            $data[] = $row;
        }

        return $data;
    }

    public function getNotificationTextPhrases(string $from, string $event, string $subject, string $dateRange, string $userName, string $link = '', string $type = 'webinar'): array
    {
        $lng = $this->dic->language();

        $subjectReplace = [
            '{EVENT}' => $event,
            '{SUBJECT}' => $subject
        ];

        $msgReplace = [
            '{NAME}' => $userName,
            '{EVENT}' => $event,
            '{SUBJECT}' => $subject,
            '{DATERANGE}' => $dateRange,
            '{LINK}' => $link,
            '{FROM}' => $from,
            '{NL}' => '%0D%0A'
        ];

        return [
            'subject' => str_replace(
                array_keys($subjectReplace),
                $subjectReplace,
                stripslashes($this->dic->language()->txt('rep_robj_xmvc_' . $type . '_notification_subject'))
            ),
            'body' => str_replace(
                array_keys($msgReplace),
                $msgReplace,
                stripslashes($this->dic->language()->txt('rep_robj_xmvc_' . $type . '_notification_body'))
            ),
        ];
    }
    #endregion MAIL NOFIFICATION


    ####################################################################################################################
    #region BBB RECORDINGS
    ####################################################################################################################

    public function getBBBRecsByRefId(int $refId, ?string $createDate = null): array
    {
        $data = [];

        $sql = "SELECT * FROM rep_robj_xmvc_recs_bbb WHERE ref_id = " . $this->db->quote($refId, 'integer');
        if(!is_null($createDate)) {
            $sql .= " AND create_date >= " . $this->db->quote($createDate, 'timestamp');
        }
        $res = $this->db->query($sql);
        while ($row = $this->db->fetchAssoc($res)) {
            $data[$row['rec_id']] = $row;
        }
        return $data;
    }

    public function getBBBRecByIds($refId, $recId): array
    {
        $data = [];

        $sql = "SELECT * FROM rep_robj_xmvc_recs_bbb WHERE ref_id = " . $this->db->quote($refId, 'integer') .
            " AND rec_id = " . $this->db->quote($recId, 'string');
        $res = $this->db->query($sql);
        while ($row = $this->db->fetchAssoc($res)) {
            $data[] = $row;
        }
        return $data;
    }

    public function getBBBRecByTask($task): array
    {
        $data = [];

        $sql = "SELECT ref_id, rec_id FROM rep_robj_xmvc_recs_bbb WHERE task = " . $this->db->quote($task, 'string');
        $res = $this->db->query($sql);
        while ($row = $this->db->fetchAssoc($res)) {
            $data[] = $row;
        }
        return $data;
    }

    public function storeBBBRec(int $refId, string $recId, string $meetingId, int $createDate, ?int $available = null): bool
    {
        $isNewEntry = !count($this->getBBBRecByIds($refId, $recId));

        if($isNewEntry) {
            $available = $this->checkBBBRecAvailabilitySettings();
            $values = [
                'ref_id' => ['integer', $refId],
                'rec_id' => ['string', $recId],
                'meeting_id' => ['string', $meetingId],
                'available' => ['integer', $available],
                'create_date' => ['timestamp', date('Y-m-d H:i:s', $createDate)],
            ];
            $this->db->insert('rep_robj_xmvc_recs_bbb', $values);
            return true;
        }
        return false;
    }

    public function updateBBBRec(int $refId, string $recId, ?int $available = null, ?string $task = null): bool
    {
        $values = [
            'available' => ['integer', $available],
            'update_date' => ['timestamp', date('Y-m-d H:i:s')],
            'updated_by' => ['integer', $this->dic->user()->getId()],
            'task' => ['string', $task],
        ];

        $where = [
            'ref_id' => ['integer', $refId],
            'rec_id' => ['string', $recId],
        ];

        $this->db->update('rep_robj_xmvc_recs_bbb', $values, $where);

        return true;
    }

    public function deleteBBBRecById(int $refId, string $recId, ?ilApiBBB $vcObj = null): bool
    {
        if($vcObj instanceof ilApiBBB) {
            if(!$vcObj->deleteRecord($recId)->isDeleted()) {
                return false;
            }
        }
        $refId = $this->db->quote($refId, 'integer');
        $recId = $this->db->quote($recId, 'string');

        $this->db->query("DELETE FROM rep_robj_xmvc_recs_bbb WHERE ref_id = $refId AND rec_id = $recId");

        return true;
    }

    public function runBBBRecTask($task, ?ilApiBBB $vcObj = null): bool
    {
        if($vcObj instanceof ilApiBBB && count($bbbRecs = $this->getBBBRecByTask($task))) {
            $cmd = $task . 'BBBRecById';
            foreach ($bbbRecs as $bbbRec) {
                $this->$cmd($bbbRec['ref_id'], $bbbRec['rec_id'], $vcObj);
            }
        }
        return true;
    }

    private function checkBBBRecAvailabilitySettings(): int
    {
        $settings = new ilMultiVcConfig($this->getConnId());
        return (int) ($settings->getPubRecsChoose() && $this->getPubRecs() || $settings->getPubRecsDefault() && !$settings->getPubRecsChoose());
    }


    #endregion BBB RECORDINGS

    /**
     * Get all user ids with LP status completed
     */
    public function getLPCompleted(): array
    {
        return ilMultiVcLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_COMPLETED_NUM);
    }

    /**
     * Get all user ids with LP status not attempted
     */
    public function getLPNotAttempted(): array
    {
        return ilMultiVcLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
    }

    /**
     * Get all user ids with LP status failed
     */
    public function getLPFailed(): array
    {
        return ilMultiVcLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_FAILED_NUM);
    }

    /**
     * Get all user ids with LP status in progress
     */
    public function getLPInProgress(): array
    {
        return ilMultiVcLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
    }

    /**
     * Get current status for given user
     */
    public function getLPStatusForUser(int $a_user_id): int
    {
        return ilMultiVcLPStatus::getLPDataForUserFromDb($this->getId(), $a_user_id);
    }

    /**
     * Track access for learning progress
     */
    public function trackAccess()
    {
        global $DIC;
        // track access for learning progress
        if ($DIC->user()->getId() != ANONYMOUS_USER_ID && $this->getLPMode() == self::LP_ACTIVE) {
            ilMultiVcLPStatus::trackAccess($DIC->user()->getId(), $this->getId(), $this->getRefId());
        }
    }

    ####### userLog for Teams
    public function getLogEntriesForTeams()
    {

    }
}

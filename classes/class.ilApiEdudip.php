<?php

use ILIAS\DI\Container;
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilApiInterface.php");

class ilApiEdudip implements ilApiInterface
{
    const INI_FILENAME = 'plugin';
    const PLUGIN_PATH = './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';
    const PLAYBACKURL_SPLIT = '2.0/playback.html?meetingId=';

    const API_URL = 'https://api.edudip-next.com/api/';

    CONST ENDPOINT_WEBINAR = 'webinars';

    /** @var ilObjMultiVc $object */
    private $object;


    /** @var ilObjMultiVcGUI|null $objGui */
    private $objGui = null;

    /** @var ilMultiVcConfig $settings */
    private $settings;

    /** @var string $meetingId */
    private $meetingId = 0;

    /** @var bool $moderatedMeeting */
    private $moderatedMeeting;

    /** @var string $userRole */
    private $userRole;

    /** @var bool $meetingStartable */
    private $meetingStartable = true;

    /** @var bool $meetingRecordable */
    private $meetingRecordable = false;

    /** @var string $displayName */
    private $displayName;

    /** @var array $pluginIniSet */
    private $pluginIniSet = [];

    /** @var bool|ilObject $parentObj */
    private $parentObj;

    /** @var bool|ilObjCourse $course */
    private $course;

    /** @var bool|ilObjGroup $group */
    private $group;

    /** @var bool|ilObjCategory $category */
    private $category;

    /** @var ilObjSession $ilObjSession */
    private $ilObjSession;

    /** @var Container $dic */
    private $dic;

    /** @var null|int $relId */
    private $relId = null;

    /** @var null|string $start */
    private $start = null;

    /** @var null|string $end */
    private $end = null;

    /** @var null|string $email */
    private $email = null;

    /** @var null|string $webLink */
    private $webLink = null;


    /** @var ilApiEdudip|null $instance */
    static private $instance = null;



    /**
     * ilApiEdudip constructor.
     * @param ilObjMultiVcGUI $a_parent
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct(ilObjMultiVcGUI $a_parent)
    {
        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;


        $plugin = ilPluginAdmin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'MultiVc');

        #$a_parent = $a_parent ?? new ilObjectGUIFactory();

        $this->objGui = $a_parent;
        $this->object = $a_parent->object;
        $this->settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        $this->pluginIniSet = ilApiMultiVC::setPluginIniSet($this->settings);
        $this->moderatedMeeting = $this->object->get_moderated();
        $this->setUserRole();
    }

    /**
     * @param int $id
     * @return ilApiEdudip
     * @throws ilObjectException
     * @throws ilObjectNotFoundException
     * @throws ilDatabaseException
     */
    static public function getInstanceByRefId(int $id) {
        $objF = new ilObjectGUIFactory();
        $gui = $objF->getInstanceByRefId($id);
        return self::$instance ?? self::$instance = new self($gui);
    }


    /**
     * @param array $param
     * @param string $type
     * @return bool|string
     */
    public function sessionCreate(array $param = [], string $type = 'webinar'): string
    {
        $dates = [];
        $dates[] = [
            'date' => $param['dateStart'],
            'duration'  => $param['duration']
        ];

        $param = [
            'title' => $param['title'],
            'max_participants'  => $param['max_participants'], # 1 -
            'recording'         => 0, # 1
            'registration_type' => 'date', # series
            'access'            => 'all', # invitation all
            'dates'             => json_encode($dates),
        ];
        return $this->restfulApiCall(self::ENDPOINT_WEBINAR, 'post', $param);
    }

    /**
     * @param int $sessId
     * @return bool
     */
    public function sessionDelete( int $sessId ): bool
    {
        $response = $this->restfulApiCall(self::ENDPOINT_WEBINAR . '/' . $sessId, 'delete');
        $json = json_decode($response, 1);
        return isset($json['success']) && $json['success'] === true;
    }

    /**
     * @param int|null $sessId
     * @return bool|string
     */
    public function sessionGet( ?int $sessId = null ): string
    {
        if( null === $sessId ) {
            return false;
        }
        return $this->restfulApiCall(self::ENDPOINT_WEBINAR . '/' . $sessId, 'get');
    }

    /**
     * @return string|bool
     */
    public function sessionList(): string
    {
        return $this->restfulApiCall(self::ENDPOINT_WEBINAR);
    }

    /**
     * @param int $sessId
     * @param string $sessDate
     * @param string $firstname
     * @param string $lastname
     * @param string|null $email
     * @return bool|string
     */
    public function sessionParticipantAdd(int $sessId, string $sessDate, string $firstname, string $lastname, ?string $email = null) {
        $endPoint = self::ENDPOINT_WEBINAR . '/' . $sessId . '/register-participant';
        $param = [
            'email'     => $email ?? date('YmdHis') . uniqid() . '@example.com',
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'webinar_date'  => $sessDate
        ];
        return $this->restfulApiCall($endPoint, 'post', $param);
    }

    /**
     * @param int $sessId
     * @param string $firstname
     * @param string $lastname
     * @param string|null $email
     * @return bool|string
     */
    public function sessionModeratorAdd(int $sessId, string $firstname, string $lastname, string $email) {
        $endPoint = self::ENDPOINT_WEBINAR . '/' . $sessId . '/moderators/add';
        $param = [
            'email'     => $email,
            'firstname' => $firstname,
            'lastname'  => $lastname,
            #'webinar_date'  => $sessDate
        ];
        return $this->restfulApiCall($endPoint, 'post', $param);
    }

    /**
     * @param string|null $endpoint
     * @param string $method
     * @param array $param
     * @param string $contentType
     * @return bool|string
     */
    public function restfulApiCall(?string $endpoint = null, string $method = 'GET', $param = [], $contentType = 'application/json;charset=UTF-8')
    {
        $authUser = $this->object->getAuthUser();
        $accessToken = $this->object->getAccessToken();
        if( is_null($authUser) || is_null($accessToken) ) {
            return false;
        }

        $url = self::API_URL . $endpoint ?? self::ENDPOINT_WEBINAR;

        $isPost = false !== array_search(strtoupper($method), ['POST', 'PUT']);

        $timeout = 60;
        $maxRedirects = 10;

        $header = [
            'content-type: ' . ($isPost ? 'application/x-www-form-urlencoded' : $contentType),
            'accept: application/json, text/plain, */*',
            #'accept-encoding: gzip, deflate, br',
            #'accept-language: en,de;q=0.9,de-DE;q=0.8,en-GB;q=0.7,en-US;q=0.6',
            #'cache-control: no-cache',
            'Authorization: Bearer ' . $accessToken,
            #'timezone: Europe/Berlin'
        ];

        try {

            $curl = new ilCurlConnection($url);
            defined(ILIAS_VERSION) && 0 === strpos(ILIAS_VERSION, 5) ? $curl->init() : $curl->init(false);
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
            #$curl->setOpt(CURLOPT_CONNECTTIMEOUT, $timeout);
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
            $curl->setOpt(CURLOPT_MAXREDIRS, $maxRedirects);
            $curl->setOpt(CURLOPT_HTTPHEADER, $header);
            $curl->setOpt(CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if( $isPost ) {
                $curl->setOpt(CURLOPT_POST, 1);
                $curl->setOpt(CURLOPT_POSTFIELDS, http_build_query($param));
            }
            $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
            $response = $curl->exec();
            #echo '<pre>'; var_dump($response); exit;
            $code = (int)$curl->getInfo(CURLINFO_HTTP_CODE);
            $json = json_decode($response, true);
            $json['http_code'] = $code;
            $json['called_param'] = $param;
            if( strlen($json['error']) && substr($json['error'], -1) !== '.' ) {
                $json['error'] .= '.';
            }
            $json['called_endpoint'] = $endpoint;
            $json['called_method'] = $method;
        } catch (ilCurlConnectionException $e) {
            $json = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            if( (bool)strlen($json['error']) ) {
                ilUtil::sendFailure($this->dic->language()->txt('error') . '<br />' . $json['error'], true);
                $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
            }
        }


        return json_encode($json);

    }

    /**
     * @return string
     */
    public function getMeetingId(): string
    {
        return $this->meetingId;
    }



    /**
     * @return bool
     */
    public function isModeratedMeeting(): bool
    {
        return $this->moderatedMeeting;
    }

    /**
     * @return bool
     */
    public function isUserModerator(): bool
    {
        return $this->userRole === 'moderator';
    }

    public function isUserAdmin(): bool {
        global $DIC; /** @var Container $DIC */
        $userLiaRoles = $DIC->rbac()->review()->assignedRoles($DIC->user()->getId());
        if( null !== $this->course ) {
            $found = array_search($this->course->getDefaultAdminRole(), $userLiaRoles);
        } else {
            $found = $DIC->access()->checkAccessOfUser($DIC->user()->getId(), 'write', 'showContent', $this->object->getRefId());
        }

        //var_dump($userLiaRoles); exit;

        return false !== $found;
    }

    /**
     * @return string
     */
    public function getUserRole(): string
    {
        return $this->userRole;
    }

    /**
     * @return bool
     */
    public function isMeetingStartable(): bool
    {
        return $this->meetingStartable;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isMeetingRunning(): bool
    {
        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isModeratorPresent(): bool
    {
        return true;

    }


    /**
     * @return bool
     */
    public function hasSessionObject(): bool
    {
        return !!$this->ilObjSession;
    }

    public function isValidAppointmentUser(): bool
    {
        return !$this->ilObjSession ||
            (
                !!$this->ilObjSession &&
                ilEventParticipants::_isRegistered($this->dic->user()->getId(), $this->ilObjSession->getId())
            );
    }


    /**
     * @return bool
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    private function isInCourseOrGroup(): bool
    {
        if( !$this->parentObj )
        {
            $this->setParentObj();
        }

        if( !$this->category )
        {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    private function isAdminOrTutor(): bool
    {
        global $DIC; /** @var Container $DIC */

        if( $this->isInCourseOrGroup() )
        {
            $userLiaRoles = $DIC->rbac()->review()->assignedRoles($DIC->user()->getId());
            if( !!$this->course )
            {
                $found = array_search($this->course->getDefaultAdminRole(), $userLiaRoles);
                $found = false !== $found ? true : array_search($this->course->getDefaultTutorRole(), $userLiaRoles);
                return false !== $found;
            }
            if( !!$this->group )
            {
                $found = array_search($this->group->getDefaultAdminRole(), $userLiaRoles);
                return false !== $found;
            }
        }
        return false;
    }

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    private function setUserRole(): void
    {
        global $DIC; /** @var Container $DIC */

        switch (true)
        {
            case $this->isInCourseOrGroup() && $this->isAdminOrTutor():
            case $this->isInCourseOrGroup() && $DIC->access()->checkAccessOfUser($DIC->user()->getId(), 'write', 'showContent', $this->object->getRefId()):
            case !$this->isInCourseOrGroup() && $DIC->access()->checkAccessOfUser($DIC->user()->getId(), 'write', 'showContent', $this->object->getRefId()):
            case !$this->isModeratedMeeting():
                $this->userRole = 'moderator';
                break;
            default:
                $this->userRole = 'attendee';
        }
    }

    /**
     * Set the Name to show in meeting room
     */
    private function setDisplayName(): void
    {
        global $DIC; /** @var Container $DIC */
        $this->displayName = $DIC->user()->firstname . ' ' . $DIC->user()->lastname;
    }

    /**
     * @return bool|ilObject
     */
    public function getParentObj()
    {
        return $this->parentObj;
    }

    /**
     * @return bool|ilObject
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    private function setParentObj(): void
    {
        global $DIC; /** @var Container $DIC */

        $path = array_reverse($DIC->repositoryTree()->getPathFull($this->object->getRefId()));
        $keys = array_keys($path);
        #$parent = $path[$keys[1]];
        foreach( $path as $key => $node ) {
            if( false !== array_search($node['type'], ['crs', 'grp', 'cat']) ) {
                $parent = $node;
                break;
            }
        }

        $this->parentObj = ilObjectFactory::getInstanceByRefId($parent['ref_id']);
        switch( true )
        {
            case 'crs' === $parent['type'] && !$this->course:
                $this->course = $this->parentObj;
                break;
            case 'grp' === $parent['type'] && !$this->group:
                $this->group = $this->parentObj;
                break;
            case 'cat' === $parent['type'] && !$this->category:
                $this->category = $this->parentObj;
                break;
        }

        //$appAss = ilCalendarCategoryAssignments::_getAssignedAppointments([$this->parentObj->getId()]);
        //var_dump($appAss); exit;

        // check for ilObjSession
        if( false !== array_search($parent['type'], ['crs', 'grp']) ) {
            $events = ilEventItems::getEventsForItemOrderedByStartingTime($this->object->getRefId());
            if( (bool)sizeof($events) ) {
                $now = date('U');
                //var_dump($now);
                foreach( $events as $eventId => $eventStart ) {
                    if( !$this->ilObjSession ) {
                        /** @var ilObjSession $tmpSessObj */
                        $tmpSessObj = ilObjectFactory::getInstanceByObjId($eventId);

                        $dTplId = ilDidacticTemplateObjSettings::lookupTemplateId($tmpSessObj->getRefId());
                        //echo $dTplId; exit;
                        //if( (int)$dTplId === 2 )
                        if( !(bool)$dTplId && $now >= $eventStart ) {
                            //var_dump( ilSessionAppointment::_lookupAppointment($eventId)['fullday'] ); exit;
                            $event = ilSessionAppointment::_lookupAppointment($eventId);
                            $end = (bool)$event['fullday']
                                ? $eventStart + 60 * 60 *24
                                : $event['end'];
                            if( $now < $end ) {
                                $this->ilObjSession = $tmpSessObj; // ilObjectFactory::getInstanceByObjId($eventId);
                                //var_dump( $this->ilObjSession->getMembersObject()->getEventParticipants()->getUserId() ); exit;
                                //var_dump( $this->ilObjSession->isUserRegistered($this->dic->user()->getId()) ); exit;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $value
     * @return string|null
     */
    public function getPluginIniSet(string $value = 'max_concurrent_users'): ?string
    {
        return isset($this->pluginIniSet[$value]) ? $this->pluginIniSet[$value] : null;
    }


    /**
     */
    private function setPluginIniSet(): void
    {
        /*
        // Plugin wide ini settings (plugin.ini)
        #$this->parseIniFile( self::INI_FILENAME );
        ilApiMultiVC::parseIniFile(self::INI_FILENAME, $this->pluginIniSet);

        // Host specific ini settings (lms.example.com.ini)
        #$this->parseIniFile( $this->dic->http()->request()->getUri() );
        ilApiMultiVC::parseIniFile($this->dic->http()->request()->getUri(), $this->pluginIniSet);
        */
    }

    /**
     * @return int|null
     */
    public function getRelId(): ?int
    {
        return $this->relId;
    }

    /**
     * @param int|null $relId
     */
    public function setRelId(?int $relId): void
    {
        $this->relId = $relId;
    }

    /**
     * @return string|null
     */
    public function getStart(): ?string
    {
        return $this->start;
    }

    /**
     * @param string|null $start
     */
    public function setStart(?string $start): void
    {
        $this->start = $start;
    }

    /**
     * @return string|null
     */
    public function getEnd(): ?string
    {
        return $this->end;
    }

    /**
     * @param string|null $end
     */
    public function setEnd(?string $end): void
    {
        $this->end = $end;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getWebLink(): ?string
    {
        return $this->webLink;
    }

    /**
     * @param string|null $webLink
     */
    public function setWebLink(?string $webLink): void
    {
        $this->webLink = $webLink;
    }




}

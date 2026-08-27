<?php

class ilApiEdudip implements ilApiInterface
{
    public const INI_FILENAME = 'plugin';
    public const PLUGIN_PATH = './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';
    public const PLAYBACKURL_SPLIT = '2.0/playback.html?meetingId=';

//    public const API_URL = 'https://api.edudip-next.com/api/';
    public const API_URL = 'https://api.v2.edudip.com/api/v1/';

    public const ENDPOINT_WEBINAR = 'webinars';

    private ?ilObjMultiVc $object;

    private ?ilObjMultiVcGUI $objGui = null;

    private ilMultiVcConfig $settings;

    private string $meetingId = '0';

    private bool $moderatedMeeting;

    private string $userRole;

    private bool $meetingStartable = true;

    private bool $meetingRecordable = false;

    private string $displayName;

    private array $pluginIniSet = [];

    /** @var bool|ilObject $parentObj */
    private $parentObj;

    /** @var bool|ilObjCourse $course */
    private $course;

    /** @var bool|ilObjGroup $group */
    private $group;

    /** @var bool|ilObjCategory $category */
    private $category;

    private ?ilObjSession $ilObjSession = null;

    private ILIAS\DI\Container $dic;

    private ?int $relId = null;

    private ?string $start = null;

    private ?string $end = null;

    private ?string $email = null;

    private ?string $webLink = null;

    private static ?ilApiEdudip $instance = null;



    /**
     * ilApiEdudip constructor.
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct(ilObjMultiVcGUI $a_parent)
    {
        global $DIC;
        $this->dic = $DIC;


        #$a_parent = $a_parent ?? new ilObjectGUIFactory();

        $this->objGui = $a_parent;
        $this->object = $a_parent->object;
        $this->settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        $this->pluginIniSet = ilApiMultiVC::setPluginIniSet($this->settings);
        $this->moderatedMeeting = $this->object->get_moderated();
        $this->setUserRole();
    }



    public function sessionCreate(array $param = [], string $type = 'webinar'): bool|string
    {
        $s_response = $this->restfulApiCall('users/self', 'get');
        $response = json_decode($s_response, true);
        $ownerID = (int) $response['id'];
//        die(var_dump($response['id']));

        $dates = [];
        $utcStart = new ilDateTime($param['dateStart'], IL_CAL_DATETIME, $this->dic->user()->getTimeZone());
        $dateStart = $utcStart->get(IL_CAL_ISO_8601, 'Y-m-dTH:i:s', $this->dic->user()->getTimeZone());
        $dates[] = [
            'date' => $param['dateStart'],
            'duration' => $param['duration']
        ];
        $initialWebinarDates = [];
        $initialWebinarDates[] = [
            'date' => $dateStart,//$param['dateStart'],
            'durationMinutes' => $param['duration'],
            'title' => $param['title']
        ];

        $param = [
            'title' => $param['title'],
            'maxParticipants' => $param['max_participants'], # 1 -
            'language' => 'de',
            'ownerId' => $ownerID,
            'recording' => 0, # 1
//            'WebinarRegistrationType' => 'date', # series
            'access' => 'all', # invitation all
//            'dates' => json_encode($dates),
            'initialWebinarDates' => $initialWebinarDates
        ];
//        die(var_dump($param));
//        die(var_dump($this->restfulApiCall(self::ENDPOINT_WEBINAR, 'post', $param)));
        return $this->restfulApiCall(self::ENDPOINT_WEBINAR, 'post', $param);
    }

    public function sessionDelete(int $sessId): bool
    {
        $response = $this->restfulApiCall(self::ENDPOINT_WEBINAR . '/' . $sessId, 'delete');
        $json = json_decode($response, 1);
        //404 = webinar not found - may be deleted in edudip itself
        if (isset($json['http_code']) && ($json['http_code'] == 204 || $json['http_code'] == 404)) {
            return true;
        }
        return false;
    }

    public function sessionGet(?int $sessId = null): bool|string
    {
        if(null === $sessId) {
            return false;
        }
        return $this->restfulApiCall(self::ENDPOINT_WEBINAR . '/' . $sessId, 'get');
    }

    public function sessionList(): string|bool
    {
        return $this->restfulApiCall(self::ENDPOINT_WEBINAR .'?limit=99&sort=desc&startDate=2021-01-01', 'GET');
    }

    public function sessionDates(string $id): string|bool
    {
        return $this->restfulApiCall(self::ENDPOINT_WEBINAR . '/' . $id . '/webinarDates', 'GET');
    }

    public function sessionParticipantAdd(int $sessId, string $sessDate, string $firstname, string $lastname, ?string $email = null): bool|string
    {
        $endPoint = self::ENDPOINT_WEBINAR . '/' . $sessId . '/register';
        $param = [
            'email' => $email ?? date('YmdHis') . uniqid() . '@example.com',
            'firstname' => $firstname,
            'lastname' => $lastname,
            'timezone' => $this->dic->user()->getTimeZone(),
            'webinar_date' => $sessDate
        ];
        return $this->restfulApiCall($endPoint, 'post', $param);
    }

    public function sessionModeratorAdd(int $sessId, string $firstname, string $lastname, string $email): array
    {
        $endPoint = self::ENDPOINT_WEBINAR . '/' . $sessId . '/moderators';
        $s_result = $this->restfulApiCall($endPoint, 'get');
        $result = json_decode($s_result, true);
        $moderators = $result['moderators'];
        for ($i = 0; $i < count($moderators); $i++) {
            if($moderators[$i]['email'] === $email) {
                return ['id' => $moderators[$i]['id'], 'webLink' => $moderators[$i]['roomAuthLink']];
            }
        }

        $endPoint = self::ENDPOINT_WEBINAR . '/' . $sessId . '/moderators';
        $param = [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'timezone' => $this->dic->user()->getTimeZone()
        ];
        $s_result = $this->restfulApiCall($endPoint, 'post', $param);
        $result = json_decode($s_result, true);
        if (isset($result['error'])) {
            return $result;
        } else {
            return ['id' => $result['id'], 'webLink' => $result['roomAuthLink']];
        }
    }


    public function restfulApiCall(?string $endpoint = null, string $method = 'GET', array $param = [], string $contentType = 'application/json;charset=UTF-8'): bool|string
    {
        $authUser = $this->object->getAuthUser();
        $accessToken = $this->object->getAccessToken();
        if(is_null($authUser) || is_null($accessToken)) {
            return false;
        }

        $url = self::API_URL . $endpoint ?? self::ENDPOINT_WEBINAR;
        #die ($url);
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
            $curl->init(false);
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
            #$curl->setOpt(CURLOPT_CONNECTTIMEOUT, $timeout);
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
            $curl->setOpt(CURLOPT_MAXREDIRS, $maxRedirects);
            $curl->setOpt(CURLOPT_HTTPHEADER, $header);
            $curl->setOpt(CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if($isPost) {
                $curl->setOpt(CURLOPT_POST, 1);
                $curl->setOpt(CURLOPT_POSTFIELDS, http_build_query($param));
            }
            $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
            $response = $curl->exec();
            #echo '<pre>'; var_dump($response); exit;
            $code = (int) $curl->getInfo(CURLINFO_HTTP_CODE);
            $json = json_decode($response, true);
            $json['http_code'] = $code;
            $json['called_param'] = $param;
            if(isset($json['error'])) {//&& substr($json['error'], -1) !== '.') {
//                die(var_dump($json['error']));
                //$json['error'] .= '.';
            }
            $json['called_endpoint'] = $endpoint;
            $json['called_method'] = $method;
            #echo '<pre>'.$url; var_dump($json); exit;
        } catch (ilCurlConnectionException $e) {
            $json = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            if(strlen($json['error']) > 0) {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . '<br />' . $json['error'], true);
                $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
            }
        }

        return json_encode($json);

    }

    public function getMeetingId(): string
    {
        return $this->meetingId;
    }

    public function isModeratedMeeting(): bool
    {
        return $this->moderatedMeeting;
    }

    public function isUserModerator(): bool
    {
        return $this->userRole === 'moderator';
    }

    public function isUserAdmin(): bool
    {
        global $DIC;
        $userLiaRoles = $DIC->rbac()->review()->assignedRoles($DIC->user()->getId());
        if(null !== $this->course) {
            $found = in_array($this->course->getDefaultAdminRole(), $userLiaRoles);
        } else {
            $found = $DIC->access()->checkAccessOfUser($DIC->user()->getId(), 'write', 'showContent', $this->object->getRefId());
        }

        //var_dump($userLiaRoles); exit;

        return false !== $found;
    }

    public function getUserRole(): string
    {
        return $this->userRole;
    }

    public function isMeetingStartable(): bool
    {
        return $this->meetingStartable;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @throws Exception
     */
    public function isMeetingRunning(): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function isModeratorPresent(): bool
    {
        return true;

    }


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
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    private function isInCourseOrGroup(): bool
    {
        if(!$this->parentObj) {
            $this->setParentObj();
        }

        if(!$this->category) {
            return true;
        }

        return false;
    }

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    private function isAdminOrTutor(): bool
    {
        global $DIC;

        if($this->isInCourseOrGroup()) {
            $userLiaRoles = $DIC->rbac()->review()->assignedRoles($DIC->user()->getId());
            if(!!$this->course) {
                $found = array_search($this->course->getDefaultAdminRole(), $userLiaRoles);
                $found = false !== $found ? true : array_search($this->course->getDefaultTutorRole(), $userLiaRoles);
                return false !== $found;
            }
            if(!!$this->group) {
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
        global $DIC;

        switch (true) {
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
        global $DIC;
        $this->displayName = $DIC->user()->firstname . ' ' . $DIC->user()->lastname;
    }

    public function getParentObj(): bool|ilObject
    {
        return $this->parentObj;
    }

    public function getGroup(): bool|ilObject
    {
        return $this->group;
    }

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    private function setParentObj(): void
    {
        global $DIC;

        $path = array_reverse($DIC->repositoryTree()->getPathFull($this->object->getRefId()));
        $keys = array_keys($path);
        #$parent = $path[$keys[1]];
        foreach($path as $key => $node) {
            if(false !== array_search($node['type'], ['crs', 'grp', 'cat'])) {
                $parent = $node;
                break;
            }
        }

        $this->parentObj = ilObjectFactory::getInstanceByRefId($parent['ref_id']);
        switch(true) {
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
        if(false !== array_search($parent['type'], ['crs', 'grp'])) {
            $events = ilEventItems::getEventsForItemOrderedByStartingTime($this->object->getRefId());
            if((bool) sizeof($events)) {
                $now = date('U');
                //var_dump($now);
                foreach($events as $eventId => $eventStart) {
                    if(!$this->ilObjSession) {
                        /** @var ilObjSession $tmpSessObj */
                        $tmpSessObj = ilObjectFactory::getInstanceByObjId($eventId);

                        $dTplId = ilDidacticTemplateObjSettings::lookupTemplateId($tmpSessObj->getRefId());
                        //echo $dTplId; exit;
                        //if( (int)$dTplId === 2 )
                        if(!(bool) $dTplId && $now >= $eventStart) {
                            //var_dump( ilSessionAppointment::_lookupAppointment($eventId)['fullday'] ); exit;
                            $event = ilSessionAppointment::_lookupAppointment($eventId);
                            $end = (bool) $event['fullday']
                                ? $eventStart + 60 * 60 * 24
                                : $event['end'];
                            if($now < $end) {
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

    public function getPluginIniSet(string $value = 'max_concurrent_users'): ?string
    {
        return isset($this->pluginIniSet[$value]) ? $this->pluginIniSet[$value] : null;
    }


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

    public function getRelId(): ?int
    {
        return $this->relId;
    }

    public function setRelId(?int $relId): void
    {
        $this->relId = $relId;
    }

    public function getStart(): ?string
    {
        return $this->start;
    }

    public function setStart(?string $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?string
    {
        return $this->end;
    }

    public function setEnd(?string $end): void
    {
        $this->end = $end;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getWebLink(): ?string
    {
        return $this->webLink;
    }

    public function setWebLink(?string $webLink): void
    {
        $this->webLink = $webLink;
    }

}

<?php

class ilApiWebex implements ilApiInterface
{
    public const INI_FILENAME = 'plugin';
    public const PLUGIN_PATH = './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';
    public const PLAYBACKURL_SPLIT = '2.0/playback.html?meetingId=';

    public const API_URL = 'https://webexapis.com/v1/';

    public const ENDPOINT_MEETINGS = 'meetings';

    public const ENDPOINT_MEETING_INVITEES = 'meetingInvitees';

    public const ENDPOINT_PARTICIPANTS = 'meetingParticipants/';

    public const ENDPOINT_ACCESS_TOKEN = 'access_token';

    private ilObjMultiVc|ilObject|null $object;

    private ?ilObjMultiVcGUI $objGui = null;

    private ilMultiVcConfig $settings;

    private string $meetingId = "0";

    private bool $moderatedMeeting;

    private string $userRole;

    private bool $meetingStartable;

    private bool $meetingRunning = true;

    private string $rolePwd;

    private string $displayName;

    private string $userAvatar = '';

    private array $pluginIniSet = [];

    private object $concurrent;

    private bool|ilObject $parentObj = false;

    private bool|ilObjCourse $course = false;

    private bool|ilObjGroup $group = false;

    private bool|ilObjCategory $category = false;

    private ?ilObjSession $ilObjSession = null;

    private ILIAS\DI\Container $dic;
    private ?int $relId = null;

    private ?string $start = null;

    private ?string $end = null;

    private ?string $email = null;

    private ?string $webLink = null;
    private bool $redirectOnError = true;

    private static ?ilApiWebex $instance = null;



    /**
     * ilApiBBB constructor.
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct(\ilObjMultiVcGUI $a_parent)
    {
        global $DIC;
        $this->dic = $DIC;

        $this->objGui = $a_parent;
        $this->object = $a_parent->object;
        $this->settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        $this->pluginIniSet = ilApiMultiVC::setPluginIniSet($this->settings);
        $this->moderatedMeeting = $this->object->get_moderated();
        $this->setUserRole();
        $this->setMeetingStartable();
    }


    public function refreshAccessToken(bool $adminScope = false): bool|string
    {
        $param = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->settings->getSvrUsername(),
            'client_secret' => $this->settings->getSvrSalt(),
            'refresh_token' => $adminScope ? $this->settings->getRefreshToken() : $this->object->getRefreshToken()
        ];
        return $this->restfulApiCall(self::ENDPOINT_ACCESS_TOKEN, 'post', $param);
    }

    /**
     * @throws ilCurlConnectionException
     * @throws Exception
     */
    public function sessionCreate(array $param = [], string $type = 'meeting'): string|bool
    {
        $format = "Y-m-d H:i:s";
        $timezone = $this->dic->user()->getTimeZone();
        $dateTimeZone = new DateTimeZone($timezone);
        $now = new DateTime('now', $dateTimeZone);
        $start = $now->add(date_interval_create_from_date_string('2 minutes'));
        $end = $now->add(date_interval_create_from_date_string('15 minutes'));

        $param = array_replace([
            'title' => $this->object->getTitle(),
            'start' => $start->format($format),
            'end' => $end->format($format),
            'timezone' => $timezone,
            'enabledAutoRecordMeeting' => false,
            'allowAnyUserToBeCoHost' => false
        ], $param);

        #var_dump($param); exit;
        return $this->restfulApiCall(self::ENDPOINT_MEETINGS, 'post', $param);
    }

    public function sessionDelete(string $sessId): bool
    {
        $response = $this->restfulApiCall(self::ENDPOINT_MEETINGS . '/' . $sessId, 'delete');
        $json = json_decode($response, 1);
        return isset($json['success']) && $json['success'] === true;
    }

    public function sessionGet(?string $meetingId = null, string $hostEmail = ''): bool|string
    {
        if(null === $meetingId) {
            return false;
        }
        return $this->restfulApiCall(self::ENDPOINT_MEETINGS . '/' . $meetingId, 'get', ['hostEmail' => $hostEmail, 'current' => false]);
    }

    /**
     * @throws ilCurlConnectionException
     */
    public function sessionList(): string|bool
    {
        return $this->restfulApiCall(self::ENDPOINT_MEETINGS);
    }

    public function sessionParticipantsList(string $meetingId, string $hostEmail = ''): bool|string
    {
        $this->redirectOnError = false;
        return $this->restfulApiCall(self::ENDPOINT_PARTICIPANTS, 'get', ['meetingId' => $meetingId, 'hostEmail' => $hostEmail]);
    }

    public function sessionParticipantAdd(string $meetingId, string $firstName, string $lastName, string $email, bool $isCoHost = false, ?string $hostEmail = null): bool|string
    {
        $displayName = $firstName . ' ' . $lastName;
        $param = [
            'meetingId' => $meetingId,
            'displayName' => $displayName,
            'email' => $email,
            'coHost' => $isCoHost,
        ];

        if(!is_null($hostEmail)) {
            $param['hostEmail'] = $hostEmail;
        }

        return $this->restfulApiCall(self::ENDPOINT_MEETING_INVITEES, 'post', $param);
    }

    public function sessionModeratorAdd(string $meetingId, string $firstName, string $lastName, string $email, ?string $hostEmail = null): bool|string
    {
        $displayName = $firstName . ' ' . $lastName;
        $param = [
            'meetingId' => $meetingId,
            'displayName' => $displayName,
            'email' => $email,
            'coHost' => true,
        ];

        if(!is_null($hostEmail)) {
            $param['hostEmail'] = $hostEmail;
        }

        return $this->restfulApiCall(self::ENDPOINT_MEETING_INVITEES, 'post', $param);
    }

    public function restfulApiCall(?string $endpoint = null, string $method = 'GET', array $param = [], string $contentType = 'application/json;charset=UTF-8'): bool|string
    {
        $isAdminScope = 'admin' === $this->settings->getAuthMethod();
        $authUser = $this->object->getAuthUser();
        if($isAdminScope) {
            $accessToken = $this->settings->getAccessToken();
        } else {
            $accessToken = $this->object->getAccessToken();
        }
        //$accessToken = $this->object->getAccessToken();
        $param['hostEmail'] = $this->object->getAuthUser();
        if(is_null($authUser) || is_null($accessToken)) {
            return false;
        }

        $url = self::API_URL . $endpoint ?? self::ENDPOINT_MEETINGS;

        $isPost = false !== array_search(strtoupper($method), ['POST', 'PUT']);

        $timeout = 60;
        $maxRedirects = 10;

        $header = [
            'accept: application/json, text/plain, */*',
            #'accept-encoding: gzip, deflate, br',
            #'accept-language: en,de;q=0.9,de-DE;q=0.8,en-GB;q=0.7,en-US;q=0.6',
            #'cache-control: no-cache',
            'Authorization: Bearer ' . $accessToken,
            'timezone: Europe/Berlin'
        ];

        if($isPost) {
            $param = json_encode($param);
            $header = array_merge($header, [
                'content-length: ' . mb_strlen($param),
                'content-type: ' . $contentType,
            ]);
        } elseif(!empty($param)) {
            if(is_array($param)) {
                $query = http_build_query($param);
                $url .= '?' . $query;
            }
        }

        try {
            $curl = new ilCurlConnection($url);
            #$curl->init(false);
            defined(ILIAS_VERSION) && 0 === strpos(ILIAS_VERSION, 5) ? $curl->init() : $curl->init(false);
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
            #$curl->setOpt(CURLOPT_CONNECTTIMEOUT, $timeout);
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
            $curl->setOpt(CURLOPT_MAXREDIRS, $maxRedirects);
            $curl->setOpt(CURLOPT_HTTPHEADER, $header);
            $curl->setOpt(CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if($isPost) {
                $curl->setOpt(CURLOPT_POST, 1);
                $curl->setOpt(CURLOPT_POSTFIELDS, $param);
            }
            $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
            //Add Proxy
            if (\ilProxySettings::_getInstance()->isActive()) {
                $proxyHost = \ilProxySettings::_getInstance()->getHost();
                $proxyPort = \ilProxySettings::_getInstance()->getPort();
                $proxyURL = $proxyHost . ":" . $proxyPort;
                $curl->setopt(CURLOPT_PROXY, $proxyURL);
            }
            $response = $curl->exec();

            $code = (int) $curl->getInfo(CURLINFO_HTTP_CODE);
            $json = json_decode($response, true);
            $errMsg = '';
            if(isset($json["errors"])) {
                $errMsg = $json["message"];
                $errMsg .= strtolower(trim($errMsg)) === strtolower(trim($json["errors"][0]['description']))
                    ? ''
                    : '<br />' . $json["errors"][0]['description'];
            }
            $json['http_code'] = $code;
            $json['error'] = $errMsg;
            $json['success'] = !(bool) strlen($json['error']);
            $json['called_param'] = $param;
            $json['called_url_endpoint'] = $url;
            $json['called_method'] = $method;
        } catch (ilCurlConnectionException $e) {
            $json = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        if($this->redirectOnError && (bool) strlen($json['error'])) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . '<br />' . $json['error'], true);
            if((int) $this->dic->user()->getId() === (int) $this->object->getOwner()) {
                $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
            } else {
                $this->dic->ctrl()->redirect($this->objGui, 'showContent');
            }
        } else {
            // if false set it true. Next requests can set it to false again
            $this->redirectOnError = true;
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
            $found = array_search($this->course->getDefaultAdminRole(), $userLiaRoles);
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

    public function isMeetingRunning(?string $meetingId = null): bool
    {
        if(!is_null($meetingId)) {
            if($response = $this->sessionParticipantsList($meetingId)) {
                $response = json_decode($response, 1);
                #echo '<pre>'; var_dump($response); exit;
                if(!empty($response['items'])) {
                    #echo '<pre>'; var_dump($response); exit;
                    foreach($response['items'] as $participant) {
                        $isModerator =
                        $hasJoined = false;
                        $isModerator = $participant['host'] || $participant['coHost'];
                        $hasJoined = $isModerator && $participant['state'] === 'joined';
                        if($isModerator && $hasJoined) {
                            #echo '<pre>'; var_dump($response); exit;
                            return true;
                        }
                    }
                }
            }
            #echo '<pre>'; var_dump($response); exit;
            return false;

        }
        return $this->meetingRunning;
    }

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
    public function isAdminOrTutor(): bool
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

    private function setRolePwd(): void
    {
        $this->rolePwd = $this->isUserModerator() ? $this->object->getModeratorPwd() : $this->object->getAttendeePwd();
    }


    private function setMeetingStartable(): void
    {
        if(!!$this->ilObjSession) {
            $dump = [$this->dic->user()->getId(), $this->ilObjSession->getId(), ilEventParticipants::_isRegistered($this->dic->user()->getId(), $this->ilObjSession->getId()) ];
        }

        #var_dump($this->getMaxAvailableJoins());
        #exit;

        switch (true) {
            case $this->isUserModerator() || $this->isUserAdmin():
            case !$this->isUserModerator() && $this->isMeetingRunning() && $this->isModeratorPresent() /* && $this->isValidAppointmentUser() */:
                $this->meetingStartable = true;
                break;

            default:
                $this->meetingStartable = false;
        }
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

        $parent = [];
        $path = array_reverse($DIC->repositoryTree()->getPathFull($this->object->getRefId()));
        $keys = array_keys($path);
        #$parent = $path[$keys[1]];
        foreach($path as $key => $node) {
            if(false !== array_search($node['type'], ['crs', 'grp', 'cat'])) {
                $parent = $node;
                break;
            }
        }

        if(!$parent['ref_id']) {
            #$this->dic->ui()->mainTemplate()->setMessage('error', 'No RefId given', true);
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', 'No RefId given', true);
            $this->dic->ctrl()->redirectToURL(ILIAS_HTTP_PATH);
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

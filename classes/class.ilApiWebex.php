<?php

use ILIAS\DI\Container;
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilApiInterface.php");

class ilApiWebex implements ilApiInterface
{
    CONST INI_FILENAME = 'plugin';
    CONST PLUGIN_PATH = './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';
    CONST PLAYBACKURL_SPLIT = '2.0/playback.html?meetingId=';

    CONST API_URL = 'https://webexapis.com/v1/';

    CONST ENDPOINT_MEETINGS = 'meetings';

    const ENDPOINT_MEETING_INVITEES = 'meetingInvitees';

    CONST ENDPOINT_PARTICIPANTS = 'meetingParticipants/';

    CONST ENDPOINT_ACCESS_TOKEN = 'access_token';

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
    private $meetingStartable;

    /** @var bool $meetingRunning */
    private $meetingRunning = true;

    /** @var string $rolePwd */
    private $rolePwd;

    /** @var string $displayName */
    private $displayName;

    /** @var string $userAvatar */
    private $userAvatar = '';

    /** @var array $pluginIniSet */
    private $pluginIniSet = [];

    /** @var object $concurrent */
    private $concurrent;

    /** @var $meetingInfo */
    private $meetingInfo;

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

    /** @var bool $redirectOnError */
    private $redirectOnError = true;


    /** @var ilApiWebex|null $instance */
    static private $instance = null;



    /**
     * ilApiBBB constructor.
     * @param ilObjMultiVcGUI $a_parent
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct(\ilObjMultiVcGUI $a_parent)
    {
        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;

        $this->objGui = $a_parent;
        $this->object = $a_parent->object;
        $this->settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        $this->pluginIniSet = ilApiMultiVC::setPluginIniSet($this->settings);
        $this->moderatedMeeting = $this->object->get_moderated();
        $this->setUserRole();
        $this->setMeetingStartable();
    }


    /**
     * @param int $id
     * @return ilApiWebex
     * @throws ilObjectException
     * @throws ilObjectNotFoundException
     * @throws ilDatabaseException
     */
    static public function getInstanceByRefId(int $id) {
        $objF = new ilObjectGUIFactory();
        $gui = $objF->getInstanceByRefId($id);
        return self::$instance ?? self::$instance = new self($gui);
    }


    public function refreshAccessToken( bool $adminScope = false )
    {
        $param = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->settings->getSvrUsername(),
            'client_secret' => $this->settings->getSvrSalt(),
            'refresh_token' => $adminScope ? $this->settings->getRefreshToken() : $this->object->getRefreshToken()
        ];
        return $this->restfulApiCall(self::ENDPOINT_ACCESS_TOKEN, 'post', $param);
    }

    /**
     * @param array $param
     * @param string $type
     * @return bool|string
     * @throws ilCurlConnectionException
     * @throws Exception
     */
    public function sessionCreate(array $param = [], string $type = 'meeting'): string
    {
        $format = "Y-m-d H:i:s";
        $timezone = $this->dic->user()->getTimeZone();
        $dateTimeZone = new DateTimeZone($timezone);
        $now = new DateTime('now', $dateTimeZone);
        $start = $now->add( date_interval_create_from_date_string('2 minutes') );
        $end = $now->add( date_interval_create_from_date_string('15 minutes') );

        $param = array_replace([
            'title'     => $this->object->getTitle(),
            'start'     => $start->format($format),
            'end'       => $end->format($format),
            'timezone'  => $timezone,
            'enabledAutoRecordMeeting' => false,
            'allowAnyUserToBeCoHost' => false
        ], $param);

        #var_dump($param); exit;
        return $this->restfulApiCall(self::ENDPOINT_MEETINGS, 'post', $param);
    }

    /**
     * @param int $sessId
     * @return bool
     */
    public function sessionDelete( string $sessId ): bool
    {
        $response = $this->restfulApiCall(self::ENDPOINT_MEETINGS . '/' . $sessId, 'delete');
        $json = json_decode($response, 1);
        return isset($json['success']) && $json['success'] === true;
    }

    /**
     * @param string|null $meetingId
     * @param string $hostEmail
     * @return bool|string
     */
    public function sessionGet( ?string $meetingId = null, string $hostEmail = '' )
    {
        if( null === $meetingId ) {
            return false;
        }
        return $this->restfulApiCall(self::ENDPOINT_MEETINGS . '/' . $meetingId, 'get', ['hostEmail' => $hostEmail, 'current' => false]);
    }

    /**
     * @return string|bool
     * @throws ilCurlConnectionException
     */
    public function sessionList(): string
    {
        return $this->restfulApiCall(self::ENDPOINT_MEETINGS);
    }

    /**
     * @param string $meetingId
     * @param string $hostEmail
     * @return bool|string
     */
    public function sessionParticipantsList( string $meetingId, string $hostEmail = '' )
    {
        $this->redirectOnError = false;
        return $this->restfulApiCall(self::ENDPOINT_PARTICIPANTS, 'get', ['meetingId' => $meetingId, 'hostEmail' => $hostEmail]);
    }

    /**
     * @param string $meetingId
     * @param string $firstName
     * @param string $lastName
     * @param string|null $email
     * @param string|null $hostEmail
     * @param bool $isCoHost
     * @return bool|string
     */
    public function sessionParticipantAdd( string $meetingId, string $firstName, string $lastName, string $email, bool $isCoHost = false, ?string $hostEmail = null  ) {
        $displayName = $firstName . ' ' . $lastName;
        $param = [
            'meetingId'     => $meetingId,
            'displayName'   => $displayName,
            'email'         => $email,
            'coHost'        => $isCoHost,
        ];

        if( !is_null($hostEmail) ) {
            $param['hostEmail'] = $hostEmail;
        }

        return $this->restfulApiCall(self::ENDPOINT_MEETING_INVITEES, 'post', $param);
    }

    /**
     * @param string $meetingId
     * @param string $firstName
     * @param string $lastName
     * @param string|null $email
     * @param string|null $hostEmail
     * @return bool|string
     */
    public function sessionModeratorAdd( string $meetingId, string $firstName, string $lastName, string $email, ?string $hostEmail = null ) {
        $displayName = $firstName . ' ' . $lastName;
        $param = [
            'meetingId'     => $meetingId,
            'displayName'     => $displayName,
            'email'       => $email,
            'coHost'  => true,
        ];

        if( !is_null($hostEmail) ) {
            $param['hostEmail'] = $hostEmail;
        }

        return $this->restfulApiCall(self::ENDPOINT_MEETING_INVITEES, 'post', $param);
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
        $isAdminScope = 'admin' === $this->settings->getAuthMethod();
        $authUser = $this->object->getAuthUser();
        if( $isAdminScope ) {
            $accessToken = $this->settings->getAccessToken();
            $param['hostEmail'] = $this->object->getAuthUser();
        } else {
            $accessToken = $this->object->getAccessToken();
        }
        $param['hostEmail'] = $this->object->getAuthUser();

        if( is_null($authUser) || is_null($accessToken) ) {
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

        if( $isPost ) {
            $param = json_encode($param);
            $header = array_merge($header, [
                'content-length: ' . mb_strlen($param),
                'content-type: ' . $contentType,
            ]);
        } elseif( !empty($param) ) {
            if( is_array($param) ) {
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
            if( $isPost ) {
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

            $code = (int)$curl->getInfo(CURLINFO_HTTP_CODE);
            $json = json_decode($response, true);
            $errMsg = '';
            if( isset($json["errors"]) ) {
                $errMsg = $json["message"];
                $errMsg .= strtolower(trim($errMsg)) === strtolower(trim($json["errors"][0]['description']))
                    ? ''
                    : '<br />' . $json["errors"][0]['description'];
            }
            $json['http_code'] = $code;
            $json['error'] = $errMsg;
            $json['success'] = !(bool)strlen($json['error']);
            $json['called_param'] = $param;
            $json['called_url_endpoint'] = $url;
            $json['called_method'] = $method;
        } catch (ilCurlConnectionException $e) {
            $json = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        if( $this->redirectOnError && (bool)strlen($json['error']) ) {
            ilUtil::sendFailure($this->dic->language()->txt('error') . '<br />' . $json['error'], true);
            $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
        } else {
            // if false set it true. Next requests can set it to false again
            $this->redirectOnError = true;
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
        global $DIC; /** @var \DI\Container $DIC */
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
     * @param string|null $meetingId
     * @return bool
     */
    public function isMeetingRunning(?string $meetingId = null): bool
    {
        if( !is_null($meetingId) ) {
            if($response = $this->sessionParticipantsList($meetingId)) {
                $response = json_decode($response, 1);
                #echo '<pre>'; var_dump($response); exit;
                if( !empty($response['items']) ) {
                    #echo '<pre>'; var_dump($response); exit;
                    foreach( $response['items'] as $participant ) {
                        $isModerator =
                        $hasJoined = false;
                        $isModerator = $participant['host'] || $participant['coHost'];
                        $hasJoined = $isModerator && $participant['state'] === 'joined';
                        if( $isModerator && $hasJoined ) {
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

    /**
     * @return bool
     */
    public function isModeratorPresent(): bool
    {
        return true;
    }


    public function getMeetings(): GetMeetingsResponse {
        return $this->bbb->getMeetings();
    }

    /**
     * @return string
     */
    public function getUrlJoinMeeting(): string
    {
        global $DIC; /** @var Container $DIC */
        if(
            $this->isUserModerator() && !$this->isMeetingRunning() ||
            !$this->isUserModerator() && $this->isMeetingRunning() // ||
            //!$this->isUserModerator() && !!$this->ilObjSession && $this->isValidAppointmentUser()
        ) {
            $this->createMeeting();
        }
        $joinParams = new \BigBlueButton\Parameters\JoinMeetingParameters($this->meetingId, $this->displayName, $this->rolePwd);
        $joinParams->setJoinViaHtml5(true);
        $joinParams->setRedirect(true);
        //$joinParams->setAvatarURL($this->userAvatar);
        $joinParams->setUserId($DIC->user()->getId());
        $joinParams->setClientURL($DIC->http()->request()->getUri());
        return $this->bbb->getJoinMeetingURL($joinParams);
    }

    public function logMaxConcurrent() {
        $details = [
            'svrUrl' => $this->settings->getSvrPublicUrl(),
            'meetingId' => $this->getMeetingId(),
            'allParentMeetingsParticipantsCount' => $this->concurrent->allParentMeetingsParticipantsCount,
        ];
        //var_dump($this->concurrent); exit;
        $this->object->saveMaxConcurrent($this->concurrent->meetings, $this->concurrent->users, $details);
    }

    /**
     */
    private function setConcurrent(): void
    {
        $this->concurrent = (object)[
            'meetings'  => 0,
            'users'     => 0,
            'allParentMeetingsParticipantsCount' => []
        ];
        $all = 0;

        /** @var BigBlueButton\Core\Meeting[] $meetings */
        $meetings = (array)($this->bbb->getMeetings())->getMeetings();
        if( !!(bool)(sizeof($meetings)) ) {
            $checkId = $this->iliasDomain . ';' . CLIENT_ID;
            foreach ($meetings as $meeting) {
                //if( $meeting->getMeetingName() === $this->object->getTitle() ) {}

                if( substr($meeting->getMeetingId() , 0, strlen($checkId)) === $checkId ) {
                    $all += $meeting->getParticipantCount();
                    $this->concurrent->allParentMeetingsParticipantsCount[$meeting->getMeetingId()] = $meeting->getParticipantCount();
                    $this->concurrent->meetings += 1;
                    $this->concurrent->users += $meeting->getParticipantCount();
                } else {
                    $this->concurrent->allBreakoutRoomsParticipantsCount[$meeting->getMeetingId()] = $meeting->getParticipantCount();
                }
            } // EOF foreach ($meetings as $meeting)
            /*
            echo '<b>$all, $this->concurrent->users</b>';
            echo $all .', '. $this->concurrent->users;
            echo '<br><br><br><b>$this->concurrent->allParentMeetingsParticipantsCount</b>';
            var_dump($this->concurrent->allParentMeetingsParticipantsCount);
            echo '<br><br><br><b>$this->concurrent->allBreakoutRoomsParticipantsCount</b>';
            var_dump($this->concurrent->allBreakoutRoomsParticipantsCount);
            echo '<br><br><br><b>$this->getMeetingInfo()->getRawXml()->breakoutRooms</b>';
            var_dump($this->getMeetingInfo()->getRawXml()->breakoutRooms);
            echo '<br><br><br><b>$this->getMeetingInfo()->getRawXml()</b>';
            var_dump($this->getMeetingInfo()->getRawXml());
            //var_dump([$this->getMeetingInfo(), $this->bbb->getMeetings()->getMeetings()]);
            exit;
            */


        }
    }

    public function addConcurrent(): void {
        $this->concurrent->users += 1;
        $this->concurrent->allParentMeetingsParticipantsCount[$this->getMeetingId()] += 1;
        $this->addConcurrentMeeting();
    }

    private function addConcurrentMeeting(): void {
        $meetingParam = new \BigBlueButton\Parameters\GetMeetingInfoParameters($this->meetingId, $this->settings->getSvrSalt());
        $meetingInfo = $this->bbb->getMeetingInfo($meetingParam);
        $meeting = $meetingInfo->getMeeting();
        //var_dump($meeting->getStartTime()); exit;
        if( 0 === (int)$meeting->getStartTime() ) {
            $this->concurrent->meetings += 1;
        }
    }

    /**
     * Create bbb-meeting by server side request
     */
    private function createMeeting(): void {
        $this->bbb->createMeeting($this->createMeetingParam);
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
    public function isAdminOrTutor(): bool
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

    private function setRolePwd(): void
    {
        $this->rolePwd = $this->isUserModerator() ? $this->object->getModeratorPwd() : $this->object->getAttendeePwd();
    }

    /**
     * @return \BigBlueButton\Responses\GetMeetingInfoResponse
     */
    private function getMeetingInfo(): \BigBlueButton\Responses\GetMeetingInfoResponse
    {
        if( !($this->meetingInfo instanceof \BigBlueButton\Responses\GetMeetingInfoResponse) ) {
            $meetingParam = new \BigBlueButton\Parameters\GetMeetingInfoParameters($this->meetingId, $this->settings->getSvrSalt());
            $this->meetingInfo = $this->bbb->getMeetingInfo($meetingParam);
        }
        return $this->meetingInfo;
    }

    public function getMeetingIId()
    {
        return $this->getMeetingInfo()->getMeeting()->getInternalMeetingId();
    }


    private function setMeetingStartable(): void
    {
        if( !!$this->ilObjSession ) {
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

    /**
     * @param string $userAvatar
     */
    public function setUserAvatar(string $userAvatar): void
    {
        $this->userAvatar = $userAvatar;
    }

    public function getUserAvatar(): string
    {
        return $this->userAvatar;
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

        $parent = [];
        $path = array_reverse($DIC->repositoryTree()->getPathFull($this->object->getRefId()));
        $keys = array_keys($path);
        #$parent = $path[$keys[1]];
        foreach( $path as $key => $node ) {
            if( false !== array_search($node['type'], ['crs', 'grp', 'cat']) ) {
                $parent = $node;
                break;
            }
        }

        if( !$parent['ref_id'] ) {
            #$this->dic->ui()->mainTemplate()->setMessage('error', 'No RefId given', true);
            ilUtil::sendFailure('No RefId given', true);
            $this->dic->ctrl()->redirectToURL(ILIAS_HTTP_PATH);
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

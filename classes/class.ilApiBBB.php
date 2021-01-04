<?php

use BigBlueButton\Core\Record;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Responses\GetMeetingsResponse;
use BigBlueButton\Responses\GetRecordingsResponse;
use BigBlueButton\Parameters\DeleteRecordingsParameters;
use BigBlueButton\Util\UrlBuilder;
use ILIAS\DI\Container;

require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/bbb/vendor/autoload.php';
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilApiInterface.php");
//require_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

class ilApiBBB implements ilApiInterface
{
    const INI_FILENAME = 'plugin.ini';
    const PLUGIN_PATH = './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';
    const PLAYBACKURL_SPLIT = '2.0/playback.html?meetingId=';


    /** @var \BigBlueButton\BigBlueButton $bbb */
    private $bbb;

    /** @var ilObjMultiVc $object */
    private $object;

    /** @var ilMultiVcConfig $settings */
    private $settings;

    /** @var string $meetingId */
    private $meetingId = 0;

    /** @var string $iliasDomain */
    private $iliasDomain;

    /** @var int $specialId */
    private $specialId = 0;

    /** @var bool $moderatedMeeting */
    private $moderatedMeeting;

    /** @var string $userRole */
    private $userRole;

    /** @var bool $meetingStartable */
    private $meetingStartable;

    /** @var bool $meetingRunning */
    private $meetingRunning = false;

    /** @var bool $meetingRecordable */
    private $meetingRecordable = false;

    /** @var string $rolePwd */
    private $rolePwd;

    /** @var string $displayName */
    private $displayName;

    /** @var string $userAvatar */
    private $userAvatar = '';

    /** @var CreateMeetingParameters $createMeetingParam */
    private $createMeetingParam;

    /** @var array $pluginIniSet */
    private $pluginIniSet = [];

    /** @var object $concurrent */
    private $concurrent;

    /** @var BigBlueButton\Responses\GetMeetingInfoResponse $meetingInfo */
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

        $this->object = $a_parent->object;
        $this->settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        $this->setPluginIniSet();
        $this->bbb = new InitBBB($this->settings->getSvrSalt(), $this->settings->getSvrPrivateUrl());
        //$this->bbb = new \BigBlueButton\BigBlueButton();
        //$this->bbb->setEnv($this->settings->getSvrSalt(), $this->settings->getSvrPrivateUrl());
        $this->moderatedMeeting = $this->object->get_moderated();
        $this->setMeetingId();
        $this->setUserRole();
        $this->setRolePwd();
        $this->setConcurrent();
        $this->setMeetingStartable();
        $this->setDisplayName();
        $this->setMeetingRecordable((bool)$this->object->isRecordingAllowed());
        $this->setRecordingOnlyForModeratedMeeting();
        $this->setCreateMeetingParam();
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
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return bool
     */
    public function isMeetingRunning(): bool
    {
        try {
            //$meetingParams = new \BigBlueButton\Parameters\IsMeetingRunningParameters($this->object->getRefId());
            $meetingParams = new \BigBlueButton\Parameters\IsMeetingRunningParameters($this->meetingId);
            $response = $this->bbb->isMeetingRunning($meetingParams);
            $this->meetingRunning = $response->isRunning();
            //return $this->meetingRunning;
        } catch (Exception $e) {}
        //$this->meetingRunning = false;
        return $this->meetingRunning;
    }

    /**
     * @return bool
     */
    public function isModeratorPresent(): bool
    {
        return (bool)$this->getMeetingInfo()->getMeeting()->getModeratorCount();
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
    private function isAdminOrTutor(): bool
    {
        global $DIC; /** @var \DI\Container $DIC */

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
        global $DIC; /** @var \DI\Container $DIC */

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

        switch (true) {
            case $this->getMaxAvailableJoins() < 1:
                $this->meetingStartable = false;
                break;

            case $this->isUserModerator() || $this->isUserAdmin():
            case !$this->isUserModerator() && $this->isMeetingRunning() && $this->isModeratorPresent() && $this->isValidAppointmentUser():
                $this->meetingStartable = true;
                break;

            default:
                $this->meetingStartable = false;
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

        $path = array_reverse($DIC->repositoryTree()->getPathFull($this->object->getRefId()));
        $keys = array_keys($path);
        $parent = $path[$keys[1]];

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

    private function setRecordingOnlyForModeratedMeeting(): void {
        if( (bool)$this->object->isRecordingAllowed() &&
            $this->settings->isRecordOnlyForModeratedRoomsDefault() &&
            !$this->isModeratedMeeting()
        ) {
            $this->setMeetingRecordable(false);
        }
    }

    private function setCreateMeetingParam(): void {
        global $DIC; /** @var Container $DIC */
        $rqUri = $DIC->http()->request()->getUri();
        $joinBtnUrl = ILIAS_HTTP_PATH . '/' . substr($rqUri, strpos($rqUri, 'ilias.php')) . '&startBBB=10';

        $this->createMeetingParam = new CreateMeetingParameters($this->meetingId, $this->object->getTitle());
        $this->createMeetingParam->setModeratorPassword($this->object->getModeratorPwd())
            ->setAttendeePassword($this->object->getAttendeePwd())
            ->setMaxParticipants($this->settings->getMaxParticipants())
            ->setAllowStartStopRecording( $this->isMeetingRecordable() )
            ->setRecord( $this->isMeetingRecordable() )
            ->setAutoStartRecording(false)
            ->setWebcamsOnlyForModerator((bool)$this->object->isCamOnlyForModerator())
            ->setLogoutUrl($joinBtnUrl)
            ->setLockSettingsDisablePrivateChat(!(bool)$this->object->isPrivateChat())
        ;

        if( !is_null($value = $this->getPluginIniSet('mute_on_start')) ) {
            $this->createMeetingParam->setMuteOnStart((bool)$value);
        }

        if( (bool)(strlen($pdf = $this->settings->getAddPresentationUrl())) ) {
            $this->createMeetingParam->addPresentation($pdf);
        }

        if( $this->settings->issetAddWelcomeText() ) {
            $welcomeText = str_replace(
                [
					'{MEETING_TITLE}'
                ],
                [
					$this->object->getTitle()
                ],
                $this->dic->language()->txt('rep_robj_xmvc_welcome_text')
            );
            $this->createMeetingParam->setWelcomeMessage($welcomeText);
        }

        if( $this->settings->getDisableSip() ) {
            $this->createMeetingParam
                ->setVoiceBridge( 0 )
                ->setDialNumber( '613-555-1234' );
        }

    }

    /**
     *
     */
    private function setMeetingId(): void
    {
        global $ilIliasIniFile, $DIC; /** @var Container $DIC */
		
		$this->iliasDomain = $ilIliasIniFile->readVariable('server', 'http_path');
		$this->iliasDomain = preg_replace("/^(https:\/\/)|(http:\/\/)+/", "", $this->iliasDomain);

		$rawMeetingId = $this->iliasDomain . ';' . CLIENT_ID . ';' . $this->object->getId();
		
        if ( trim($this->settings->get_objIdsSpecial()) !== '') {
            $ArObjIdsSpecial = [];
            $rawIds = explode(",", $this->settings->get_objIdsSpecial());
            foreach ($rawIds as $id) {
                $id = trim($id);
                if (is_numeric($id)) {
                    array_push($ArObjIdsSpecial, $id);
                }
            }
            if (in_array($this->object->getId(), $ArObjIdsSpecial)) {
                $rawMeetingId .= 'r' . $this->object->getRefId();
            }
        }
        // $this->meetingId = md5($rawMeetingId);
        $this->meetingId = $rawMeetingId;
    }


    /**
     */
    public function getRecordings(): array
    {
        require_once "./Services/Calendar/classes/class.ilDateTime.php";

        $recParam = new \BigBlueButton\Parameters\GetRecordingsParameters();
        $recParam->setMeetingId($this->meetingId);

        //var_dump($this->bbb->getRecordings($recParam)->getRecords());exit;
        $recList = [];
        /** @var BigBlueButton\Core\Record $rec */
        foreach ( $this->bbb->getRecordings($recParam)->getRecords() AS $key => $rec ) {
            $bbbRecId = $rec->getRecordId();
            $ilStartTime = new ilDateTime(substr ($rec->getStartTime(),0,10), IL_CAL_UNIX);
            $ilEndTime = new ilDateTime(substr ($rec->getEndTime(),0,10), IL_CAL_UNIX);
            $recList[$bbbRecId]['startTime'] = ilDatePresentation::formatDate($ilStartTime);
            $recList[$bbbRecId]['endTime'] = ilDatePresentation::formatDate($ilEndTime); // $rec->getEndTime();
            $recList[$bbbRecId]['playback'] = $rec->getPlaybackUrl();
            $recList[$bbbRecId]['download'] = $this->getMP4DownStreamUrl($recList[$bbbRecId]['playback']);
            $recList[$bbbRecId]['meetingId'] = $rec->getMeetingId();
        }
        return $recList;
    }

    /**
     * @param string $recordUrl
     * @return string
     */
    private function getMP4DownStreamUrl(string $recordUrl): string {
        // https://github.com/createwebinar/bbb-download
        $part = explode(self::PLAYBACKURL_SPLIT, $recordUrl);
        $url = str_replace('playback', 'download', $part[0]) . $part[1] .  '/' . $part[1] . '.mp4';
        return $this->mp4Exists($url) ? $url : '';
    }

    /**
     * Check if a mp4 file exists
     * @param string $url
     * @return bool
     */
    private function mp4Exists( string $url ): bool
    {
        $http_code = 0;
        $ch = curl_init($url);
        ob_start();
        curl_exec($ch);
        ob_end_clean();
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        curl_close($ch);
        return (int)$http_code === 200;
    }

    /**
     * @param string $recId
     * @return \BigBlueButton\Responses\DeleteRecordingsResponse
     */
    public function deleteRecord( string $recId ): \BigBlueButton\Responses\DeleteRecordingsResponse
    {
        $delRecParam = new \BigBlueButton\Parameters\DeleteRecordingsParameters($recId);
        return $this->bbb->deleteRecordings($delRecParam);
    }

    public function getMeetingsUrl() {
        return $this->bbb->getMeetingsUrl();
    }

    /**
     * @param string $value
     * @return string|null
     */
    public function getPluginIniSet(string $value = 'max_concurrent_users'): ?string
    {
        return isset($this->pluginIniSet[$value]) ? $this->pluginIniSet[$value] : null;
    }

    public function getAvailableConcurrentUsers(): int {
        if( null !== ($maxUsers = $this->getPluginIniSet('max_concurrent_users')) ) {
            return (($available = $maxUsers - $this->concurrent->users) > 0) ? $available : 0;
        }
        return 0;
    }

    public function getAvailableParticipants() {
        if( (int)$this->settings->getMaxParticipants() > 0 ) {
            $meetingParam = new \BigBlueButton\Parameters\GetMeetingInfoParameters($this->meetingId, $this->settings->getSvrSalt());
            $meetingInfo = $this->bbb->getMeetingInfo($meetingParam);
            //var_dump($meetingInfo->getRawXml()); exit;
            $meeting = $meetingInfo->getMeeting();
            return (($available = (int)$this->settings->getMaxParticipants() - $meeting->getParticipantCount()) > 0) ? $available : 0;
        }
        return 0;
    }

    public function getMaxAvailableJoins(): int  {
        $hasValueMaxConcurrentUsers = null !== $this->getPluginIniSet();
        $hasValueMaxUsersPerMeeting = (int)$this->settings->getMaxParticipants() > 0;
        switch(true) {
            case $hasValueMaxConcurrentUsers && $hasValueMaxUsersPerMeeting && $this->getAvailableConcurrentUsers() >= $this->getAvailableParticipants():
            case !$hasValueMaxConcurrentUsers && $hasValueMaxUsersPerMeeting:
                return $this->getAvailableParticipants();
            case $hasValueMaxConcurrentUsers && $hasValueMaxUsersPerMeeting && $this->getAvailableConcurrentUsers() < (int)$this->getAvailableParticipants():
            case $hasValueMaxConcurrentUsers && !$hasValueMaxUsersPerMeeting:
                return $this->getAvailableConcurrentUsers();
            default:
                return 1000000000;
        }
    }


    /**
     */
    private function setPluginIniSet(): void
    {
        global $DIC; /** @var \DI\Container $DIC */
        $iniPathFile = self::PLUGIN_PATH . '/' . self::INI_FILENAME;
        if( file_exists($iniPathFile) ) {
            $iniContent = file_get_contents($iniPathFile);
            foreach( explode("\n", $iniContent) as $line ) {
                if( substr_count($line, '=') ) {
                    list($key, $value) = explode('=', $line);
                    $this->pluginIniSet[trim($key)] = trim($value);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isMeetingRecordable(): bool
    {
        return $this->meetingRecordable;
    }

    /**
     * @param bool $meetingRecordable
     */
    public function setMeetingRecordable(bool $meetingRecordable): void
    {
        $this->meetingRecordable = $meetingRecordable;
    }

    /**
     * @param string $displayName
     * @return string
     */
    public function getInviteUserUrl(string $displayName = 'Gast'): string
    {
        $guestLinkUrlPart = [
            ILIAS_HTTP_PATH,
            substr(dirname(__FILE__), strpos(dirname(__FILE__), 'Customizing'), -8),
            'index.php?'
        ];
        $guestLinkQueryParam = [
            'ref_id=' . $this->object->getRefId(),
            'client=' . CLIENT_ID
        ];

        if( (bool)$this->getPluginIniSet('guest_link_shortener') ) {
            return $guestLinkUrlPart[0] . '/' .
                'm/' .
                CLIENT_ID . '/' .
                $this->object->getRefId();
        }

        return implode('/', $guestLinkUrlPart) . implode('&', $guestLinkQueryParam);
    }






}

class InitBBB extends \BigBlueButton\BigBlueButton
{
    public function __construct($securitySecret, $baseUrl)
    {
        parent::__construct();
        $this->setSecuritySecret($securitySecret);
        $this->setBbbServerBaseUrl($baseUrl);
        $this->setUrlBuilder();

    }

    /**
     * @param array|false|string $bbbServerBaseUrl
     */
    private function setBbbServerBaseUrl($bbbServerBaseUrl): void
    {
        $this->bbbServerBaseUrl = $bbbServerBaseUrl;
    }

    /**
     * @param array|false|string $securitySecret
     */
    private function setSecuritySecret($securitySecret): void
    {
        $this->securitySecret = $securitySecret;
    }

    /**
     * Initialize bbb/urlBuilder
     */
    private function setUrlBuilder(): void
    {
        $this->urlBuilder       = new UrlBuilder($this->securitySecret, $this->bbbServerBaseUrl);
    }

    /**
     * @param string $voiceBridge
     *
     * @return InitBBB
     */
    public function setVoiceBridge(string $voiceBridge)
    {
        $this->voiceBridge = $voiceBridge;

        return $this;
    }
}


<?php

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;

class ilApiTeams implements ilApiInterface
{
    /** @var ilObjMultiVc|ilObject|null $object */
    private $object;

    /** @var ilObjMultiVcGUI|null $objGui */
    private ?ilObjMultiVcGUI $objGui = null;

    private ilMultiVcConfig $settings;

    private string $meetingId = "0";

    private bool $moderatedMeeting;

    private string $userRole;

    private bool $meetingStartable;

    private bool $meetingRunning = true;

    private string $displayName;

    private object $concurrent;

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


    public function __construct(\ilObjMultiVcGUI $a_parent)
    {
        global $DIC;
        $this->dic = $DIC;

        $this->objGui = $a_parent;
        $this->object = $a_parent->object;
        $this->settings = ilMultiVcConfig::getInstance($this->object->getConnId());
        //        $this->pluginIniSet = ilApiMultiVC::setPluginIniSet($this->settings);
        $this->moderatedMeeting = $this->object->get_moderated();
        $this->setUserRole();
        $this->setMeetingStartable();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getAccessToken(): ?string
    {
        // TeamsClientCredentials
        $clientId = $this->settings->getSvrUsername();
        $clientSecret = $this->settings->getSvrSalt();
        $tenantId = $this->settings->getSvrPublicUrl();
        $scope = 'https://graph.microsoft.com/.default';
        $guzzle = new \GuzzleHttp\Client();
        $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
        $token = json_decode($guzzle->post($url, [
        'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
                'grant_type' => 'client_credentials',
            ],
        ])->getBody()->getContents());
        return $token->access_token;
    }

    public static function getAccessTokenDirect(string $clientId, string $clientSecret, string $tenantId): ?string
    {
        // TeamsClientCredentials
        $scope = 'https://graph.microsoft.com/.default';
        $guzzle = new \GuzzleHttp\Client();
        $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
        $token = json_decode($guzzle->post($url, [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
                'grant_type' => 'client_credentials',
            ],
        ])->getBody()->getContents());
        return $token->access_token;
    }

    public function getUserByMail(string $mail): ?Model\User
    {
        try {
            $graph = new Graph();
            $graph->setAccessToken($this->getAccessToken());
            $user = $graph->createRequest("GET", "/users('" . $mail . "')")
                          ->setReturnType(Model\User::class)
                          ->execute();
            return $user;
        } catch (\Exception $e) {
            die($e->getMessage());
            return null;
        }
    }
    public static function getUserByMailDirect(string $mail, string $clientId, string $clientSecret, string $tenantId): ?Model\User
    {
        try {
            $graph = new Graph();
            $graph->setAccessToken(ilApiTeams::getAccessTokenDirect($clientId, $clientSecret, $tenantId));
            $user = $graph->createRequest("GET", "/users('" . $mail . "')")
                          ->setReturnType(Model\User::class)
                          ->execute();
            return $user;
        } catch (\Exception $e) {
            die($e->getMessage());
            return null;
        }
    }
    public function getDefaultCalendarByUserMail(string $mail): ?Model\Calendar
    {
        try {
            $graph = new Graph();
            $graph->setAccessToken($this->getAccessToken());
            $cal = $graph->createRequest("GET", "/users('" . $mail . "')/calendar")
                          ->setReturnType(Model\Calendar::class)
                          ->execute();
            return $cal;
        } catch (\Exception $e) {
            die($e->getMessage());
            return null;
        }
    }




    /**
     * @throws ilCurlConnectionException
     * @throws Exception
     */
    public function sessionCreateTeams(string $meetingTitle, ilDateTime $utcStart, ilDateTime $utcEnd): array
    {
        $this->dic->language()->loadLanguageModule('rep_robj_xmvc');
        $timezone = 'UTC';
        // $format = "Y-m-d H:i:s";
        // $dateTimeZone = new DateTimeZone($timezone);
        // $now = new DateTime('now', $dateTimeZone);
        // $start = $now->add(date_interval_create_from_date_string('2 minutes'));
        // $end = $now->add(date_interval_create_from_date_string('15 minutes'));

        // $param = array_replace([
        //     'timezone'  => $timezone,
        //     'enabledAutoRecordMeeting' => false,
        //     'allowAnyUserToBeCoHost' => false
        // ], $param);
        #die(var_dump($param));
        $mail = $this->dic->user()->getEmail();
        $subject = $this->dic->language()->txt('rep_robj_xmvc_subject_' . $this->parentObj->getType());
        $subject = str_replace('{MEETING_TITLE}', $meetingTitle, $subject);
        $subject = str_replace('{PARENT_TITLE}', $this->parentObj->getTitle(), $subject);

        $attendees = [];
        $coorganizers = [];
        $members = $this->object->getContainerMembers((int) $this->parentObj->getId());
        foreach ($members as $member) {
            $memberMail = ilObjUser::_lookupEmail($member['usr_id']);
            $memberName = ilObjUser::_lookupFullname($member['usr_id']);
            $attendees[] = [
                "emailAddress" => [
                   "address" => $memberMail,
                   "name" => $memberName
                ],
//                "type" => "required"
            ];
            if (($member['admin'] == 1 || $member['tutor'] == 1) && $memberMail != $mail) {
                $checkUser = $this->getUserByMail($memberMail);
                if (isset($checkUser)) {
                    $coorganizers[] = [
                        "identity" => [
                            "user" => [
                            "id" => $checkUser->getId(),
                            "displayName" => $checkUser->getDisplayName()
                            ]
                        ],
                        "upn" => $memberName,
                        "role" => "coorganizer" //"coorganizerpresenter"
                    ];
                }
            }

        }

        $meetingChatMode = "disabled";
        if ($this->object->isPrivateChat()) {
            $meetingChatMode = "enabled";
        }

        $allowedPresenters = "everyone";
        $isDialInBypassEnabled = true;
        $lobbyBypassScope = "everyone";
        if ($this->object->get_moderated()) {
            $allowedPresenters = "roleIsPresenter";
            $isDialInBypassEnabled = false;
            $lobbyBypassScope = "organizer";
        }
        $allowAttendeeToEnableCameraMic = true;
        if ($this->object->isCamOnlyForModerator()) {
            $allowAttendeeToEnableCameraMic = false;
            $allowedPresenters = "roleIsPresenter";//necessary
        }

        $om_event = [
            "startDateTime" => $utcStart->get(IL_CAL_ISO_8601, 'Y-m-dTH:i:s', ''),
            "endDateTime" => $utcEnd->get(IL_CAL_ISO_8601, 'Y-m-dTH:i:s', ''),
            "subject" => $meetingTitle,
            "allowedPresenters" => $allowedPresenters,
            "lobbyBypassSettings" => ["scope" => $lobbyBypassScope, "isDialInBypassEnabled" => $isDialInBypassEnabled],
            "participants" => ["attendees" => $coorganizers],
            "recordAutomatically" => $this->object->isRecordingAllowed(),
            "allowMeetingChat" => $meetingChatMode,
            "allowAttendeeToEnableCamera" => $allowAttendeeToEnableCameraMic,
            "allowAttendeeToEnableMic" => $allowAttendeeToEnableCameraMic
        ];
        // moderiert: allowedPresenters => roleIsPresenter

        $userId = $this->getUserByMail($mail)->getId();
        //        die(var_dump($om_event) . '----'.var_dump($userId));


        $graph = new Graph();
        $graph->setAccessToken($this->getAccessToken());
        try {
            $ret = $graph->createRequest("POST", "/users/" . $userId . "/onlineMeetings")
                         ->addHeaders(array("Content-Type" => "application/json"))
                         ->setReturnType(Model\OnlineMeetingInfo::class)
                         ->attachBody($om_event)
                         ->execute();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // die (var_dump($e->getMessage()));
            //log
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . '<br />' . $e->getMessage(), true);
            $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
        }

        $htmlContent = $this->dic->language()->txt('rep_robj_xmvc_teams_join_links');
        $htmlContent = str_replace('{JOIN_URL}', $ret->getJoinUrl(), $htmlContent);
        $htmlContent = str_replace('{PERMANENT_LINK}', ilLink::_getLink($this->object->getRefId(), $this->object->getType()), $htmlContent);
        $htmlContent = str_replace('{br}', '<br/>', $htmlContent);

        if ($this->object->isRecordingAllowed()) {
            $htmlContent .= '<br/>&nbsp;<br/>' . $this->dic->language()->txt('rep_robj_xmvc_recording_warning');
        }
        //
        $event = [
            "subject" => $subject,//$body,
            "body" => //$ret->getJoinInformation(),
            [
                  "contentType" => "html",
                  "content" => $htmlContent
               ],
            "start" => [
                     "dateTime" => $utcStart->get(IL_CAL_DATETIME, 'Y-m-dTH:i:s', 'UTC'),
                     "timeZone" => 'UTC'
                  ],
            "end" => [
                      "dateTime" => $utcEnd->get(IL_CAL_DATETIME, 'Y-m-dTH:i:s', 'UTC'),
                      "timeZone" => 'UTC'
                  ],
            "attendees" => $attendees,
            "isOnlineMeeting" => false,
            "allowNewTimeProposals" => false,
            "responseRequested" => false
//            "onlineMeetingProvider" => Model\OnlineMeetingProviderType::TEAMS_FOR_BUSINESS
        ];
        #die(var_dump($event));
        $graph = new Graph();
        $graph->setAccessToken($this->getAccessToken());
        try {
            $retCal = $graph->createRequest("POST", "/users('" . $mail . "')/calendar/events")
                 ->addHeaders(array("Content-Type" => "application/json"))
                 ->setReturnType(Model\Event::class)
                 ->attachBody($event)
                 ->execute();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // die (var_dump($e->getMessage()));
            //log
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . '<br />' . $e->getMessage(), true);
            $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
        }

        #die(var_dump($ret)."----".var_dump($retAr));

        //        try {

        $rel_data = [
            'id' => $ret->getId(),
            'title' => $meetingTitle,
            'calId' => $retCal->getId(),
            //            'changeKey' => $ret->getChangeKey(),
            //iCalUId
            //            'reminderMinutesBeforeStart' => $ret->getReminderMinutesBeforeStart(),
            //            'isReminderOn' => $ret->getIsReminderOn(),
            //            'hasAttachments' => $ret->getHasAttachments(),
            // 'importance' => $ret->getImportance(),
            //            'allowNewTimeProposals' => $ret->getAllowNewTimeProposals(),
            //            'hideAttendees' => $ret->getHideAttendees(),
            //            'organizer' =>[
            //                'emailAddress' => [
            //                    'address' => $ret->getOrganizer()->getEmailAddress()->getAddress(),
            //                    'name' => $ret->getOrganizer()->getEmailAddress()->getName()
            //                ]
            //            ],
            'joinUrl' => $ret->getJoinUrl()
        ];
        //        } catch (Exception $e) {die(var_dump($ret)); }

        $retAr = [
            'start' => $utcStart->get(IL_CAL_DATETIME, 'Y-m-d H:i:s', 'UTC'),
            'end' => $utcEnd->get(IL_CAL_DATETIME, 'Y-m-d H:i:s', 'UTC'),
            'timezone' => 'UTC',
            'recurrence' => '',
            'rel_id' => $ret->getId(),
            'rel_data' => json_encode($rel_data)
        ];


        return $retAr;
    }

    public static function changeParticipant(string $a_event, ilObjMultiVc $multiVcObj, ilMultiVcConfig $multiVcConn, array $upcomingMeeting, int $parentObjId, int $userId, int $role): void
    {
        global $DIC;
        $logger = $DIC->logger()->root();
        //        $logger->dump($upcomingMeeting);

        $clientId = $multiVcConn->getSvrUsername();
        $clientSecret = $multiVcConn->getSvrSalt();
        $tenantId = $multiVcConn->getSvrPublicUrl();
        $accessToken = self::getAccessTokenDirect($clientId, $clientSecret, $tenantId);


        foreach ($upcomingMeeting as $meeting) {
            $meetingRelData = json_decode($meeting['rel_data']);
            $logger->dump($meetingRelData);
            //$title = $meetingRelData->title;
            if (isset($meetingRelData->calId)) {
                $calId = $meetingRelData->calId;
                $attendees = [];
                $members = $multiVcObj->getContainerMembers($parentObjId);
                foreach ($members as $member) {
                    $attendees[] = [
                        "emailAddress" => [
                            "address" => ilObjUser::_lookupEmail($member['usr_id']),
                            "name" => ilObjUser::_lookupFullname($member['usr_id'])
                        ],
                        //                "type" => "required"
                    ];
                }
                $event = [
                    "attendees" => $attendees
                ];
                $graph = new Graph();
                $graph->setAccessToken($accessToken);
                try {
                    $ret = $graph->createRequest("PATCH", "/users('" . $meeting['auth_user'] . "')/calendar/events/" . $calId)
                                 ->addHeaders(array("Content-Type" => "application/json"))
                                 ->setReturnType(Model\Event::class)
                                 ->attachBody($event)
                                 ->execute();
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $logger->debug($e->getMessage());
                }

            } else { //old implementation with many calender events - keep it for old data some time
                if ($a_event = "addParticipant") {

                    $attendees[] = [
                        "emailAddress" => [
                            "address" => ilObjUser::_lookupEmail($userId),
                            "name" => ilObjUser::_lookupFullname($userId)
                        ]
                    ];

                    $joinUrl = $meetingRelData->joinUrl;
                    $logger->debug('joinUrl: ' . $joinUrl);

                    $subject = $DIC->language()->txt('rep_robj_xmvc_subject_' . ilObject::_lookupType($parentObjId));
                    $subject = str_replace('{MEETING_TITLE}', $meetingRelData->title, $subject);
                    $subject = str_replace('{PARENT_TITLE}', ilObject::_lookupTitle($parentObjId), $subject);

                    $htmlContent = $DIC->language()->txt('rep_robj_xmvc_teams_join_links');
                    $htmlContent = str_replace('{JOIN_URL}', $joinUrl, $htmlContent);
                    $htmlContent = str_replace('{PERMANENT_LINK}', ilLink::_getLink($multiVcObj->getRefId(), $multiVcObj->getType()), $htmlContent);
                    $htmlContent = str_replace('{br}', '<br/>', $htmlContent);

                    if ($multiVcObj->isRecordingAllowed()) {
                        $htmlContent .= '<br/>&nbsp;<br/>' . $DIC->language()->txt('rep_robj_xmvc_recording_warning');
                    }


                    $event = [
                        "subject" => $subject,
                        "body" => //$ret->getJoinInformation(),
                            [
                                "contentType" => "html",
                                "content" => $htmlContent
                            ],
                        "start" => [
                            "dateTime" => $meeting['start'],//->get(IL_CAL_ISO_8601, 'Y-m-dTH:i:s', ''),
                            "timeZone" => 'UTC'
                        ],
                        "end" => [
                            "dateTime" => $meeting['end'],//->get(IL_CAL_ISO_8601, 'Y-m-dTH:i:s', ''),
                            "timeZone" => 'UTC'
                        ],
                        "attendees" => $attendees,
                        "isOnlineMeeting" => false,
                        "allowNewTimeProposals" => false,
                        "responseRequested" => false
                    ];

                    $graph = new Graph();
                    $graph->setAccessToken($accessToken);
                    try {
                        $ret = $graph->createRequest("POST", "/users('" . $meeting['auth_user'] . "')/calendar/events")
                                     ->addHeaders(array("Content-Type" => "application/json"))
                                     ->setReturnType(Model\Event::class)
                                     ->attachBody($event)
                                     ->execute();
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        $logger->debug($e->getMessage());
                    }
                }
            }
        }
    }

    public function sessionDelete(string $meetingId): bool
    {
        $userId = $this->getUserByMail($this->dic->user()->getEmail())->getId();
        $graph = new Graph();
        $graph->setAccessToken($this->getAccessToken());
        try {
            $ret = $graph->createRequest("DELETE", "/users/" . $userId . "/onlineMeetings/" . $meetingId)
                         ->addHeaders(array("Content-Type" => "application/json"))
                         ->execute();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // die (var_dump($e->getMessage()));
            //log
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . '<br />' . $e->getMessage(), true);
            $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
        }
        if ($ret->getStatus() == 204) {
            return true;
        }
        return false;
    }

    public function sessionGet(?string $meetingId = null, string $hostEmail = ''): ?Model\Calendar
    {
        if(null === $meetingId) {
            return null;
        }
        if ($hostEmail == '') {
            $hostEmail = ilObjUser::_lookupEmail($this->object->getOwner());
        }
        return $this->getDefaultCalendarByUserMail($hostEmail);//
    }

    /**
     * @throws ilCurlConnectionException
     */
    public function sessionList(): string
    {
        return "";//$this->restfulApiCall(self::ENDPOINT_MEETINGS);
    }

    public function sessionParticipantsList(string $meetingId, string $hostEmail = ''): bool|string
    {
        die('sessionParticipantsList');
    }

    public function sessionParticipantAdd(string $meetingId, string $firstName, string $lastName, ?string $email, bool $isCoHost = false, ?string $hostEmail = null): bool
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
        return true;
    }

    public function sessionModeratorAdd(string $meetingId, string $firstName, string $lastName, ?string $email, ?string $hostEmail = null): bool
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

        return true;
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
                $found = in_array($this->course->getDefaultAdminRole(), $userLiaRoles);
                $found = false !== $found ? true : array_search($this->course->getDefaultTutorRole(), $userLiaRoles);
                return false !== $found;
            }
            if(!!$this->group) {
                $found = in_array($this->group->getDefaultAdminRole(), $userLiaRoles);
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


    private function setMeetingStartable(): void
    {
        if(!!$this->ilObjSession) {
            $dump = [$this->dic->user()->getId(), $this->ilObjSession->getId(), ilEventParticipants::_isRegistered($this->dic->user()->getId(), $this->ilObjSession->getId()) ];
        }


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
            if(false !== array_search($node['type'], ['crs', 'grp'])) {
                $parent = $node;
                break;
            }
        }

        if(!$parent['ref_id']) {
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

    public static function getAttendanceReport(int $objId, int $refId, int $LPMode, int $LpTime, string $clientId, string $clientSecret, string $tenantId): array
    {
        global $DIC;
        $logger = $DIC->logger()->root();
        $db = $DIC->database();
        $dbObjId = $db->quote($objId, 'integer');
        $dbRefId = $db->quote($refId, 'integer');
        $meetingIds = [];
        $starts = [];
        $owner = ilObject::_lookupOwner($objId);
        $objOwner = new ilObjUser($owner);
        $ownerEmail = $objOwner->getEmail();
        $ownerId = ilApiTeams::getUserByMailDirect($ownerEmail, $clientId, $clientSecret, $tenantId)->getId();

        //assign users by email
        $emails = [];
        $path = array_reverse($DIC->repositoryTree()->getPathFull($refId));
        $keys = array_keys($path);
        foreach($path as $key => $node) {
            if(in_array($node['type'], ['crs', 'grp'])) {
                $parent = $node;
                break;
            }
        }
        if (isset($parent['ref_id'])) {
            $parentRefId = $parent['ref_id'];
            //            $parentType = ilObject::_lookupType($parentRefId,true);
            $parentObjId = ilObject::_lookupObjectId($parentRefId);
            $query = "SELECT max(usr_data.usr_id) as max_usr_id, usr_data.email FROM obj_members, usr_data"
                . " WHERE usr_data.usr_id = obj_members.usr_id AND obj_members.obj_id = " . $db->quote($parentObjId, 'integer')
                . " GROUP BY usr_data.email";
            $res = $db->query($query);
            while ($row = $db->fetchAssoc($res)) {
                $emails[(int) $row['max_usr_id']] = $row['email'];
            }
        }

        //zum Testen aufräummen
        //        $db->query("DELETE FROM rep_robj_xmvc_user_log WHERE ref_id = " . $dbRefId);

        //hole meetingIds - später unter Berücksichtigung flag
        //WHERE isnull(cron) AND end < aktuelles datum in UTC
        $query = "SELECT rep_robj_xmvc_schedule.start, rep_robj_xmvc_schedule.end, rep_robj_xmvc_schedule.rel_id"
            . " FROM rep_robj_xmvc_schedule, rep_robj_xmvc_session"
            . " WHERE rep_robj_xmvc_schedule.rel_id = rep_robj_xmvc_session.rel_id AND rep_robj_xmvc_schedule.end < CURRENT_TIMESTAMP"
            . " AND ISNULL(rep_robj_xmvc_session.cron) AND rep_robj_xmvc_session.obj_id = " . $dbObjId . " ORDER BY start desc";
        $logger->debug($query);
        $res = $db->query($query);
        while ($row = $db->fetchAssoc($res)) {
            $starts[] = $row["start"];
            $ends[] = $row["end"];
            $meetingIds[] = $row["rel_id"];
        }
        $sumSeconds = 0;
        $sumTimeDiff = 0;
        for ((int) $i = 0; $i < count($meetingIds); $i++) {
            $meetingId = $meetingIds[$i];
            //list
            $graph = new Graph();
            $graph->setAccessToken(ilApiTeams::getAccessTokenDirect($clientId, $clientSecret, $tenantId));//$this->getAccessToken());
            try {
                $ret0 = $graph->createRequest(
                    "GET",
                    "/users/" . $ownerId . "/onlineMeetings/" . $meetingId . "/attendanceReports"
                ) //attendanceReports
                             ->addHeaders(array("Content-Type" => "application/json"))
                             ->execute();
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                //die (var_dump($e->getMessage()));
                $DIC->ui()->mainTemplate()->setOnScreenMessage(
                    'failure',
                    $DIC->language()->txt('error') . '<br />' . $e->getMessage(),
                    true
                );
                //$this->dic->ctrl()->redirect($this->objGui, 'userLog');
            }

            if ($ret0 !== null && $ret0->getBody() !== null && isset($ret0->getBody()["value"][0])) {
                //                $logger->dump($ret0->getBody()["value"]);

                $attendanceRecord = "";

                $startDb = new ilDateTime($starts[$i], IL_CAL_DATETIME, 'UTC');
                $endDb = new ilDateTime($ends[$i], IL_CAL_DATETIME, 'UTC');

                for ($j = 0; $j < count($ret0->getBody()["value"]); $j++) {
                    $meetingStartDateTime = $ret0->getBody()["value"][$j]["meetingStartDateTime"];
                    $meetingEndDateTime = $ret0->getBody()["value"][$j]["meetingEndDateTime"];
                    $utcTmpStart = new ilDateTime($meetingStartDateTime, IL_CAL_DATETIME, 'UTC');
                    $utcTmpEnd = new ilDateTime($meetingEndDateTime, IL_CAL_DATETIME, 'UTC');
                    if ($utcTmpStart < $endDb && $utcTmpEnd > $startDb) {
                        $attendanceRecord = $ret0->getBody()["value"][$j]["id"];
                        $utcStart = $utcTmpStart;
                        $utcEnd = $utcTmpEnd;
                    }

                }

                //                if (isset($starts[$i])) {
                //                    $startDb = new ilDateTime($starts[$i], IL_CAL_DATETIME, 'UTC');
                //                } else {
                //                    $startDb = $utcStart;
                //                }
                //                if (isset($ends[$i])) {
                //                    $endDb = new ilDateTime($ends[$i], IL_CAL_DATETIME, 'UTC');
                //                } else {
                //                    $endDb = $utcEnd;
                //                }

                if ($attendanceRecord !== "") {

                    $meassureStart = $utcStart;
                    if ($startDb > $utcStart) {
                        $meassureStart = $startDb;
                    }
                    $meassureEnd = $utcEnd;
                    if ($endDb < $utcEnd) {
                        $meassureEnd = $endDb;
                    }
                    $timediff = $meassureEnd->get(IL_CAL_UNIX) - $meassureStart->get(IL_CAL_UNIX);
                    if ($timediff > 0) {
                        $sumTimeDiff += $timediff;
                    }

                    $logger->debug("startDb=" . $startDb . ", utcStart=" . $utcStart . ", meassureStart=" . $meassureStart
                        . ", endDb=" . $endDb . ", utcEnd=" . $utcEnd . ", meassureEnd=" . $meassureEnd
                        . " -> timediff=" . $timediff . ", sumTimeDiff=" . $sumTimeDiff);

                    $start = $utcStart->get(IL_CAL_DATETIME, 'Y-m-d H:i:s', 'UTC');
                    $end = $utcEnd->get(IL_CAL_DATETIME, 'Y-m-d H:i:s', 'UTC');

                    $values = [
                        'start' => ['datetime', $start],
                        'end' => ['datetime', $end]
                    ];
                    $where = [
                        'obj_id' => ['integer', $objId],
                        'rel_id' => ['string', $meetingId]
                    ];
                    $db->update('rep_robj_xmvc_session', $values, $where);

                    //effektive start und endzeiten mit Sekunden eintragen mit Flag geholt
                    try {
                        $ret = $graph->createRequest(
                            "GET",
                            "/users/" . $ownerId . "/onlineMeetings/" . $meetingId . "/attendanceReports/" . $attendanceRecord . "/attendanceRecords"
                        )
                                     ->addHeaders(array("Content-Type" => "application/json"))
                                     ->execute();
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        $DIC->ui()->mainTemplate()->setOnScreenMessage(
                            'failure',
                            $DIC->language()->txt('error') . '<br />' . $e->getMessage(),
                            true
                        );
                        //$this->dic->ctrl()->redirect($this->objGui, 'userLog');
                    }

                    if (isset($ret->getBody()["value"])) {
                        $logger->debug("/onlineMeetings/" . $meetingId . "/attendanceReports/" . $attendanceRecord . "/attendanceRecords");
                        //                $logger->dump($ret->getBody()["value"]);
                        for ((int) $k = 0; $k < count($ret->getBody()["value"]); $k++) {
                            $entry = $ret->getBody()["value"][$k];
                            $isModerator = 0;
                            $userId = 0;

                            if (isset($entry["role"])) {
                                $role = $entry["role"];
                                if ($role == "Organizer") {
                                    $isModerator = 1;
                                }
                            }
                            if (isset($entry["emailAddress"])) {
                                $emailAdress = $entry["emailAddress"];
                                $userIdTmp = array_search($emailAdress, $emails);
                                if ($userIdTmp > 0) {
                                    $userId = $userIdTmp;
                                }
                            }
                            $name = $entry["identity"]["displayName"];
                            if (isset($entry["attendanceIntervals"])) {
                                //                        $logger->dump($entry);
                                for ($j = 0; $j < count($entry["attendanceIntervals"]); $j++) {
                                    $values = [];
                                    $joinDate = $entry["attendanceIntervals"][$j]["joinDateTime"];
                                    //umwandeln von UTC
                                    $leaveDate = $entry["attendanceIntervals"][$j]["leaveDateTime"];
                                    //umwandeln von UTC
                                    $durationSeconds = (int) $entry["attendanceIntervals"][$j]["durationInSeconds"];
                                    $sumSeconds += $durationSeconds;
                                    //hole userId unter Berücksichtigung obj_id
                                    //die($joinDate);
                                    $joinTime = new ilDateTime($joinDate, IL_CAL_DATETIME, 'UTC');
                                    $leaveTime = new ilDateTime($leaveDate, IL_CAL_DATETIME, 'UTC');
                                    //die(var_dump(count($entry["attendanceIntervals"])).'cc'.$joinTime);
                                    $primaryKeys = [
                                        'ref_id' => ['integer', $refId],
                                        'user_id' => ['integer', $userId],
                                        'display_name' => ['text', $name],
                                        'join_time' => ['integer', (int) $joinTime->getUnixTime()]
                                    ];
                                    $values = [
                                        'ref_id' => ['integer', $refId],
                                        'user_id' => ['integer', $userId],
                                        'display_name' => ['text', $name],
                                        'is_moderator' => ['integer', $isModerator],
                                        'join_time' => ['integer', (int) $joinTime->getUnixTime()],
                                        'meeting_id' => ['text', $meetingId],
                                        'leave_time' => ['integer', (int) $leaveTime->getUnixTime()],
                                        'duration_seconds' => ['integer', (int) $durationSeconds]
                                    ];
                                    try {
                                        $db->replace('rep_robj_xmvc_user_log', $primaryKeys, $values);
                                    } catch (Exception $e) {
                                        $logger->info("Teams-Log not possible for ref_id=" . $refId . ", user_id=" . $userId . ", display_name=" . $name . ", join_date=" . $joinDate . ", join_time=" . $joinTime . "=" . $joinTime->getUnixTime());
                                        die();
                                    }
                                }
                            }
                            if ($LPMode == ilObjMultiVc::LP_ACTIVE) {
                                $status = ilLPStatus::LP_STATUS_IN_PROGRESS_NUM;
                                $percentage = 0;
                                if ($sumTimeDiff > 0) {
                                    round($percentage = $sumSeconds * 100 / $sumTimeDiff);
                                }
                                if ($percentage > 100) {
                                    $percentage = 100;
                                }
                                if ($percentage > $LpTime) {
                                    $status = ilLPStatus::LP_STATUS_COMPLETED_NUM;
                                }
                                if ($userId > 0) {
                                    ilLPStatus::writeStatus($objId, $userId, $status, (int) $percentage, true);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $meetingIds;
    }

    //    public function getRecordings(): array
    //    {
    //        return [];
    //    }

}

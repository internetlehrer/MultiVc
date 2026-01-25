<?php

class ilApiZoom implements ilApiInterface
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

    public function getAccessToken(): ?string
    {
        // ZoomClientCredentials with check scopes
        $clientId = $this->settings->getSvrUsername();
        $clientSecret = $this->settings->getSvrSalt();
        $accountId = $this->settings->getSvrPublicUrl();
        $header = [
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            'Accept: application/json'
        ];
        $curl = new ilCurlConnection();
        $curl->init(false);
        $curl->setOpt(CURLOPT_HTTPHEADER, $header);
        $curl->setOpt(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        //Add Proxy
        if (\ilProxySettings::_getInstance()->isActive()) {
            $proxyHost = \ilProxySettings::_getInstance()->getHost();
            $proxyPort = \ilProxySettings::_getInstance()->getPort();
            $proxyURL = $proxyHost . ":" . $proxyPort;
            $curl->setopt(CURLOPT_PROXY, $proxyURL);
        }
        $timecalled = time();
        $data = [
            'grant_type' => 'account_credentials',
            'account_id' => $accountId,
        ];
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        $curl->setOpt(CURLOPT_POST, 1);
        $curl->setOpt(CURLOPT_POSTFIELDS, http_build_query($data));
        $curl->setOpt(CURLOPT_URL, 'https://zoom.us/oauth/token');
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $response = $curl->exec();
        $json = json_decode($response);
        if (empty($json->access_token)) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('zoom_err_no_access_token'), true);
            //$this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
        }
        //check scopes
        $this->checkScopes($json->scope);

        $token = $json->access_token;

        //caching possible?
//        if (isset($response->expires_in)) {
//            $expires = $response->expires_in + $timecalled;
//        } else {
//            $expires = 3599 + $timecalled;
//        }

        return $token;
    }

    public static function getAccessTokenDirect(string $clientId, string $clientSecret, string $accountId): ?string
    {
        global $DIC;

        $header = [
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            'Accept: application/json'
        ];
        $curl = new ilCurlConnection();
        $curl->init(false);
        $curl->setOpt(CURLOPT_HTTPHEADER, $header);
        $curl->setOpt(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        //Add Proxy
        if (\ilProxySettings::_getInstance()->isActive()) {
            $proxyHost = \ilProxySettings::_getInstance()->getHost();
            $proxyPort = \ilProxySettings::_getInstance()->getPort();
            $proxyURL = $proxyHost . ":" . $proxyPort;
            $curl->setopt(CURLOPT_PROXY, $proxyURL);
        }
        $timecalled = time();
        $data = [
            'grant_type' => 'account_credentials',
            'account_id' => $accountId,
        ];
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        $curl->setOpt(CURLOPT_POST, 1);
        $curl->setOpt(CURLOPT_POSTFIELDS, http_build_query($data));
        $curl->setOpt(CURLOPT_URL, 'https://zoom.us/oauth/token');
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $response = $curl->exec();
        $json = json_decode($response);
        if (empty($json->access_token)) {
            $logger = $DIC->logger()->root();
            $logger->debug('failure in Cron: zoom_err_no_access_token');
        }

        return $json->access_token;
    }

    protected function checkScopes(string $scope): void {
        $scopes = explode(' ', $scope);
        $scopetype = in_array('meeting:read:admin', $scopes, true) ? 'classic' : 'granular';
        $requiredscopes = [
            'granular' => [
                'meeting:read:meeting:admin',
                'meeting:read:invitation:admin',
                'meeting:delete:meeting:admin',
                'meeting:update:meeting:admin',
                'meeting:write:meeting:admin',
                'user:read:list_schedulers:admin',
                'user:read:settings:admin',
                'user:read:user:admin'
            ],
            'classic' => [
                'meeting:read:admin',
                'meeting:write:admin',
                'user:read:admin'
            ]
        ];
        $missingscopes = array_diff($requiredscopes[$scopetype], $scopes);
        if (!empty($missingscopes)) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('zoom_err_scopes') . ': ' . implode(', ', $missingscopes), true);
            $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
        } elseif ($this->object->get_moderated()) {
            //webinars
            $requiredscopes = [
                'granular' => [
                    'webinar:read:webinar:admin',
                    'webinar:delete:webinar:admin',
                    'webinar:update:webinar:admin',
                    'webinar:write:webinar:admin'
                ],
                'classic' => [
                    'webinar:read:admin',
                    'webinar:write:admin'
                ]
            ];
            $missingscopes = array_diff($requiredscopes[$scopetype], $scopes);
            if (!empty($missingscopes)) {
                $this->object->set_moderated(false);
                $this->object->doUpdate();
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('zoom_err_scopes_webinar') . ': ' . implode(', ', $missingscopes), true);
                $this->dic->ctrl()->redirect($this->objGui, 'editProperties');//$this->dic->ctrl()->getCmd());//, 'applyFilterScheduledMeetings');
            }
        }
        if($this->object->getLPMode() != ilObjMultiVc::LP_INACTIVE) {
            //Rate Limit Label: MEDIUM
            $requiredscopes = [
                'granular' => [
//                    'meeting:read:past_meeting',
                    'meeting:read:past_meeting:admin',
//                    'meeting:read:list_past_participants',
                    'meeting:read:list_past_participants:admin'
                ],
                'classic' => [
                    'meeting:read:admin',
                    'meeting:read'
                ]
            ];
            if ($this->object->get_moderated()) {
                $requiredscopes = [
                    'granular' => [
                        'webinar:read:list_past_instances:admin',
                        'webinar:read:list_past_participants:admin'
                    ],
                    'classic' => [
                        'webinar:read:admin',
                        'webinar:read'
                    ]
                ];
            }
            $missingscopes = array_diff($requiredscopes[$scopetype], $scopes);
            if (!empty($missingscopes)) {
                $this->object->setLPMode(ilObjMultiVc::LP_INACTIVE);
                $this->object->doUpdate();
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('zoom_err_scopes_lp') . ': ' . implode(', ', $missingscopes), true);
                $this->dic->ctrl()->redirect($this->objGui, 'editProperties');//$this->dic->ctrl()->getCmd());//, 'applyFilterScheduledMeetings');
            }
        }

    }

    public static function getApiUrl(): string
    {
        return 'https://api.zoom.us/v2/';  //europe: https://eu01api-www4local.zoom.us/v2/
    }

    private static function makeCall(string $token, string $endpoint, array $param = [], string $method = 'GET', ?bool $isUI = true): stdClass {
        global $DIC;
        $method = strtoupper($method);
        $url = self::getApiUrl() . $endpoint;
        $isPost = in_array($method, ['POST', 'PUT', 'PATCH']);
        $timeout = 60;
        $maxRedirects = 10;

        $json = new stdClass();
        $json->code = 200;

        $header = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ];
        try {
            $curl = new ilCurlConnection($url);
            $curl->init(false);
            $curl->setOpt(CURLOPT_CUSTOMREQUEST, $method);
    //        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
    //        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
    //        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, $timeout);
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
            $curl->setOpt(CURLOPT_MAXREDIRS, $maxRedirects);
            //Add Proxy
            if (\ilProxySettings::_getInstance()->isActive()) {
                $proxyHost = \ilProxySettings::_getInstance()->getHost();
                $proxyPort = \ilProxySettings::_getInstance()->getPort();
                $proxyURL = $proxyHost . ":" . $proxyPort;
                $curl->setopt(CURLOPT_PROXY, $proxyURL);
            }
            if ($isPost) {
                $header[] = 'Content-Type: application/json';
                $curl->setOpt(CURLOPT_POST, 1);
//                $curl->setOpt(CURLOPT_POSTFIELDS, http_build_query($param));
                $curl->setOpt(CURLOPT_POSTFIELDS, json_encode($param));
            }
    //        $curl->setOpt(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $curl->setOpt(CURLOPT_HTTPHEADER, $header);
            $curl->setOpt(CURLOPT_URL, $url);
            $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
            $response = $curl->exec();
            #echo '<pre>'; var_dump($response); exit;
            if (json_decode($response) != null) $json = json_decode($response);
            $code = $curl->getInfo(CURLINFO_HTTP_CODE);
            if ($code != null && isset($json->code)) {
                $json->code = $code;
            }
            #echo '<pre>'.$url; var_dump($json); exit;
        } catch (ilCurlConnectionException $e) {
            $logger = $DIC->logger()->root();
            $logger->debug('MulitVc Zoom '. $e->getMessage());
            if ($isUI) {
                $DIC->ui()->mainTemplate()->setOnScreenMessage('failure',
                    $DIC->language()->txt('error') . '<br />' . $e->getMessage(), true);
//                $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
            }
        }
        return $json;
    }
//    public function getUserByMail(string $mail): ?Model\User
//    {
//        try {
//            $graph = new Graph();
//            $graph->setAccessToken($this->getAccessToken());
//            $users = [];
//            $users = $graph->createRequest("GET", "/users?\$filter=mail eq '" . $mail."'")
//                          ->setReturnType(Model\User::class)
//                          ->execute();
//            if(empty($users[0])) {
//                //ALIAS
//                $users = $graph->createRequest("GET", "/users?\$filter=proxyAddresses/any(x:x eq 'smtp:" . $mail."')")
//                              ->setReturnType(Model\User::class)
//                              ->execute();
//            }
//            if(empty($users[0])) {
//                $user = $graph->createRequest("GET", "/users('" . $mail . "')")
//                    ->setReturnType(Model\User::class)
//                    ->execute();
//                return $user;
//            }
//            return $users[0];
//        } catch (\Exception $e) {
//            die($e->getMessage());
//            return null;
//        }
//    }
//    public static function getUserByMailDirect(string $mail, string $clientId, string $clientSecret, string $tenantId): ?Model\User
//    {
//        try {
//            $graph = new Graph();
//            $graph->setAccessToken(ilApiTeams::getAccessTokenDirect($clientId, $clientSecret, $tenantId));
//            $user = $graph->createRequest("GET", "/users('" . $mail . "')")
//                          ->setReturnType(Model\User::class)
//                          ->execute();
//            return $user;
//        } catch (\Exception $e) {
//            die($e->getMessage());
//            return null;
//        }
//    }
//    public function getDefaultCalendarByUserMail(string $mail): ?Model\Calendar
//    {
//        try {
//            $graph = new Graph();
//            $graph->setAccessToken($this->getAccessToken());
//            $cal = $graph->createRequest("GET", "/users('" . $mail . "')/calendar")
//                          ->setReturnType(Model\Calendar::class)
//                          ->execute();
//            return $cal;
//        } catch (\Exception $e) {
//            die($e->getMessage());
//            return null;
//        }
//    }



    protected function getHostId (string $mail): string {
        $hostId = '';
        $message = '';
        $token = $this->getAccessToken();
        try {
            $response = $this->makeCall($token, 'users/' . $mail);
            if(isset($response->id)) {
                $hostId = $response->id;
            } else {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . '<br />' . $response->code . ": " . $response->message, true);
                $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
            }
        } catch (\Exception $e) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . '<br />' . $e->getMessage(), true);
            $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
        }
        return $hostId;
    }

    /**
     * @throws ilCurlConnectionException
     * @throws Exception
     */
    public function sessionCreateZoom(string $meetingTitle, ilDateTime $utcStart, ilDateTime $utcEnd): array
    {
        $token = $this->getAccessToken();
        $this->dic->language()->loadLanguageModule('rep_robj_xmvc');
        $duration = ceil(($utcEnd->getUnixTime() - $utcStart->getUnixTime())/60);
        $start_time = $utcStart->get(IL_CAL_ISO_8601, 'Y-m-dTH:i:s', 'UTC');
        $timezone = 'UTC';

        $mail = $this->dic->user()->getEmail();
        $hostID = $this->getHostId($mail);
        //Lizenz abfragen $this->provide_license(
        $endpoint = 'users/' . $hostID . '/';
        if ($this->object->get_moderated()) { //isModeratedMeeting()
            $endpoint .= 'webinars';
        } else {
            $endpoint .= 'meetings';
        }
        $param = [];


        $subject = $this->dic->language()->txt('rep_robj_xmvc_subject_' . $this->parentObj->getType());
        $subject = str_replace('{MEETING_TITLE}', $meetingTitle, $subject);
        $subject = str_replace('{PARENT_TITLE}', $this->parentObj->getTitle(), $subject);

        $param['agenda'] = $subject; //topic

        $param['schedule_for'] = $mail;

        $param['duration'] = $duration;
        $param['start_time'] = $start_time;
        $param['timezone'] = $timezone;
        $param['type'] = 2;
        $param['pre_schedule'] = false;
//settings
        $settings = [];
        $settings['allow_multiple_devices'] = true;
        $settings['contact_email'] = $mail;
        $settings['contact_name'] = ilObjUser::_lookupFullname($this->dic->user()->getId());
        $settings['email_notification'] = true;
        $settings['host_video'] = true;
        $settings['show_share_button'] = false;

        if (!$this->object->get_moderated()) {
            $settings['mute_upon_entry'] = true;
            $settings['show_join_info'] = false;

            if($this->object->isCamOnlyForModerator()) {
                $settings['participant_video'] = false;
            } else {
                $settings['participant_video'] = true;
            }

            if ($this->object->getExtraCmd() == 0) {
                $settings['waiting_room'] = false;
                $settings['join_before_host'] = true;
                $settings['jbh_time'] = 15;
            } else {
                $settings['waiting_room'] = true;
                $waiting_room_options = [];
                $waiting_room_options['mode'] = 'custom';
                $options = ['nobody', 'everyone', 'users_not_in_account', 'users_not_in_account_or_whitelisted_domains', 'users_not_on_invite'];
                $waiting_room_options['who_goes_to_waiting_room'] = $options[$this->object->getExtraCmd()];
                $settings['waiting_room_options'] = $waiting_room_options;
            }

            $settings['continuous_meeting_chat'] = ['enabled' => $this->object->isPrivateChat()];

            $settings['auto_recording'] = 'none';
        }
//        'registrants_confirmation_email' => true,
//    'registrants_email_notification' => true,
//    'registration_type' => 1,



        $meeting_invitees = [];
        $alternative_hosts_m = [];
        $members = $this->object->getContainerMembers($this->parentObj->getId());
        foreach ($members as $member) {
            $memberMail = ilObjUser::_lookupEmail($member['usr_id']);
            if ($memberMail != $mail) {
                $meeting_invitees[] = [
                    'email' => $memberMail
                ];
                if (($member['admin'] == 1 || $member['tutor'] == 1)) {
                    $alternative_hosts_m[] = $memberMail;
                }
            }
        }
        if (!$this->object->get_moderated() && count($meeting_invitees) > 0) {
            $settings['meeting_invitees'] = $meeting_invitees;
        }
        $param['settings'] = $settings;

        $ret = $this->makeCall($token, $endpoint, $param, 'post');
        if (isset($ret->code)) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . ' ' . $ret->code . ": " . $ret->message, true);
            $this->dic->ctrl()->redirect($this->objGui, 'applyFilterScheduledMeetings');
        }
//echo(var_dump($ret));exit();

        $htmlContent = $this->dic->language()->txt('rep_robj_xmvc_teams_join_links');
        $htmlContent = str_replace('{JOIN_URL}', $ret->start_url, $htmlContent);
        $htmlContent = str_replace('{PERMANENT_LINK}', ilLink::_getLink($this->object->getRefId(), $this->object->getType()), $htmlContent);//deprecated
        $htmlContent = str_replace('{br}', '<br/>', $htmlContent);

        $rel_data = [
            'id' => $ret->id,
            'title' => $meetingTitle,
            'uuid' => $ret->uuid,
            'startLink' => $ret->start_url,
            'joinUrl' => $ret->join_url
        ];

        $retAr = [
            'start' => $utcStart->get(IL_CAL_DATETIME, 'Y-m-d H:i:s', 'UTC'),
            'end' => $utcEnd->get(IL_CAL_DATETIME, 'Y-m-d H:i:s', 'UTC'),
            'timezone' => 'UTC',
            'recurrence' => '',
            'rel_id' => $ret->id,
            'rel_data' => json_encode($rel_data)
        ];

        //Add alternative_hosts if possible
        if (count($alternative_hosts_m) > 0) {
            $param = [];
            $endpoint = "";
            if ($this->object->get_moderated()) {
                $endpoint = 'webinars/' . $ret->id;
            } else {
                $endpoint = 'meetings/' . $ret->id;
                $param['settings']['alternative_hosts_email_notification'] = true;
                $param['settings']['alternative_host_manage_meeting_summary'] = true;
                $param['settings']['alternative_host_manage_cloud_recording'] = true;
            }

            $param['settings']['alternative_hosts'] = implode(';', $alternative_hosts_m);
            $param['settings']['alternative_host_update_polls'] = true;
            $retH = self::makeCall($token, $endpoint, $param, 'PATCH');
            if (isset($retH->code) && isset($retH->message)) {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $this->dic->language()->txt('error') . ' ' . $retH->code . ": " . $retH->message, true);
            }
        }

        return $retAr;
    }

    public static function changeParticipant(string $a_event, ilObjMultiVc $multiVcObj, ilMultiVcConfig $multiVcConn, array $upcomingMeeting, int $parentObjId, int $userId): void
    {
        global $DIC;
        $logger = $DIC->logger()->root();
        $clientId = $multiVcConn->getSvrUsername();
        $clientSecret = $multiVcConn->getSvrSalt();
        $accountId = $multiVcConn->getSvrPublicUrl();
        $token = self::getAccessTokenDirect($clientId, $clientSecret, $accountId);

        foreach ($upcomingMeeting as $meeting) {
            $logger->dump($meeting);
            $mail = ilObjUser::_lookupEmail($meeting['user_id']);
            $param = [];
            $param['settings'] = [];

            $meeting_invitees = [];
            $alternative_hosts_m = [];
            $members = $multiVcObj->getContainerMembers($parentObjId);
            foreach ($members as $member) {
                $memberMail = ilObjUser::_lookupEmail($member['usr_id']);
                if ($memberMail != $mail) {
                    $meeting_invitees[] = [
                        'email' => $memberMail
                    ];
                    if (($member['admin'] == 1 || $member['tutor'] == 1)) {
                        $alternative_hosts_m[] = $memberMail;
                    }
                }
            }
            if (count($meeting_invitees) > 0) {
                //note: 100 requests per day. The rate limit is applied to the userId of the webinar host used to make the request.
                //https://developers.zoom.us/docs/api/meetings/#tag/webinars/patch/webinars/{webinarId}
                if (!$multiVcObj->get_moderated()) { //not moderated = Meeting - no action for webinars
                    $param['settings']['meeting_invitees'] = $meeting_invitees;
                    $ret = self::makeCall($token, 'meetings/'.$meeting['rel_id'], $param, 'PATCH');
                    $logger->dump($ret);
                }
            }
            if (count($alternative_hosts_m) > 0) {
                $param['settings'] = [];
                $param['settings']['alternative_hosts'] = implode(';', $alternative_hosts_m);
                $param['settings']['alternative_hosts_email_notification'] = true;
                if (!$multiVcObj->get_moderated()) { //not moderaated = Meeting
                    $param['settings']['alternative_host_update_polls'] = true;
                    $param['settings']['alternative_host_manage_meeting_summary'] = true;
                    $param['settings']['alternative_host_manage_cloud_recording'] = true;
                    $ret = self::makeCall($token, 'meetings/'.$meeting['rel_id'], $param, 'PATCH');
                } else {
                    $ret = self::makeCall($token, 'webinars/'.$meeting['rel_id'], $param, 'PATCH');
                }
                $logger->dump($ret);
            }
        }
    }

    public function sessionDelete(string $meetingId): bool
    {
        $token = $this->getAccessToken();
        if ($this->isModeratedMeeting()) {
            $endpoint = 'webinars/' . $meetingId . '?schedule_for_reminder=true&cancel_meeting_reminder=true';
        } else {
            $endpoint = 'meetings/' . $meetingId . '?schedule_for_reminder=true&cancel_meeting_reminder=true';
        }
        $ret = $this->makeCall($token, $endpoint, [],'DELETE');
        if ($ret->code == 204 || $ret->code == 404) {
            return true;
        } else {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('info', $this->dic->language()->txt('error') . '<br />' . $ret->message);
        }
        return false;
    }

//    public function sessionGet(?string $meetingId = null, string $hostEmail = ''): ?string
//    {
//        if(null === $meetingId) {
//            return null;
//        }
//        if ($hostEmail == '') {
//            $hostEmail = ilObjUser::_lookupEmail($this->object->getOwner());
//        }
//        return $this->getDefaultCalendarByUserMail($hostEmail);//
//    }

    /**
     * @throws ilCurlConnectionException
     */
    public function sessionList(): string
    {
        return "";//$this->restfulApiCall(self::ENDPOINT_MEETINGS);
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
        return true;
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
        $this->meetingStartable = true;
//        if(!!$this->ilObjSession) {
//            $dump = [$this->dic->user()->getId(), $this->ilObjSession->getId(), ilEventParticipants::_isRegistered($this->dic->user()->getId(), $this->ilObjSession->getId()) ];
//        }
//
//
//        switch (true) {
//            case $this->isUserModerator() || $this->isUserAdmin():
//            case !$this->isUserModerator() && $this->isMeetingRunning() && $this->isModeratorPresent() /* && $this->isValidAppointmentUser() */:
//                $this->meetingStartable = true;
//                break;
//
//            default:
//                $this->meetingStartable = false;
//        }
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
            if(in_array($node['type'], ['crs', 'grp'])) {
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
        if(in_array($parent['type'], ['crs', 'grp'])) {
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

    public static function getAttendanceReport(string $token, int $objId, int $refId, int $LPMode, int $LpTime): array
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
        //$token = $this->getAccessToken();

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

        $moderated = 0;
        $query = "SELECT moderated FROM rep_robj_xmvc_data WHERE id = " . $dbObjId;
        $res = $db->query($query);
        while ($row = $db->fetchAssoc($res)) {
            $moderated = (int) $row['moderated'];
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
        $sumSeconds = [];
        $sumTimeDiff = 0;
        for ($i = 0; $i < count($meetingIds); $i++) {
            $meetingId = $meetingIds[$i];
            $timeCorrection = false;
            //list
            if ($moderated == 1) {
                $startDb = new ilDateTime($starts[$i], IL_CAL_DATETIME, 'UTC');
                $endDb = new ilDateTime($ends[$i], IL_CAL_DATETIME, 'UTC');
                $sumTimeDiff = $endDb->get(IL_CAL_UNIX) - $startDb->get(IL_CAL_UNIX);
            } elseif ($moderated == 0) {
                $endpoint = 'past_meetings/' . $meetingId; //Check webinare

                $ret0 = self::makeCall($token, $endpoint, [],'GET', false);
                    //$this->dic->ctrl()->redirect($this->objGui, 'userLog');

                if(isset($ret0->participants_count) && $ret0->participants_count != "0") {
                    $attendanceRecord = "";

                    $startDb = new ilDateTime($starts[$i], IL_CAL_DATETIME, 'UTC');
                    $endDb = new ilDateTime($ends[$i], IL_CAL_DATETIME, 'UTC');

                    $meetingStartDateTime = $ret0->start_time;
                    $meetingEndDateTime = $ret0->end_time;

                    $utcTmpStart = new ilDateTime($meetingStartDateTime, IL_CAL_DATETIME, 'UTC');
                    $utcTmpEnd = new ilDateTime($meetingEndDateTime, IL_CAL_DATETIME, 'UTC');
                    if ($utcTmpStart < $endDb && $utcTmpEnd > $startDb) {
                        $attendanceRecord = $ret0->id;
                        $utcStart = $utcTmpStart;
                        $utcEnd = $utcTmpEnd;
                    }

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
                        $timeCorrection = true;
                    }
                }
            }
            if ($moderated == 1 || $timeCorrection === true) {
                if ($moderated == 0) {
                    //effektive start und endzeiten mit Sekunden eintragen mit Flag geholt
                    $endpoint = 'past_meetings/' . $meetingId . '/participants';
                } else {
                    $endpoint = 'past_webinars/' . $meetingId . '/participants?page_size=300';
                }
                $ret = self::makeCall($token, $endpoint, [],'GET', false);

                if ($ret != null) {
//                    $logger->debug("/onlineMeetings/" . $meetingId . "/attendanceReports/" . $attendanceRecord . "/attendanceRecords");
                    //                $logger->dump($ret->getBody()["value"]);
                    for ($k = 0; $k < count($ret->participants); $k++) {
                        $entry = $ret->participants[$k];
                        $isModerator = 0;
                        $userId = 0;

//                            if (isset($entry["role"])) {
//                                $role = $entry["role"];
//                                if ($role == "Organizer") {
//                                    $isModerator = 1;
//                                }
//                            }
                        if (isset($entry->user_email)) {
                            $emailAdress = $entry->user_email;
                            $userIdTmp = array_search($emailAdress, $emails);
                            if ($userIdTmp > 0) {
                                $userId = $userIdTmp;
                            }
                        }
                        $name = $entry->name;
                        if (!isset($sumSeconds[$userId])) {
                            $sumSeconds[$userId] = 0;
                        }
                        //toDo mehrere LogIns

//                            if (isset($entry["attendanceIntervals"])) {
                        //                        $logger->dump($entry);
//                                for ($j = 0; $j < count($entry["attendanceIntervals"]); $j++) {
                        $values = [];
                        $joinDate = $entry->join_time;
                        //umwandeln von UTC
                        $leaveDate = $entry->leave_time;
                        //umwandeln von UTC
                        $durationSeconds = (int) $entry->duration;
                        $sumSeconds[$userId] += $durationSeconds; //CHECK $sumSeconds +
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
                            'duration_seconds' => ['integer', $durationSeconds]
                        ];
                        try {
                            $db->replace('rep_robj_xmvc_user_log', $primaryKeys, $values);
                        } catch (Exception $e) {
                            $logger->info("Zoom-Log not possible for ref_id=" . $refId . ", user_id=" . $userId . ", display_name=" . $name . ", join_date=" . $joinDate . ", join_time=" . $joinTime . "=" . $joinTime->getUnixTime());
//                            die();
                        }
//                                }
//                            }
                        if ($LPMode == ilObjMultiVc::LP_ACTIVE) {
                            $status = ilLPStatus::LP_STATUS_IN_PROGRESS_NUM;
                            $percentage = 0;
                            if ($sumTimeDiff > 0) {
                                round($percentage = $sumSeconds[$userId] * 100 / $sumTimeDiff);
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
        return $meetingIds;
    }


}

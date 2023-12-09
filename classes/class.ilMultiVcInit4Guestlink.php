<?php

use ILIAS\DI\Container;
use BigBlueButton\Core\Record;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Responses\GetMeetingsResponse;
use BigBlueButton\Responses\GetRecordingsResponse;
use BigBlueButton\Parameters\DeleteRecordingsParameters;

$absDirPrefix = str_replace(
    substr(__DIR__, strpos(__DIR__, 'Customizing/')),
    '',
    __DIR__
);
chdir($absDirPrefix);

require_once($absDirPrefix . '/Services/Context/classes/class.ilContext.php');
require_once($absDirPrefix . "/Services/Init/classes/class.ilInitialisation.php");
require_once($absDirPrefix . '/Services/Language/classes/class.ilLanguage.php');
require_once __DIR__ . '/class.ilApiBBB.php';
//require_once __DIR__ . '/bbb/autoload.php';


/**
 * MultiVc initialization ilias class
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 *
 */

class JoinMeetingByGuestLink
{
    public const DEFAULT_LANG = 'de';

    public const PLUGIN_DIR = 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';

    private static ?JoinMeetingByGuestLink $instance = null;

    private Container $dic;

    private ilUtil $ilUtil;

    private \BigBlueButton\BigBlueButton $bbb;

    private int $refId = 0;

    private string $client;

    //    /** @var ilObjMultiVc $object */
    private ilObjMultiVc $pluginObject;

    //    /** @var ilMultiVcConfig $settings */
    private ilMultiVcConfig $pluginConfig;

    private string $meetingId = "0";

    private string $attendeePwd;

    private string $urlJoinMeeting;

    private string $iliasDomain;

    //    private $form;

    private string $displayName = '';

    private ?string $guestPassword = null;

    /** @var string[] $userAccept */
    private array $userAccept = [
        'termsOfUse' => false
    ];

    /** @var bool[] $errState */
    private array $errState = [
        'displayname'  => false,
        'termsOfUse' => false,
        'moderator' => false
    ];

    private string $userLang = 'de';

    /** @var string[] $isoLangCode */
    private array $isoLangCode = [
        'de' => 'de-DE',
        'en' => 'en-US'
    ];

    //    private array $langVar = [];

    private array $formField = [];

    private ilTemplate $htmlTpl;




    // BigBlueButton

    private function setMeetingId(): void
    {
        global $ilIliasIniFile;
        $this->iliasDomain = $ilIliasIniFile->readVariable('server', 'http_path');
        $this->iliasDomain = preg_replace("/^(https:\/\/)|(http:\/\/)+/", "", $this->iliasDomain);

        $rawMeetingId = $this->iliasDomain . ';' . $this->client . ';' . $this->pluginObject->getId();

        if (trim($this->pluginConfig->get_objIdsSpecial()) !== '') {
            $ArObjIdsSpecial = [];
            $rawIds = explode(",", $this->pluginConfig->get_objIdsSpecial());
            foreach ($rawIds as $id) {
                $id = trim($id);
                if (is_numeric($id)) {
                    array_push($ArObjIdsSpecial, $id);
                }
            }
            if (in_array($this->pluginObject->getId(), $ArObjIdsSpecial)) {
                $rawMeetingId .= 'r' . $this->pluginObject->getRefId();
            }
        }
        // $this->meetingId = md5($rawMeetingId);
        $this->meetingId = $rawMeetingId;
    }

    private function isMeetingRunning(): bool
    {
        try {
            $meetingParams = new \BigBlueButton\Parameters\IsMeetingRunningParameters($this->meetingId);
            $response = $this->bbb->isMeetingRunning($meetingParams);
            $running = $response->isRunning();
        } catch (Exception $e) {
            $running = false;
        }
        return $running;
    }

    private function getUrlJoinMeeting(): bool
    {
        if(!$this->isMeetingRunning()) {
            return false;
        }
        $joinParams = new \BigBlueButton\Parameters\JoinMeetingParameters($this->meetingId, $this->displayName, $this->attendeePwd);
        //        $joinParams->setJoinViaHtml5(true);
        $joinParams->setRedirect(true);
        $joinParams->setClientURL($this->dic->http()->request()->getUri());
        //$joinParams->set
        if((bool)strlen($this->urlJoinMeeting = $this->bbb->getJoinMeetingURL($joinParams))) {
            return true;
        }
        return false;
    }



    // Header-Redirect to BBB

    private function redirectToVc(): void
    {
        header('Status: 303 See Other', false, 303);
        header('Location:' . $this->urlJoinMeeting);
        exit;
    }

    /**
     * @throws Exception
     */
    private function setUserLog(): void
    {
        $dateTime = new DateTime(null, new DateTimeZone('UTC'));
        $values = [
            'ref_id' => ['integer', $this->refId],
            'user_id' => ['integer', 0],
            'join_time' => ['integer', $dateTime->getTimestamp()],
            'display_name' => ['text', $this->displayName],
            'is_moderator' => ['integer', 0],
            'meeting_id' => ['text', $this->bbb->getMeetingInfo(
                new \BigBlueButton\Parameters\GetMeetingInfoParameters(
                    $this->meetingId,
                    $this->pluginConfig->getSvrSalt()
                )
            )->getMeeting()->getInternalMeetingId()]
        ];

        $this->dic->database()->insert($this->pluginObject::TABLE_USER_LOG, $values);
    }


    // Language Vars & HTML-Form

    private function setFormElements(): void
    {
        $input = function ($name, $value, $type = 'text', $title = '', $class="", $addAttr = "") {
            return '<input type="' . $type . '" name="' . $name . '" value="' . $value . '" title="' . $title . '" placeholder="' . $title . '" class="' . $class . '"' . $addAttr . ' />';
        };
        $this->formField = [
            'display_name' => $input('display_name', $this->displayName, 'text', $this->getLangVar('guest_displayname_input'), 'form-control'),
            'submit' => $input('submit', $this->getLangVar('btntext_join_meeting'), 'submit', $this->getLangVar('btntext_join_meeting'), 'btn btn-primary'),
            'guest_password' => $input('guest_password', $this->guestPassword, 'password', $this->getLangVar('guest_password_input'), 'form-control', ' autocomplete="new-password"'),
            'guest_password_hidden' => $input('guest_password', rawurldecode($this->pluginObject->getAccessToken()), 'hidden', $this->getLangVar('guest_password_input'), 'form-control'),
            'guest_login_button' => $input('guest_login_button', $this->getLangVar('btntext_guest_login_button'), 'submit', $this->getLangVar('btntext_guest_login'), 'btn btn-primary'),
        ];

    }

    private function setHtmlDocument(): void
    {
        $http_base = ILIAS_HTTP_PATH;
        if (strpos($http_base, '/m/')) {
            $http_base = strstr($http_base, '/m/', true) . '/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';
        }

        $this->htmlTpl = new ilTemplate(dirname(__DIR__) . '/' . 'templates/guestlink/tpl.html5doc.html', true, true);
        $this->htmlTpl->setVariable('USER_LANG', $this->isoLangCode[$this->userLang]);
        $this->htmlTpl->setVariable('HTTP_BASE', $http_base);
        $this->htmlTpl->setVariable('MEETING_TITLE', $this->getMeetingTitle() . ' - ' . $this->getLangVar('big_blue_button'));
        $this->htmlTpl->setVariable('H1', $this->getMeetingTitle() . ' - ' . $this->getLangVar('big_blue_button'));
        $this->htmlTpl->setVariable('INFO_TOP_MODERATED_M', $this->getLangVar('info_top_moderated_m_bbb'));
        $this->htmlTpl->setVariable('ERR_STATE_INPUT_FIELD', (int)$this->errState['displayname']);
        $this->htmlTpl->setVariable('ERR_MSG_INPUT_FIELD', !$this->errState['displayname'] ? '' : $this->getLangVar('err_msg_displayname'));
        $this->htmlTpl->setVariable('ERR_STATE_TERMSOFUSE', (int)$this->errState['termsOfUse']);
        $this->htmlTpl->setVariable('VAL_TERMSOFUSE', (int)$this->userAccept['termsOfUse']);
        $this->htmlTpl->setVariable('TXT_ACCEPT_TERMSOFUSE', str_replace('{br}', '<br />', $this->getLangVar('terms_of_use')));
        $this->htmlTpl->setVariable('ERR_STATE_MODERATOR', (int)$this->errState['moderator']);
        $this->htmlTpl->setVariable('ERR_MSG_MODERATOR', !$this->errState['moderator'] ? '' : $this->getLangVar('wait_join_meeting_guest'));
        $this->htmlTpl->setVariable('FORM_ACTION', filter_var('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
        $this->htmlTpl->setVariable('INFO_BOTTOM', $this->getLangVar('info_bottom'));
        $this->htmlTpl->setVariable('INFO_REQUIREMENTS', $this->getLangVar('info_requirements_bbb'));


        if($this->isUserLoggedIn()) {
            #ilSession::set('guestLoggedIn', false);
            $this->htmlTpl->setVariable('INPUT_FIELD', $this->getFormField('display_name'));
            $this->htmlTpl->setVariable('INPUT_FIELD_INFO', $this->getLangVar('guest_displayname_info'));
            $this->htmlTpl->setVariable('SUBMIT_BUTTON', $this->getFormField('guest_password_hidden') . $this->getFormField('submit'));
        }
        // GUEST PASSWORD/LOGIN
        if($this->isPwEnabled() && !$this->isUserLoggedIn()) {
            if($this->dic->http()->wrapper()->post()->has('guest_password') || $this->pluginObject->isSecretExpired()) {
                $this->htmlTpl->setVariable('ERR_STATE_INPUT_FIELD', 1);
                $this->htmlTpl->setVariable('ERR_MSG_INPUT_FIELD', $this->getLangVar(
                    !$this->pluginObject->isSecretExpired()
                        ? 'err_msg_guest_password'
                        : 'err_msg_guest_password_expired'
                ));
            }
            if(!$this->pluginObject->isSecretExpired()) {
                $this->htmlTpl->setVariable('INPUT_FIELD', $this->getFormField('guest_password'));
                $this->htmlTpl->setVariable('INPUT_FIELD_INFO', $this->getLangVar('guest_password_input_info'));
                $this->htmlTpl->setVariable('SUBMIT_BUTTON', $this->getFormField('guest_login_button'));
            }
        }

    }

    private function isPwEnabled(): bool
    {
        return (bool)strlen(trim($this->pluginObject->getAccessToken()));
    }

    private function isUserLoggedIn() : bool
    {
        if (ilSession::get('guestLoggedIn') == true) return true;
        return false;
    }

    //    private function checkPw( ?string $phrase = null ): bool
    //    {
    //        $validPw = trim($phrase) === trim($this->pluginObject->getAccessToken());
    //        return $validPw && !$this->pluginObject->isSecretExpired();
    //    }

    private function setGuestLoginState(): void
    {
        $phrase = '';
        if ($this->dic->http()->wrapper()->post()->has('guest_password')) {
            $phrase = trim($this->dic->http()->wrapper()->post()->retrieve('guest_password',
                $this->dic->refinery()->kindlyTo()->string()));
        }

        if ($this->isUserLoggedIn() || !$this->isPwEnabled()) {
            ilSession::set('guestLoggedIn', true);
        } elseif ($phrase === trim($this->pluginObject->getAccessToken())) {
            ilSession::set('guestLoggedIn', !$this->pluginObject->isSecretExpired());
        } else {
            ilSession::set('guestLoggedIn', false);
        }
    }


    private function getFormField($fieldName)
    {
        return strlen($field = $this->formField[$fieldName]) ? $field : '';
    }

    private function getMeetingTitle(): string
    {
        return $this->pluginObject->getTitle();
    }

    private function getLangVar(string $value): string
    {
        return ilLanguage::_lookupEntry($this->userLang, 'rep_robj_xmvc', 'rep_robj_xmvc_' . $value);
        //return isset($this->langVar[$value]) ? $this->langVar[$value] : '-' . $value . '-';
    }

    private function setUserLangBySvrParam(): void
    {
        if(isset($this->dic->http()->request()->getServerParams()['HTTP_ACCEPT_LANGUAGE']) && strlen($this->dic->http()->request()->getServerParams()['HTTP_ACCEPT_LANGUAGE']) >= 2) {
            $this->userLang = substr($this->dic->http()->request()->getServerParams()['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }
    }
    //
    //    private function setLangVars(): void
    //    {
    //        $langFilePath = dirname(__FILE__) . '/lang/';
    //        $langFileName = 'ilias_' . $this->userLang . '.lang';
    //        $langPathFile = $langFilePath . $langFileName;
    //        if( !file_exists($langPathFile) ) {
    //            $langPathFile = $langFilePath . 'ilias_' . self::DEFAULT_LANG . '.lang';
    //        }
    //        if( file_exists($langPathFile) ) {
    //            $langFileContent = file_get_contents($langPathFile);
    //            foreach( explode("\n", $langFileContent) as $line ) {
    //                if( substr_count($line, '#:#') ) {
    //                    list($key, $value) = explode('#:#', $line);
    //                    $this->langVar[trim($key)] = trim($value);
    //                }
    //            }
    //        }
    //    }



    // validation checks

    private function checkPostRequest(): bool
    {
        $score = 0;

        if ($this->dic->http()->wrapper()->post()->has('display_name')) {
            $this->displayName = trim($this->dic->http()->wrapper()->post()->retrieve('display_name', $this->dic->refinery()->kindlyTo()->string()));
            $score += 2;
            if($this->displayName == '') {
                $score -= 2;
                $this->errState['displayname'] = true;
            }
        }
        if ($this->dic->http()->wrapper()->post()->has('terms_of_use')) {
            $this->userAccept['termsOfUse'] = (bool)$this->dic->http()->wrapper()->post()->retrieve('terms_of_use', $this->dic->refinery()->kindlyTo()->int());
            $score += 4;
        } else {
            $this->errState['termsOfUse'] = true;
        }

        return $score >= 6;
    }

    private function validateInvitation(): void
    {
        switch(true) {
            case !$this->pluginObject->get_moderated():
                $this->httpExit(403);
                break;
            case $this->pluginConfig->isGuestlinkDefault():
            case $this->pluginConfig->isGuestlinkChoose() && $this->pluginObject->isGuestlink():
                break;
            default:
                $this->httpExit(403);
        }
    }

    private function httpExit(int $code = 404): void
    {
        $text = [
            403 => 'Forbidden',
            404 => 'Not Found'
        ];
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        http_response_code($code);
        header($protocol . ' ' . $code . ' ' . $text[$code]);
        exit;
    }



    // Constructor & initz

    private function __construct()
    {
        //vor Version 8 wurde client statt client_id genutzt
        if(isset($_GET["client"])) {
            $_GET["client_id"] = $_GET["client"];
        }
        \ilContext::init(\ilContext::CONTEXT_SOAP_NO_AUTH);
        \ilInitialisation::initILIAS();

        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;
        $this->client = $this->dic->http()->wrapper()->query()->retrieve('client_id', $this->dic->refinery()->kindlyTo()->string());
        $this->refId = $this->dic->http()->wrapper()->query()->retrieve('ref_id', $this->dic->refinery()->kindlyTo()->int());
        try {
            $this->pluginObject = new ilObjMultiVc($this->refId);
        } catch (ilObjectNotFoundException $e) {
            $this->httpExit(404);
        }

        $this->pluginConfig = ilMultiVcConfig::getInstance($this->pluginObject->getConnId());

        // exit if not valid
        $this->validateInvitation();

        #var_dump($_SESSION);

        $this->setGuestLoginState();

        $this->setUserLangBySvrParam();
        //$this->setLangVars();

        // redirect to BBB if valid
        if($this->checkPostRequest()) {
            if(!$this->errState['displayname']) {
                //                $this->bbb = new InitBBB($this->pluginConfig->getSvrSalt(), $this->pluginConfig->getSvrPrivateUrl());  // \BigBlueButton\BigBlueButton();
                $this->bbb = new \BigBlueButton\BigBlueButton($this->pluginConfig->getSvrPrivateUrl(), $this->pluginConfig->getSvrSalt());
                $this->attendeePwd = $this->pluginObject->getAttendeePwd();
                $this->setMeetingId();
                if($this->getUrlJoinMeeting()) {
                    $this->setUserLog();
                    $this->redirectToVc();
                }
                $this->errState['moderator'] = true;
            }
        }

        $this->setFormElements();
        $this->setHtmlDocument();

    }

    public static function init(): ?JoinMeetingByGuestLink
    {
        if(self::$instance instanceof JoinMeetingByGuestLink) {
            return self::$instance;
        }
        return self::$instance = new self();
    }

    public function __toString(): string
    {
        return $this->htmlTpl->get();
    }

}

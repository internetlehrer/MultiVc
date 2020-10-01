<?php
ini_set('display_errors', 1);
ini_set('error_reporting', 5);

use BigBlueButton\Core\Record;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Responses\GetMeetingsResponse;
use BigBlueButton\Responses\GetRecordingsResponse;
use BigBlueButton\Parameters\DeleteRecordingsParameters;
use ILIAS\DI\Container;

chdir("../../../../../../../");
require_once('./Services/Context/classes/class.ilContext.php');
require_once("./Services/Init/classes/class.ilInitialisation.php");
require_once("./Services/Utilities/classes/class.ilUtil.php");
//require_once("./Services/Utilities/classes/class.ilBrowser.php");
require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilApiBBB.php';
require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/bbb/vendor/autoload.php';


class ilInitialisationIlias4GuestLink extends ilInitialisation
{

    protected static function getIniHost() {
        // Create ini-handler (onces)
        self::initIliasIniFile();
        global $ilIliasIniFile;
        // Return [server] -> 'http_path' variable from 'ilias.init.php'
        $http_path = $ilIliasIniFile->readVariable('server', 'http_path');
        // Strip http:// & https://
        if (strpos($http_path, 'https://') !== false)
            $http_path = substr($http_path, 8);
        if (strpos($http_path, 'http://') !== false)
            $http_path = substr($http_path, 7);
        // Return clean host
        return $http_path;
    }

    public static function initIlias($client_id, $client_token = '') {
        define ("CLIENT_ID", $client_id);
        define('IL_COOKIE_HTTPONLY', true); // Default Value
        define('IL_COOKIE_EXPIRE', 0);
        define('IL_COOKIE_PATH', '/');
        define('IL_COOKIE_DOMAIN', '');
        \ilContext::init(\ilContext::CONTEXT_SCORM);
        //UK
        \ilInitialisation::initILIAS();
        \ilInitialisation::buildHTTPPath();
        // Remember original values
        // $_ORG_SERVER = array(
        // 'HTTP_HOST'    => $_SERVER['HTTP_HOST'],
        // 'REQUEST_URI'  => $_SERVER['REQUEST_URI'],
        // 'PHP_SELF'     => $_SERVER['PHP_SELF'],
        // );
        // // Overwrite $_SERVER entries which would confuse ILIAS during initialisation
        // $_SERVER['REQUEST_URI'] = '';
        // $_SERVER['PHP_SELF']    = '/index.php';
        // $_SERVER['HTTP_HOST']   = self::getIniHost();
        // require_once "./Services/Utilities/classes/class.ilUtil.php";
        // //ilInitialisation::initIliasIniFile();
        // ilInitialisation::initClientIniFile();
        // ilInitialisation::initDatabase();

        // // Restore original, since this could lead to bad side-effects otherwise
        // $_SERVER['HTTP_HOST']   = $_ORG_SERVER['HTTP_HOST'];
        // $_SERVER['REQUEST_URI'] = $_ORG_SERVER['REQUEST_URI'];
        // $_SERVER['PHP_SELF']    = $_ORG_SERVER['PHP_SELF'];
        // ilInitialisation::initLog();//UK
    }

    /**
     * Function; initGlobal($a_name, $a_class, $a_source_file)
     *  Derive from protected to public...
     *
     * @see \ilInitialisation::initGlobal($a_name, $a_class, $a_source_file)
     */
    public static function initGlobal($a_name, $a_class, $a_source_file = null) {
        return parent::initGlobal($a_name, $a_class, $a_source_file);
    }

    /**
     * Function: initDatabase()
     *  Derive from protected to public...
     *
     * @see \ilInitialisation::initDatabase()
     */
    public static function initDatabase() {
        if (!isset($GLOBALS['ilDB'])) {
            parent::initGlobal("ilBench", "ilBenchmark", "./Services/Utilities/classes/class.ilBenchmark.php");
            parent::initDatabase();
        }
    }

    /**
     * Function: initIliasIniFile()
     *  Derive from protected to public...
     *
     * @see \ilInitialisation::initIliasIniFile()
     */
    public static function initIliasIniFile() {
        if (!isset($GLOBALS['ilIliasIniFile'])) {
            parent::initIliasIniFile();
        }
    }

    /**
     * Function: initClientIniFile()
     *  Derive from protected to public...
     *
     * @see \ilInitialisation::initIliasIniFile()
     */
    public static function initClientIniFile() {
        if (!isset($GLOBALS['initClientIniFile'])) {
            parent::initClientIniFile();
        }
    }

    //UK
    public static function initLog() {
        if (!isset($GLOBALS['ilLog'])) {
            parent::initLog();
            parent::initGlobal("ilAppEventHandler", "ilAppEventHandler", "./Services/EventHandling/classes/class.ilAppEventHandler.php");
        }
    }
}


class JoinMeetingByGuestLink
{
    const DEFAULT_LANG = 'de';

    /** @var JoinMeetingByGuestLink|null $instance */
    static private $instance;

    /** @var Container $dic */
    private $dic;

    /** @var ilUtil $ilUtil */
    private $ilUtil;

    /** @var InitBBB $bbb */
    private $bbb;

    /** @var int $refId */
    private $refId = 0;

    /** @var string $client */
    private $client;

    /** @var ilObjMultiVc $object */
    private $pluginObject;

    /** @var ilMultiVcConfig $settings */
    private $pluginConfig;

    /** @var string $meetingId */
    private $meetingId = 0;

    /** @var string $attendeePwd */
    private $attendeePwd;

    /** @var string $urlJoinMeeting */
    private $urlJoinMeeting;

    /** @var string $iliasDomain */
    private $iliasDomain;

    private $form;

    /** @var string $userTitle */
    private $userTitle = '';

    /** @var string $displayName */
    private $displayName = '';

    /** @var bool[] $errState */
    private $errState = [
        'displayname'  => false,
        'moderator' => false
    ];

    /** @var string $userLang */
    private $userLang = 'de';

    /** @var string[] $isoLangCode */
    private $isoLangCode = [
        'de' => 'de-DE',
        'en' => 'en-US'
    ];

    /** @var array $langVar */
    private $langVar = [];

    /** @var array $formField */
    private $formField = [];

    /** @var ilTemplate $htmlTpl */
    private $htmlTpl;




    // BigBlueButton

    private function setMeetingId(): void
    {
        // $rawMeetingId = $DIC->settings()->get('inst_id',0) . $this->object->getId();
        $this->iliasDomain = substr(ILIAS_HTTP_PATH,7);
        if (substr($this->iliasDomain,0,1) === "/") {
            $this->iliasDomain = substr($this->iliasDomain, 1);
        }
        if (substr($this->iliasDomain,0,4) === "www.") {
            $this->iliasDomain = substr($this->iliasDomain, 4);
        }
        $excludePathSegment = substr($this->iliasDomain, strpos($this->iliasDomain,
            false === strpos($this->iliasDomain, '/Customizing') ? '/m' : '/Customizing'));
        $rawMeetingId = str_replace($excludePathSegment, '', $this->iliasDomain) . ';' . $this->client . ';' . $this->pluginObject->getId();

        if ( trim($this->pluginConfig->get_objIdsSpecial()) !== '') {
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
        if( !$this->isMeetingRunning() ) {
            return false;
        }
        $joinParams = new \BigBlueButton\Parameters\JoinMeetingParameters($this->meetingId, trim($this->userTitle . ' ') . $this->displayName, $this->attendeePwd);
        $joinParams->setJoinViaHtml5(true);
        $joinParams->setRedirect(true);
        $joinParams->setClientURL($this->dic->http()->request()->getUri());
        //$joinParams->set
        if( (bool)strlen($this->urlJoinMeeting = $this->bbb->getJoinMeetingURL($joinParams)) )
        {
            return true;
        }
        return false;
    }



    // Header-Redirect to BBB

    private function redirectToVc(): void {
        header('Status: 303 See Other', false, 303);
        header('Location:' . $this->urlJoinMeeting);
        exit;
    }



    // Language Vars & HTML-Form

    private function setFormElements() {
        $input = function($name, $value, $type = 'text', $title = '', $class="") {
            return '<input type="' . $type . '" name="' . $name . '" value="' . $value . '" title="' . $title . '" placeholder="' . $title . '" class="' . $class . '" />';
        };
        $this->formField = [
            'user_title' => $input('user_title', $this->userTitle, 'text', 'Titel'),
            'display_name' => $input('display_name', $this->displayName, 'text', $this->getLangVar('guest_displayname_input'), 'form-control'),
            'submit' => $input('submit', $this->getLangVar('btntext_join_meeting'), 'submit', $this->getLangVar('btntext_join_meeting'), 'btn btn-primary')
        ];

    }

    private function setHtmlDocument()
    {
        $this->htmlTpl = new ilTemplate( dirname(__FILE__) . '/' . 'templates/guestlink/tpl.html5doc.html', true, true);
        $this->htmlTpl->setVariable('USER_LANG', $this->isoLangCode[$this->userLang]);
        $this->htmlTpl->setVariable('MEETING_TITLE', $this->getMeetingTitle() . ' - ' . $this->getLangVar('big_blue_button'));
        $this->htmlTpl->setVariable('H1', $this->getMeetingTitle() . ' - ' . $this->getLangVar('big_blue_button'));
        $this->htmlTpl->setVariable('INFO_TOP_MODERATED_M', $this->getLangVar('info_top_moderated_m_bbb'));
        $this->htmlTpl->setVariable('ERR_STATE_DISPLAYNAME', (int)$this->errState['displayname']);
        $this->htmlTpl->setVariable('ERR_MSG_DISPLAYNAME', !$this->errState['displayname'] ? '' : $this->getLangVar('err_msg_displayname'));
        $this->htmlTpl->setVariable('ERR_STATE_MODERATOR', (int)$this->errState['moderator']);
        $this->htmlTpl->setVariable('ERR_MSG_MODERATOR', !$this->errState['moderator'] ? '' : $this->getLangVar('wait_join_meeting_guest'));
        $this->htmlTpl->setVariable('FORM_ACTION', filter_var('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
        $this->htmlTpl->setVariable('DISPLAY_NAME', $this->getFormField('display_name'));
        $this->htmlTpl->setVariable('DISPLAY_NAME_INFO', $this->getLangVar('guest_displayname_info'));
        $this->htmlTpl->setVariable('INFO_BOTTOM', $this->getLangVar('info_bottom'));
        $this->htmlTpl->setVariable('INFO_REQUIREMENTS', $this->getLangVar('info_requirements_bbb'));
        $this->htmlTpl->setVariable('SUBMIT_BUTTON', $this->getFormField('submit'));
    }

    private function getFormField($fieldName) {
        return strlen($field = $this->formField[$fieldName]) ? $field : '';
    }

    private function getMeetingTitle()
    {
        return $this->pluginObject->getTitle();
    }

    private function assignTranslatedLangVars()
    {
        $langFilePath = dirname(__FILE__) . '/lang/';

        // lang files
        $deLangFileName = $langFilePath . 'ilias_de.lang';
        $enLangFileName = $langFilePath . 'ilias_en.lang';

        // file content
        $deLangFileContent = file_get_contents($deLangFileName);
        $enLangFileContent = file_get_contents($enLangFileName);

        // content lines 2 array
        $deLangFileContentArr = explode("\n", $deLangFileContent);
        $enLangFileContentArr = explode("\n", $enLangFileContent);

        // build array with langVarKeys
        $enLangKeyVal = [];
        $i = 0;
        foreach( $deLangFileContentArr as $line ) {
            if( substr_count($line, '#:#') ) {
                list($key, $value) = explode('#:#', $line);
                $enLangKeyVal[] = trim($key) . '#:#' . trim($enLangFileContentArr[$i]);
                $i++;
            }
        }

        echo implode("\n", $enLangKeyVal);



    }

    private function getLangVar(string $value): string
    {
        return isset($this->langVar[$value]) ? $this->langVar[$value] : '-' . $value . '-';
    }

    private function setUserLangBySvrParam(): void
    {
        if( isset($this->dic->http()->request()->getServerParams()['HTTP_ACCEPT_LANGUAGE']) && strlen($this->dic->http()->request()->getServerParams()['HTTP_ACCEPT_LANGUAGE']) >= 2 ) {
            $this->userLang = substr($this->dic->http()->request()->getServerParams()['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }
    }

    private function setLangVars(): void
    {
        $langFilePath = dirname(__FILE__) . '/lang/';
        $langFileName = 'ilias_' . $this->userLang . '.lang';
        $langPathFile = $langFilePath . $langFileName;
        if( !file_exists($langPathFile) ) {
            $langPathFile = $langFilePath . 'ilias_' . self::DEFAULT_LANG . '.lang';
        }
        if( file_exists($langPathFile) ) {
            $langFileContent = file_get_contents($langPathFile);
            foreach( explode("\n", $langFileContent) as $line ) {
                if( substr_count($line, '#:#') ) {
                    list($key, $value) = explode('#:#', $line);
                    $this->langVar[trim($key)] = trim($value);
                }
            }
        }
    }



    // validation checks

    private function checkPostRequest()
    {
        $score = 0;

        if( is_array($_POST) ) {
            foreach($_POST as $key => $val) {
                if( filter_var($key, FILTER_SANITIZE_STRING) === 'user_title' ) {
                    $this->userTitle = filter_var($val, FILTER_SANITIZE_STRING);
                    $score += 1;
                }
                if( filter_var($key, FILTER_SANITIZE_STRING) === 'display_name' ) {
                    $this->displayName = filter_var($val, FILTER_SANITIZE_STRING);
                    $score += 2;
                }
            }
            if( !(bool)strlen($this->displayName) ) {
                $score -= 2;
                $this->errState['displayname'] = isset($_POST['display_name']);
            }
        }
        return $score >= 2;
    }

    private function validateInvitation()
    {
        switch( true ) {
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

    private function httpExit(int $code = 404)
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
        $this->client = filter_var($_GET['client'], FILTER_SANITIZE_STRING);
        $this->refId = filter_var($_GET['ref_id'], FILTER_SANITIZE_NUMBER_INT);
        ilInitialisationIlias4GuestLink::initIlias($this->client);
        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;

        try {
            $this->pluginObject = ilObjectFactory::getInstanceByRefId($this->refId);
        } catch (ilDatabaseException $e) {
        } catch (ilObjectNotFoundException $e) {
            $this->httpExit(404);
        }
        $this->pluginConfig = ilMultiVcConfig::getInstance($this->pluginObject->getConnId());

        // exit if not valid
        $this->validateInvitation();

        $this->setUserLangBySvrParam();
        $this->setLangVars();

        // redirect to BBB if valid
        if( $this->checkPostRequest() ) {
            if( !$this->errState['displayname'] ) {
                $this->bbb = new InitBBB($this->pluginConfig->getSvrSalt(), $this->pluginConfig->getSvrPrivateUrl());  // \BigBlueButton\BigBlueButton();
                $this->attendeePwd = $this->pluginObject->getAttendeePwd();
                $this->setMeetingId();
                if( $this->getUrlJoinMeeting() ) {
                    //echo $this->urlJoinMeeting;
                    $this->redirectToVc();
                }
                $this->errState['moderator'] = true;
            }
        }

        $this->setFormElements();
        $this->setHtmlDocument();

    }

    public static function init()
    {
        if( self::$instance instanceof JoinMeetingByGuestLink) {
            return self::$instance;
        }
        return self::$instance = new self();
    }

    public function __toString(): string
    {
        return $this->htmlTpl->get();
    }

}

echo JoinMeetingByGuestLink::init();







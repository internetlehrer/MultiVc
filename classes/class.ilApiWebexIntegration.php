<?php

use ILIAS\DI\Container;


class ilApiWebexIntegration
{
    const CMD_AUTH_Webex = 'authorizeWebexIntegration';

    const DEFAULT_LANG = 'de';

    const PLUGIN_DIR = 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';

    /** @var AuthorizeWebexIntegration|null $instance */
    static private $instance;

    /** @var Container $dic */
    private $dic;

    /** @var ilPlugin $plugin */
    private $plugin;

    /** @var ilObjMultiVcGUI|ilMultiVcConfigGUI $parentObj */
    private $parentObj;

    /** @var bool $isObjGui */
    private $isObjGui = false;

    /** @var bool $isConfigGui */
    private $isConfigGui = false;

    /** @var ilUtil $ilUtil */
    private $ilUtil;

    #/** @var int $iliasRefId */
    #private $iliasRefId = 0;

    /** @var int $iliasConnId */
    private $iliasConnId = 0;

    /** @var string $iliasClientId */
    private $iliasClientId;

    /** @var int|null $iliasRefId */
    private $iliasRefId = null;

    /** @var string $iliasToken */
    private $iliasToken = 'abcdefgh87654321';

    /** @var ilObjMultiVc $pluginObject */
    private $pluginObject;

    /** @var ilMultiVcConfig $pluginConfig */
    private $pluginConfig;

    /** @var string $iliasDomain */
    private $iliasDomain;

    /** @var string $userLang */
    private $userLang = 'de';

    /** @var string[] $isoLangCode */
    private $isoLangCode = [
        'de' => 'de-DE',
        'en' => 'en-US'
    ];

    /** @var array $langVar */
    private $langVar = [];

    /** @var ilTemplate $htmlTpl */
    private $htmlTpl;





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

    private function webexAuthorize()
    {
        $scopes = 'spark:kms meeting:schedules_read meeting:schedules_write meeting:participants_read meeting:participants_write meeting:preferences_read meeting:preferences_write';
        if( $this->isConfigGui ) {
            // WE ARE IN PLUGIN CONFIGURATION GUI
            #$scopes .= ' ';
            #$scopes .= 'meeting:controls_read meeting:controls_write'; #  meeting:controls_read meeting:controls_write admin_schedule_read
            $scopeArr = [
                '',
                'meeting:controls_read',
                'meeting:controls_write',
                'meeting:admin_participants_read',
                'meeting:admin_schedule_read',
                'meeting:admin_schedule_write',
                'meeting:admin_recordings_read',
                'meeting:admin_recordings_write',
            ];
            $scopes .= implode(' ', $scopeArr);
        }

        $redirectUri = ILIAS_HTTP_PATH . substr($this->plugin->getDirectory(), 1) . '/server.php';
        #$gotoAfterReturn = filter_var( ilSession::get('referer'), FILTER_SANITIZE_ENCODED );
        $gotoAfterReturn = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_ENCODED );
        if( $this->isConfigGui && isset($_GET['connId']) ) {
            $gotoAfterReturn .=  filter_var( '&connId=' . $_GET['connId'], FILTER_SANITIZE_ENCODED );
        }

        $queryParam = [
            'response_type' => 'code',
            'client_id'     => $this->pluginConfig->getSvrUsername(),
            'redirect_uri'  => rawurlencode($redirectUri), # rawurlencode(ILIAS_HTTP_PATH . '/server.php'),
            'scope'         => rawurlencode($scopes),
            'state'         => $gotoAfterReturn # rawurlencode('authorizeWebexIntegration_' . $this->iliasConnId  . '_' . $this->iliasToken . '_' . $this->iliasRefId ?? '' . '&' . $params)
        ];

        $urlAuthorizeWebexIntegration = 'https://webexapis.com/v1/authorize?'; # . http_build_query($queryParam); # . '&scope=' . $scope;
        $urlAuthorizeWebexIntegration .= 'client_id=' . $queryParam['client_id'];
        $urlAuthorizeWebexIntegration .= '&response_type=code';
        $urlAuthorizeWebexIntegration .= '&redirect_uri=' . ($queryParam['redirect_uri']);
        $urlAuthorizeWebexIntegration .= '&scope=' . ($queryParam['scope']);
        $urlAuthorizeWebexIntegration .= '&state=' . ($queryParam['state']);

        $this->dic->ctrl()->redirectToURL($urlAuthorizeWebexIntegration);
        exit;
    }

    /**
     * @param string $code
     * @throws ilCurlConnectionException
     */
    private function webexAccess(string $code)
    {
        $redirectUri = ILIAS_HTTP_PATH . substr($this->plugin->getDirectory(), 1) . '/server.php';
        $post = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->pluginConfig->getSvrUsername(),
            'client_secret'   => $this->pluginConfig->getSvrSalt(), # rawurlencode('f0d5215f693b19303b5ff8e6795204e4e466524bd8c1bdb77bbabb2575389102'),
            'code'          => $code,
            'redirect_uri'  => $redirectUri
        ];

        $curl = new ilCurlConnection('https://webexapis.com/v1/access_token');
        $curl->init(false);
        $curl->setopt(CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $curl->setopt(CURLOPT_POST, 1);
        $curl->setopt(CURLOPT_POSTFIELDS, http_build_query($post));
        $curl->setopt(CURLOPT_RETURNTRANSFER, true);
        $response = $curl->exec();
        $curl->close();
        $webex = json_decode($response);
        #echo '<pre>'; var_dump($webex); exit;

        if( $this->isConfigGui ) {
            $this->pluginConfig->setAccessToken($webex->access_token);
            $this->pluginConfig->setRefreshToken($webex->refresh_token);
            $this->pluginConfig->save();
        } else {
            /*
             * isset($_GET['state']) && (bool)($state = filter_var($_GET['state'], FILTER_SANITIZE_ENCODED))
                && substr($state, 0, strlen(self::CMD_AUTH_Webex)) === self::CMD_AUTH_Webex
                &&
             */
            $this->parentObj->object->setAccessToken($webex->access_token);
            $this->parentObj->object->setRefreshToken($webex->refresh_token);
            $this->parentObj->object->updateAccessRefreshToken();
        }

        $parentCmd = $this->isConfigGui ? 'configure' : 'editProperties';
        ilUtil::sendSuccess($this->dic->language()->txt('rep_robj_xmvc_token_stored'), true);
        $this->dic->ctrl()->redirect($this->parentObj, $parentCmd);
    }

    private function webexRefresh()
    {

    }

    private function initByIliasCmd()
    {
        $this->iliasClientId = filter_var($_GET['iliasClientId'], FILTER_SANITIZE_STRING);
        $this->iliasConnId = filter_var($_GET['iliasConnId'], FILTER_SANITIZE_NUMBER_INT);
        $this->iliasRefId = !isset($_GET['iliasRefId']) ? null : filter_var($_GET['iliasRefId'], FILTER_SANITIZE_NUMBER_INT);
        #$this->initIlias();
        $this->pluginConfig = ilMultiVcConfig::getInstance($this->iliasConnId);
    }

    private function initByWebexCmd()
    {
        list($cmd, $this->iliasConnId, $this->iliasToken, $this->iliasRefId) = explode('_', $_GET['state']);
        #$this->initIlias();
        $this->pluginConfig = ilMultiVcConfig::getInstance($this->iliasConnId);
    }


    // Constructor & initz

    private function __construct($parentObj)
    {
        $this->plugin = ilPluginAdmin::getPluginObjectById('xmvc');
        $this->parentObj = $parentObj;
        $this->isConfigGui = !$this->isObjGui = get_class($this->parentObj) === 'ilObjMultiVcGUI';
        $this->iliasConnId = $this->isObjGui ? $this->parentObj->object->getConnId() : $_GET['connId'];
        $this->pluginConfig = ilMultiVcConfig::getInstance($this->iliasConnId);
        $this->iliasRefId = $this->isObjGui ? $this->parentObj->object->getRefId() : null;

        $this->iliasClientId = filter_var($_GET['iliasClientId'], FILTER_SANITIZE_STRING);
        #$this->initIlias();

        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;

        switch (true) {
            case !isset($_GET['code']) && isset($_GET['cmd']) && filter_var($_GET['cmd'], FILTER_SANITIZE_STRING) === self::CMD_AUTH_Webex:
                #$this->initByIliasCmd();
                $this->webexAuthorize();
                break;
            case isset($_GET['code']) && (bool)($code = filter_var($_GET['code'], FILTER_SANITIZE_ENCODED)):
                #$this->initByWebexCmd();
                $this->webexAccess($code);
                break;
            default:
                $this->httpExit();

        }
    }

    public static function init($parentObj)
    {
        if( self::$instance instanceof ilApiWebexIntegration) {
            return self::$instance;
        }
        return self::$instance = new self($parentObj);
    }

}
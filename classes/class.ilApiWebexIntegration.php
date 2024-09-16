<?php

class ilApiWebexIntegration
{
    public const CMD_AUTH_Webex = 'authorizeWebexIntegration';

    public const DEFAULT_LANG = 'de';

    public const PLUGIN_DIR = 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';

    //    /** @var AuthorizeWebexIntegration|null $instance */ //todo
    private static ?ilApiWebexIntegration $instance = null;

    private ILIAS\DI\Container $dic;

    private ilPlugin $plugin;

    /** @var ilObjMultiVcGUI|ilMultiVcConfigGUI $parentObj */
    private $parentObj;

    private bool $isObjGui = false;

    private bool $isConfigGui = false;

    private ilUtil $ilUtil;

    #/** @var int $iliasRefId */
    #private $iliasRefId = 0;

    private int $iliasConnId = 0;

    private string $iliasClientId;

    private ?int $iliasRefId = null;

    private string $iliasToken = 'abcdefgh87654321';

    //    private ilObjMultiVc $pluginObject;

    private ilMultiVcConfig $pluginConfig;

    //    private string $iliasDomain;
    //
    //    private string $userLang = 'de';
    //
    //    /** @var string[] $isoLangCode */
    //    private array $isoLangCode = [
    //        'de' => 'de-DE',
    //        'en' => 'en-US'
    //    ];
    //
    //    private array $langVar = [];
    //
    //    private ilTemplate $htmlTpl;
    //




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

    private function webexAuthorize(): void
    {
        $scopes = 'spark:kms meeting:schedules_read meeting:schedules_write meeting:participants_read meeting:participants_write meeting:preferences_read meeting:preferences_write';
        if($this->isConfigGui) {
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

        $redirectUri = ILIAS_HTTP_PATH . '/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/server.php';
        #$gotoAfterReturn = filter_var( ilSession::get('referer'), FILTER_SANITIZE_ENCODED );
        $gotoAfterReturn = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_ENCODED);
        if($this->isConfigGui && $this->dic->http()->wrapper()->query()->has('connId')) {
            $sConnId = $this->dic->http()->wrapper()->query()->retrieve('connId', $this->dic->refinery()->kindlyTo()->string());
            $gotoAfterReturn .= filter_var('&connId=' . $sConnId, FILTER_SANITIZE_ENCODED);
        }

        $queryParam = [
            'response_type' => 'code',
            'client_id' => $this->pluginConfig->getSvrUsername(),
            'redirect_uri' => rawurlencode($redirectUri), # rawurlencode(ILIAS_HTTP_PATH . '/server.php'),
            'scope' => rawurlencode($scopes),
            'state' => $gotoAfterReturn # rawurlencode('authorizeWebexIntegration_' . $this->iliasConnId  . '_' . $this->iliasToken . '_' . $this->iliasRefId ?? '' . '&' . $params)
        ];

        $urlAuthorizeWebexIntegration = 'https://webexapis.com/v1/authorize?'; # . http_build_query($queryParam); # . '&scope=' . $scope;
        $urlAuthorizeWebexIntegration .= 'client_id=' . $queryParam['client_id'];
        $urlAuthorizeWebexIntegration .= '&response_type=code';
        $urlAuthorizeWebexIntegration .= '&redirect_uri=' . ($queryParam['redirect_uri']);
        $urlAuthorizeWebexIntegration .= '&scope=' . ($queryParam['scope']);
        $urlAuthorizeWebexIntegration .= '&state=' . ($queryParam['state']);

        $this->dic->logger()->root()->debug($urlAuthorizeWebexIntegration);

        $this->dic->ctrl()->redirectToURL($urlAuthorizeWebexIntegration);
        exit;
    }

    /**
     * @throws ilCurlConnectionException
     */
    private function webexAccess(string $code): void
    {
        $redirectUri = ILIAS_HTTP_PATH . '/Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/server.php';
        $post = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->pluginConfig->getSvrUsername(),
            'client_secret' => $this->pluginConfig->getSvrSalt(), # rawurlencode('f0d5215f693b19303b5ff8e6795204e4e466524bd8c1bdb77bbabb2575389102'),
            'code' => $code,
            'redirect_uri' => $redirectUri
        ];
        //        $this->dic->logger()->root()->debug((string) var_dump($post));

        $curl = new ilCurlConnection('https://webexapis.com/v1/access_token');
        $curl->init(false);
        $curl->setopt(CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $curl->setopt(CURLOPT_POST, 1);
        $curl->setopt(CURLOPT_POSTFIELDS, http_build_query($post));
        $curl->setopt(CURLOPT_RETURNTRANSFER, true);
        //Add Proxy
        if (\ilProxySettings::_getInstance()->isActive()) {
            $proxyHost = \ilProxySettings::_getInstance()->getHost();
            $proxyPort = \ilProxySettings::_getInstance()->getPort();
            $proxyURL = $proxyHost . ":" . $proxyPort;
            $curl->setopt(CURLOPT_PROXY, $proxyURL);
        }
        $response = $curl->exec();
        $curl->close();
        $webex = json_decode($response);
        #echo '<pre>'; var_dump($webex); exit;

        $this->dic->logger()->root()->debug($response);

        if($this->isConfigGui) {
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
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt('rep_robj_xmvc_token_stored'), true);
        $this->dic->ctrl()->redirect($this->parentObj, $parentCmd);
    }

    private function webexRefresh(): void
    {

    }

    private function initByIliasCmd(): void
    {
        $this->iliasClientId = $this->dic->http()->wrapper()->query()->retrieve('iliasClientId', $this->dic->refinery()->kindlyTo()->string());
        $this->iliasConnId = $this->dic->http()->wrapper()->query()->retrieve('iliasConnId', $this->dic->refinery()->kindlyTo()->int());
        //        $this->iliasRefId = !isset($_GET['iliasRefId']) ? null : filter_var($_GET['iliasRefId'], FILTER_SANITIZE_NUMBER_INT);
        $this->iliasRefId = null;
        if ($this->dic->http()->wrapper()->query()->has('iliasRefId')) {
            $this->iliasRefId = $this->dic->http()->wrapper()->query()->retrieve('iliasRefId', $this->dic->refinery()->kindlyTo()->int());
        }
        #$this->initIlias();
        $this->pluginConfig = ilMultiVcConfig::getInstance($this->iliasConnId);
    }

    private function initByWebexCmd(): void
    {
        //        list($cmd, $this->iliasConnId, $this->iliasToken, $this->iliasRefId) = explode('_', $_GET['state']);
        list($cmd, $this->iliasConnId, $this->iliasToken, $this->iliasRefId) = explode('_', $this->dic->http()->wrapper()->query()->retrieve('state', $this->dic->refinery()->kindlyTo()->string()));
        #$this->initIlias();
        $this->pluginConfig = ilMultiVcConfig::getInstance($this->iliasConnId);
    }


    // Constructor & initz

    private function __construct($parentObj)
    {
        global $DIC;
        $this->dic = $DIC;
        //        $this->plugin = ilPluginAdmin::getPluginObjectById('xmvc');
        $this->parentObj = $parentObj;
        $this->isConfigGui = !$this->isObjGui = get_class($this->parentObj) === 'ilObjMultiVcGUI';
        $this->iliasConnId = $this->isObjGui ? $this->parentObj->object->getConnId() : $this->dic->http()->wrapper()->query()->retrieve('connId', $this->dic->refinery()->kindlyTo()->int());
        $this->pluginConfig = ilMultiVcConfig::getInstance($this->iliasConnId);
        $this->iliasRefId = $this->isObjGui ? $this->parentObj->object->getRefId() : null;

        //        $this->iliasClientId = $this->dic->http()->wrapper()->query()->retrieve('iliasClientId', $this->dic->refinery()->kindlyTo()->string());
        #$this->initIlias();

        $this->dic->logger()->root()->debug($_SERVER['REQUEST_URI']);
        switch (true) {
            case $this->dic->http()->wrapper()->query()->has('code') && $this->dic->http()->wrapper()->query()->retrieve('code', $this->dic->refinery()->kindlyTo()->string()) != "":// && (bool)($code = filter_var($this->dic->http()->wrapper()->query()->retrieve('code', $this->dic->refinery()->kindlyTo()->string()), FILTER_SANITIZE_ENCODED)):
                #$this->initByWebexCmd();
                $code = filter_var($this->dic->http()->wrapper()->query()->retrieve('code', $this->dic->refinery()->kindlyTo()->string()), FILTER_SANITIZE_ENCODED);
                $this->dic->logger()->root()->debug('webexAccess' . $code);
                $this->webexAccess($code);
                break;
                //!$this->dic->http()->wrapper()->query()->has('code') &&
            case $this->dic->http()->wrapper()->query()->has('cmd') && $this->dic->http()->wrapper()->query()->retrieve('cmd', $this->dic->refinery()->kindlyTo()->string()) === self::CMD_AUTH_Webex:
                #$this->initByIliasCmd();
                $this->dic->logger()->root()->debug('webexAuthorize');
                $this->webexAuthorize();
                break;
            default:
                $this->httpExit();

        }
    }

    public static function init($parentObj): ?ilApiWebexIntegration
    {
        if(self::$instance instanceof ilApiWebexIntegration) {
            return self::$instance;
        }
        return self::$instance = new self($parentObj);
    }

}

<?php

use ILIAS\DI\Container;

/**
 * MultiVc mail notification class
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 *
 */
class ilMultiVcMailNotification #extends ilMailNotification
{
    #region PROPERTIES
    // v Notifications affect members & co. v
    const TYPE_STATUS_CHANGED = 24;

    const PROC_PENDING = 0;
    const PROC_IN_PROGRESS = 1;
    const PROC_SUCCEEDED = 2;
    const PROC_FAILED = 3;

    #endregion PROPERTIES

    /**
     * Notifications which are not affected by "mail_grp_member_notification"
     * setting because they addresses admins
     */
    protected $permanent_enabled_notifications = array(
        self::TYPE_STATUS_CHANGED
    );

    private $force_sending_mail = false;

    /**
     * @var ilLogger
     */
    protected $logger;

    /**
     * @var ilSetting
     */
    protected $settings;

    /** @var array $moduleParam */
    protected $moduleParam = [];

    /** @var Container $DIC */
    private $dic;
    /**
     * @var mixed
     */
    private $templateService;
    /**
     * @var ilTemplate
     */
    private $tpl;
    /**
     * @var ilCtrl
     */
    private $ctrl;
    /**
     * @var ilLanguage
     */
    private $lng;

    /**
     * @var ilObjUser
     */
    private $user;
    /**
     * @var ilTabsGUI
     */
    private $tabs;

    /**
     * @var ilToolbarGUI
     */
    private $toolbar;
    /**
     * @var ilRbacSystem
     */
    private $rbacsystem;

    /**
     * @var ilFormatMail
     */
    private $umail;
    /**
     * @var ilMailbox
     */
    private $mbox;

    /**
     * @var ilFileDataMail
     */
    private $mfile;
    /**
     * @var ilMailBodyPurifier
     */
    private $purifier;


    public function __construct()
    {
        global $DIC; /** @var Container $DIC */

        #parent::__construct();

        $this->dic = $DIC;
        $this->logger = $this->dic->logger();
        $this->settings = $this->dic->settings();

        $templateService = $DIC['mail.texttemplates.service'];
        $this->templateService = $templateService;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->user = $DIC->user();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->rbacsystem = $DIC->rbac()->system();

        $this->umail = new ilFormatMail($this->user->getId());
        $this->mfile = new ilFileDataMail($this->user->getId());
        $this->mbox = new ilMailbox($this->user->getId());

        $bodyPurifier = new ilMailBodyPurifier();

        $this->purifier = $bodyPurifier;

        if (isset($_POST['mobj_id']) && (int) $_POST['mobj_id']) {
            $_GET['mobj_id'] = $_POST['mobj_id'];
        }

        if (!(int) $_GET['mobj_id']) {
            $_GET['mobj_id'] = $this->mbox->getInboxFolder();
        }
        $_GET['mobj_id'] = (int) $_GET['mobj_id'];

        #$this->ctrl->saveParameter($this, 'mobj_id');
        #$this->moduleParam = $parameters;
        //$this->crsObj = $crsObj;
    }

    public function setParameters(array $parameters): ilMultiVcMailNotification
    {
        $this->moduleParam = $parameters;
        $this->moduleParam['message'] = json_decode($this->moduleParam['message'], 1);

        $_POST['m_message'] = ilUtil::securePlainString($this->moduleParam['message']['body']);
        $_POST['m_subject'] = ilUtil::securePlainString($this->moduleParam['message']['subject']);
        $_POST['rcp_to'] = ilObjUser::_lookupLogin((int)$this->moduleParam['recipient']);
        $_POST['rcp_cc'] =
        $_POST['rcp_bcc'] = '';

        return $this;
    }

    public function sendMessage(): bool
    {
        $m_type = $_POST["m_type"] ?? array("normal");

        $message = (string) $_POST['m_message'];

        $mailBody = new ilMailBody($message, $this->purifier);

        $sanitizedMessage = rawurldecode($mailBody->getContent());

        $files = $this->decodeAttachmentFiles(isset($_POST['attachments']) ? (array) $_POST['attachments'] : array());

        $mailer = $this->umail
            ->withContextId(ilMailFormCall::getContextId() ? ilMailFormCall::getContextId() : '')
            ->withContextParameters(is_array(ilMailFormCall::getContextParameters()) ? ilMailFormCall::getContextParameters() : []);

        $mailer->setSaveInSentbox(true);

        if( (int)ILIAS_VERSION_NUMERIC < 6 ) {
            if ($errors = $mailer->sendMail(
                ilUtil::securePlainString($_POST['rcp_to']),
                ilUtil::securePlainString($_POST['rcp_cc']),
                ilUtil::securePlainString($_POST['rcp_bcc']),
                ilUtil::securePlainString($_POST['m_subject']),
                $sanitizedMessage,
                $files,
                $m_type,
                (int) $_POST['use_placeholders']
            )
            ) {
                $_POST['attachments'] = $files;
                $this->showSubmissionErrors($errors);
            } else {
                $mailer->savePostData($this->user->getId(), array(), "", "", "", "", "", "", "", "");
            }
        } else {
            if ($errors = $mailer->enqueue(
                ilUtil::securePlainString($_POST['rcp_to']),
                ilUtil::securePlainString($_POST['rcp_cc']),
                ilUtil::securePlainString($_POST['rcp_bcc']),
                ilUtil::securePlainString($_POST['m_subject']),
                $sanitizedMessage,
                $files,
                (int) $_POST['use_placeholders']
            )
            ) {
                $_POST['attachments'] = $files;
                $this->showSubmissionErrors($errors);
            } else {
                $mailer->savePostData($this->user->getId(), array(), "", "", "", "", "", "", "", "");
            }
        }

        return true;
        #$this->showForm();
    }

    protected function decodeAttachmentFiles(array $files): array
    {
        $decodedFiles = array();

        foreach ($files as $value) {
            if (is_file($this->mfile->getMailPath() . '/' . $this->user->getId() . '_' . urldecode($value))) {
                $decodedFiles[] = urldecode($value);
            }
        }

        return $decodedFiles;
    }

    /**
     * @param $errors ilMailError[]
     */
    protected function showSubmissionErrors(array $errors)
    {
        $formatter = new ilMailErrorFormatter($this->lng);
        $formattedErrors = $formatter->format($errors);

        if (strlen($formattedErrors) > 0) {
            ilUtil::sendFailure($formattedErrors);
        }
    }

    public function send() : bool
    {
        return $this->sendMessage();
        /*
        $mailContent = [];
        #foreach( $recipients as $rcp ) {
            $user = new ilObjUser($this->moduleParam['user_id']);
            #$mailContent = $this->prepareContent($user);
            $this->initMail();
        $this->setSender($this->moduleParam['user_id']);

        $this->initLanguage($this->moduleParam['user_id']);
        #$this->setLanguage($user->getCurrentLanguage());
        $this->setSubject($this->moduleParam['message']['subject']);
        $this->setBody(ilMail::getSalutation(
            $this->moduleParam['recipient'],
            $this->getLanguage()
        ));
        $this->appendBody("\n\n");
        $this->appendBody(
            $this->moduleParam['message']['body']
        );

        $this->getMail()->appendInstallationSignature(true);
        #$this->sendMail(array($this->moduleParam['recipient']), array('system'));

        $this->sendMail(array($this->moduleParam['recipient']), false);

        return true;
        */
    }

    public function forceSendingMail(bool $status)
    {
        $this->force_sending_mail = $status;
    }


    protected function initLanguage($usr_id)
    {
    }


    /*
    protected function isNotificationTypeEnabled(int $type) : bool
    {
        return (
            $this->force_sending_mail ||
            $this->settings->get('mail_lso_member_notification', true) ||
            in_array($type, $this->permanent_enabled_notifications)
        );
    }
    */
}

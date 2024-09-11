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
    public const TYPE_STATUS_CHANGED = 24;

    public const PROC_PENDING = 0;
    public const PROC_IN_PROGRESS = 1;
    public const PROC_SUCCEEDED = 2;
    public const PROC_FAILED = 3;

    #endregion PROPERTIES

    /**
     * Notifications which are not affected by "mail_grp_member_notification"
     * setting because they addresses admins
     */
    protected array $permanent_enabled_notifications = array(
        self::TYPE_STATUS_CHANGED
    );

    //    private bool $force_sending_mail = false;

    //    protected $logger;

    //    protected ilSetting $settings;

    protected array $moduleParam = [];

    private Container $dic;
    /**
     * @var mixed
     */
    //    private $templateService;
    //    private $ctrl;

    private ilLanguage $lng;

    private ilObjUser $user;

    private ilFormatMail $umail;

    //    private ilMailbox $mbox;

    //    private ilFileDataMail $mfile;

    private ilMailBodyPurifier $purifier;


    public function __construct()
    {
        global $DIC; /** @var Container $DIC */

        #parent::__construct();

        $this->dic = $DIC;
        //        $this->logger = $this->dic->logger();
        //        $this->settings = $this->dic->settings();

        //        $templateService = $DIC['mail.texttemplates.service'];
        //        $this->templateService = $templateService;
        //        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->user = $DIC->user();

        $this->umail = new ilFormatMail($this->user->getId());
        //        $this->mfile = new ilFileDataMail($this->user->getId());
        //        $this->mbox = new ilMailbox($this->user->getId());

        $bodyPurifier = new ilMailBodyPurifier();

        $this->purifier = $bodyPurifier;

        //        if (isset($_POST['mobj_id']) && (int) $_POST['mobj_id']) {
        //            $_GET['mobj_id'] = $_POST['mobj_id'];
        //        }
        //
        //        if (!(int) $_GET['mobj_id']) {
        //            $_GET['mobj_id'] = $this->mbox->getInboxFolder();
        //        }
        //        $_GET['mobj_id'] = (int) $_GET['mobj_id'];

        #$this->ctrl->saveParameter($this, 'mobj_id');
        #$this->moduleParam = $parameters;
        //$this->crsObj = $crsObj;
    }

    //    public function setParameters(array $parameters): ilMultiVcMailNotification
    //    {
    //        $this->moduleParam = $parameters;
    //        $this->moduleParam['message'] = json_decode($this->moduleParam['message'], 1);
    //
    //        $_POST['m_message'] = ilUtil::securePlainString($this->moduleParam['message']['body']);
    //        $_POST['m_subject'] = ilUtil::securePlainString($this->moduleParam['message']['subject']);
    //        $_POST['rcp_to'] = ilObjUser::_lookupLogin((int)$this->moduleParam['recipient']);
    //        $_POST['rcp_cc'] =
    //        $_POST['rcp_bcc'] = '';
    //
    //        return $this;
    //    }

    public function sendMessage(array $parameters): bool
    {
        $this->moduleParam = $parameters;
        $this->moduleParam['message'] = json_decode($this->moduleParam['message'], 1);
        //        $m_type = $_POST["m_type"] ?? array("normal");

        $message = stripslashes($this->moduleParam['message']['body']);

        $mailBody = new ilMailBody($message, $this->purifier);

        $sanitizedMessage = rawurldecode($mailBody->getContent());

        //        $files = $this->decodeAttachmentFiles(isset($_POST['attachments']) ? (array) $_POST['attachments'] : array());

        $mailer = $this->umail
            ->withContextId(ilMailFormCall::getContextId() ? ilMailFormCall::getContextId() : '')
            ->withContextParameters(is_array(ilMailFormCall::getContextParameters()) ? ilMailFormCall::getContextParameters() : []);

        $mailer->setSaveInSentbox(true);

        if ($errors = $mailer->enqueue(
            ilObjUser::_lookupLogin((int)$this->moduleParam['recipient']),
            '',
            '',
            stripslashes($this->moduleParam['message']['subject']),
            $sanitizedMessage,
            [],
            false
        )
        ) {
            $this->showSubmissionErrors($errors);
        } else {
//            $mailer->savePostData($this->user->getId(), [], ilObjUser::_lookupLogin((int)$this->moduleParam['recipient']), "", "", stripslashes($this->moduleParam['message']['subject']), $sanitizedMessage, false, "", []);
            $mailer->persistToStage(
                $this->user->getId(),
                [],
                ilObjUser::_lookupLogin((int)$this->moduleParam['recipient']),
                '',
                '',
                stripslashes($this->moduleParam['message']['subject']),
                $sanitizedMessage
            );
        }

        return true;
        #$this->showForm();
    }

    //    protected function decodeAttachmentFiles(array $files): array
    //    {
    //        $decodedFiles = array();
    //
    //        foreach ($files as $value) {
    //            if (is_file($this->mfile->getMailPath() . '/' . $this->user->getId() . '_' . urldecode($value))) {
    //                $decodedFiles[] = urldecode($value);
    //            }
    //        }
    //
    //        return $decodedFiles;
    //    }

    /**
     * @param $errors ilMailError[]
     */
    protected function showSubmissionErrors(array $errors): void
    {
        $formatter = new ilMailErrorFormatter($this->lng);
        $formattedErrors = $formatter->format($errors);

        if (strlen($formattedErrors) > 0) {
            $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $formattedErrors);
        }
    }

    //    public function send() : bool
    //    {
    //        return $this->sendMessage();
    //        /*
    //        $mailContent = [];
    //        #foreach( $recipients as $rcp ) {
    //            $user = new ilObjUser($this->moduleParam['user_id']);
    //            #$mailContent = $this->prepareContent($user);
    //            $this->initMail();
    //        $this->setSender($this->moduleParam['user_id']);
    //
    //        $this->initLanguage($this->moduleParam['user_id']);
    //        #$this->setLanguage($user->getCurrentLanguage());
    //        $this->setSubject($this->moduleParam['message']['subject']);
    //        $this->setBody(ilMail::getSalutation(
    //            $this->moduleParam['recipient'],
    //            $this->getLanguage()
    //        ));
    //        $this->appendBody("\n\n");
    //        $this->appendBody(
    //            $this->moduleParam['message']['body']
    //        );
    //
    //        $this->getMail()->appendInstallationSignature(true);
    //        #$this->sendMail(array($this->moduleParam['recipient']), array('system'));
    //
    //        $this->sendMail(array($this->moduleParam['recipient']), false);
    //
    //        return true;
    //        */
    //    }

    //    public function forceSendingMail(bool $status) : void
    //    {
    //        $this->force_sending_mail = $status;
    //    }


    //    protected function initLanguage($usr_id) : void
    //    {
    //    }


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

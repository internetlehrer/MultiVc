<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE
 */

use ILIAS\DI\Container;

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * multiVC plugin: vc types table GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */
class ilMultiVcConnTableGUI extends ilTable2GUI
{
    private bool $webex = false;

    private Container $dic;


    /**
     * Constructor
     * @param object        parent object
     * @param string        parent command
     * @throws ilPluginException
     */
    public function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '')
    {
        // this uses the cached plugin object
        //		$this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'MultiVc');

        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;
    }

    /**
     * Init the table with some configuration
     *
     *
     * @access public
     * @param $a_parent_obj
     */
    public function init($a_parent_obj)
    {
        $this->addColumn($this->dic->language()->txt('id'), 'type_id', '10%');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_conf_title'), 'title', '30%');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_conf_availability'), 'availability', '20%');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_untrashed_usages'), 'usages', '10%');
        $this->addColumn($this->dic->language()->txt('actions'), '', '20%');
        $this->setEnableHeader(true);
        $this->setFormAction($this->dic->ctrl()->getFormAction($a_parent_obj));
        $this->addCommandButton('createMulitVcConn', $this->dic->language()->txt('rep_robj_xmvc_create_type'));
        // ToDo: check
        // $this->addCommandButton('viewLogs', $lng->txt('rep_robj_xxcf_view_logs'));

        $this->setRowTemplate('tpl.types_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->getMyDataFromDb();
    }

    /**
     * Get data and put it into an array
     */
    public function getMyDataFromDb()
    {
        //todo?
        //    	$this->plugin_object->includeClass('class.ilMultiVcConfig.php');
        // get types data with usage info
        $data = ilMultiVcConfig::_getMultiVcConnData(true);
        $this->setDefaultOrderField('conn_id');
        $this->setDefaultOrderDirection('asc');
        $this->setData($data);
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set): void
    {
        $ilCtrl = $this->dic->ctrl();

        $ilCtrl->setParameter($this->parent_obj, 'conn_id', $a_set['conn_id']);

        $this->tpl->setVariable('TXT_ID', $a_set['conn_id']);
        $this->tpl->setVariable('TXT_TITLE', $a_set['title']);
        $this->tpl->setVariable('TXT_AVAILABILITY', $this->dic->language()->txt('rep_robj_xmvc_conf_availability_' . $a_set['availability']));
        $this->tpl->setVariable('TXT_USAGES', (int) $a_set['usages']);

        if($a_set['showcontent'] === 'webex' && $a_set['auth_method'] === 'admin') {
            $this->setWebex(true);
            $hrefSetAuth = $this->dic->ctrl()->getLinkTarget($this->getParentObject(), 'authorizeWebexIntegration');
            $hrefSetAuth .= '&connId=' . $a_set['conn_id'];
            #$linkInteg = 'https://webexapis.com/v1/authorize?client_id=C1cc0ecc0b6c4cf4adb19a0754a611456a4d8b38fced968c8631dba27c300fdc0&response_type=code&redirect_uri=https%3A%2F%2Fcass.aptum.net%2Frelease_6_webex%2FCustomizing%2Fglobal%2Fplugins%2FServices%2FRepository%2FRepositoryObject%2FMultiVc%2Fserver.php&scope=spark%3Akms%20meeting%3Aschedules_read%20meeting%3Aparticipants_read%20meeting%3Apreferences_write%20meeting%3Apreferences_read%20meeting%3Aparticipants_write%20meeting%3Aschedules_write&state=set_state_here';
            $this->tpl->setVariable('TXT_INTEGRATION', $this->dic->language()->txt('rep_robj_xmvc_authorize'));
            $this->tpl->setVariable('LINK_INTEGRATION', $hrefSetAuth);
            /*
            $this->tpl->setVariable('LINK_INTEGRATION', ILIAS_HTTP_PATH . substr($this->plugin_object->getDirectory(), 1) .
                '/server.php?cmd=authorizeWebexIntegration&iliasConnId=' . $a_set['conn_id'] . '&iliasClientId=' . CLIENT_ID);
            */
            #$this->tpl->setVariable('LINK_INTEGRATION', $linkInteg);
            //$this->tpl->setVariable('ID_INTEGRATION', $a_set['svrusername']);
        } else {
            $this->tpl->setVariable('CSS_HIDE_INTEGRATION', 'hidden');
        }

        $this->tpl->setVariable('TXT_EDIT', $this->dic->language()->txt('edit'));
        $this->tpl->setVariable('LINK_EDIT', $ilCtrl->getLinkTarget($this->parent_obj, 'editMultiVcConn'));

        if ($a_set['usages'] == 0) {
            $this->tpl->setVariable('TXT_DELETE', $this->dic->language()->txt('delete'));
            $this->tpl->setVariable('LINK_DELETE', $ilCtrl->getLinkTarget($this->parent_obj, 'deleteMultiVcConn'));
        }
    }

    public function isWebex(): bool
    {
        return $this->webex;
    }

    public function setWebex(bool $webex)
    {
        $this->webex = $webex;
    }


}

<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * multiVC plugin: vc types table GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
class ilMultiVcConnTableGUI extends ilTable2GUI {

    /** @var ilPlugin|mixed|null $plugin_object */
    protected $plugin_object;
    /**
     * Constructor
     * 
     * @param 	object		parent object
     * @param 	string		parent command
     */
    function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '') 
    {
    	// this uses the cached plugin object
		$this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'MultiVc');

		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
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
        global $ilCtrl, $lng;

        $this->addColumn($lng->txt('id'), 'type_id', '10%');
        $this->addColumn($this->plugin_object->txt('conf_title'), 'title', '30%');
        $this->addColumn($this->plugin_object->txt('conf_availability'), 'availability', '20%');
        $this->addColumn($this->plugin_object->txt('untrashed_usages'), 'usages', '10%');
        $this->addColumn($lng->txt('actions'), '', '20%');
        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->addCommandButton('createMulitVcConn', $lng->txt('rep_robj_xmvc_create_type'));
		// ToDo: check
        // $this->addCommandButton('viewLogs', $lng->txt('rep_robj_xxcf_view_logs'));

        $this->setRowTemplate('tpl.types_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->getMyDataFromDb();
    }

    /**
     * Get data and put it into an array
     */
    function getMyDataFromDb() 
    {
    	$this->plugin_object->includeClass('class.ilMultiVcConfig.php');
        // get types data with usage info
        $data = ilMultiVcConfig::_getMultiVcConnData(true);
        $this->setDefaultOrderField('conn_id');
        $this->setDefaultOrderDirection('asc');
        $this->setData($data);
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set) 
    {
        global $lng, $ilCtrl;

        $ilCtrl->setParameter($this->parent_obj, 'conn_id', $a_set['conn_id']);

        $this->tpl->setVariable('TXT_ID', $a_set['conn_id']);
        $this->tpl->setVariable('TXT_TITLE', $a_set['title']);
        $this->tpl->setVariable('TXT_AVAILABILITY', $this->plugin_object->txt('conf_availability_' . $a_set['availability']));
        $this->tpl->setVariable('TXT_USAGES', (int) $a_set['usages']);

        $this->tpl->setVariable('TXT_EDIT', $lng->txt('edit'));
        $this->tpl->setVariable('LINK_EDIT', $ilCtrl->getLinkTarget($this->parent_obj, 'editMultiVcConn'));

        if ($a_set['usages'] == 0) {
            $this->tpl->setVariable('TXT_DELETE', $lng->txt('delete'));
            $this->tpl->setVariable('LINK_DELETE', $ilCtrl->getLinkTarget($this->parent_obj, 'deleteMultiVcConn'));
        }
    }

}

?>
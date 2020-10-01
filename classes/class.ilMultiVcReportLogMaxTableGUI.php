<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * MultiVc plugin: report logged max concurrent values table GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
class ilMultiVcReportLogMaxTableGUI extends ilTable2GUI {

    private $plugin_object;

    /**
     * ilMultiVcReportLogMaxTableGUI constructor.
     * @param $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
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
     */
    public function init($a_parent_obj) 
    {
        global $ilCtrl, $lng;

        $this->addColumn($lng->txt('year'), 'year', '15%');
        $this->addColumn($lng->txt('month'), 'month', '15%');
        $this->addColumn($lng->txt('day'), 'day', '15%');
        $this->addColumn($lng->txt('hour'), 'hour', '15%');
        $this->addColumn($this->plugin_object->txt('max_meetings'), 'max_meetings', '20%');
        $this->addColumn($this->plugin_object->txt('max_users'), 'max_users', '20%');
        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->addCommandButton('downloadCsv', $lng->txt('export'));
        //$this->addCommandButton('createType', $lng->txt('rep_robj_xxcf_create_type'));
		// ToDo: check
        // $this->addCommandButton('viewLogs', $lng->txt('rep_robj_xxcf_view_logs'));
        $this->setRowTemplate('tpl.report_log_max_concurrent_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->getMyDataFromDb();

    }

    public function downloadCsv() {
        $this->exportData(2, true);
    }

    /**
     * Get data and put it into an array
     */
    function getMyDataFromDb() 
    {
        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilObjMultiVc.php');
    	//$this->plugin_object->includeClass('class.ilObjMultiVc.php');
        // get types data with usage info
        $db = new ilObjMultiVc();
        $this->setDefaultOrderField('year');
        $this->setDefaultOrderDirection('desc');
        $this->setData($db->getMaxConcurrent());
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set) 
    {
        global $lng, $ilCtrl;

        $ilCtrl->setParameter($this->parent_obj, 'year', $a_set['year']);

        $this->tpl->setVariable('TXT_YEAR', $a_set['year']);
        $this->tpl->setVariable('TXT_MONTH', $a_set['month']);
        $this->tpl->setVariable('TXT_DAY', $a_set['day']);
        $this->tpl->setVariable('TXT_HOUR', $a_set['hour']);
        $this->tpl->setVariable('TXT_MAX_MEETINGS', $a_set['max_meetings']);
        $this->tpl->setVariable('TXT_MAX_USERS', $a_set['max_users']);
    }

}

?>
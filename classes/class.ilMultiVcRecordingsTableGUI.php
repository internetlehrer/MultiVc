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
class ilMultiVcRecordingsTableGUI extends ilTable2GUI {

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

            /*
        $this->addColumn($lng->txt('year'), 'year', '15%');
        $this->addColumn($lng->txt('month'), 'month', '15%');
        $this->addColumn($lng->txt('day'), 'day', '15%');
        $this->addColumn($lng->txt('hour'), 'hour', '15%');
        $this->addColumn($this->plugin_object->txt('max_meetings'), 'max_meetings', '20%');
        $this->addColumn($this->plugin_object->txt('max_users'), 'max_users', '20%');
        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->addCommandButton('downloadCsv', $lng->txt('export_csv'));
        //$this->addCommandButton('createType', $lng->txt('rep_robj_xxcf_create_type'));
        // $this->addCommandButton('viewLogs', $lng->txt('rep_robj_xxcf_view_logs'));
        $this->setRowTemplate('tpl.report_log_max_concurrent_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->getMyDataFromDb();
        */
    }

    public function initColumns($lng)
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */

        //$lng = $DIC['lng'];
        //$lng->loadLanguageModule('date');

        //var_dump($lng); exit;

        $this->addColumn($lng->txt('select'), '', '5%');
        $this->addColumn($lng->txt('starttime'), 'starttime', '');
        $this->addColumn($lng->txt('endtime'), 'endtime', '');
        $this->addColumn($lng->txt('playback'), 'playback', '');
        $this->addColumn($lng->txt('download'), 'download', '');


    }

    /**
     * Fill a single data row.
     * @param array $a_set
     */
    protected function fillRow($a_set) 
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */
        $lng = $DIC['lng'];
        $ilCtrl = $DIC['ilCtrl'];

        //$ilCtrl->setParameter($this->parent_obj, 'year', $a_set['year']);

        $this->tpl->setVariable('ROWSELECTOR', $a_set['rowSelector']);
        $this->tpl->setVariable('STARTTIME', $a_set['startTime']);
        $this->tpl->setVariable('ENDTIME', $a_set['endTime']);
        $this->tpl->setVariable('PLAYBACK', $a_set['playback']);
        $this->tpl->setVariable('DOWNLOAD', $a_set['download']);

    }


    public function addRowSelector( array $a_data ): array
    {
        foreach ($a_data as $key => $data) {
            $checkbox = new ilCheckboxInputGUI('', 'rec_id[]');
            $checkbox->setValue($key);
            $checkbox->setChecked( isset($_POST) && isset($_POST['rec_id']) && array_search($a_data[$key], $_POST['rec_id']) );
            $a_data[$key]['rowSelector'] = $checkbox->render();
        } // EOF foreach ($a_data as $a_datum)
        return $a_data;
    }


}

?>
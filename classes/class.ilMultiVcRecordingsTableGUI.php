<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

use ILIAS\DI\Container;

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * MultiVc plugin: report logged max concurrent values table GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
class ilMultiVcRecordingsTableGUI extends ilTable2GUI {

    /** @var Container $dic */
    private $dic;

    private $plugin_object;

    /**
     * ilMultiVcReportLogMaxTableGUI constructor.
     * @param $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     */
    function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '') 
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;
    	// this uses the cached plugin object
		$this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'MultiVc');

        $this->setId('table_recordings');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        $this->initColumns($this->plugin_object);

        $this->setFormAction($this->dic->ctrl()->getFormAction($this->parent_obj, 'showContent'));
        $this->setEnableHeader(true);

        $this->setExternalSorting(false);
        $this->setExternalSegmentation(false);
        $this->setShowRowsSelector(false);

        $this->setDefaultOrderField('START_TIME'); # display_name join_time
        $this->setDefaultOrderDirection('asc');
        $this->enable('sort');

        $this->setRowTemplate('tpl.recordings_table_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->setEnableNumInfo(false);

        $this->addCommandButton('confirmDeleteRecords', $DIC->language()->txt('delete'));

    }

    public function initColumns($lng)
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */

        $this->addColumn($lng->txt('select'), '', '5%');
        $this->addColumn($lng->txt('starttime'), 'START_TIME', '');
        $this->addColumn($lng->txt('endtime'), 'END_TIME', '');
        $this->addColumn($lng->txt('playback'), 'playback', '');
        $this->addColumn($lng->txt('download'), 'download', '');
    }

    /**
     * Fill a single data row.
     * @param array $a_set
     * @throws ilDateTimeException
     */
    protected function fillRow($a_set) 
    {
        global $DIC; /** @var \ILIAS\DI\Container $DIC */
        $lng = $DIC['lng'];
        $ilCtrl = $DIC['ilCtrl'];

        $a_set['START_TIME'] = new ilDateTime($a_set['START_TIME'], IL_CAL_UNIX);
        $a_set['END_TIME'] = new ilDateTime($a_set['END_TIME'], IL_CAL_UNIX);

        $this->tpl->setVariable('ROWSELECTOR', $a_set['rowSelector']);
        $this->tpl->setVariable('STARTTIME', ilDatePresentation::formatDate($a_set['START_TIME']));
        $this->tpl->setVariable('ENDTIME', ilDatePresentation::formatDate($a_set['END_TIME']));
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
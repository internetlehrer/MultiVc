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
class ilMultiVcTableGUIListMeetings extends ilTable2GUI {

    /** @var Container $dic */
    private $dic;

    /** @var ilPlugin|null $plugin_object */
    private $plugin_object;

    /** @var int|null $refId */
    protected $refId;

    /** @var ilDateTime|null $dateStart */
    private $dateStart = null;

    /** @var ilDateTime|null $dateEnd */
    private $dateEnd = null;

    /** @var bool $keepFilterValues */
    private $keepFilterValues = false;

    /** @var ilDateDurationInputGUI $filterItemDateDuration */
    private $filterItemDateDuration;



    /**
     * ilMultiVcReportLogMaxTableGUI constructor.
     * @param ilObjMultiVcGUI|ilMultiVcConfigGUI|object $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     * @throws Exception
     */
    function __construct(object $a_parent_obj, $a_parent_cmd = '', $a_template_context = '')
    {
        global $DIC; /** @var Container $DIC */
        \iljQueryUtil::initjQuery();
        #\ilYuiUtil::initPanel();
        #\ilYuiUtil::initOverlay();

        $this->dic = $DIC;
        $this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'MultiVc');
        $this->parent_obj = $a_parent_obj;
        // meetings shown in infoScreen
        if( $this->parent_obj instanceof ilObjMultiVcGUI ) {
            $this->refId = (int)$this->parent_obj->object->ref_id;
        }

        $this->setId('list_meetings');
        #$this->setFormName('user_log');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->initColumns();
        #$this->setTitle($this->plugin_object->txt('scheduled_meeting_overview'));
        #$this->setFormAction($this->dic->ctrl()->getFormAction($this->parent_obj));
        $this->setEnableHeader(true);

        $this->setExternalSorting(false);
        $this->setExternalSegmentation(false);
        $this->setShowRowsSelector(false);

        $this->setDefaultOrderField('start_time'); # display_name join_time
        $this->setDefaultOrderDirection('asc');
        //$this->disable('sort');

        $this->setRowTemplate('tpl.list_meetings_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        /*
        $this->initFilterDateDuration();
        $this->setFilterCommand('applyFilterScheduledMeetings');
        $this->setResetCommand('resetFilterScheduledMeetings');
        $this->setDefaultFilterVisiblity(false);
        $this->setDisableFilterHiding(true);
        */
        $this->setEnableNumInfo(false);
        $this->getDataFromDb();
    }

    /**
     * Init the table columns
     *
     *
     * @access public
     * @param int|null $refId
     * @throws Exception
     */
    private function initColumns()
    {
        global $ilCtrl, $lng;

        // Columns
        $wS = '10%';
        $wM = '25%';
        $wL = '60%';
        $this->addColumn($this->plugin_object->txt('start_time'), 'START_TIME', 'auto');
        $this->addColumn($this->plugin_object->txt('end_time'), 'END_TIME', 'auto');
        $this->addColumn($lng->txt('title'), 'TITLE', $wL);
    }

    /**
     * Get data and put it into an array
     * @throws ilDateTimeException
     */
    private function getDataFromDb()
    {
        require_once dirname(__FILE__) . "/class.ilObjMultiVc.php";

        $data = [];
        $ScheduledMeeting = $this->parent_obj->object->getScheduledMeetingsByDateFrom( #
            date('Y-m-d H:i:s'),
            $this->refId
        ) ?? [];
        #var_dump($ScheduledMeeting);
        #exit;

        foreach ($ScheduledMeeting as $a_set) {
            $json = json_decode($a_set['rel_data']);
            if($this->parent_obj->isEdudip) {
                $json->title = $json->webinar->title;
            }

            $dtMeetingStart = new ilDateTime($a_set['start'], IL_CAL_DATETIME, $a_set['timezone']);
            $meetingStart = $dtMeetingStart->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());

            $dtMeetingEnd = new ilDateTime($a_set['end'], IL_CAL_DATETIME, $a_set['timezone']);
            $meetingEnd = $dtMeetingEnd->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());

            $data[] = [
                'TITLE' => $json->title,
                'START_TIME' => $meetingStart,
                'END_TIME' => $meetingEnd,
            ];
        } // EOF foreach ($ScheduledMeeting as $key => $row)

        #$data = ilUtil::sortArray($data, 3, 'asc');
        $this->setData($data);
        return $this->data = $data;
    }

    /**
     * Fill a single data row.
     * @throws Exception
     */
    protected function fillRow($a_set)
    {
        global $lng;

        $startTime = new ilDateTime(strtotime($a_set['START_TIME']), IL_CAL_UNIX);
        $endTime = new ilDateTime(strtotime($a_set['END_TIME']), IL_CAL_UNIX);
        $this->tpl->setVariable('TITLE', $a_set['TITLE']);
        $this->tpl->setVariable('START_TIME', ilDatePresentation::formatDate($startTime));
        $this->tpl->setVariable('END_TIME', ilDatePresentation::formatDate($endTime));
    }

}


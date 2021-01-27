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
class ilMultiVcUserLogTableGUI extends ilTable2GUI {

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
        \ilYuiUtil::initPanel();
        \ilYuiUtil::initOverlay();

        $this->dic = $DIC;
        $this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'MultiVc');
        $this->parent_obj = $a_parent_obj;
        // meetings shown in infoScreen
        if( $this->parent_obj instanceof ilObjMultiVcGUI ) {
            $this->refId = (int)$this->parent_obj->object->ref_id;
            #var_dump($this->); exit;
        }

        if( isset($_POST['cmd']['applyFilterUserLog']) || $_GET['cmd'] === 'applyFilterUserLog' ) {
            //var_dump($_SESSION); exit;
            $this->keepFilterValues = true;
        }

        $this->setId('user_log');
        #$this->setFormName('user_log');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->initColumns();
        $this->setTitle($this->plugin_object->txt('user_log'));
        $this->setFormAction($this->dic->ctrl()->getFormAction($this->parent_obj, 'applyFilterUserLog'));
        $this->setEnableHeader(true);

        $this->setExternalSorting(false);
        $this->setExternalSegmentation(false);
        $this->setShowRowsSelector(false);

        $this->setDefaultOrderField('ref_id'); # display_name join_time
        $this->setDefaultOrderDirection('asc');
        //$this->disable('sort');

        $this->setRowTemplate('tpl.user_log_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->initFilterDateDuration();
        $this->setFilterCommand('applyFilterUserLog');
        $this->setResetCommand('resetFilterUserLog');
        // shown in Administration, add export button
        // if( $this->parent_obj instanceof ilMultiVcConfigGUI ) {
            $this->addCommandButton('downloadUserLog', $this->dic->language()->txt('export'));            #var_dump($this->); exit;
        // }

        $this->getDataFromDb();
    }

    public function downloadCsv() {
        $this->exportData(2, true);
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
        $wM = '15%';
        $wL = '30%';
        $this->addColumn($lng->txt('repository'), 'REF');
        // if( $this->getParentCmd() === 'downloadUserLog' ) {
            // $this->addColumn('ILIAS-'.$lng->txt('user'), 'USER');
        // }
        $this->addColumn($this->plugin_object->txt('DISPLAY_NAME'), 'display_name', $wM);
        $this->addColumn($lng->txt('role'), 'IS_MODERATOR', $wS);
        $this->addColumn($this->plugin_object->txt('join_time'), 'JOIN_TIME', $wM);
        $this->addColumn($this->plugin_object->txt('start_time'), 'START_TIME', $wM);
        if( $this->parent_obj instanceof ilMultiVcConfigGUI ) {
            $this->addColumn($this->plugin_object->txt('meeting') . ' ID', 'MEETING_ID', $wM);
        }
    }

    /**
     * Get data and put it into an array
     */
    private function getDataFromDb()
    {
        require_once dirname(__FILE__) . "/class.ilObjMultiVc.php";

        $data = [];
        $userLog = ilObjMultiVc::getInstance()->getUserLog(
            $this->refId,
            $this->dateStart->getUnixTime(),
            $this->dateEnd->getUnixTime(),
            $this->parent_obj instanceof ilMultiVcConfigGUI
        );

        foreach ($userLog as $a_set) {
            $tree = [];
            foreach( $this->dic->repositoryTree()->getPathFull($a_set['ref_id']) as $key => $item) {
                if( (bool)$key ) {
                    $tree[] = $item['title'];
                }
            }

            $dtJoinTime = new ilDateTime($a_set['join_time'], IL_CAL_UNIX);
            $joinTime = $dtJoinTime->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());
            $dtMeetingStart = new ilDateTime($a_set['start_time'], IL_CAL_UNIX);
            $meetingStart = $dtMeetingStart->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());

            $data[] = [
                'REF' => implode(' / ', $tree),
                // 'USER' => ilObjUser::_lookupFullname($a_set['user_id']),
                'DISPLAY_NAME' => $a_set['display_name'],
                'IS_MODERATOR' => (bool)$a_set['is_moderator'] ? $this->plugin_object->txt('moderator') : '',
                'JOIN_TIME' => $joinTime,
                'START_TIME' => $meetingStart,
                'MEETING_ID' => $a_set['meeting_id']
            ];
        } // EOF foreach ($userLog as $key => $row)

        $data = ilUtil::sortArray($data, 4, 'asc');
        $this->setData($data);
        return $this->data = $data;
    }

    /**
     * Fill a single data row.
     * @throws Exception
     */
    protected function fillRow($a_set)
    {
        global $lng, $ilCtrl;
        /*
        $tree = [];
        foreach( $this->dic->repositoryTree()->getPathFull($a_set['ref_id']) as $key => $item) {
            if( (bool)$key ) {
                $tree[] = $item['title'];
            }
        }

        $dtJoinTime = new ilDateTime($a_set['join_time'], IL_CAL_UNIX);
        $joinTime = $dtJoinTime->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());
        $dtMeetingStart = new ilDateTime($a_set['start_time'], IL_CAL_UNIX);
        $meetingStart = $dtMeetingStart->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());
        */
        $this->tpl->setVariable('REF', $a_set['REF']);
        $this->tpl->setVariable('DISPLAY_NAME', $a_set['DISPLAY_NAME']);
        $this->tpl->setVariable('IS_MODERATOR', $a_set['IS_MODERATOR']);
        $this->tpl->setVariable('JOIN_TIME', $a_set['JOIN_TIME']);
        $this->tpl->setVariable('START_TIME', $a_set['START_TIME']);
        if( $this->parent_obj instanceof ilMultiVcConfigGUI ) {
            $this->tpl->setVariable('MEETING_ID', $a_set['MEETING_ID']);
        } else {
            $this->tpl->setVariable('HIDE_MEETING_ID', ' style="display:none;"');
        }

        /*
        $this->tpl->setVariable('REF', implode(' / ', $tree));
        $this->tpl->setVariable('USER', ilObjUser::_lookupFullname($a_set['user_id']));
        $this->tpl->setVariable('DISPLAY_NAME', $a_set['display_name']);
        $this->tpl->setVariable('IS_MODERATOR', (bool)$a_set['is_moderator'] ? $this->plugin_object->txt('moderator') : '');
        $this->tpl->setVariable('JOIN_TIME', $joinTime);
        $this->tpl->setVariable('MEETING', $meetingStart);
        if( $this->parent_obj instanceof ilMultiVcConfigGUI ) {
            $this->tpl->setVariable('MEETING_ID', $a_set['meeting_id']);
        } else {
            $this->tpl->setVariable('HIDE_MEETING_ID', ' style="display:none;"');
        }
        */
    }

    /**
     * @throws ilDateTimeException
     */
    private function initFilterDateDuration(): void
    {
        // $this->tpl->addJavaScript("./Services/Form/js/Form.js");
        $this->filterItemDateDuration = new ilDateDurationInputGUI($this->plugin_object->txt('user_log_duration'), 'date_duration');
        $this->filterItemDateDuration->setAllowOpenIntervals(true);
        $this->filterItemDateDuration->setShowTime(true);
        $this->filterItemDateDuration->setStartText($this->dic->language()->txt('from'));
        $this->filterItemDateDuration->setEndText($this->dic->language()->txt('to'));
        $this->addFilterItem($this->filterItemDateDuration, false);
        if( !$this->keepFilterValues ) {
            $this->setFilterDateDurationDefaultValues();
        } else {
            $this->writeFilterToSession();
            $this->filterItemDateDuration->readFromSession();
        }
        $this->dateStart = $this->filterItemDateDuration->getStart();
        $this->dateEnd = $this->filterItemDateDuration->getEnd();
    }


    /**
     * @return void
     * @throws ilDateTimeException
     * @throws Exception
     */
    private function setFilterDateDurationDefaultValues(): void
    {
        $this->dateEnd = new ilDateTime( (new DateTime(null, new DateTimeZone('UTC')))->getTimestamp(), IL_CAL_UNIX);
        $this->dateStart = new ilDateTime( (new DateTime(null, new DateTimeZone('UTC')))->getTimestamp(), IL_CAL_UNIX);
        $this->dateStart->setDate($this->dateStart->getUnixTime()-(60*60*24*7), IL_CAL_UNIX);
        $this->filterItemDateDuration->setEnd($this->dateEnd);
        $this->filterItemDateDuration->setStart($this->dateStart);
        $this->filterItemDateDuration->writeToSession();
    }


}

?>
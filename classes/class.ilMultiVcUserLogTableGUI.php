<?php

/**
 * MultiVc plugin: report logged max concurrent values table GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */
class ilMultiVcUserLogTableGUI extends ilTable2GUI
{
    private ILIAS\DI\Container $dic;

    protected ?int $refId = null;

    private ?ilDateTime $dateStart = null;

    private ?ilDateTime $dateEnd = null;

    private bool $keepFilterValues = false;

    private ilDateDurationInputGUI $filterItemDateDuration;


    /**
     * ilMultiVcReportLogMaxTableGUI constructor.
     * @throws Exception
     */
    public function __construct(ilObjMultiVcGUI|ilMultiVcConfigGUI $a_parent_obj, string $a_parent_cmd = '', string $a_template_context = '')
    {
        global $DIC;
        \iljQueryUtil::initjQuery();
        \ilYuiUtil::initPanel();
        \ilYuiUtil::initOverlay();

        $this->dic = $DIC;
        $this->parent_obj = $a_parent_obj;
        // meetings shown in infoScreen
        if($this->parent_obj instanceof ilObjMultiVcGUI) {
            $this->refId = (int) $this->parent_obj->object->getRefId();
            #var_dump($this->); exit;
        }

        if ($a_parent_cmd === 'applyFilterUserLog' || $a_parent_cmd === 'downloadUserLog') {
            $this->keepFilterValues = true;
        }
        $this->setPreventDoubleSubmission(false);
        $this->setShowRowsSelector(true);

        $this->setId('user_log');
        #$this->setFormName('user_log');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->initColumns();
        $this->setTitle($this->dic->language()->txt('rep_robj_xmvc_user_log'));
        $this->setFormAction($this->dic->ctrl()->getFormAction($this->parent_obj, 'applyFilterUserLog'));
        $this->setEnableHeader(true);
        $this->setExternalSorting(false);
        $this->setExternalSegmentation(false);
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

    public function downloadCsv(): void
    {
        $this->exportData(2, true);
    }

    /**
     * Init the table columns
     *
     *
     * @access public
     * @throws Exception
     */
    private function initColumns()
    {
        // Columns
        $wS = '10%';
        $wM = '15%';
        $wL = '30%';
        $this->addColumn($this->dic->language()->txt('repository'), 'REF');
        if($this->getParentCmd() === 'downloadUserLog') {
            $this->addColumn('ILIAS-' . $this->dic->language()->txt('user'), 'USER');
        }
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_display_name'), 'display_name', $wM);
        $this->addColumn($this->dic->language()->txt('role'), 'IS_MODERATOR', $wS);
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_join_time'), 'JOIN_TIME', $wM);
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_leave_time'), 'LEAVE_TIME', $wM);
        if($this->parent_obj instanceof ilMultiVcConfigGUI) {
            $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_meeting') . ' ID', 'MEETING_ID', $wM);
        }
    }

    /**
     *
     */
    private function initDtFilter()
    {
        // Columns
        $wS = '10%';
        $wM = '15%';
        $wL = '30%';
        $this->addColumn($this->dic->language()->txt('repository'), 'REF');
        if($this->getParentCmd() === 'downloadUserLog') {
            $this->addColumn('ILIAS-' . $this->dic->language()->txt('user'), 'USER');
        }
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_display_name'), 'display_name', $wM);
        $this->addColumn($this->dic->language()->txt('role'), 'IS_MODERATOR', $wS);
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_join_time'), 'JOIN_TIME', $wM);
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_leave_time'), 'LEAVE_TIME', $wM);
        if($this->parent_obj instanceof ilMultiVcConfigGUI) {
            $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_meeting') . ' ID', 'MEETING_ID', $wM);
        }
    }


    /**
     * Get data and put it into an array
     */
    private function getDataFromDb(): void
    {
        #echo '<pre>'; var_dump([$this->dateStart->getUnixTime(), $this->dateEnd->getUnixTime(),]); exit;
        $data = [];
        $objMc = new ilObjMultiVc();

        $userLog = $objMc->getUserLog(
            $this->refId,
            $this->dateStart->getUnixTime(),
            $this->dateEnd->getUnixTime(),
            $this->parent_obj instanceof ilMultiVcConfigGUI
        );

        foreach ($userLog as $a_set) {
            $tree = [];
            foreach($this->dic->repositoryTree()->getPathFull($a_set['ref_id']) as $key => $item) {
                if((bool) $key) {
                    $tree[] = $item['title'];
                }
            }

            $dtJoinTime = new ilDateTime($a_set['join_time'], IL_CAL_UNIX);
            $joinTime = $dtJoinTime->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());
            $dtMeetingStart = new ilDateTime($a_set['start_time'], IL_CAL_UNIX);
            $meetingStart = $dtMeetingStart->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());
            $dtLeaveTime = new ilDateTime($a_set['leave_time'], IL_CAL_UNIX);
            $leaveTime = $dtLeaveTime->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());

            $data[] = [
                'REF' => implode(' / ', $tree),
                'USER' => ilObjUser::_lookupFullname($a_set['user_id']),
                'DISPLAY_NAME' => $a_set['display_name'],
                'IS_MODERATOR' => !(bool) $a_set['is_moderator'] ? !(bool) $a_set['user_id'] ? $this->dic->language()->txt('rep_robj_xmvc_guest') : '' : $this->dic->language()->txt('rep_robj_xmvc_moderator'),
                'JOIN_TIME' => $joinTime,
                'START_TIME' => $meetingStart,
                'MEETING_ID' => $a_set['meeting_id'],
                'LEAVE_TIME' => $leaveTime
            ];
        } // EOF foreach ($userLog as $key => $row)

        $data = ilArrayUtil::sortArray($data, 4, 'asc');
        $this->setData($data);
        //        return $this->data = $data;
    }

    /**
     * Fill a single data row.
     * @throws Exception
     */
    protected function fillRow($a_set): void
    {
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
        $this->tpl->setVariable('USER', $a_set['USER']);
        $this->tpl->setVariable('DISPLAY_NAME', $a_set['DISPLAY_NAME']);
        $this->tpl->setVariable('IS_MODERATOR', $a_set['IS_MODERATOR']);
        $this->tpl->setVariable('JOIN_TIME', $a_set['JOIN_TIME']);
        $this->tpl->setVariable('LEAVE_TIME', $a_set['LEAVE_TIME']);
        if($this->parent_obj instanceof ilMultiVcConfigGUI) {
            $this->tpl->setVariable('MEETING_ID', $a_set['MEETING_ID']);
        } else {
            $this->tpl->setVariable('HIDE_MEETING_ID', ' style="display:none;"');
        }

        /*
        $this->tpl->setVariable('REF', implode(' / ', $tree));
        $this->tpl->setVariable('USER', ilObjUser::_lookupFullname($a_set['user_id']));
        $this->tpl->setVariable('DISPLAY_NAME', $a_set['display_name']);
        $this->tpl->setVariable('IS_MODERATOR', (bool)$a_set['is_moderator'] ? $this->dic->language()->txt('rep_robj_xmvc_moderator') : '');
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
        $this->filterItemDateDuration = new ilDateDurationInputGUI($this->dic->language()->txt('rep_robj_xmvc_user_log_duration'), 'date_duration');
        $this->filterItemDateDuration->setAllowOpenIntervals(true);
        $this->filterItemDateDuration->setShowTime(true);
        $this->filterItemDateDuration->setStartText($this->dic->language()->txt('from'));
        $this->filterItemDateDuration->setEndText($this->dic->language()->txt('to'));
        $this->addFilterItem($this->filterItemDateDuration, false);
        if(!$this->keepFilterValues) {
            $this->setFilterDateDurationDefaultValues();
        } else {
            $this->writeFilterToSession();
            $this->filterItemDateDuration->readFromSession();
        }
        $this->dateStart = $this->filterItemDateDuration->getStart();
        $this->dateEnd = $this->filterItemDateDuration->getEnd();
    }


    /**
     * @throws ilDateTimeException
     * @throws Exception
     */
    private function setFilterDateDurationDefaultValues(): void
    {
        $this->dateEnd = new ilDateTime((new DateTime('now', new DateTimeZone('UTC')))->getTimestamp(), IL_CAL_UNIX);
        $this->dateStart = new ilDateTime((new DateTime('now', new DateTimeZone('UTC')))->getTimestamp(), IL_CAL_UNIX);
        $this->dateStart->setDate($this->dateStart->getUnixTime() - (60 * 60 * 24 * 7), IL_CAL_UNIX);
        $this->filterItemDateDuration->setEnd($this->dateEnd);
        $this->filterItemDateDuration->setStart($this->dateStart);
        $this->filterItemDateDuration->writeToSession();
    }


}

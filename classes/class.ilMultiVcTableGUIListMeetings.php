<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE
 */

/**
 * MultiVc plugin: report logged max concurrent values table GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */
class ilMultiVcTableGUIListMeetings extends ilTable2GUI
{
    private ILIAS\DI\Container $dic;

    protected ?int $refId;

    public function __construct(object $a_parent_obj, string $a_parent_cmd = '', string $a_template_context = '')
    {
        global $DIC;

        $this->dic = $DIC;

        $this->parent_obj = $a_parent_obj;
        // meetings shown in infoScreen
        if($this->parent_obj instanceof ilObjMultiVcGUI) {
            $this->refId = (int) $this->parent_obj->object->getRefId();
        }

        $this->setId('list_meetings');
        #$this->setFormName('user_log');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->initColumns();
        $this->setEnableHeader(true);

        $this->setExternalSorting(false);
        $this->setExternalSegmentation(false);
        $this->setShowRowsSelector(false);

        $this->setDefaultOrderField('start_time');
        $this->setDefaultOrderDirection('asc');
        //$this->disable('sort');

        $this->setRowTemplate('tpl.list_meetings_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->setEnableNumInfo(false);
        $this->getDataFromDb();
    }

    private function initColumns()
    {
        // Columns
        $wS = '10%';
        $wM = '25%';
        $wL = '60%';
        $this->addColumn($this->lng->txt('rep_robj_xmvc_start_time'), 'START_TIME', 'auto');
        $this->addColumn($this->lng->txt('rep_robj_xmvc_end_time'), 'END_TIME', 'auto');
        $this->addColumn($this->dic->language()->txt('title'), 'TITLE', $wL);
    }

    /**
     * Get data and put it into an array
     * @throws ilDateTimeException
     */
    private function getDataFromDb(): void
    {
        $data = [];
        if ($this->parent_obj->isTeams || $this->parent_obj->isZoom) {
            $ScheduledMeeting = $this->parent_obj->object->getScheduledMeetingsByDateFrom(date('Y-m-d H:i:s'), $this->refId, 'UTC') ?? [];
        } else {
            $ScheduledMeeting = $this->parent_obj->object->getScheduledMeetingsByDateFrom(date('Y-m-d H:i:s'), $this->refId) ?? [];
        }
        #var_dump($ScheduledMeeting);exit;

        foreach ($ScheduledMeeting as $a_set) {
            $meetingTitle = $a_set['title'];
            $json = json_decode($a_set['rel_data']);
            if($this->parent_obj->isEdudip) {
//                $meetingTitle = $json->webinar->title;
            }

            $dtMeetingStart = new ilDateTime($a_set['start'], IL_CAL_DATETIME, $a_set['timezone']);
            $meetingStart = $dtMeetingStart->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());

            $dtMeetingEnd = new ilDateTime($a_set['end'], IL_CAL_DATETIME, $a_set['timezone']);
            $meetingEnd = $dtMeetingEnd->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());

            $data[] = [
                'TITLE' => $meetingTitle,
                'START_TIME' => $meetingStart,
                'END_TIME' => $meetingEnd,
            ];
        }

        #$data = ilUtil::sortArray($data, 3, 'asc');
        $this->setData($data);
    }

    /**
     * Fill a single data row.
     * @throws Exception
     */
    protected function fillRow($a_set): void
    {
        $startTime = new ilDateTime(strtotime($a_set['START_TIME']), IL_CAL_UNIX);
        $endTime = new ilDateTime(strtotime($a_set['END_TIME']), IL_CAL_UNIX);
        $this->tpl->setVariable('TITLE', $a_set['TITLE']);
        $this->tpl->setVariable('START_TIME', ilDatePresentation::formatDate($startTime));
        $this->tpl->setVariable('END_TIME', ilDatePresentation::formatDate($endTime));
    }

}

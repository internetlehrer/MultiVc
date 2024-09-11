<?php

class ilMultiVcLpUserResultsGUI extends ilTable2GUI
{
    private ILIAS\DI\Container $dic;
    protected ?int $refId = null;
    protected ilLogger $logger;


    public function __construct(ilObjMultiVcGUI $a_parent_obj, string $a_parent_cmd = '', string $a_template_context = '')
    {
        global $DIC;
        $this->dic = $DIC;
        $this->logger = $this->dic->logger()->root();
        $this->parent_obj = $a_parent_obj;
        // meetings shown in infoScreen
        if ($this->parent_obj instanceof ilObjMultiVcGUI) {
            $this->refId = (int) $this->parent_obj->object->getRefId();
            #var_dump($this->); exit;
        }
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        $vcobj = new ilObjMultiVc($this->refId);
        $tSettings = ilMultiVcConfig::getInstance($vcobj->getConnId());
        $meetingIds = ilApiTeams::getAttendanceReport(ilObject::_lookupObjectId($this->refId), $this->refId, $vcobj->getLPMode(), $vcobj->getLpTime(), $tSettings->getSvrUsername(), $tSettings->getSvrSalt(), $tSettings->getSvrPublicUrl());

        $this->initColumns();
        $this->setTitle($this->dic->language()->txt('rep_robj_xmvc_user_results'));
        $this->setDescription($this->dic->language()->txt('rep_robj_xmvc_user_results_desc'));
        $this->setRowTemplate('tpl.lp_user_result.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->getDataFromDb();
    }
    private function initColumns()
    {
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_title'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_meeting_start'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_meeting_end'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_display_name'), '');
        $this->addColumn($this->dic->language()->txt('role'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_user_start'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_user_end'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_user_time'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_user_percent'), '');
    }

    private function getDataFromDb(): void
    {
        $db = $this->dic->database();
        $objId = ilObject::_lookupObjectId($this->refId);
        $dbObjId = $db->quote($objId, 'integer');
        #echo '<pre>'; var_dump([$this->dateStart->getUnixTime(), $this->dateEnd->getUnixTime(),]); exit;
        $data = [];
        $meeting = [];
        $meeting_ids = [];

        //missing check Teams
        $query = "SELECT rep_robj_xmvc_schedule.rel_id as meeting_id, rep_robj_xmvc_schedule.rel_data,"
            ." rep_robj_xmvc_schedule.start as schedule_start, rep_robj_xmvc_schedule.end as schedule_end, "
            ." rep_robj_xmvc_session.start as session_start, rep_robj_xmvc_session.end as session_end"
            ." FROM rep_robj_xmvc_schedule, rep_robj_xmvc_session"
            ." WHERE rep_robj_xmvc_schedule.rel_id = rep_robj_xmvc_session.rel_id"
            //." AND rep_robj_xmvc_session.end < CURRENT_TIMESTAMP"
            ." AND rep_robj_xmvc_schedule.end < CURRENT_TIMESTAMP"
//            ." AND NOT ISNULL(rep_robj_xmvc_session.cron)"
            ." AND rep_robj_xmvc_session.obj_id = " . $dbObjId
            ." ORDER BY rep_robj_xmvc_session.start asc";
        $this->logger->debug($query);
        $res = $db->query($query);
        while ($row = $db->fetchAssoc($res)) {
            $meetingRelData = json_decode($row['rel_data']);
            $this->logger->dump($meetingRelData);
            $title = $meetingRelData->title;
            $meeting_id = $row['meeting_id'];
            $meeting_ids[] = $meeting_id;
            $meeting[$meeting_id]['title'] = $title;
            $meassureStart = $row['session_start'];
            if ($row['schedule_start'] > $row['session_start']) {
                $meassureStart = $row['schedule_start'];
            }
            $meassureEnd = $row['session_end'];
            if ($row['schedule_end'] < $row['session_end']) {
                $meassureEnd = $row['schedule_end'];
            }

            $meassureTime = strtotime($meassureEnd) - strtotime($meassureStart);
            if($meassureTime < 0) {
                $meassureTime = 0;
            }
            $meeting[$meeting_id]['start'] = ilDatePresentation::formatDate(new ilDateTime($meassureStart,IL_CAL_DATETIME, 'UTC'));
            $meeting[$meeting_id]['end'] = ilDatePresentation::formatDate(new ilDateTime($meassureEnd,IL_CAL_DATETIME, 'UTC'));
            $meeting[$meeting_id]['meassureTime'] = $meassureTime;
        }
        $query = "SELECT display_name, meeting_id, user_id, sum(duration_seconds) as seconds, max(is_moderator) as is_moderator, min(join_time) as user_start, max(leave_time) as user_end"
            ." FROM rep_robj_xmvc_user_log WHERE ref_id =" . $db->quote($this->refId, 'integer')
            ." GROUP BY display_name, meeting_id, user_id ORDER BY min(join_time)";
        $this->logger->debug($query);
        $res = $db->query($query);
        while ($row = $db->fetchAssoc($res)) {
            $meeting_id = $row['meeting_id'];
            $dat = [];
            $this->logger->debug($meeting_id .":".$meeting[$meeting_id]['title']);
            $dat['title'] = $meeting[$meeting_id]['title'];
            $dat['meeting_start'] = $meeting[$meeting_id]['start'];
            $dat['meeting_end'] = $meeting[$meeting_id]['end'];
            $dat['display_name'] = $row['display_name'];
            if ((int) $row['user_id'] == 0) {
                $dat['role'] = "Gast";
            } else {
                if((int) $row['is_moderator'] == 1) {
                    $dat['role'] = "Moderator";
                } else {
                    $dat['role'] = "Mitglied";
                }
            }
            $dtJoinTime = new ilDateTime($row['user_start'], IL_CAL_UNIX);
            $joinTime = $dtJoinTime->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s');
            $dat['user_start'] = ilDatePresentation::formatDate(new ilDateTime($joinTime,IL_CAL_DATETIME, $this->dic->user()->getTimeZone()));
            $dtLeaveTime = new ilDateTime($row['user_end'], IL_CAL_UNIX);
            $leaveTime = $dtLeaveTime->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s');
            $dat['user_end'] = ilDatePresentation::formatDate(new ilDateTime($leaveTime,IL_CAL_DATETIME, $this->dic->user()->getTimeZone()));
            $seconds = (int) $row['seconds'];
            $dat['time'] = ilDatePresentation::secondsToString($seconds, true);
            $percent = '';
            $meassureTime = (int) $meeting[$meeting_id]['meassureTime'];
            if ($seconds > 0 &&  $meassureTime > 0) {
                $percentInt = round($seconds*100/$meassureTime);
                $percent = (string) $percentInt . " %";
                if($percentInt > 100) {
                    $percent = "100 % (gekürzt)";
                }
            }
            $dat['percent'] = $percent;

            $data[] = $dat;
        }

//        die(var_dump($meeting));
        $this->setData($data);
    }
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
        $this->tpl->setVariable('TITLE', $a_set['title']);
        $this->tpl->setVariable('MEETING_START', $a_set['meeting_start']);
        $this->tpl->setVariable('MEETING_END', $a_set['meeting_end']);
        $this->tpl->setVariable('DISPLAY_NAME', $a_set['display_name']);
        $this->tpl->setVariable('ROLE', $a_set['role']);
        $this->tpl->setVariable('USER_START', $a_set['user_start']);
        $this->tpl->setVariable('USER_END', $a_set['user_end']);
        $this->tpl->setVariable('TIME', $a_set['time']);
        $this->tpl->setVariable('PERCENT', $a_set['percent']);

    }


}
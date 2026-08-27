<?php

class ilMultiVcLpUserResultsGUI extends ilTable2GUI
{
    private ILIAS\DI\Container $dic;
    protected ?int $refId = null;
    protected ilLogger $logger;
    protected bool $isTeams = false;


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
        if ($tSettings->getShowContent() == 'teams') {
            $this->isTeams = true;
            $meetingIds = ilApiTeams::getAttendanceReport(
                ilObject::_lookupObjectId($this->refId),
                $this->refId,
                $vcobj->getLPMode(),
                $vcobj->getLpTime(),
                $tSettings->getSvrUsername(),
                $tSettings->getSvrSalt(),
                $tSettings->getSvrPublicUrl()
            );
        } else {
            $token = ilApiZoom::getAccessTokenDirect($tSettings->getSvrUsername(), $tSettings->getSvrSalt(), $tSettings->getSvrPublicUrl());
            $meetingIds = ilApiZoom::getAttendanceReport($token, ilObject::_lookupObjectId($this->refId), $this->refId,$vcobj->getLPMode(), $vcobj->getLpTime());
        }

        $this->initColumns();
        $this->setTitle($this->dic->language()->txt('rep_robj_xmvc_user_results'));
        $this->setDescription($this->dic->language()->txt('rep_robj_xmvc_user_results_desc'));
        $this->setEnableHeader(true);
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->addCommandButton('lpUserResultsDownload', $this->lng->txt('export'));
        $this->setRowTemplate('tpl.lp_user_result.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->setData($vcobj->getScheduledSessionLpUserResults());
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
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_user_rel_span'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_user_time'), '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_user_percent'), '');
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
        $role = $a_set['role'];
        if ($role == 'guest') {
            $role = $this->dic->language()->txt('rep_robj_xmvc_guest');
        } elseif ($this->isTeams) {
            if ($role == 'moderator') {
                $role = $this->dic->language()->txt('rep_robj_xmvc_moderator');
            } else {
                $role = $this->dic->language()->txt('member');
            }
        } else {
            $role = '';
        }
        $percent = $a_set['percent'];
        if ($percent != '') {
            if ((int) $percent > 100) {
                $percent = '100 % (>)';
            } else {
                $percent .= ' %';
            }
        }
        $this->tpl->setVariable('TITLE', $a_set['title']);
        $this->tpl->setVariable('MEETING_START', $a_set['meeting_start']);
        $this->tpl->setVariable('MEETING_END', $a_set['meeting_end']);
        $this->tpl->setVariable('DISPLAY_NAME', $a_set['display_name']);
        $this->tpl->setVariable('ROLE', $role);
        $this->tpl->setVariable('USER_START', $a_set['user_start']);
        $this->tpl->setVariable('USER_END', $a_set['user_end']);
        $this->tpl->setVariable('USER_REL_SPAN', $a_set['user_rel_span']);
        $this->tpl->setVariable('TIME', $a_set['time']);
        $this->tpl->setVariable('PERCENT', $percent);

    }

    public function downloadCsv()
    {
        $this->exportData(2, true);
    }


}

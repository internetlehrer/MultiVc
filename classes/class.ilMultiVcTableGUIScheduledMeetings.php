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
class ilMultiVcTableGUIScheduledMeetings extends ilTable2GUI
{
    public const MEETING_DURATION = 60 * 60;

    private ?ilPropertyFormGUI $meetingPropertiesForm =null;

    /** @var ilCheckboxInputGUI[]|ilSelectInputGUI[]|ilTextInputGUI[]|ilDateDurationInputGUI[]|ilEMailInputGUI[]|ilHiddenInputGUI[]|ilNonEditableValueGUI[] $meetingProperty */
    private array $meetingProperty = [];

    private Container $dic;

    private ?ilPlugin $plugin_object;

    private ?ilMultiVcConfig $plugin_settings;

    protected ?int $refId;

    private ?ilDateTime $dateStart = null;

    private ?ilDateTime $dateEnd = null;

    private bool $keepFilterValues = false;

    private ilDateDurationInputGUI $filterItemDateDuration;

    private ilSelectInputGUI $filterItemDataSource;

    private bool $dataSource;

    private ilCheckboxInputGUI $filterItemSync;

    private bool $dataSync;

    private array $meetingRequestParam = [];

    private array $diffLocalSessHostSessRelIds = [];


    /**
     * ilMultiVcReportLogMaxTableGUI constructor.
     * @param ilObjMultiVcGUI|ilMultiVcConfigGUI|object $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     * @throws Exception
     */
    public function __construct(object $a_parent_obj, $a_parent_cmd = '', string $a_template_context = '')
    {
        global $DIC; /** @var Container $DIC */
        \iljQueryUtil::initjQuery();
        #\ilYuiUtil::initPanel();
        #\ilYuiUtil::initOverlay();

        $this->dic = $DIC;
        $this->parent_obj = $a_parent_obj;
        // meetings shown in infoScreen
        if($this->parent_obj instanceof ilObjMultiVcGUI) {
            $this->refId = (int)$this->parent_obj->object->getRefId();
        }
        $this->plugin_settings = ilMultiVcConfig::getInstance($this->parent_obj->object->getConnId());

        //if( isset($_POST['cmd']['applyFilterScheduledMeetings']) || $_GET['cmd'] === 'applyFilterScheduledMeetings' ) {
        if ($a_parent_cmd == 'applyFilterScheduledMeetings') {
            //        if (($this->dic->http()->wrapper()->post()->has('cmd') &&
            //                $this->dic->http()->wrapper()->post()->retrieve('cmd', $this->dic->refinery()->kindlyTo()->string()) === 'applyFilterScheduledMeetings') ||
            //            $this->dic->http()->wrapper()->query()->retrieve('cmd', $this->dic->refinery()->kindlyTo()->string()) === 'applyFilterScheduledMeetings') {
            $this->keepFilterValues = true;
        }
        $this->setId('scheduled_meetings');
        #$this->setFormName('user_log');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->initColumns();
        $this->setTitle($this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_overview'));
        $this->setFormAction($this->dic->ctrl()->getFormAction($this->parent_obj, 'applyFilterScheduledMeetings'));
        $this->setEnableHeader(true);

        $this->setExternalSorting(false);
        $this->setExternalSegmentation(false);
        $this->setShowRowsSelector(false);

        $this->setDefaultOrderField('START_TIME'); # display_name join_time
        $this->setDefaultOrderDirection('asc');
        //$this->disable('sort');

        $this->setRowTemplate('tpl.scheduled_meeting_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->initFilterDateDurationAndDataSource();
        #$this->initFilterDataSource();
        $this->setFilterCommand('applyFilterScheduledMeetings');
        $this->setResetCommand('resetFilterScheduledMeetings');
        $this->setEnableNumInfo(false);
        $this->setDefaultFilterVisiblity(true);
        $this->setDisableFilterHiding(true);

        if('applyFilterScheduledMeetingsKeepForm' === $this->dic->http()->wrapper()->query()->retrieve('cmd', $this->dic->refinery()->kindlyTo()->string())
            || 'applyFilterScheduledMeetings' === $this->dic->http()->wrapper()->query()->retrieve('cmd', $this->dic->refinery()->kindlyTo()->string())
        ) {
            $this->setFilterValuesBySessionVar();
        }

        // shown in Administration, add export button
        // if( $this->parent_obj instanceof ilMultiVcConfigGUI ) {
        #$this->addCommandButton('downloadScheduledMeetings', $this->dic->language()->txt('export'));            #var_dump($this->); exit;
        // }

        $this->setLimit(50);

        $this->getDataFromDb($a_parent_cmd);
    }


    /**
     * Init the table columns
     */
    private function initColumns(): void
    {
        // Columns
        $wS = '10%';
        $wM = '15%';
        $wL = '30%';
        $this->addColumn($this->dic->language()->txt('title'), 'TITLE');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_start_time'), 'START_TIME', $wM);
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_end_time'), 'END_TIME', $wM);
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_related_meeting'), '', $wM);
        #$this->addColumn($this->dic->language()->txt('rep_robj_xmvc_scheduled_meeting_recurrence'), 'RECURRENCE', $wM);
        #$this->addColumn($this->dic->language()->txt('rep_robj_xmvc_scheduled_meeting_state'), 'STATE', $wM);
        $this->addColumn($this->dic->language()->txt('action'), '', $wL);

    }

    /**
     * Get data and put it into an array
     * @return void
     * @throws ilCurlConnectionException
     * @throws ilDatabaseException
     * @throws ilDateTimeException
     * @throws ilObjectNotFoundException
     */
    private function getDataFromDb(string $a_parent_cmd): void
    {
        $start = date('Y-m-d H:i:s', $this->dateStart->getUnixTime());
        $end = date('Y-m-d H:i:s', $this->dateEnd->getUnixTime());
        $storedHostSessions = null;
        $getWebexMeetingsList = (bool)$this->dataSource && (int)$this->dic->user()->getId() === (int)$this->parent_obj->object->getOwner();
        $getDiffLocalSessHostSess = false;
        if($getWebexMeetingsList && //$a_parent_cmd == 'applyFilterScheduledMeetings') {
                        $this->dic->http()->wrapper()->query()->has('fallbackCmd')) {
//                        $this->dic->http()->wrapper()->post()->retrieve('cmd', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()))[0] == 'applyFilterScheduledMeetings') {//isset($_POST['cmd']['applyFilterScheduledMeetings']) ) {
            $this->getSessionsFromHost(); //ToDo
            #$this->getMeetingsFromWebex();
            // see 750 ...
            $storedHostSessions = $this->parent_obj->object->getStoredHostSessionsByDateRange($start, $end, $this->refId);
            $getDiffLocalSessHostSess = true;
            //die(var_dump( $this->dic->http()->wrapper()->post()));
            if($this->parent_obj->isWebex) {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('success',
                    $this->dic->language()->txt('rep_robj_xmvc_webex_meetings_list_downloaded'), true);
            } elseif ($this->parent_obj->isTeams) {
                    $this->dic->ui()->mainTemplate()->setOnScreenMessage('success',$this->dic->language()->txt('rep_robj_xmvc_teams_meetings_list_downloaded'), true);
            } else {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('success',$this->dic->language()->txt('rep_robj_xmvc_edudip_meetings_list_downloaded'), true);
            }
//            $this->dic->ctrl()->redirect($this->parent_obj, 'applyFilterScheduledMeetings');
        } elseif($getWebexMeetingsList &&$a_parent_cmd == 'applyFilterScheduledMeetings') {
//            && $this->dic->http()->wrapper()->query()->retrieve('cmd', $this->dic->refinery()->kindlyTo()->string()) === 'applyFilterScheduledMeetings') {
            //bei folgendem konnte nichts herauskommen
            $storedHostSessions = $this->parent_obj->object->getStoredHostSessionsByDateRange($start, $end, $this->refId);
            #echo '<pre>'; var_dump($storedHostSessions); exit;
            $getDiffLocalSessHostSess = true;
            #echo '<pre>'; var_dump($storedHostSessions); exit;
        } else {
            $storedHostSessions = [];
        }

        $allMeetings = $this->parent_obj->object->getScheduledMeetingsByDateRange($start, $end, $this->parent_obj->object->getRefId()) ?? [];

        if($getDiffLocalSessHostSess) {
            $hostSessRelIds = !count($storedHostSessions) ? [] : array_map(function ($col) {
                return $col['rel_id'];
            }, $storedHostSessions);
            $localSessRelIds = array_map(function ($col) {
                return $col['rel_id'];
            }, $allMeetings);
            $this->diffLocalSessHostSessRelIds = array_diff($localSessRelIds, $hostSessRelIds);
        }
        #echo '<pre>'; var_dump($this->diffLocalSessHostSessRelIds); exit;

        //        if( $this->dic->user()->getId() === 6 ) {
        //            #echo '<pre>'; var_dump($allMeetings); exit;
        //            #echo '<pre>'; var_dump($storedHostSessions); exit;
        //        }
        foreach ($storedHostSessions as $key => $hostSession) { #$webexMeeting
            #var_dump($storedSessions); exit;
            if(
                is_null($this->parent_obj->object->getScheduledMeetingByRelId($hostSession['rel_id']))
            ) {
                $allMeetings[] = $hostSession;
            }
        } // EOF foreach ($webexMeetings as $webexMeeting)
        //        if( (int)$this->dic->user()->getId() === 6 ) {
        //            #echo '<pre>'; var_dump($allMeetings); exit;
        //            #echo '<pre>'; var_dump($storedHostSessions); exit;
        //        }

        //        require_once dirname(__FILE__) . "/class.ilObjMultiVc.php";

        $data = [];
        foreach($allMeetings as $key => $a_set) {
            $json = json_decode($a_set['rel_data']);

            // CHECK IF ILIAS USER EQUALS SESS USER
            $ilUserId = $this->dic->user()->getId();
            $ilUserEmail = $ownerMail = ilObjUser::_lookupEmail($this->parent_obj->object->getOwner()); #$this->dic->user()->getEmail();

            $continue = false;
            if(isset($a_set['user_id']) && isset($a_set['auth_user'])) {
                // locally assigned sessions check to show it
                switch(true) {
                    case (int)$ilUserId !== (int)$a_set['user_id']:
                        // Uncomment to hide/unset for non-owner
                    case $ilUserEmail !== $a_set['auth_user']:
                        $continue = true;
                        break;
                    default:
                        break;
                }
            } elseif($ilUserEmail !== $json->email) {
                // don't show hosted sessions for non-owner
                $continue = true;
            }

            if($continue) {
                continue;
            }

            if($this->parent_obj->isEdudip || $this->parent_obj->isWebex || $this->parent_obj->isTeams) {
                $vcType = strtoupper(ilMultiVcConfig::getInstance($this->parent_obj->object->getConnId())->getShowContent());
                #$json->webLink = ILIAS_HTTP_PATH . '/' . $this->dic->ctrl()->getLinkTarget($this->parent_obj, 'showContent') .
                $json->startLink = ILIAS_HTTP_PATH . '/' . $this->dic->ctrl()->getLinkTarget($this->parent_obj, 'showContent') .
                    '&start' . $vcType . '=1&rel_id=' . $a_set['rel_id'];
            }

            $dtMeetingStart = new ilDateTime($a_set['start'], IL_CAL_DATETIME, $a_set['timezone']);
            $meetingStart = $dtMeetingStart->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());

            $dtMeetingEnd = new ilDateTime($a_set['end'], IL_CAL_DATETIME, $a_set['timezone']);
            $meetingEnd = $dtMeetingEnd->get(IL_CAL_FKT_DATE, 'Y-m-d H:i:s', $this->dic->user()->getTimeZone());

//            $joinUrl = (bool)$json->wbxmvcRelatedMeeting ? date('Y-m-d H:i:s') > $meetingEnd ? $json->webLink : $json->startLink : '';
            $joinUrl = '';
            if ((bool) $json->wbxmvcRelatedMeeting) {
                if (date('Y-m-d H:i:s') > $meetingEnd) {
                    if (isset($json->webLink)) {
                        $joinUrl = $json->webLink;
                    }
                } else {
                    if (isset($json->startLink)) {
                        $joinUrl = $json->startLink;
                    } else {
                        die ($json->onlineMeeting->joinUrl);
                    }

                }
            }

            $deleteLocalOnly = -1 < array_search($a_set['rel_id'], $this->diffLocalSessHostSessRelIds);

//            die(var_dump($json));
//            if (!isset($a_set['recurrence'])) {
                $a_set['recurrence'] = "";
//            }
            $setState = 'ready';
            if(isset($json->state)) {
                $setState = $json->state;
            }

            $rowCss = 'info';
            if ($deleteLocalOnly) {
                if ($json->wbxmvcRelatedMeeting) {
                    $rowCss = 'danger';
                }
            } else {
                if ($json->wbxmvcRelatedMeeting) {
                    $rowCss = '';
                }
            }

            $data[$key] = [
#                'ROW_CSS'       => $deleteLocalOnly ? 'danger' : '',
                'ROW_CSS'       => $rowCss,
                'TITLE'         => $json->title,
                'START_TIME'    => $meetingStart,
                'END_TIME'      => $meetingEnd,
                'RECURRENCE'    => $this->dic->language()->txt('rep_robj_xmvc_recurrence_' . strtolower($a_set['recurrence'])),
                'STATE'         => $this->dic->language()->txt('rep_robj_xmvc_state_' . $setState),
                'IS_RELATED'    => $this->dic->language()->txt((bool)$json->wbxmvcRelatedMeeting ? 'yes' : 'no'),
                'JOIN_URL'      => $joinUrl,
                'JOINBTN_HIDE'  => !(bool)$json->wbxmvcRelatedMeeting || $deleteLocalOnly || $joinUrl == "" ? 'visibility: hidden' : '',
                //  RELATE_MEETING_DATA
                'RELATE_MEETING_HIDE' => (bool)$json->wbxmvcRelatedMeeting ? 'hidden' : '',
                'RELATE_MEETING_TXT' => $this->dic->language()->txt('rep_robj_xmvc_relate_' . $this->parent_obj->sessType),
                'RELATE_MEETING_DATA' => (bool)$json->wbxmvcRelatedMeeting ? '' : rawurlencode(json_encode([
                    'ref_id' => $this->parent_obj->object->getRefId(),
                    #'ref_id' => $a_set['ref_id'],
                    'start' => $a_set['start'],
                    'end' => $a_set['end'],
                    'timezone' => $a_set['timezone'],
                    'rel_id'    => $a_set['rel_id'],
                    'rel_data' => $json,
                ])),
                // DELETE MEETING
                'DELETE_DATA' => !(bool)$json->wbxmvcRelatedMeeting ? '' : rawurlencode(json_encode([
                    'ref_id' => $this->parent_obj->object->getRefId(),
                    'delete_local_only' => (int)$deleteLocalOnly,
                    #'ref_id' => $a_set['ref_id'],
                    'start' => $a_set['start'],
                    'end' => $a_set['end'],
                    'timezone' => $a_set['timezone'],
                    'modal' => [
                        'id' => 'deleteMeeting_' . $a_set['rel_id'] . '_', //ref_id
                        'title' => $this->dic->language()->txt('rep_robj_xmvc_modal_delete_scheduled_' . $this->parent_obj->sessType . '_title'),
                        'body' => implode(' ', [
                            '<b>' . $json->title . '</b>',
                            $a_set['start'],
                            '-',
                            $a_set['end']
                        ]),
                        'txtAccept' => $this->dic->language()->txt('rep_robj_xmvc_modal_delete_scheduled_' . $this->parent_obj->sessType . '_accept'),
                        'txtAbort' => $this->dic->language()->txt('cancel'),
                    ]
                ])),
                'DELETE_TXT' => $this->dic->language()->txt('delete'),
                'HIDE_DELETE' => !(bool)$json->wbxmvcRelatedMeeting ? 'hidden' : '',
                'MODAL_DATA' => rawurlencode(json_encode([
                    'modal' => [
                        'id' => 'detailsMeeting_' . $a_set['rel_id'] . '_', //ref_id
                        'title' => $this->dic->language()->txt('rep_robj_xmvc_modal_details_scheduled_' . $this->parent_obj->sessType . '_title'),
                        'body' => $a_set['rel_data'],
                        'txtAccept' => $this->dic->language()->txt('close'),
                        'btnAbort' => false,
                    ]
                ]))
            ];
        } // EOF foreach ($ScheduledMeeting as $key => $row)
        $this->setData($data);
        $this->data = $data;
    }

    /**
     * Fill a single data row.
     * @throws Exception
     */
    protected function fillRow(array $a_set): void
    {
        $this->tpl->setVariable('ROW_CSS', $a_set['ROW_CSS']);
        $this->tpl->setVariable('TITLE', $a_set['TITLE']);
        $startTime = new ilDateTime(strtotime($a_set['START_TIME']), IL_CAL_UNIX);
        $endTime = new ilDateTime(strtotime($a_set['END_TIME']), IL_CAL_UNIX);
        $this->tpl->setVariable('START_TIME', ilDatePresentation::formatDate($startTime));
        $this->tpl->setVariable('END_TIME', ilDatePresentation::formatDate($endTime));
        $this->tpl->setVariable('IS_RELATED', $a_set['IS_RELATED']);

        #$this->tpl->setVariable('RECURRENCE', $a_set['RECURRENCE']);
        #$this->tpl->setVariable('STATE', $a_set['STATE']);

        // JOIN MEETING
        $this->tpl->setVariable('JOIN_URL', $a_set['JOIN_URL']);
        $this->tpl->setVariable('JOINBTNTEXT', $this->dic->language()->txt('rep_robj_xmvc_btntext_join_' . $this->parent_obj->sessType));
        $this->tpl->setVariable('JOINBTN_HIDE', $a_set['JOINBTN_HIDE']);

        //RELATE_MEETING
        $this->tpl->setVariable('RELATE_MEETING_TXT', $a_set['RELATE_MEETING_TXT']);
        $this->tpl->setVariable('RELATE_MEETING_DATA', $a_set['RELATE_MEETING_DATA']);
        $this->tpl->setVariable('RELATE_MEETING_HIDE', $a_set['RELATE_MEETING_HIDE']);

        // DELETE MEETING
        $this->tpl->setVariable('DELETE_TXT', $a_set['DELETE_TXT']);
        $this->tpl->setVariable('DELETE_DATA', $a_set['DELETE_DATA']);
        $this->tpl->setVariable('HIDE_DELETE', $a_set['HIDE_DELETE']);


        // MEETING DETAILS
        // Modal only for Admin
        if ($this->dic->access()->checkAccess("write", "", $this->parent_obj->getRefId())) {
            // if ( $this->parent_obj->vcObj->isUserAdmin() ) {
            $this->tpl->setVariable('MODAL_DATA', $a_set['MODAL_DATA']);
        } else {
            $this->tpl->setVariable('MODAL_HIDE', 'hidden');
        }



    }

    /**
     * @throws ilDateTimeException
     */
    private function initFilterDateDurationAndDataSource(): void
    {
        # DATE DURATION
        // $this->tpl->addJavaScript("./Services/Form/js/Form.js");
        $this->filterItemDateDuration = new ilDateDurationInputGUI($this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_duration'), 'date_duration');
        $this->filterItemDateDuration->setAllowOpenIntervals(true);
        $this->filterItemDateDuration->setShowTime(true);
        $this->filterItemDateDuration->setStartText($this->dic->language()->txt('from'));
        $this->filterItemDateDuration->setEndText($this->dic->language()->txt('to'));
        $this->addFilterItem($this->filterItemDateDuration, false);


        # DATA SOURCE
        $this->filterItemDataSource = new ilSelectInputGUI($this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_data_source'), 'data_source');
        $dataSourceOptions = [];
        if((int)$this->dic->user()->getId() === (int)$this->parent_obj->object->getOwner()) {
            $dataSourceOptions['true'] = $this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_both_data_sources');
        }
        $dataSourceOptions['false'] = $this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_local_data_sources');
        $this->filterItemDataSource->setOptions($dataSourceOptions);
        $this->addFilterItem($this->filterItemDataSource, false);


        if(!$this->keepFilterValues) {
            $this->filterItemDataSource->setValue('false');
            $this->setFilterDateDurationDefaultValues();
        } else {
            $this->writeFilterToSession();
            $this->filterItemDataSource->readFromSession();
            $this->filterItemDateDuration->readFromSession();
        }

        $this->dataSource = $this->filterItemDataSource->getValue() === 'true';
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
        $limitDays = 60 * 60 * 24 * 14;

        $this->dateEnd = new ilDateTime((new DateTime('now', new DateTimeZone('UTC')))->getTimestamp(), IL_CAL_UNIX);
        $endDateYmd = date('Y-m-d', $this->dateEnd->getUnixTime()+$limitDays);
        $this->dateEnd->setDate($endDateYmd . ' 23:59:59', IL_CAL_DATETIME);

        $this->dateStart = new ilDateTime((new DateTime('now', new DateTimeZone('UTC')))->getTimestamp(), IL_CAL_UNIX);
        $startDateYmd = date('Y-m-d', $this->dateStart->getUnixTime()-$limitDays);
        $this->dateStart->setDate($startDateYmd . ' 00:00:00', IL_CAL_DATETIME);
        #$this->dateStart->setDate($this->dateStart->getUnixTime()-$limitDays, IL_CAL_UNIX);

        $this->filterItemDateDuration->setEnd($this->dateEnd);
        $this->filterItemDateDuration->setStart($this->dateStart);
        $this->filterItemDateDuration->writeToSession();
    }

    /**
     * @throws ilDateTimeException
     * @throws Exception
     */
    private function setFilterValuesBySessionVar(): void
    {
        #ilSession::clear('scheduleMeetingRequestParam');
        $sessData = ilSession::get('scheduleMeetingRequestParam');
        $pattern = "%([\d]{4}-[\d]{2}-[\d]{2}\s[\d]{2}:[\d]{2}|[\d]{2}.[\d]{2}.[\d]{4}\s[\d]{2}:[\d]{2})%";
        //die(var_dump($sessData));
        if($sessData != null && is_array($sessData) && isset($sessData['fml_date_duration']['start']) 
        && (bool)preg_match($pattern, $sessData['fml_date_duration']['start'], $match)) {
            $dt = new DateTime($match[0]);
            $dateStart = $dt->format('Y-m-d H:i:s');
            $this->dateStart->setDate($dateStart, IL_CAL_DATETIME);
            $this->getFilterItemDateDuration()->setStart($this->dateStart);
        }
        if($sessData != null && is_array($sessData) && isset($sessData['fml_date_duration']['end']) && (bool)preg_match($pattern, $sessData['fml_date_duration']['end'], $match)) {
            $dt = new DateTime($match[0]);
            $dateEnd = $dt->format('Y-m-d H:i:s');
            $this->dateEnd->setDate($dateEnd, IL_CAL_DATETIME);
            $this->getFilterItemDateDuration()->setEnd($this->dateEnd);
            unset($sessData['fml_date_duration']);
        }
        if($sessData != null && is_array($sessData) && isset($sessData['fml_data_source'])) {
            $this->getFilterItemDataSource()->setValue($sessData['fml_data_source']);
            unset($sessData['fml_data_source']);
        }
        ilSession::clear('scheduleMeetingRequestParam');
        ilSession::set('scheduleMeetingRequestParam', $sessData);
        #var_dump(ilSession::get('scheduleMeetingRequestParam')); exit;
    }


    ############################################################################################################
    #### CREATE/EDIT MEETING FORM
    ############################################################################################################

    public function getHtmlMeetingPropertiesAndOverview(bool $keepForm = false, ?string $cmd =  'create'): string
    {
        // prepare meeting properties form
        $this->initFormMeetingProperties();

        $this->meetingPropertiesForm->setFormAction($this->getFormAction());

        $this->meetingPropertiesForm->setTitle($this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_' . $cmd));
        $this->meetingPropertiesForm->setId('meeting_create');

        switch ($cmd) {
            case 'create':
            case 'update':
            default:
                $this->meetingPropertiesForm->setValuesByArray(
                    $this->getDefaultMeetingProperties()
                );

                $scheduleMeetingRequestParam = ilSession::get('scheduleMeetingRequestParam');
                if ($keepForm) {
//                    $this->keepFilterValues = true;
                    //todo
//                    var_dump($scheduleMeetingRequestParam);exit;
//                    $arReqParam = (array)$scheduleMeetingRequestParam;
//                    if (is_array($arReqParam)) {
//                        $this->meetingProperty['meeting_title'] = $scheduleMeetingRequestParam['meeting_title'];
//                        var_dump($scheduleMeetingRequestParam['meeting_title']); exit;
//                        if (is_array($arReqParam['meeting_duration'])) {
//                            $this->meetingProperty['duration']->setStart($arReqParam['meeting_duration']['start']);
//                        }
//                    }
                    $keepForm = false;
                    $this->meetingPropertiesForm->setValuesByArray($scheduleMeetingRequestParam);

                    //                die(var_dump($scheduleMeetingRequestParam));
//                if(!empty($scheduleMeetingRequestParam) 
//                && isset($scheduleMeetingRequestParam->keepCreateMeetingForm)){ 
//                && (bool)$scheduleMeetingRequestParam['keepCreateMeetingForm']) {
                    #$this->meetingProperty['duration']->setStart();
                    #$this->meetingProperty['duration']->setEnd();
//                    $this->meetingPropertiesForm->setValuesByArray(
//                        $scheduleMeetingRequestParam
//                    );
//                    $scheduleMeetingRequestParam['keepCreateMeetingForm'] = false;
                    ilSession::set('scheduleMeetingRequestParam', $scheduleMeetingRequestParam);
                } 
                else {
                    $this->meetingProperty['duration']->setStart(new ilDateTime((int) date('U') + ilObjMultiVc::MEETING_TIME_AHEAD, IL_CAL_UNIX));
                    $this->meetingProperty['duration']->setEnd(new ilDateTime((int) date('U') + self::MEETING_DURATION + ilObjMultiVc::MEETING_TIME_AHEAD, IL_CAL_UNIX));
                }
//var_dump($this->filterItemDateDuration->getStart());exit;
                // Form Elem To Keep Filter Values
                $this->meetingProperty['filter_date_duration_start']->setValue($this->filterItemDateDuration->getStart());
                $this->meetingProperty['filter_date_duration_end']->setValue($this->filterItemDateDuration->getEnd());
                $this->meetingProperty['filter_data_source']->setValue($this->filterItemDataSource->getValue());

                // Command Buttons
                $this->meetingPropertiesForm->addCommandButton('meeting_' . $cmd, $this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_create_btn'));
                $this->meetingPropertiesForm->setShowTopButtons(false);
                break;
        }

        // keep open properties form and tableGui form
        #$this->meetingPropertiesForm->setCloseTag(false);
        #$this->setOpenFormTag(false);

        // return both together as html
        #return $this->getHTML() . $this->meetingPropertiesForm->getHTML();
        $returnHtml = '';
        if((int)$this->dic->user()->getId() === (int)$this->parent_obj->object->getOwner()) {
            $returnHtml .= $this->meetingPropertiesForm->getHTML();
        }
        $returnHtml .= $this->getHTML();

        return $returnHtml;
    }

    public function getMeetingPropertiesForm(): ilPropertyFormGUI
    {
        if(!($this->meetingPropertiesForm instanceof ilPropertyFormGUI)) {
            $this->initFormMeetingProperties();
        }
        return $this->meetingPropertiesForm;
    }

    private function initFormMeetingProperties()
    {
        $this->meetingProperty['title'] = new ilTextInputGUI($this->dic->language()->txt('rep_robj_xmvc_title'), 'meeting_title');
        $this->meetingProperty['title']->setRequired(true);

        if($this->parent_obj->isEdudip) {
            $this->meetingProperty['agenda'] = new ilHiddenInputGUI('meeting_agenda');
        } else {
            $this->meetingProperty['agenda'] = new ilTextAreaInputGUI($this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_agenda'), 'meeting_agenda');
        }

        $this->meetingProperty['duration'] = new ilDateDurationInputGUI($this->dic->language()->txt('rep_robj_xmvc_scheduled_' . $this->parent_obj->sessType . '_duration'), 'meeting_duration');
        $this->meetingProperty['duration']->setRequired(true);
        $this->meetingProperty['duration']->setShowTime(true);

        /*
        $this->meetingProperty['recurrence'] = new ilSelectInputGUI($this->dic->language()->txt('rep_robj_xmvc_scheduled_meeting_recurrence'), 'meeting_recurrence');
        $this->meetingProperty['recurrence']->setOptions([
            1 => 'DAILY',
            0 => 'NONE',
            2 => 'WEEKLY',
            3 => 'YEARLY'
        ]);
        */

        /*
        if( !(bool)$this->parent_obj->object->getAccessToken() ) {
            $this->meetingProperty['hostEmail'] = new ilEMailInputGUI($this->dic->language()->txt('rep_robj_xmvc_webex_host_email'), 'meeting_host_email');
            $this->meetingProperty['hostEmail']->setRequired(true);
        } else {
            $this->meetingProperty['hostEmail'] = new ilHiddenInputGUI('host_email');
        }
        */

        // KEEP FILTER VALUES
        $this->meetingProperty['filter_date_duration_start'] = new ilHiddenInputGUI('fml_date_duration[start]');
        $this->meetingProperty['filter_date_duration_end'] = new ilHiddenInputGUI('fml_date_duration[end]');
        $this->meetingProperty['filter_data_source'] = new ilHiddenInputGUI('fml_data_source');
        $this->meetingProperty['filter_data_source']->setValue($this->filterItemDataSource->getValue());
        #$this->meetingProperty['filter_data_source']->writeToSession();

        // APPLY FILTER CMD
        #$this->meetingProperty['apply_filter'] = new ilHiddenInputGUI('cmd[applyFilterScheduledMeetings]');

        // DELETE MEETING HIDDEN FIELDS
        $this->meetingProperty['dsm_ref_id'] = new ilHiddenInputGUI('delete_scheduled_meeting[ref_id]');
        $this->meetingProperty['dsm_delete_local_only'] = new ilHiddenInputGUI('delete_scheduled_meeting[delete_local_only]');
        $this->meetingProperty['dsm_start'] = new ilHiddenInputGUI('delete_scheduled_meeting[start]');
        $this->meetingProperty['dsm_end'] = new ilHiddenInputGUI('delete_scheduled_meeting[end]');
        $this->meetingProperty['dsm_timezone'] = new ilHiddenInputGUI('delete_scheduled_meeting[timezone]');
        $this->meetingProperty['dsm_cmd'] = new ilHiddenInputGUI('delete_scheduled_meeting[cmd]');

        // RELATE MEETING HIDDEN FIELDS
        $this->meetingProperty['rwm_ref_id'] = new ilHiddenInputGUI('relate_meeting[ref_id]');
        $this->meetingProperty['rwm_start'] = new ilHiddenInputGUI('relate_meeting[start]');
        $this->meetingProperty['rwm_end'] = new ilHiddenInputGUI('relate_meeting[end]');
        $this->meetingProperty['rwm_timezone'] = new ilHiddenInputGUI('relate_meeting[timezone]');
        $this->meetingProperty['rwm_rel_id'] = new ilHiddenInputGUI('relate_meeting[rel_id]');
        $this->meetingProperty['rwm_rel_data'] = new ilHiddenInputGUI('relate_meeting[rel_data]');
        $this->meetingProperty['rwm_cmd'] = new ilHiddenInputGUI('relate_meeting[cmd]');


        $this->meetingPropertiesForm = new ilPropertyFormGUI();

        foreach ($this->meetingProperty as $property) {
            $this->meetingPropertiesForm->addItem($property);
        }

        /*
        $sessSchedMeets = ilSession::get('form_scheduled_meetings');
        echo '<pre>';
        var_dump($sessSchedMeets);
        var_dump($_SESSION);
        echo '</pre>';
        exit;
        */
    }

    private function getDefaultMeetingProperties(): array
    {
        return [
            'meeting_title'     => $this->parent_obj->object->getTitle(),
            'meeting_agenda'     => '',
            #'meeting_recurrence'    => 0,
            #'meeting_host_email'     => $this->dic->user()->getEmail()
        ];
        // todo
        /*
        return array_replace([
            'meeting_title'     => $this->parent_obj->object->getTitle(),
            'meeting_agenda'     => '',
            #'meeting_recurrence'    => 0,
            'meeting_host_email'     => $this->dic->user()->getEmail()
        ], ilSession::get('scheduleMeetingRequestParam') ?? []);
        */
    }

    /**
     * @return array
     */
    public function getMeetingRequestParam(): array
    {
        return $this->meetingRequestParam;
    }

    /**
     * @param array $meetingRequestParam
     */
    public function setMeetingRequestParam(array $meetingRequestParam): void
    {
        $this->meetingRequestParam = $meetingRequestParam;
    }

    /**
     * @return ilDateDurationInputGUI
     */
    public function getFilterItemDateDuration(): ilDateDurationInputGUI
    {
        return $this->filterItemDateDuration;
    }

    /**
     * @return ilSelectInputGUI
     */
    public function getFilterItemDataSource(): ilSelectInputGUI
    {
        return $this->filterItemDataSource;
    }

    ############################################################################################################
    #### HOST SESSION
    ############################################################################################################

    /**
     * @return bool
     * @throws ilCurlConnectionException
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    private function getSessionsFromHost(): bool
    {
        switch(true) {
            case $this->parent_obj->isWebex:
                $this->getMeetingsFromWebex();
                break;
            case $this->parent_obj->isEdudip:
                $this->getSessionsFromEdudip();
                break;
            default:
                return false;
        }
        return true;
    }


    ############################################################################################################
    #### GET MEETING FROM Webex
    ############################################################################################################

    private function getMeetingsFromWebex(): bool
    {
        $isAdminAuth = $this->plugin_settings->getAuthMethod() === 'admin';

        $param = [
            'from'  => date('Y-m-d H:i:s', $this->dateStart->getUnixTime()),
            'to'  => date('Y-m-d H:i:s', $this->dateEnd->getUnixTime()),
            'max' => 100
        ];

        if($isAdminAuth) {
            $param['hostEmail'] = $this->parent_obj->object->getAuthUser();
            $accessToken = $this->plugin_settings->getAccessToken();
        } else {
            $accessToken = $this->parent_obj->object->getAccessToken();
        }

        $response = json_decode($this->parent_obj->vcObj->restfulApiCall($this->parent_obj->vcObj::ENDPOINT_MEETINGS, 'get', $param));
        $this->parent_obj->object->deleteStoredHostSessionById($this->refId);
        #die(var_dump($response->items[0]));
        if(is_object($response) && is_array($response->items)) {#is_object($response->items['0'])) {
            $items = [];
            foreach ($response->items as $key => $session) {
                $session = json_decode(json_encode($session), 1);
                #echo '<pre>'; var_dump([$session["hostEmail"], $this->parent_obj->object->getAuthUser()]); exit;
                if($session["hostEmail"] === $this->parent_obj->object->getAuthUser()) {
                    $session["email"] = $session["hostEmail"];

                    $items[] = [
                        'start'     => $session['start'],
                        'end'       => $session['end'],
                        'timezone'  => $session['timezone'],
                        'rel_data'  => json_encode($session),
                        'host'      => 'webex',
                        'type'      => $session['meetingType'],
                        'ref_id'    => $this->parent_obj->object->getRefId(),
                        'rel_id'    => $session['id'],
                    ];
                }
            }
            #echo '<pre>'; var_dump($response->items[0]); exit;
            if($storeWebexMeeting = $this->parent_obj->object->storeHostSession($this->refId, $items)) {
                #if( $storeWebexMeeting = $this->parent_obj->object->storeWebexMeeting($this->refId, $response->items) ) {
                return (bool)$storeWebexMeeting;
            }
            //return ($this->parent_obj->object->storeHostSession($this->refId, $items));

        }
        return false;
        #var_dump($response->items);
        #exit;

    }



    #### EDUDIP

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     * @throws ilCurlConnectionException
     */
    private function getSessionsFromEdudip(): void
    {
        $edudip = $this->parent_obj->vcObj ?? new ilApiEdudip($this->parent_obj);
        /*
        $this->parent_obj->object->checkAndSetMultiVcObjUserAsAuthUser(
            $this->dic->user()->getId(),
            $this->dic->user()->getEmail(),
            true, true
        );
        */
        if((bool)strlen($sessions = $edudip->sessionList())) {
#            echo '<pre>'; var_dump($sessions); echo '</pre>'; exit;
            $items = [];
            try {
                foreach (json_decode($sessions, true)["webinars"] as $key => $session) {
                    #echo '<pre>'; var_dump($session); exit;
                    if ($session["moderators"][0]["email"] === $this->parent_obj->object->getAuthUser()) {
                        $session["email"] = $session["moderators"][0]["email"];
                        $session["start"] = $session['dates'][0]['date'];
                        $session["end"] = $session['dates'][0]['date_end'];
                        $session["timezone"] = $this->dic->user()->getTimeZone();


                        $items[] = [
                            'start' => $session['dates'][0]['date'],
                            'end' => $session['dates'][0]['date_end'],
                            'timezone' => $this->dic->user()->getTimeZone(),
                            'rel_data' => json_encode($session),
                            'host' => 'edudip',
                            'type' => 'webinar',
                            'ref_id' => $this->parent_obj->object->getRefId(),
                            'rel_id' => $session['id'],
                        ];
                    }
                }

                $this->parent_obj->object->deleteStoredHostSessionById($this->refId);
                if ((bool)sizeof($items)) {
                    if ($storeHostSession = $this->parent_obj->object->storeHostSession($this->refId, $items)) {
                        //                    return (bool)$storeHostSession;
                    }
                }
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $this->dic->language()->txt('rep_robj_xmvc_edudip_meetings_list_downloaded'), true);
            } catch (Exception $e) {
                $this->dic->ui()->mainTemplate()->setOnScreenMessage('info', $this->dic->language()->txt('rep_robj_xmvc_edudip_meetings_list_unavailabe'), true);
            }

        }

        # json_decode($sessions,1)["webinars"][0]["moderators"][0]["email"]
    }

}

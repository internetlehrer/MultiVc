<?php

/**
 * MultiVc plugin: report logged max concurrent values table GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */
class ilMultiVcTableGUIRecordingsBBB extends ilTable2GUI
{
    private ILIAS\DI\Container $dic;

    protected ?object $parent_obj;

    protected ?ilMultiVcConfig $plugin_settings;

    /**
     * ilMultiVcReportLogMaxTableGUI constructor.
     * @throws ilPluginException
     */
    public function __construct($a_parent_obj, $a_parent_cmd = '', string $a_template_context = '')
    {
        global $DIC;

        $this->dic = $DIC;

        $this->parent_obj = $a_parent_obj;

        $this->plugin_settings = ilMultiVcConfig::getInstance($this->parent_obj->object->getConnId());

        $this->setId('table_recordings_bbb');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        $this->addColumn($this->dic->language()->txt($this->parent_obj->getVcObj()->isUserModerator() || $this->parent_obj->getVcObj()->isUserAdmin() ? 'select' : 'status'), '', '8%');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_starttime'), 'START_TIME', '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_endtime'), 'END_TIME', '');
        $this->addColumn($this->dic->language()->txt('rep_robj_xmvc_duration'), '', '');
        $this->addColumn('', '', '');
        $this->addColumn('', '', '');
        /*
         * $this->addColumn($this->dic->language()->txt('playback'), '', '');
        $this->addColumn($this->dic->language()->txt('download'), '', '');
         */


        $this->setFormAction($this->dic->ctrl()->getFormAction($this->parent_obj, 'showContent'));
        $this->setEnableHeader(true);

        $this->setExternalSorting(false);
        $this->setExternalSegmentation(false);
        $this->setShowRowsSelector(false);

        $this->setDefaultOrderField('START_TIME'); # display_name join_time
        $this->setDefaultOrderDirection('asc');
        $this->enable('sort');

        $this->setRowTemplate('tpl.recordings_bbb_table_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->setEnableNumInfo(false);

        if($this->parent_obj->getVcObj()->isUserModerator()) {
            $this->addMultiCommand('confirmDeleteRecords', $DIC->language()->txt('delete'));
            $this->addMultiCommand('setRecordAvailable', $DIC->language()->txt('rep_robj_xmvc_unlock'));
            $this->addMultiCommand('setRecordLocked', $DIC->language()->txt('rep_robj_xmvc_lock'));
            $this->setTopCommands(false);
            $this->setSelectAllCheckbox('rec_id');
        }

    }


    /**
     * Fill a single data row.
     * @throws ilDateTimeException
     */
    protected function fillRow(array $a_set): void
    {
        #echo '<pre>'; var_dump($a_set); exit;

        $duration = (int) $a_set['END_TIME'] - (int) $a_set['START_TIME'];
        $a_set['START_TIME'] = new ilDateTime($a_set['START_TIME'], IL_CAL_UNIX);
        $a_set['END_TIME'] = new ilDateTime($a_set['END_TIME'], IL_CAL_UNIX);

        // SHOW SELECT COLUMN
        if($this->parent_obj->getVcObj()->isUserModerator() || $this->parent_obj->getVcObj()->isUserAdmin()) {
            #$this->tpl->setVariable('UNHIDE_SELECT_COL', 'un');
        }

        // SHOW MEDIA LINKS IF AVAILABLE
        if((bool) $a_set['available'] || $this->parent_obj->getVcObj()->isUserModerator() || $this->parent_obj->getVcObj()->isUserAdmin()) {
            $this->tpl->setVariable('UNHIDE_LINK', 'un');
        }

        // SHOW SYMBOL LOCKED

        if(!(bool) $a_set['available']) {
            $this->tpl->setVariable('UNHIDE_LOCKED', 'un');
        } else {
            $this->tpl->setVariable('UNHIDE_UNLOCKED', 'un');
        }


        // REMOVE MEDIA LINKS FOR USERS
        if(!(bool) $a_set['available'] && !($this->parent_obj->getVcObj()->isUserModerator() || $this->parent_obj->getVcObj()->isUserAdmin())) {
            $a_set['playback'] = '-';
            $a_set['download'] = '-';
        }

        $updateInfo =
        $updDate = '';
        if($this->parent_obj->getVcObj()->isUserModerator() || $this->parent_obj->getVcObj()->isUserAdmin()) {
            $this->tpl->setVariable('ROWSELECTOR', $a_set['rowSelector']);
            $this->tpl->setVariable('FLOAT_RIGHT', 'float:right; ');
        }

        if(!is_null($a_set['updated_by'])) {
            $a_set['update_date'] = new ilDateTime($a_set['update_date'], IL_CAL_DATETIME);
            $updDate = ilDatePresentation::formatDate($a_set['update_date']);
            $updByUserName = ilObjUser::_lookupFullname($a_set['updated_by']);
            $updateInfo = $this->dic->language()->txt((bool) $a_set['available'] ? 'rep_robj_xmvc_unlocked_by' : 'rep_robj_xmvc_locked_by') . ': ' . $updByUserName . ', ' . $updDate . "\r\n";
        }
        $duration = ilDatePresentation::secondsToString($duration);
        $updateInfo .= $this->dic->language()->txt((bool) $a_set['available'] ? 'rep_robj_xmvc_rec_visible' : 'rep_robj_xmvc_rec_hidden');
        $this->tpl->setVariable('TXT_UPDATE_INFO', $updateInfo);

        $this->tpl->setVariable('STARTTIME', ilDatePresentation::formatDate($a_set['START_TIME']));
        $this->tpl->setVariable('ENDTIME', ilDatePresentation::formatDate($a_set['END_TIME']));
        $this->tpl->setVariable('DURATION', $duration);
        $this->tpl->setVariable('PLAYBACK', $a_set['playback']);
        $this->tpl->setVariable('TXT_PLAYBACK', $this->dic->language()->txt('rep_robj_xmvc_playback'));
        #$this->tpl->setVariable('REC_LENGTH', $duration);
        if (file_exists($a_set['rec_id'] . '.mp4')) {
            $this->tpl->setVariable('DOWNLOAD', $a_set['download']);
            $this->tpl->setVariable('TXT_DOWNLOAD', $this->dic->language()->txt('rep_robj_xmvc_download'));
            $this->tpl->setVariable('REC_ID', $a_set['rec_id'] . '.mp4');
        }

    }


    public function addRowSelector(array $a_data): array
    {
        $refId = $this->parent_obj->object->getRefId();
        $hideRecsUntilDate = $this->plugin_settings->getHideRecsUntilDate();
        $returnData = [];
        #echo '<pre>'; var_dump($a_data); exit;
        // STORE NEW DATA TO DB
        foreach ($a_data as $key => $data) {
            $this->parent_obj->object->storeBBBRec(
                $refId,
                $key,
                $data['meetingId'],
                $data['END_TIME']
            );
        } // EOF foreach ($a_data as $a_datum)

        // GET FILTERED DATA FROM DB AND ADD BBB-DATA
        #echo '<pre>'; var_dump([$hideRecsUntilDate, $this->parent_obj->object->getBBBRecsByRefId($refId, $hideRecsUntilDate)]); exit;
        foreach($this->parent_obj->object->getBBBRecsByRefId($refId, $hideRecsUntilDate) as $key => $data) {
            if(!is_array($a_data[$key])) {
                $a_data[$key] = [
                    'START_TIME' => '',
                    'END_TIME' => '',
                    'playback' => '',
                    'download' => ''
                ];
            }
            $returnData[$key] = array_merge($data, $a_data[$key]);
            $checkbox = new ilCheckboxInputGUI('', 'rec_id[]');
            $checkbox->setValue($key);
            $checkbox->setChecked($this->dic->http()->wrapper()->post()->has('rec_id') && array_search($a_data[$key], $this->dic->http()->wrapper()->post()->retrieve('rec_id', $this->dic->refinery()->kindlyTo()->listOf($this->dic->refinery()->kindlyTo()->string()))));
            $returnData[$key]['rowSelector'] = $checkbox->render();
        }
        #echo '<pre>'; var_dump([$returnData]); exit;
        return $returnData;
    }

}

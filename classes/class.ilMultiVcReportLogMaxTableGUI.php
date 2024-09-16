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
class ilMultiVcReportLogMaxTableGUI extends ilTable2GUI
{
    /**
     * ilMultiVcReportLogMaxTableGUI constructor.
     */
    public function __construct(object $a_parent_obj, string $a_parent_cmd = '', string $a_template_context = '')
    {
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
        $wS = '10%';
        $wM = '20%';
        $wL = '30%';
        $this->addColumn($this->lng->txt('year'), 'year', $wS);
        $this->addColumn($this->lng->txt('month'), 'month', $wS);
        $this->addColumn($this->lng->txt('day'), 'day', $wS);
        $this->addColumn($this->lng->txt('hour'), 'hour', $wS);
        $this->addColumn($this->lng->txt('rep_robj_xmvc_max_meetings'), 'max_meetings', $wS);
        $this->addColumn($this->lng->txt('rep_robj_xmvc_max_users'), 'max_users', $wS);
        $this->addColumn($this->lng->txt('rep_robj_xmvc_url'), 'usage', 'auto');
        $this->setEnableHeader(true);
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->addCommandButton('downloadCsv', $this->lng->txt('export'));
        //$this->addCommandButton('createType', $lng->txt('rep_robj_xxcf_create_type'));
        // ToDo: check
        // $this->addCommandButton('viewLogs', $lng->txt('rep_robj_xxcf_view_logs'));
        $this->setRowTemplate('tpl.report_log_max_concurrent_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
        $this->getMyDataFromDb();

    }

    public function downloadCsv()
    {
        $this->exportData(2, true);
    }

    /**
     * Get data and put it into an array
     */
    public function getMyDataFromDb()
    {
        //$this->plugin_object->includeClass('class.ilObjMultiVc.php');
        // get types data with usage info
        $db = new ilObjMultiVc();
        $this->setDefaultOrderField('year');
        $this->setDefaultOrderDirection('desc');
        $this->setData($this->getDataMaxConcurrent());
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set): void
    {
        global $DIC;

        $DIC->ctrl()->setParameter($this->parent_obj, 'year', $a_set['year']);

        $this->tpl->setVariable('TXT_YEAR', $a_set['year']);
        $this->tpl->setVariable('TXT_MONTH', $a_set['month']);
        $this->tpl->setVariable('TXT_DAY', $a_set['day']);
        $this->tpl->setVariable('TXT_HOUR', $a_set['hour']);
        $this->tpl->setVariable('TXT_MAX_MEETINGS', $a_set['max_meetings']);
        $this->tpl->setVariable('TXT_MAX_USERS', $a_set['max_users']);
        $this->tpl->setVariable('TXT_URL', $a_set['url']);

    }

    private function getDataMaxConcurrent(): array
    {
        $data = [];
        $objMc = new ilObjMultiVc();
        $dbTableData = $objMc->getMaxConcurrent();
        foreach ($dbTableData as $row => $column) {
            if (!(bool) ($logData = unserialize($column['log']))) {
                $data[] = array_replace($column, ['url' => '']);
            } else {
                $newRow = $this->createRowFromLog($column);
                foreach ($newRow as $item) {
                    $data[] = $item;
                } // EOF foreach ($newRow as $item)
            }
        } // EOF foreach($dbTableData as $row => $column)
        //var_dump($data); exit;
        return $data;
    }

    private function createRowFromLog(array $row): array
    {
        $data = [];
        foreach (unserialize($row['log']) as $url => $meetings) {
            $nMeetings = $nUsers = 0;
            foreach($meetings as $meeting => $users) {
                $nUsers += $users;
                $nMeetings++;
            }
            $newRow = [
                'max_meetings' => $nMeetings,
                'max_users' => $nUsers,
                'url' => $url
            ];
            $data[] = array_replace($row, $newRow);
        }
        //var_dump($data); exit;
        return $data;
    }

}

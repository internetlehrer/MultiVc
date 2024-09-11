<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

/**
 * Class ilMultiVcCron
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 */

class ilMultiVcCron extends ilCronJob
{
    protected \ILIAS\DI\Container $dic;
    const JOB_ID = 'multivc_lp';

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->dic->logger()->root()->debug('init MultiVc CronJob');
        $this->dic->language()->loadLanguageModule("rep_robj_xmvc");
    }

    public function getId() : string
    {
        return self::JOB_ID;
    }

    public function getTitle() : string
    {
        return $this->dic->language()->txt("rep_robj_xmvc_cronjob_title");
    }

    public function getDescription() : string
    {
        return $this->dic->language()->txt("rep_robj_xmvc_cronjob_description");
    }

    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_DAILY;
    }

    public function getDefaultScheduleValue(): int
    {
        return 1;
    }

    public function hasAutoActivation() : bool
    {
        return true;
    }

    public function hasFlexibleSchedule() : bool
    {
        return false;
    }

    public function run(): ilCronJobResult
    {
        $cronResult = new ilCronJobResult();
        $cronResult->setStatus(ilCronJobResult::STATUS_NO_ACTION);

        try {
            $this->execJob();
            $cronResult->setStatus(ilCronJobResult::STATUS_OK);
        } catch(Exception $e) {
            $cronResult->setStatus(ilCronJobResult::STATUS_FAIL);
            $this->dic->logger()->root()->log($e->getMessage());
        }

        return $cronResult;
    }

    private function execJob() : void
    {
        $ilDB = $this->dic->database();
        $this->dic->logger()->root()->debug("multivc cron exec");
        $teamsObjects = [];
        //teams = UTC
        date_default_timezone_set('UTC');
        $query = "SELECT rep_robj_xmvc_session.obj_id, rep_robj_xmvc_data.lp_time,"
            ." rep_robj_xmvc_conn.svrusername as client, rep_robj_xmvc_conn.svrsalt as secret, rep_robj_xmvc_conn.svrpublicurl as tenant"
            ." FROM rep_robj_xmvc_session, rep_robj_xmvc_data, rep_robj_xmvc_conn"
            ." WHERE rep_robj_xmvc_data.id = rep_robj_xmvc_session.obj_id AND rep_robj_xmvc_conn.id = rep_robj_xmvc_data.conn_id"
            ." AND rep_robj_xmvc_data.lp_mode>0 AND rep_robj_xmvc_conn.showcontent='teams'"
            ." AND ISNULL(rep_robj_xmvc_session.cron) AND rep_robj_xmvc_session.end < ". $ilDB->quote(date('Y-m-d H:i:s'),'timestamp')
            ." GROUP BY rep_robj_xmvc_session.obj_id"
        ;
        $this->dic->logger()->root()->debug($query);
        $res = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($res)) {
            $teamsObjects[] = $row;
        }

        foreach ($teamsObjects as $teamsObject) {
            $refIds = ilObject::_getAllReferences($teamsObject['obj_id']);
            foreach ($refIds as $refId) {
                //ilObjMultiVc::LP_ACTIVE
                $meetingIds = ilApiTeams::getAttendanceReport((int) $teamsObject['obj_id'], $refId, 1, (int) $teamsObject['lp_time'], $teamsObject['client'], $teamsObject['secret'], $teamsObject['tenant']);
                if (isset($meetingIds)) {
                    $query = 'UPDATE rep_robj_xmvc_session SET cron=1 WHERE '. $ilDB->in('rel_id', $meetingIds, false, 'string');
                    $this->dic->logger()->root()->debug($query);
                    $ilDB->query($query);
                }
            }
        }
    }

    public static function installCronJob(ilMultiVcPlugin $plugin)
    {
        global $DIC;
        if (isset($DIC['cron.repository'])) {
            $job = $DIC->cron()->repository()->getJobInstance(self::JOB_ID, 'Plugins/MultiVc', self::class,
                false); //Todo Check false
            $DIC->cron()->repository()->createDefaultEntry($job, 'Plugins/MultiVc', self::class,
                $plugin->getDirectory() . '/classes/');
        }
    }

    public static function uninstallCronJob(ilMultiVcPlugin $plugin)
    {
        global $DIC;
        if (isset($DIC['cron.repository'])) {
            $DIC->cron()->repository()->unregisterJob('Plugins/MultiVc', []);
        }
//        ilCronManager::clearFromXML($plugin::PLUGIN_COMPONENT, []);
    }

}
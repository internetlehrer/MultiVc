<?php
/**
 * Class ilMultiVcLPStatus
 *
 * This class is an adapter to access protected methods of ilLPStatusPlugin
 * It is used by the ilLPStatusPluginInterface methods of ilObjMultiVc
 *
 * The plugin stores the lp status using ilLearningProgress::_tracProgress() and ilLPStatusWrapper::_updateStatus()
 * This data is stored in the common learning progress tables and can be taken from there.
 * It must, however, be deliverd by methods of ilObjMultiVc
 *
 * Example:
 * - ilLPStatusWrapper::getInProgress($a_obj_id) calls ilLPStatusPlugin::_getInProgress($a_obj_id)
 * - ilLPStatusPlugin::_getInProgress($a_obj_id) creates $obj and calls $obj->getLPInProgress()
 * The status can't be read there via public methods of ilLPStatusWrapper or ilLPStatusPlugin (would be a loop)
 */

class ilMultiVcLPStatus extends ilLPStatusPlugin
{
    /**
     * Get the LP status data directly from the database table
     * This can be called from ilObjMultiVc::getLP* methods avoiding loops
     */
    public static function getLPStatusDataFromDb($a_obj_id, $a_status): array
    {
        return self::getLPStatusData((int) $a_obj_id, (int) $a_status);
    }

    /**
     * Get the LP data directly from the database table
     * This can be called from ilObjMultiVc::getLP* methods avoiding loops
     *
     * @param $a_obj_id
     * @param $a_user_id
     * @return int
     */
    public static function getLPDataForUserFromDb($a_obj_id, $a_user_id): int
    {
        try {
            return self::getLPDataForUser((int) $a_obj_id, (int) $a_user_id);
        }
        catch (Exception $e) {
            return self::LP_STATUS_NOT_ATTEMPTED_NUM;
        }
    }


    /**
     * Track read access to the object
     * Prevents a call of determineStatus() that would return "not attempted"
     * @see ilLearningProgress::_tracProgress()
     *
     * @param $a_user_id
     * @param $a_obj_id
     * @param $a_ref_id
     */
    public static function trackAccess($a_user_id, $a_obj_id, $a_ref_id)
    {
        ilChangeEvent::_recordReadEvent('xmvc', (int) $a_ref_id, (int) $a_obj_id, (int) $a_user_id);

        try {
            $status = self::getLPDataForUser((int) $a_obj_id, (int) $a_user_id);
        }
        catch (Exception $e) {
            $status = self::LP_STATUS_NOT_ATTEMPTED_NUM;
        }
        if ($status == self::LP_STATUS_NOT_ATTEMPTED_NUM)
        {
            self::writeStatus((int) $a_obj_id, (int) $a_user_id, self::LP_STATUS_IN_PROGRESS_NUM);
            self::raiseEventStatic((int) $a_obj_id, (int) $a_user_id, self::LP_STATUS_IN_PROGRESS_NUM,
                self::getPercentageForUser((int) $a_obj_id, (int) $a_user_id));
        }
    }

    /**
     * Track result from the external content
     *
     * @param $a_user_id
     * @param $a_obj_id
     * @param $a_status
     * @param $a_percentage
     */
    public static function trackResult($a_user_id, $a_obj_id, $a_status, $a_percentage)
    {
        self::writeStatus((int) $a_obj_id, (int) $a_user_id, (int) $a_status, (int) $a_percentage, true);
        self::raiseEventStatic((int) $a_obj_id, (int) $a_user_id, (int) $a_status, (int) $a_percentage);
    }

    /**
     * Static version if ilLPStatus::raiseEvent
     * This function is just a workaround for PHP7 until ilLPStatus::raiseEvent is declared as static
     *
     * @param $a_obj_id
     * @param $a_usr_id
     * @param $a_status
     * @param $a_percentage
     */
    protected static function raiseEventStatic($a_obj_id, $a_usr_id, $a_status, $a_percentage)
    {
        global $DIC;

        $DIC->event()->raise("Services/Tracking", "updateStatus", array(
            "obj_id" => (int) $a_obj_id,
            "usr_id" => (int) $a_usr_id,
            "status" => (int) $a_status,
            "percentage" => (int) $a_percentage
        ));
    }

}
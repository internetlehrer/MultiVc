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
     */
    public static function getLPDataForUserFromDb(int $a_obj_id, int $a_user_id): int
    {
        try {
            return self::getLPDataForUser($a_obj_id, $a_user_id);
        } catch (Exception $e) {
            return self::LP_STATUS_NOT_ATTEMPTED_NUM;
        }
    }


    /**
     * Track read access to the object
     * Prevents a call of determineStatus() that would return "not attempted"
     * @see ilLearningProgress::_tracProgress()
     *
     */
    public static function trackAccess(int $a_user_id, int $a_obj_id, int $a_ref_id)
    {
        ilChangeEvent::_recordReadEvent('xmvc', $a_ref_id, $a_obj_id, $a_user_id);

        try {
            $status = self::getLPDataForUser($a_obj_id, $a_user_id);
        } catch (Exception $e) {
            $status = self::LP_STATUS_NOT_ATTEMPTED_NUM;
        }
        if ($status == self::LP_STATUS_NOT_ATTEMPTED_NUM) {
            self::writeStatus($a_obj_id, $a_user_id, self::LP_STATUS_IN_PROGRESS_NUM);
            self::raiseEventStatic(
                $a_obj_id,
                $a_user_id,
                self::LP_STATUS_IN_PROGRESS_NUM,
                self::getPercentageForUser($a_obj_id, $a_user_id)
            );
        }
    }

    public static function trackResult(int $a_user_id, int $a_obj_id, int $a_status, int $a_percentage)
    {
        self::writeStatus($a_obj_id, $a_user_id, $a_status, $a_percentage, true);
        self::raiseEventStatic($a_obj_id, $a_user_id, $a_status, $a_percentage);
    }

    /**
     * Static version if ilLPStatus::raiseEvent
     * This function is just a workaround for PHP7 until ilLPStatus::raiseEvent is declared as static
     *
     */
    protected static function raiseEventStatic(int $a_obj_id, int $a_usr_id, int $a_status, int $a_percentage)
    {
        global $DIC;

        $DIC->event()->raise("Services/Tracking", "updateStatus", array(
            "obj_id" => $a_obj_id,
            "usr_id" => $a_usr_id,
            "status" => $a_status,
            "percentage" => $a_percentage
        ));
    }

}

<?php

/**
* Access/Condition checking for MultiVc object
*
* @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
* @version $Id$
*/
class ilObjMultiVcAccess extends ilObjectPluginAccess
{
    /**
    * Checks wether a user may invoke a command or not
    * (this method is called by ilAccessHandler::checkAccess)
    * Please do not check any preconditions handled by
    * ilConditionHandler here. Also don't do usual RBAC checks.
    */
    public function _checkAccess(string $a_cmd, string $a_permission, int $a_ref_id, int $a_obj_id, ?int $a_user_id = null): bool
    {
        global $ilUser, $ilAccess;

        if ($a_user_id == null) {
            $a_user_id = $ilUser->getId();
        }

        switch ($a_permission) {
            case "read":
                switch (true) {
                    case !ilObjMultiVcAccess::checkOnline($a_obj_id) && !$ilAccess->checkAccessOfUser($a_user_id, "write", "", $a_ref_id):
                    case !ilObjMultiVcAccess::checkConnAvailability($a_obj_id) && !$ilAccess->checkAccessOfUser($a_user_id, "write", "", $a_ref_id):
                        return false;
                }
                break;
        }

        return true;
    }

    /**
    * Check online status of MultiVC object
    */
    public static function checkOnline(int $a_id): bool
    {
        global $DIC;
        $ilDB = $DIC->database();

        $set = $ilDB->query(
            "SELECT is_online FROM rep_robj_xmvc_data " .
            " WHERE id = " . $ilDB->quote($a_id, "integer")
        );
        $rec = $ilDB->fetchAssoc($set);
        return (bool) $rec["is_online"];
    }

    public static function checkConnAvailability(int $obj_id): bool
    {
        global $DIC;
        $ilDB = $DIC->database();

        $set = $ilDB->query(
            "SELECT conn_id FROM rep_robj_xmvc_data  " .
            " WHERE id = " . $ilDB->quote($obj_id, "integer")
        );
        $data = $ilDB->fetchObject($set);

        $set = $ilDB->query(
            "SELECT availability FROM rep_robj_xmvc_conn " .
            " WHERE id = " . $ilDB->quote($data->conn_id, "integer")
        );
        $conn = $ilDB->fetchObject($set);
        //var_dump([(int)ilMultiVcConfig::AVAILABILITY_NONE !== (int)$conn->availability, (int)$conn->availability]); exit;
        return (int) ilMultiVcConfig::AVAILABILITY_NONE !== (int) $conn->availability;
    }

}

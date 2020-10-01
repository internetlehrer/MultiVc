<?php

use ILIAS\DI\Container;

include_once("./Services/Repository/classes/class.ilObjectPluginAccess.php");

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
	*
	* Please do not check any preconditions handled by
	* ilConditionHandler here. Also don't do usual RBAC checks.
	*
	* @param	string		$a_cmd			command (not permission!)
 	* @param	string		$a_permission	permission
	* @param	int			$a_ref_id		reference id
	* @param	int			$a_obj_id		object id
	* @param	int			$a_user_id		user id (if not provided, current user is taken)
	*
	* @return	boolean		true, if everything is ok
	*/
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{
		global $ilUser, $ilAccess;

		if ($a_user_id == "")
		{
			$a_user_id = $ilUser->getId();
		}

		switch ($a_permission)
		{
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
	static function checkOnline($a_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT is_online FROM rep_robj_xmvc_data ".
			" WHERE id = ".$ilDB->quote($a_id, "integer")
			);
		$rec  = $ilDB->fetchAssoc($set);
		return (boolean) $rec["is_online"];
	}

	static public function checkConnAvailability(int $obj_id)
	{
		require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
		global $DIC; /** @var Container  $DIC */
		$ilDB = $DIC->database();

		$set = $ilDB->query("SELECT conn_id FROM rep_robj_xmvc_data  ".
			" WHERE id = ".$ilDB->quote($obj_id, "integer")
		);
		$data = $ilDB->fetchObject($set);

		$set = $ilDB->query("SELECT availability FROM rep_robj_xmvc_conn ".
			" WHERE id = ".$ilDB->quote($data->conn_id, "integer")
		);
		$conn  = $ilDB->fetchObject($set);
		//var_dump([(int)ilMultiVcConfig::AVAILABILITY_NONE !== (int)$conn->availability, (int)$conn->availability]); exit;
		return (int)ilMultiVcConfig::AVAILABILITY_NONE !== (int)$conn->availability;
	}
	
}

?>

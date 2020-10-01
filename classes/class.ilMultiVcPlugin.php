<?php

use ILIAS\DI\Container;

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
 
/**
* MultiVc repository object plugin
*
* @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
* @version $Id$
*
*/
class ilMultiVcPlugin extends ilRepositoryObjectPlugin
{
	function getPluginName()
	{
		return "MultiVc";
	}

	protected function uninstallCustom() {

		global $ilDB;

		if ($ilDB->tableExists('rep_robj_xmvc_data')) {
			$ilDB->dropTable('rep_robj_xmvc_data');
		}
		if ($ilDB->tableExists('rep_robj_xmvc_conn')) {
			$ilDB->dropTable('rep_robj_xmvc_conn');
		}

		if ($ilDB->tableExists('rep_robj_xmvc_log_max')) {
			$ilDB->dropTable('rep_robj_xmvc_log_max');
		}

	}

	/**
	 * @inheritdoc
	 */
	public function allowCopy()
	{
		return true;
	}

}
?>

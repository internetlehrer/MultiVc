<?php
include_once "./Services/Repository/classes/class.ilObjectPluginListGUI.php";

/**
 * ListGUI implementation for MultiVc object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @ilCtrl_Calls ilObjMultiVcListGUI: ilCommonActionDispatcherGUI
 */
class ilObjMultiVcListGUI extends ilObjectPluginListGUI
{

	/**
	 * Init type
	 */
	function initType()
	{
		$this->setType("xmvc");
	}

	public function getDescription()
	{
		require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilObjMultiVc.php");
		require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
		$a_obj_id = (int)$this->obj_id;
		$a_description = parent::getDescription();
		$conn = ilObjMultiVc::getMultiVcConnTitleAndTypeByObjId($a_obj_id);
		$separator = (bool)strlen($a_description) ? '; ' : '';
		return $a_description . $separator . ilMultiVcConfig::AVAILABLE_VC_CONN[$conn->type] . ', ' . $conn->title;
	}

	/**
	 * Get name of gui class handling the commands
	 */
	function getGuiClass()
	{
		return "ilObjMultiVcGUI";
	}

	/**
	 * Get commands
	 */
	function initCommands()
	{
		return array
		(
			array(
				"permission" => "read",
				"cmd" => "showContent",
				"default" => true),
			array(
				"permission" => "write",
				"cmd" => "editProperties",
				"txt" => $this->txt("edit"),
				"default" => false),
		);
	}

	/**
	 * Get item properties
	 *
	 * @return	array		array of property arrays:
	 *						"alert" (boolean) => display as an alert property (usually in red)
	 *						"property" (string) => property name
	 *						"value" (string) => property value
	 */
	function getProperties()
	{
		global $lng, $ilUser;
		//var_dump($this); exit;
		$props = array();

		//$this->plugin->includeClass("class.ilObjMultiVcAccess.php");
		if (!ilObjMultiVcAccess::checkOnline($this->obj_id) || !ilObjMultiVcAccess::checkConnAvailability($this->obj_id))
		{
			$props[] = array("alert" => true, "property" => $this->txt("status"),
				"value" => $this->txt("offline"));
		}

		return $props;
	}
}
?>

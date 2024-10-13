<?php

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
    public function initType()
    {
        $this->setType("xmvc");
    }

    public function getDescription(): string
    {
        $a_obj_id = (int) $this->obj_id;
        $a_description = parent::getDescription();
        $conn = ilObjMultiVc::getMultiVcConnTitleAndTypeByObjId($a_obj_id);
        $separator = $a_description != '' ? '; ' : '';
        return $a_description . $separator . ilMultiVcConfig::AVAILABLE_VC_CONN[$conn->type] . ', ' . $conn->title;
    }

    /**
     * Get name of gui class handling the commands
     */
    public function getGuiClass(): string
    {
        return "ilObjMultiVcGUI";
    }

    /**
     * Get commands
     */
    public function initCommands(): array
    {
        return array(
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
     * array of property arrays:
     * "alert" (boolean) => display as an alert property (usually in red)
     * "property" (string) => property name
     * "value" (string) => property value
     */
    public function getProperties(): array
    {
        $props = array();

        if (!ilObjMultiVcAccess::checkOnline($this->obj_id) || !ilObjMultiVcAccess::checkConnAvailability($this->obj_id)) {
            $props[] = array("alert" => true, "property" => $this->txt("status"),
                "value" => $this->txt("offline"));
        }

        $multiVcObj = new ilObjMultiVc($this->ref_id);
        if ($multiVcObj->getLPMode() == ilObjMultiVc::LP_INACTIVE) {
            unset(ilLPStatus::$list_gui_cache[$this->obj_id]);
        }

        return $props;
    }


}

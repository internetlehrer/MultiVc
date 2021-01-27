<?php
/**
 * Copyright (c) 2018 internetlehrer-gmbh.de
 * GPLv2, see LICENSE 
 */

use ILIAS\DI\Container;

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * multiVC plugin: vc types table GUI
 *
 * @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 */ 
class ilMultiVcOverviewUsesTableGUI extends ilTable2GUI {

    /** @var ilPlugin|mixed|null $plugin_object */
    protected $plugin_object;

    /**
     * Constructor
     *
     * @param object        parent object
     * @param string $a_parent_cmd
     * @param string $a_template_context
     */
    function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '') 
    {
    	// this uses the cached plugin object
		$this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'MultiVc');

		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
    }

    /**
     * Init the table with some configuration
     *
     *
     * @access public
     * @param $a_parent_obj
     * @param array|null $rows
     */
    public function init($a_parent_obj, ?array $rows = null)
    {
        //global $ilCtrl, $lng;
        global $DIC; /** @var Container $DIC */
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();

        $this->addColumn($this->plugin_object->txt('plugin_configuration'), 'plugin_configuration', '');
        $this->addColumn($lng->txt('repository'), 'repository', '');
        $this->addColumn($this->plugin_object->txt('obj_xmvc'), 'obj_xmvc', '');
        $this->addColumn($lng->txt('object_id'), 'obj_id', '7%');
        $this->addColumn($lng->txt('actions'), '', '5%');
        $this->addColumn($lng->txt('status'), 'status', '5%');
        $this->setEnableHeader(true);
		$this->disable('sort');
        $this->setEnableNumInfo(false);
		$this->setRowTemplate('tpl.uses_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc');
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set) 
    {
        global $lng, $ilCtrl;
        #var_dump($a_set); exit;
        $ilCtrl->setParameter($this->parent_obj, 'conn_id', $a_set['xmvcConnId']);

        $this->tpl->setVariable('XMVC_CONN_TITLE', $a_set['connTitle']);

        // Link to Container
        $this->tpl->setVariable('TXT_PARENT', $a_set['isInTrash'] ? $a_set['parentTitle'] : '
        <a href="' . $a_set['parentLink'] . '" target="_blank">' . $a_set['parentTitle'] . '</a>
        ');

        // Link to MultiVc Object
        $this->tpl->setVariable('TXT_TITLE', $a_set['isInTrash'] ? $a_set['xmvcObjTitle'] : '
        <a href="' . $a_set['link'] . '" target="_blank">' . $a_set['xmvcObjTitle'] . '</a>
        ');

        // Object ID
		$this->tpl->setVariable('OBJ_ID', $a_set['xmvcObjId']);

        // Action
        $linkText = $lng->txt('delete');
        $linkTitle = $this->plugin_object->txt('obj_xmvc') . " (";
        $linkTitle .= $a_set['isInTrash'] ? $lng->txt('trash') : $lng->txt('repository');
        $linkTitle .= ")";
            $this->tpl->setVariable('TXT_ACTION',
            '<a class="il_ContainerItemCommand" href="' .
            $ilCtrl->getLinkTarget($this->parent_obj, 'confirmDeleteUsesMultiVcConn') .
            '&parent_ref_id=' . $a_set['parentRefId'] .
            '&item_ref_id=' . $a_set['xmvcRefId'] .
            '&cGuiItemContent=' . rawurlencode($a_set['xmvcObjTitle'] . ' &nbsp;<span class="small">(' . $a_set['connTitle'] . ')</span> ')
            . '" title="' . $linkTitle . '">' .
            $linkText . '</a>');

        // Status
        $StatusHtml = !(bool)$a_set['isInTrash'] ? (bool)$a_set['is_online'] ? 'online' : 'offline' : '<img src="templates/default/images/icon_trash.svg" style="height: 16px; width: auto; margin:0 5px 4px" />';
        $this->tpl->setVariable('TXT_STATUS', '<span class="small">' . $StatusHtml . '</span>');

    }

}

?>
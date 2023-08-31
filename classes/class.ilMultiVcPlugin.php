<?php

use ILIAS\DI\Container;

/**
* MultiVc repository object plugin
*
* @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
* @version $Id$
*
*/
class ilMultiVcPlugin extends ilRepositoryObjectPlugin
{
    public const ID = 'xmvc';

    public function __construct()
    {
        global $DIC;
        $this->db = $DIC->database();
        parent::__construct($this->db, $DIC["component.repository"], self::ID);
    }

    public function getPluginName(): string
    {
        return "MultiVc";
    }

    protected function uninstallCustom(): void
    {
        global $DIC;
        $ilDB = $DIC->database();

        if ($ilDB->tableExists('rep_robj_xmvc_data')) {
            $ilDB->dropTable('rep_robj_xmvc_data');
        }
        if ($ilDB->tableExists('rep_robj_xmvc_conn')) {
            $ilDB->dropTable('rep_robj_xmvc_conn');
        }

        if ($ilDB->tableExists('rep_robj_xmvc_log_max')) {
            $ilDB->dropTable('rep_robj_xmvc_log_max');
        }
        if ($ilDB->tableExists('rep_robj_xmvc_user_log')) {
            $ilDB->dropTable('rep_robj_xmvc_user_log');
        }

        if ($ilDB->tableExists('rep_robj_xmvc_schedule')) {
            $ilDB->dropTable('rep_robj_xmvc_schedule');
        }

        if ($ilDB->tableExists('rep_robj_xmvc_session')) {
            $ilDB->dropTable('rep_robj_xmvc_session');
        }

    }

    /**
     * @inheritdoc
     */
    public function allowCopy(): bool
    {
        return true;
    }

}

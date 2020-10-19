<#1>
<?php
$fields_data = array(
	'id' => array(
		'type' => 'integer',
		'length' => 8,
		'notnull' => true
	),
	'is_online' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'token' => array(
		'type' => 'integer',
		'length' => 8,
		'notnull' => false
	),
	'moderated' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 1
	),
	'btn_settings' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'btn_chat' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'with_chat' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 1
	),
	'btn_locationshare' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'member_btn_fileupload' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'fa_expand' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	)
);

$ilDB->createTable("rep_robj_xmvc_data", $fields_data);
$ilDB->addPrimaryKey("rep_robj_xmvc_data", array("id"));

$fields_conn = array(
	'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
	),
	'spreed_url' => array(
			'type' => 'text',
			'length' => 256,
			'notnull' => true,
			'default' => '/webconference'
	),
	'obj_ids_special' => array(
			'type' => 'text',
			'length' => 1024,
			'notnull' => false
	),
	'protected' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 1
	),
	'moderated_choose' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 1
	),
	'moderated_default' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 1
	),
	'btn_settings_choose' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'btn_settings_default' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'btn_chat_choose' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'btn_chat_default' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'with_chat_choose' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'with_chat_default' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 1
	),
	'btn_locationshare_choose' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'btn_locationshare_default' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'member_btn_fileupload_choose' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'member_btn_fileupload_default' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	),
	'fa_expand_default' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => true,
		'default' => 0
	)
);

$ilDB->createTable("rep_robj_xmvc_conn", $fields_conn);
$ilDB->addPrimaryKey("rep_robj_xmvc_conn", array("id"));
?>
<#2>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'attendeepwd') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'attendeepwd', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => false,
            'default' => ''
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'moderatorpwd') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'moderatorpwd', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => false,
            'default' => ''
        ));
    }
}
?>
<#3>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'svrpublicurl') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'svrpublicurl', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => true,
            'default' => ''
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'svrpublicport') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'svrpublicport', array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true,
            'default' => 443
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'svrprivateurl') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'svrprivateurl', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => true,
            'default' => ''
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'svrprivateport') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'svrprivateport', array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true,
            'default' => 443
        ));
    }

    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'svrsalt') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'svrsalt', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => true,
            'default' => ''
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'maxparticipants') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'maxparticipants', array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => false,
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'showcontent') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'showcontent', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => true,
            'default' => 'spreed'
        ));
    }

}
?>
<#4>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'private_chat') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'private_chat', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 1
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'recording') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'recording', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'cam_only_for_moderator') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'cam_only_for_moderator', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 0
        ));
    }
}
?>
<#5>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'private_chat_choose') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'private_chat_choose', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'private_chat_default') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'private_chat_default', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 1
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'recording_choose') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'recording_choose', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'recording_default') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'recording_default', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'record_only_moderated_rooms') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'record_only_moderated_rooms', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 1
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'cam_only_moderator_choose') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'cam_only_moderator_choose', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'cam_only_moderator_default') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'cam_only_moderator_default', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 0
        ));
    }
}
?>
<#6>
<?php
$fields_data = array(
    'year' => array(
        'type' => 'integer',
        'length' => 2,
        'notnull' => true
    ),
    'month' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => false
    ),
    'day' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
    ),
    'hour' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
    ),
    'max_meetings' => array(
        'type' => 'integer',
        'length' => 2,
        'notnull' => true,
    ),
    'max_users' => array(
        'type' => 'integer',
        'length' => 2,
        'notnull' => true,
    ),
);

$ilDB->createTable("rep_robj_xmvc_log_max", $fields_data);
$ilDB->addPrimaryKey("rep_robj_xmvc_log_max", array("year", 'month', 'day', 'hour'));
?>
<#7>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'svrusername') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'svrusername', array(
            'type' => 'text',
            'length' => 1024,
            'notnull' => false,
        ));
    }
}
?>
<#8>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'rmid') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'rmid', array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => false
        ));
    }
}
?>
<#9>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'conn_id') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'conn_id', array(
            'type' => 'integer',
            'length' => 2,
            'notnull' => false,
            'default' => '1'
        ));
    }

    $queue = [];
    $query = 'SELECT `id`, `conn_id` FROM `rep_robj_xmvc_data`;';
    $res = $ilDB->query($query);
    while ($row = $ilDB->fetchAssoc($res)) {
        $queue[$row['id']] = [
            'conn_id'     => $row['conn_id']
        ];
    }

    if (!empty($queue)) {
        foreach ($queue as $id => $row) {
            if( is_numeric($row['conn_id']) ) {
                continue;
            }
            $ilDB->update(
                'rep_robj_xmvc_data',
                [
                    "conn_id"     => [
                        "integer", 1
                    ]
                ],
                [
                    "id" => [
                        "integer", $id
                    ]
                ]
            );
        }
    }
}
?>
<#10>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'title') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'title', array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false,
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'availability') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'availability', array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 1
        ));
    }

    $queue = [];
    $query = 'SELECT `id`, `title`, `availability` FROM `rep_robj_xmvc_conn`;';
    $res = $ilDB->query($query);
    while ($row = $ilDB->fetchAssoc($res)) {
        $queue[$row['id']] = [
            'title'     => $row['title'],
            'availability'     => $row['availability']
        ];
    }
    if (!empty($queue)) {
        foreach ($queue as $id => $row) {
            if( !(bool)strlen($row['title']) ) {
                $ilDB->update(
                    'rep_robj_xmvc_conn',
                    [
                        "title"     => [
                            "string", 'connection 1'
                        ]
                    ],
                    [
                        "id" => [
                            "integer", $id
                        ]
                    ]
                );
            }
            if( !is_numeric($row['availability']) ) {
                $ilDB->update(
                    'rep_robj_xmvc_conn',
                    [
                        "availability"     => [
                            "integer", 2
                        ]
                    ],
                    [
                        "id" => [
                            "integer", $id
                        ]
                    ]
                );
            }
        }
    }

}
?>
<#11>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'guestlink_choose') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'guestlink_choose', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'guestlink_default') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'guestlink_default', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }

    $queue = [];
    $query = 'SELECT `id`, `guestlink_choose`, `guestlink_default` FROM `rep_robj_xmvc_conn`;';
    $res = $ilDB->query($query);
    while ($row = $ilDB->fetchAssoc($res)) {
        $queue[$row['id']] = [
            'guestlink_choose'     => $row['guestlink_choose'],
            'guestlink_default'     => $row['guestlink_default']
        ];
    }
    if (!empty($queue)) {
        foreach ($queue as $id => $row) {
            if( !is_numeric($row['guestlink_choose']) ) {
                $ilDB->update(
                    'rep_robj_xmvc_conn',
                    [
                        "guestlink_choose"     => [
                            "integer", '0'
                        ]
                    ],
                    [
                        "id" => [
                            "integer", $id
                        ]
                    ]
                );
            }
            if( !is_numeric($row['guestlink_default']) ) {
                $ilDB->update(
                    'rep_robj_xmvc_conn',
                    [
                        "guestlink_default"     => [
                            "integer", 0
                        ]
                    ],
                    [
                        "id" => [
                            "integer", $id
                        ]
                    ]
                );
            }
        }
    }

}
?>

<#12>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'guestlink') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'guestlink', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => '0'
        ));
    }

    $queue = [];
    $query = 'SELECT `id`, `guestlink` FROM `rep_robj_xmvc_data`;';
    $res = $ilDB->query($query);
    while ($row = $ilDB->fetchAssoc($res)) {
        $queue[$row['id']] = [
            'guestlink'     => $row['guestlink']
        ];
    }

    if (!empty($queue)) {
        foreach ($queue as $id => $row) {
            if( !is_numeric($row['guestlink']) ) {
                $ilDB->update(
                    'rep_robj_xmvc_data',
                    [
                        "guestlink"     => [
                            "integer", 0
                        ]
                    ],
                    [
                        "id" => [
                            "integer", $id
                        ]
                    ]
                );
            }
        }
    }
}
?>
<#13>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'add_presentation_url') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'add_presentation_url', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => true,
            'default' => ''
        ));
    }
}
?>
<#14>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'add_welcome_text') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'add_welcome_text', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => '0'
        ));
    }
}
?>
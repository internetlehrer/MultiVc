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

if( !$ilDB->tableExists("rep_robj_xmvc_log_max" ) ) {
    $ilDB->createTable("rep_robj_xmvc_log_max", $fields_data);
    $ilDB->addPrimaryKey("rep_robj_xmvc_log_max", array("year", 'month', 'day', 'hour'));
}
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
<#15>
<?php
if($ilDB->tableExists('rep_robj_xmvc_log_max'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_log_max', 'log') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_log_max', 'log', array(
            'type' => 'text',
            'length' => 3072,
            'notnull' => false,
        ));
    }
}
?>
<#16>
<?php
if(!$ilDB->tableExists('rep_robj_xmvc_user_log'))
{
    $fields_log_user = [
        'ref_id' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ],
        'user_id' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ],
        'display_name' => [
            'type' => 'text',
            'length' => 64,
            'notnull' => true
        ],
        'is_moderator' => [
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ],
        'join_time' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ],
        'meeting_id' => [
            'type' => 'text',
            'length' => 64,
            'notnull' => true
        ],
    ];
    $ilDB->createTable("rep_robj_xmvc_user_log", $fields_log_user);
    $ilDB->addPrimaryKey("rep_robj_xmvc_user_log",
        array("join_time", "ref_id", "user_id", "display_name")
    );
}
?>
<#17>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'hint') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'hint', array(
            'type' => 'text',
            'length' => 1000,
            'notnull' => false,
        ));
    }
}
?>
<#18>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'disable_sip') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'disable_sip', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
}
?>
<#19>
<?php
//
?>
<#20>
<?php
//
?>
<#21>
<?php
if($ilDB->tableExists('rep_robj_xmvc_log_max'))
{
    if($ilDB->tableColumnExists('rep_robj_xmvc_log_max', 'log') )
    {
		$ilDB->dropTableColumn('rep_robj_xmvc_log_max', 'log');
    }
}
?>
<#22>
<?php
if($ilDB->tableExists('rep_robj_xmvc_log_max'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_log_max', 'log') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_log_max', 'log', array(
            'type' => 'clob',
            'notnull' => false,
            'default' => null
        ));
    }
}
?>
<#23>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'hide_username_logs') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'hide_username_logs', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }
}
?>
<#24>
<?php
if(!$ilDB->tableExists('rep_robj_xmvc_schedule')) {
    $fields_data = [
        'obj_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'start' => array(
            'type' => 'timestamp',
            'notnull' => true
        ),
        'end' => array(
            'type' => 'timestamp',
            'notnull' => true
        ),
        'timezone' => array(
            'type' => 'text',
            'length' => 64,
            'notnull' => true,
            'default' => 'Europe/Berlin'
        ),
        'recurrence' => array(
            'type' => 'text',
            'length' => 256,
            'notnull' => false,
            'default' => 0
        ),
        'user_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'auth_user' => array(
            'type' => 'text',
            'length' => 80,
            'notnull' => false
        ),
        'rel_id' => array(
            'type' => 'text',
            'length' => 256,
            'notnull' => false
        ),
        'rel_data' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'participants' => array(
            'type' => 'clob',
            'notnull' => false
        )
    ];
    $ilDB->createTable("rep_robj_xmvc_schedule", $fields_data);
    $ilDB->addPrimaryKey("rep_robj_xmvc_schedule", array("obj_id", "rel_id"));
}
?>
<#25>
<?php
if(!$ilDB->tableExists('rep_robj_xmvc_session')) {
    $fields_data = [
        'obj_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'start' => array(
            'type' => 'timestamp',
            'notnull' => true
        ),
        'end' => array(
            'type' => 'timestamp',
            'notnull' => true
        ),
        'timezone' => array(
            'type' => 'text',
            'length' => 64,
            'notnull' => true,
            'default' => 'Europe/Berlin'
        ),
        'host' => array(
            'type' => 'text',
            'length' => 64,
            'notnull' => false,
            'default' => 0
        ),
        'type' => array(
            'type' => 'text',
            'length' => 64,
            'notnull' => false,
            'default' => 0
        ),
        'rel_id' => array(
            'type' => 'text',
            'length' => 256,
            'notnull' => false,
        ),
        'rel_data' => array(
            'type' => 'clob',
            'notnull' => false,
        ),
    ];
    $ilDB->createTable("rep_robj_xmvc_session", $fields_data);
    $ilDB->addPrimaryKey("rep_robj_xmvc_session", array("obj_id", "rel_id"));
}
?>
<#26>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'extra_cmd_default') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'extra_cmd_default', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'extra_cmd_choose') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'extra_cmd_choose', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'access_token') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'access_token', array(
            'type' => 'clob',
            'notnull' => false,
            'default' => null
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'refresh_token') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'refresh_token', array(
            'type' => 'clob',
            'notnull' => false,
            'default' => null
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'auth_method') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'auth_method', array(
            'type' => 'text',
            'length' => 64,
            'notnull' => false
        ));
    }
}
?>
<#27>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'extra_cmd') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'extra_cmd', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'auth_user') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'auth_user', array(
            'type' => 'text',
            'length' => 80,
            'notnull' => false
        ));
    }
}
?>
<#28>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'logo') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'logo', array(
            'type' => 'clob',
            'notnull' => false,
            'default' => null
        ));
    }


    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'style') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'style', array(
            'type' => 'clob',
            'notnull' => false,
            'default' => null
        ));
    }

    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'lock_disable_cam') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'lock_disable_cam', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'lock_disable_cam_default') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'lock_disable_cam_default', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
}

if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'lock_disable_cam') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'lock_disable_cam', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
}
?>
<#29>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'access_token') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'access_token', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => false
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'refresh_token') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'refresh_token', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => false
        ));
    }
}
?>
<#30>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'secret_expiration') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'secret_expiration', array(
            'type' => 'text',
            'length' => 256,
            'notnull' => false,
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'max_duration') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'max_duration', array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
            'default' => 0
        ));
    }
}
?>
<#31>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'max_duration') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'max_duration', array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
            'default' => 0
        ));
    }
}
?>
<#32>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'assigned_roles') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'assigned_roles', array(
            'type' => 'clob',
            'notnull' => false
        ));
    }
}
?>
<#33>
<?php
$fields_notify = array(
    'id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'obj_id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'rel_id' => array(
        'type' => 'text',
        'length' => 256,
        'notnull' => false
    ),
    'user_id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'auth_user' => array(
        'type' => 'text',
        'length' => 80,
        'notnull' => true
    ),
    'recipient' => array(
        'type' => 'text',
        'length' => 80,
        'notnull' => true
    ),
    'status' => array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true
    ),
    'updated' => array(
        'type' => 'timestamp',
        'notnull' => false
    ),
    'proc_id' => array(
        'type' => 'text',
        'length' => 64,
        'notnull' => false
    ),
    'log' => array(
        'type' => 'clob',
        'notnull' => false
    ),
    'message' => array(
        'type' => 'clob',
        'notnull' => true
    ),
);
if(!$ilDB->tableExists('rep_robj_xmvc_notify'))
{
    $ilDB->createTable("rep_robj_xmvc_notify", $fields_notify);
    $ilDB->addPrimaryKey("rep_robj_xmvc_notify", array("id"));
    $ilDB->createSequence('rep_robj_xmvc_notify');
}
?>
<#34>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if($ilDB->tableColumnExists('rep_robj_xmvc_data', 'max_duration') )
    {
        $ilDB->dropTableColumn('rep_robj_xmvc_data', 'max_duration');
    }
}
?>
<#35>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'pub_recs_choose') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'pub_recs_choose', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'pub_recs_default') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'pub_recs_default', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'show_hint_pub_recs') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'show_hint_pub_recs', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'hide_recs_until_date') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'hide_recs_until_date', array(
            'type' => 'timestamp',
            'default' => null
        ));
    }
}
?>
<#36>
<?php
if($ilDB->tableExists('rep_robj_xmvc_data'))
{
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_data', 'pub_recs') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_data', 'pub_recs', array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ));
    }
}
?>
<#37>
<?php
if(!$ilDB->tableExists('rep_robj_xmvc_recs_bbb'))
{
    $fields_data = array(
        'ref_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'rec_id' => array(
            'type' => 'text',
            'length' => 64,
            'notnull' => true
        ),
        'meeting_id' => array(
            'type' => 'text',
            'length' => 64,
            'notnull' => true
        ),
        'available' => array(
            'type' => 'integer',
            'notnull' => false,
            'default' => null
        ),
        'create_date' => array(
            'type' => 'timestamp',
            'notnull' => true
        ),
        'update_date' => array(
            'type' => 'timestamp',
            'notnull' => false,
            'default' => null
        ),
        'updated_by' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
            'default' => null
        ),
        'task' => array(
            'type' => 'text',
            'length' => 32,
            'notnull' => false,
            'default' => null
        ),
    );

    $ilDB->createTable("rep_robj_xmvc_recs_bbb", $fields_data);
    $ilDB->addPrimaryKey("rep_robj_xmvc_recs_bbb", array("ref_id", "rec_id"));
}
?>
<#38>
<?php
if($ilDB->tableExists('rep_robj_xmvc_conn'))
{
    $meetingLayoutDefault = 2;
    if(!$ilDB->tableColumnExists('rep_robj_xmvc_conn', 'meeting_layout') )
    {
        $ilDB->addTableColumn('rep_robj_xmvc_conn', 'meeting_layout', array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => $meetingLayoutDefault
        ));
    }

    $queue = [];
    $query = 'SELECT `id`, `meeting_layout` FROM `rep_robj_xmvc_conn`;';
    $res = $ilDB->query($query);
    while ($row = $ilDB->fetchAssoc($res)) {
        $queue[$row['id']] = [
            'meeting_layout' => $row['meeting_layout']
        ];
    }
    if (!empty($queue)) {
        foreach ($queue as $id => $row) {
            if( !is_numeric($row['meeting_layout']) ) {
                $ilDB->update(
                    'rep_robj_xmvc_conn',
                    [
                        "meeting_layout"     => [
                            "integer", $meetingLayoutDefault
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
<?php

return [
    'parser' => [
        'name'          => 'Microsoft JMRP',
        'enabled'       => true,
        'report_file'   => false,
        'sender_map'    => [
            '/staff@hotmail.com/',
        ],
        'body_map'      => [
            //
        ],
    ],

    'feeds' => [
        'default' => [
            'class'     => 'SPAM',
            'type'      => 'ABUSE',
            'enabled'   => true,
            'fields'    => [
                'body',
                'headers',
                'source-ip',
            ],
            'filters'    => [
                //
            ],
        ],
    ],
];

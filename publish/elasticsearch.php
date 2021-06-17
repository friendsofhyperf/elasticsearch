<?php

declare(strict_types=1);
/**
 * This file is part of elasticsearch.
 *
 * @link     https://github.com/friendsofhyperf/elasticsearch
 * @document https://github.com/friendsofhyperf/elasticsearch/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
return [
    'default' => [
        'hosts' => [
            'http://127.0.0.1:9500',
        ],
        'pool' => [
            'max_connections' => 64,
        ],
    ],
];

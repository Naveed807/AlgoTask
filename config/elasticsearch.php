<?php

return [

    'hosts' => [
        env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
    ],

    'index' => env('ELASTICSEARCH_INDEX', 'delay_cases'),

];
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    |
    | The default elastic index used with all eloquent model
    |
    */
    'index' => env('PLASTIC_INDEX', 'plastic'),

    /*
    |--------------------------------------------------------------------------
    | Index configuration
    |--------------------------------------------------------------------------
    |
    | The configuration of the indexes. Used by the console command that recreates an index.
    |
    */
    'indexes' => [
        env('PLASTIC_INDEX', 'europa-cinemas') => [
            'settings' => [
                'number_of_shards' => 4,
                'number_of_replicas' => 1,
                'analysis' => [
                    'filter' => [
                        'french_elision' => [
                            'type' => 'elision',
                            'articles_case' => true,
                            'articles' => ['l', 'm', 't', 'qu', 'n', 's', 'j', 'd', 'c', 'jusqu', 'quoiqu', 'lorsqu', 'puisqu'],
                        ],
                        'french_synonym' => [
                            'type' => 'synonym',
                            'ignore_case' => true,
                            'expand' => true,
                            'synonyms' => [
                                'united nations, un, UN',
                                'nations unies, onu, ONU',
                            ],
                        ],
                        'french_stemmer' => [
                            'type' => 'stemmer',
                            'language' => 'light_french',
                        ],
                    ],
                    'analyzer' => [
                        'standard' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase'],
                        ],
                        'default_search' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['standard', 'lowercase', 'asciifolding'],
                        ],
                        'default' => [
                            'type' => 'custom',
                            'tokenizer' => 'letter',
                            'filter' => ['standard', 'lowercase', 'asciifolding'],
                        ],
                        'french_heavy' => [
                            'tokenizer' => 'icu_tokenizer',
                            'filter' => [
                                'french_elision',
                                'icu_folding',
                                'french_synonym',
                                'french_stemmer',
                            ],
                        ],
                        'french_light' => [
                            'tokenizer' => 'icu_tokenizer',
                            'filter' => [
                                'french_elision',
                                'icu_folding',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
     * Connection settings
     */
    'connection'     => [

        /*
        |--------------------------------------------------------------------------
        | Hosts
        |--------------------------------------------------------------------------
        |
        | The most common configuration is telling the client about your cluster: how many nodes, their addresses and ports.
        | If no hosts are specified, the client will attempt to connect to localhost:9200.
        |
        */
        'hosts'   => [
            env('PLASTIC_HOST', '127.0.0.1:9200'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Reties
        |--------------------------------------------------------------------------
        |
        | By default, the client will retry n times, where n = number of nodes in your cluster.
        | A retry is only performed if the operation results in a "hard" exception.
        |
        */
        'retries' => env('PLASTIC_RETRIES', 3),

        /*
        |------------------------------------------------------------------
        | Logging
        |------------------------------------------------------------------
        |
        | Logging is disabled by default for performance reasons. The recommended logger is Monolog (used by Laravel),
        | but any logger that implements the PSR/Log interface will work.
        |
        | @more https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#enabling_logger
        |
        */
        'logging' => [
            'enabled' => env('PLASTIC_LOG', false),
            'path'    => storage_path(env('PLASTIC_LOG_PATH', 'logs/plastic.log')),
            'level'   => env('PLASTIC_LOG_LEVEL', 200),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping repository table
    |--------------------------------------------------------------------------
    |
    | The sql table to store the mappings logs
    |
    */
    'mappings'       => env('PLASTIC_MAPPINGS', 'mappings'),

    /*
    |------------------------------------------------------------------
    | Populate settings
    |------------------------------------------------------------------
    |
    | The settings for populating an index.
    |
    */
    'populate' => [

        /*
        |------------------------------------------------------------------
        | Models
        |------------------------------------------------------------------
        |
        | The list of models, per index, from which to recreate the documents when running the console command to
        | populate or recreate an index.
        |
        */
        'models' => [

            // The models for the default index
            env('PLASTIC_INDEX', 'plastic') => [],
        ],

        /*
        |------------------------------------------------------------------
        | Chunk size
        |------------------------------------------------------------------
        |
        | The size of documents chunks to index per model
        |
        */
        'chunk_size' => 1000,
    ],

];

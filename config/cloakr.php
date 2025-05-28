<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Servers
    |--------------------------------------------------------------------------
    |
    | The available Cloakr servers that your client can connect to.
    | When sharing sites or TCP ports, you can specify the server
    | that should be used using the `--server=` option.
    |
    */
    'servers' => [
        'main' => [
            'host' => 'sharedwithcloakr.com',
            'port' => 443,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Endpoint
    |--------------------------------------------------------------------------
    |
    | When you specify a server that does not exist in above static array,
    | Cloakr will perform a GET request to this URL and tries to retrieve
    | a JSON payload that looks like the configurations servers array.
    |
    | Cloakr then tries to load the configuration for the given server
    | if available.
    |
    */
    'server_endpoint' => 'https://cloakr.dev/api/servers',

    /*
    |--------------------------------------------------------------------------
    | Default Server
    |--------------------------------------------------------------------------
    |
    | The default server from the servers array,
    | or the servers endpoint above.
    |
    */
    'default_server' => 'main',

    /*
    |--------------------------------------------------------------------------
    | DNS
    |--------------------------------------------------------------------------
    |
    | The DNS server to use when resolving the shared URLs.
    | When Cloakr is running from within Docker containers, you should set this to
    | `true` to fall-back to the system default DNS servers.
    |
    */
    'dns' => '127.0.0.1',

    /*
    |--------------------------------------------------------------------------
    | Auth Token
    |--------------------------------------------------------------------------
    |
    | The global authentication token to use for the cloakr server that you
    | are connecting to. You can let cloakr automatically update this value
    | for you by running
    |
    | > cloakr token YOUR-AUTH-TOKEN
    |
    */
    'auth_token' => '',

    /*
    |--------------------------------------------------------------------------
    | Default Domain
    |--------------------------------------------------------------------------
    |
    | The custom domain to use when sharing sites with Cloakr.
    | You can register your own custom domain using Cloakr Pro
    | Learn more at: https://cloakr.dev/get-pro
    |
    | > cloakr default-domain YOUR-CUSTOM-WHITELABEL-DOMAIN
    |
    */
    'default_domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Default TLD
    |--------------------------------------------------------------------------
    |
    | The default TLD to use when sharing your local sites. Cloakr will try
    | to look up the TLD if you are using Laravel Valet automatically.
    | Otherwise you can specify it here manually.
    |
    */
    'default_tld' => 'test',

    /*
    |--------------------------------------------------------------------------
    | Default HTTPS
    |--------------------------------------------------------------------------
    |
    | Whether to use HTTPS as a default when sharing your local sites. Cloakr
    | will try to look up the protocol if you are using Laravel Valet
    | automatically. Otherwise you can specify it here manually.
    |
    */
    'default_https' => false,

    /*
    |--------------------------------------------------------------------------
    | Maximum Logged Requests
    |--------------------------------------------------------------------------
    |
    | The maximum number if requests to keep in memory when inspecting your
    | requests and responses in the local dashboard.
    |
    */
    'max_logged_requests' => 100,

    /*
    |--------------------------------------------------------------------------
    | Maximum Allowed Memory
    |--------------------------------------------------------------------------
    |
    | The maximum memory allocated to the cloakr process.
    |
    */
    'memory_limit' => '128M',

    /*
    |--------------------------------------------------------------------------
    | Skip Response Logging
    |--------------------------------------------------------------------------
    |
    | Sometimes, some responses don't need to be logged. Some are too big,
    | some can't be read (like compiled assets). This configuration allows you
    | to be as granular as you wish when logging the responses.
    |
    | If you run constantly out of memory, you probably need to set some of these up.
    |
    | Keep in mind, by default, BINARY requests/responses are not logged.
    | You do not need to add video/mp4 for example to this list.
    |
    */
    'skip_body_log' => [
        /**
         * | Skip response logging by HTTP response code. Format: 4*, 5*.
         */
        'status' => [
            // "4*"
        ],
        /**
         * | Skip response logging by HTTP response content type. Ex: "text/css".
         */
        'content_type' => [
            //
        ],
        /**
         * | Skip response logging by file extension. Ex: ".js.map", ".min.js", ".min.css".
         */
        'extension' => [
            '.js.map',
            '.css.map',
        ],
        /**
         * | Skip response logging if response size is greater than configured value.
         * | Valid suffixes are: B, KB, MB, GB.
         * | Ex: 500B, 1KB, 2MB, 3GB.
         */
        'size' => '1MB',
    ],

    'platform_url' => 'https://cloakr.dev',

    /*
    |--------------------------------------------------------------------------
    | Request Plugins
    |--------------------------------------------------------------------------
    |
    | Request plugins analyze the incoming HTTP request and extract certain
    | data of interest to show in the CLI or UI, for example which event
    | was sent by a billing provider or a webhook from a service like GitHub.
    |
    */
    'request_plugins' => [
        \Cloakr\Client\Logger\Plugins\PaddleBillingPlugin::class,
        \Cloakr\Client\Logger\Plugins\GitHubPlugin::class
    ]


];

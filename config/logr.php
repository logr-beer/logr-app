<?php

return [
    'version' => '0.4.3',

    'user_agent' => 'Logr/1.0 (personal beer tracker)',

    // Hosts that check-in photos and beer labels may be downloaded from (https only; subdomains included).
    'image_hosts' => ['untappd.akamaized.net', 'assets.untappd.com', 'images.untp.beer'],

    'serving_types' => ['draft', 'bottle', 'can', 'crowler', 'growler', 'cask'],

    'links' => [
        'github' => 'https://github.com/logr-beer/logr-app',
        'docker_hub' => 'https://hub.docker.com/r/ajpenninga/logr-app',
        'website' => 'https://logr.beer',
    ],
];

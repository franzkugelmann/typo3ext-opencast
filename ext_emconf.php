<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Opencast',
    'description' => 'Support for Opencast videos',
    'category' => 'plugin',
    'version' => '1.1.0',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Philipp Kitzberger',
    'author_email' => 'beratung@rz.uni-osnabrueck.de',
    'author_company' => 'Osnabrück University',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];

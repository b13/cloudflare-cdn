<?php
/*
 * This file is part of TYPO3 CMS-based extension "cloudflare_cdn" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Cloudflare CDN Adapter',
    'description' => 'Cloudflare CDN Purge for TYPO3 allows to flush CDN caches related to a TYPO3 installation',
    'category' => 'plugin',
    'author' => 'Benjamin Mack',
    'author_email' => 'typo3@b13.com',
    'state' => 'stable',
    'author_company' => 'b13 GmbH',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'proxycachemanager' => '*',
        ],
    ],
];

<?php

/*
 * This file is part of TYPO3 CMS-based extension "cloudflare_cdn" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

return [
    'cdn:purge' => [
        'class' => \B13\CloudflareCDN\Command\PurgeCommand::class,
        'schedulable' => true,
    ],
];

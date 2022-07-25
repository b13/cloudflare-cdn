<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "cloudflare_cdn" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\CloudflareCDN\Provider;

use B13\CloudflareCDN\CloudflareClient;
use B13\Proxycachemanager\Provider\ProxyProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adapter to EXT:proxycachemanager
 */
class CloudflareProxyProvider implements ProxyProviderInterface
{
    /**
     * @var CloudflareClient
     */
    protected $client;

    public function __construct()
    {
        $this->client = GeneralUtility::makeInstance(CloudflareClient::class);
    }

    public function setProxyEndpoints($endpoints)
    {
        // not necessary
    }

    public function flushCacheForUrl($url)
    {
        $this->client->purgeUrl($url);
    }

    public function flushAllUrls($urls = [])
    {
        $this->client->purgeEverything($urls);
    }

    public function flushCacheForUrls(array $urls)
    {
        $this->client->purgeUrls($urls);
    }

    public function isActive(): bool
    {
        return $this->client->isActive();
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "cloudflare_cdn" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\CloudflareCDN;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * This class encapsulates some Cloudflare-specific functionality with Guzzle clients
 * and some TYPO3-specific implementation for Guzzle and configuration.
 * @todo: Ideally, this client would actually throw custom exceptions so we could show proper error messages
 * (or catch them silently in the ProxyProvider interface).
 */
class CloudflareClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected string $baseUrl = 'https://api.cloudflare.com/client/v4/zones/{zoneId}/';

    /**
     * Array of Guzzle clients per Zone ID
     * @var Client[]
     */
    protected $clients = [];

    public function purgeUrl(string $url): void
    {
        if (!$this->isActive()) {
            return;
        }
        $groupedUrls = $this->groupUrlsByAllowedZones([$url]);
        foreach ($groupedUrls as $zoneId => $urls) {
            if (empty($urls)) {
                continue;
            }
            $data = ['json' => ['files' => array_values($urls)]];
            try {
                $this->logger->debug('purgeUrl: purge_cache', $data);
                $this->getClient($zoneId)->post('purge_cache', $data);
            } catch (TransferException $e) {
                $this->logger->error('Could not flush URLs for {zone} via POST "purge_cache"', [
                    'urls' => $urls,
                    'zone' => $zoneId,
                    'exception' => $e,
                ]);
            }
        }
    }

    public function purgeEverything(): void
    {
        if (!$this->isActive()) {
            return;
        }
        foreach ($this->getZones() as $zoneId) {
            $this->purgeZone($zoneId);
        }
    }

    public function purgeUrls(array $urls): void
    {
        if (!$this->isActive()) {
            return;
        }
        $groupedUrls = $this->groupUrlsByAllowedZones($urls);
        foreach ($groupedUrls as $zoneId => $urls) {
            $this->purgeInChunks($zoneId, $urls);
        }
    }

    /**
     * Cloudflare only allows to purge 30 urls per request, so we chunk this.
     *
     * @param string $zoneId
     * @param array $urls
     * @param int $chunkSize
     */
    protected function purgeInChunks(string $zoneId, array $urls, int $chunkSize = 30): void
    {
        if (empty($urls)) {
            return;
        }
        $client = $this->getClient($zoneId);
        $urlGroups = array_chunk($urls, $chunkSize);
        foreach ($urlGroups as $urlGroup) {
            if (!empty($urlGroup)) {
                try {
                    $data = ['json' => ['files' => array_values($urlGroup)]];
                    $this->logger->debug('purgeInChunks: purge_cache', $data);
                    $client->post('purge_cache', $data);
                } catch (TransferException $e) {
                    $this->logger->error('Could not flush URLs for {zone} via POST "purge_cache"', [
                        'urls' => $urls,
                        'zone' => $zoneId,
                        'exception' => $e,
                    ]);
                }
            }
        }
    }

    public function isActive(): bool
    {
        return !empty(getenv('CLOUDFLARE_API_TOKEN'));
    }

    protected function getClient(string $zoneId): Client
    {
        if (!isset($this->clients[$zoneId])) {
            $this->clients[$zoneId] = $this->initializeClient($zoneId, getenv('CLOUDFLARE_API_TOKEN'));
        }
        return $this->clients[$zoneId];
    }

    protected function initializeClient(string $zoneId, string $apiToken): Client
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        if (isset($httpOptions['handler']) && empty($httpOptions['handler'])) {
            // let guzzle choose handler
            unset($httpOptions['handler']);
        }
        $httpOptions['base_uri'] = str_replace('{zoneId}', $zoneId, $this->baseUrl);
        $httpOptions['headers']['Content-Type'] = 'application/json';
        $httpOptions['headers']['Authorization'] = 'Bearer ' . $apiToken;
        return new Client($httpOptions);
    }

    /**
     * A URL could look like www-intranet.example.com but the zone would be example.com in this case,
     * this is filtered and grouped.
     */
    protected function groupUrlsByAllowedZones(array $urls): array
    {
        $groupedUrls = [];
        $availableZones = $this->getZones();
        foreach ($availableZones as $domain => $zoneId) {
            $groupedUrls[$zoneId] = array_filter($urls, function ($url) use ($domain) {
                $domainOfUrl = parse_url($url, PHP_URL_HOST);
                if (stripos('.' . $domainOfUrl, '.' . $domain) !== false) {
                    return true;
                }
                return false;
            });
        }
        return $groupedUrls;
    }

    public function purgeZone(string $zoneId): void
    {
        try {
            $data = ['json' => ['purge_everything' => true]];
            $this->logger->debug('purgeZone: purge_cache', $data);
            $this->getClient($zoneId)->post('purge_cache', $data);
        } catch (TransferException $e) {
            $this->logger->error('Could not flush URLs for {zone} via POST "purge_cache"', [
                'zone' => $zoneId,
                'exception' => $e,
            ]);
        }
    }

    public function getZones(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cloudflare']['zones'] ?? [];
    }
}

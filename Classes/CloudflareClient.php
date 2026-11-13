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

use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * This class encapsulates some Cloudflare-specific functionality on top of TYPO3's RequestFactory.
 * @todo: Ideally, this client would actually throw custom exceptions so we could show proper error messages
 * (or catch them silently in the ProxyProvider interface).
 *
 * Public, because it is created via GeneralUtility::makeInstance(), which only injects
 * constructor arguments into public services.
 */
#[Autoconfigure(public: true)]
class CloudflareClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected string $baseUrl = 'https://api.cloudflare.com/client/v4/zones/{zoneId}/';

    public function __construct(protected readonly RequestFactory $requestFactory)
    {
    }

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
                $this->post($zoneId, 'purge_cache', $data);
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
        $urlGroups = array_chunk($urls, $chunkSize);
        foreach ($urlGroups as $urlGroup) {
            if (!empty($urlGroup)) {
                try {
                    $data = ['json' => ['files' => array_values($urlGroup)]];
                    $this->logger->debug('purgeInChunks: purge_cache', $data);
                    $this->post($zoneId, 'purge_cache', $data);
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

    protected function post(string $zoneId, string $endpoint, array $options): void
    {
        $options['headers']['Authorization'] = 'Bearer ' . getenv('CLOUDFLARE_API_TOKEN');
        $this->requestFactory->request(str_replace('{zoneId}', $zoneId, $this->baseUrl) . $endpoint, 'POST', $options);
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
            $this->post($zoneId, 'purge_cache', $data);
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

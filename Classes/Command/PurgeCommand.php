<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "cloudflare_cdn" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\CloudflareCDN\Command;

use B13\CloudflareCDN\CloudflareClient;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generic Purge CLI Command to invalidate a set of URLs or a full domain / CDN zone.
 * The latter might be useful for deployments.
 *
 * Use
 *   vendor/bin/typo3 cdn:purge --zone=zone-id
 * for invalidating by Zone ID.
 *
 * Use
 *  vendor/bin/typo3 cdn:purge --url https://example.com/my-page/
 * for invalidating by URL.
 */
class PurgeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setDescription('Purge Cloudflare CDN Caches')
            ->addOption(
                'url',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A list of absolute URLs to purge.',
                []
            )->addOption(
                'zone',
                null,
                InputOption::VALUE_REQUIRED,
                'A zone ID to purge.',
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $urls = $input->getOption('url');
        $zone = $input->getOption('zone');

        $api = GeneralUtility::makeInstance(CloudflareClient::class);

        if (!$api->isActive()) {
            $io->error('CDN is not configured properly');
            return 1;
        }

        if (!$io->isQuiet()) {
            $io->title('Invalidating CDN caches');
        }
        if (!empty($urls)) {
            try {
                $api->purgeUrls($urls);
            } catch (ClientException $e) {
                $io->error(
                    [
                        'An error occurred while purging caches',
                        (string)$e->getResponse()->getBody()->getContents(),
                    ]
                );
                return 1;
            }
            $io->success('Purged CDN caches for URLs successfully');
            return 0;
        }
        if (!empty($zone)) {
            try {
                $api->purgeZone($zone);
            } catch (ClientException $e) {
                $io->error(
                    [
                        'An error occurred while purging everything for zone "' . $zone . '"',
                        (string)$e->getResponse()->getBody()->getContents(),
                    ]
                );
                return 1;
            }
            if (!$io->isQuiet()) {
                $io->success('Purged CDN caches for zone "' . $zone . '" successfully');
            }
            return 0;
        }

        $io->error('Nothing done');
        return 1;
    }
}

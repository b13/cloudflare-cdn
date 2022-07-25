# Cloudflare CDN Adapter for TYPO3

When TYPO3 is running behind Cloudflare's CDN, this extension is a perfect companion for you.

## Installation

You can install this extension by using composer:

    composer req b13/cloudflare-cdn

## Usage

By default, EXT:cloudflare_cdn ships with a `cdn:purge` CLI command to purge a full
CDN zone or a specific URL within the CDN zone.

It is possible to purge a single or multiple URLs

    ./vendor/bin/typo3 cdn:purge --url=https://www.exmaple.com/page1 --url=https://www.exmaple.com/page2

or purge a whole CDN Zone

    ./vendor/bin/typo3 cdn:purge --zone=my-zone-id

## Integration into TYPO3 Backend

EXT:cloudflare_cdn can be used in conjunction with TYPO3's Proxy Cache Manager Extension.

Using the Cloudflare CDN Adapter for EXT:proxycachemanager flushes page caches directly
when modifying a page. This is perfect if you're dealing with Cloudflare CDN
that not just caches your static assets but also your pages.

For this, ensure to set the class `\B13\CloudflareCDN\Provider\CloudflareProxyProvider` in
the settings of EXT:proxycachemanager.

## Configuration

This extension purges CDN caches via cURL requests wrapped in Guzzle's API (bundled in TYPO3).

For this, you need a Cloudflare API Token.

API Tokens are generated from the User Profile 'API Tokens' page
(see https://dash.cloudflare.com/profile/api-tokens)

Get your Zone ID via Dash in your Zone on the right-side menu.

Ensure to set the environment variable CLOUDFLARE_API_TOKEN.

Please note that the ProxyProvider needs additional configuration for each zone in

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['cloudflare']['zones'] = [
        'example.com' => 'ZONE_ID'
    ];

This way, you can use multiple domains in one TYPO3 installation having CDN support.

## License

The extension is licensed under GPL v2+, same as the TYPO3 Core. For details see the LICENSE file in this repository.

Extension Icon courtesy of Cloudflare Inc. https://cloudflare.com

## Open Issues

If you find an issue, feel free to create an issue on GitHub or a pull request.

## Credits

This extension was created by [Benni Mack](https://github.com/bmack) in 2022 for [b13 GmbH](https://b13.com).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us
deliver value in client projects. As part of the way we work, we focus on testing and best practices
to ensure long-term performance, reliability, and results in all our code.

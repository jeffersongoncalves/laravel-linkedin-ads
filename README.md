<div class="filament-hidden">

![Laravel LinkedIn Ads](https://raw.githubusercontent.com/jeffersongoncalves/laravel-linkedin-ads/main/banners/laravel-linkedin-ads.png)

</div>

# Laravel LinkedIn Ads

[![Tests](https://github.com/jeffersongoncalves/laravel-linkedin-ads/actions/workflows/run-tests.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-linkedin-ads/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/jeffersongoncalves/laravel-linkedin-ads/actions/workflows/phpstan.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-linkedin-ads/actions/workflows/phpstan.yml)
[![Code Style](https://github.com/jeffersongoncalves/laravel-linkedin-ads/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-linkedin-ads/actions/workflows/fix-php-code-style-issues.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-linkedin-ads.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-linkedin-ads)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-linkedin-ads.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-linkedin-ads)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/laravel-linkedin-ads.svg?style=flat-square)](LICENSE.md)

A lightweight LinkedIn Ads (Marketing API v2) client for Laravel. It wraps ad account listing, campaign management, analytics reporting, creative listing, and audience count estimation behind a small static client, threads your OAuth bearer token, and returns `null` sentinels on ordinary HTTP failures instead of throwing.

## Features

- **`accounts()`** — list ad accounts accessible to the configured token
- **`campaigns()`** — list campaigns belonging to an ad account
- **`createCampaign()`** — create a new (paused) campaign within a campaign group
- **`updateCampaignStatus()`** — update a campaign's status (e.g. `ACTIVE`, `PAUSED`, `ARCHIVED`)
- **`campaignAnalytics()`** — impressions, clicks, cost, and conversions for a campaign over a date range
- **`creatives()`** — list creatives belonging to a campaign
- **`audienceCount()`** — estimate the reachable audience count for a targeting facet set

## Installation

```bash
composer require jeffersongoncalves/laravel-linkedin-ads
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="linkedin-ads-config"
```

## Configuration

Add to your `.env`:

```env
LINKEDIN_ADS_ACCESS_TOKEN=xxxxxxxxxxxxxxxxxxxxxx
LINKEDIN_ADS_API_VERSION=v2
LINKEDIN_ADS_TIMEOUT=8
```

`LINKEDIN_ADS_ACCESS_TOKEN` is the OAuth bearer token — see the [authorization code flow guide](https://learn.microsoft.com/en-us/linkedin/shared/authentication/authorization-code-flow). `LINKEDIN_ADS_API_VERSION` is the Marketing API version segment used in request URLs.

### Config Options

```php
// config/linkedin-ads.php
return [
    'access_token' => env('LINKEDIN_ADS_ACCESS_TOKEN'),
    'version' => env('LINKEDIN_ADS_API_VERSION', 'v2'),
    'timeout' => (int) env('LINKEDIN_ADS_TIMEOUT', 8),
];
```

## Usage

```php
use JeffersonGoncalves\LinkedinAds\LinkedinAdsClient;

// Ad accounts
$accounts = LinkedinAdsClient::accounts();

// Campaigns
$campaigns = LinkedinAdsClient::campaigns('123456789');

// Create a campaign (created PAUSED, matching LinkedIn's API default)
$campaign = LinkedinAdsClient::createCampaign(
    accountId: '123456789',
    campaignGroupId: '987654321',
    name: 'Q3 Launch',
    unitCost: 5.00,
    dailyBudget: 100.00,
);

// Toggle a campaign's status
LinkedinAdsClient::updateCampaignStatus('111', 'ACTIVE');

// Analytics for a date range
$stats = LinkedinAdsClient::campaignAnalytics(
    campaignId: '111',
    startYear: 2026, startMonth: 1, startDay: 1,
    endYear: 2026, endMonth: 1, endDay: 31,
);

// Creatives
$creatives = LinkedinAdsClient::creatives('111');

// Audience count for a targeting facet set
$count = LinkedinAdsClient::audienceCount([
    'include' => [
        'and' => [
            ['or' => ['urn:li:adTargetingFacet:locations' => ['urn:li:geo:103644278']]],
        ],
    ],
]);
```

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Code Formatting

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

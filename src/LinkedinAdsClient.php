<?php

namespace JeffersonGoncalves\LinkedinAds;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * LinkedIn Ads (Marketing API v2) HTTP layer. Wraps ad account listing,
 * campaign management, analytics reporting, creative listing, and audience
 * count estimation behind a small static client, threading the OAuth
 * bearer token, and returning null sentinels on ordinary HTTP failures
 * instead of throwing.
 */
class LinkedinAdsClient
{
    private const BASE_URL = 'https://api.linkedin.com';

    /**
     * List ad accounts accessible to the configured token.
     *
     * @return array<string, mixed>|null
     */
    public static function accounts(): ?array
    {
        $response = self::request('get', '/adAccountsV2', 'linkedin_ads_accounts', [
            'q' => 'search',
        ]);

        return self::jsonOrNull($response);
    }

    /**
     * List campaigns belonging to the given ad account.
     *
     * @return array<string, mixed>|null
     */
    public static function campaigns(string $accountId): ?array
    {
        $response = self::request('get', '/adCampaignsV2', 'linkedin_ads_campaigns', [
            'q' => 'search',
            'search.account.values[0]' => 'urn:li:sponsoredAccount:'.$accountId,
        ]);

        return self::jsonOrNull($response);
    }

    /**
     * Create a new (paused) campaign within a campaign group.
     *
     * @return array<string, mixed>|null
     */
    public static function createCampaign(
        string $accountId,
        string $campaignGroupId,
        string $name,
        string $type = 'SPONSORED_UPDATES',
        string $costType = 'CPC',
        float $unitCost = 5.00,
        float $dailyBudget = 100.00,
    ): ?array {
        $response = self::request('post', '/adCampaignsV2', 'linkedin_ads_create_campaign', body: [
            'account' => 'urn:li:sponsoredAccount:'.$accountId,
            'campaignGroup' => 'urn:li:sponsoredCampaignGroup:'.$campaignGroupId,
            'name' => $name,
            'type' => $type,
            'costType' => $costType,
            'unitCost' => ['amount' => $unitCost, 'currencyCode' => 'USD'],
            'dailyBudget' => ['amount' => $dailyBudget, 'currencyCode' => 'USD'],
            'status' => 'PAUSED',
        ]);

        return self::jsonOrNull($response);
    }

    /**
     * Update a campaign's status (e.g. ACTIVE, PAUSED, ARCHIVED).
     *
     * @return array<string, mixed>|null
     */
    public static function updateCampaignStatus(string $campaignId, string $status): ?array
    {
        $response = self::request('post', "/adCampaignsV2/{$campaignId}", 'linkedin_ads_update_campaign_status', body: [
            'patch' => ['$set' => ['status' => $status]],
        ]);

        return self::jsonOrNull($response);
    }

    /**
     * Fetch impressions, clicks, cost, and conversions for a campaign over
     * a date range.
     *
     * @return array<string, mixed>|null
     */
    public static function campaignAnalytics(
        string $campaignId,
        int $startYear,
        int $startMonth,
        int $startDay,
        int $endYear,
        int $endMonth,
        int $endDay,
    ): ?array {
        $response = self::request('get', '/adAnalyticsV2', 'linkedin_ads_campaign_analytics', [
            'q' => 'analytics',
            'pivot' => 'CAMPAIGN',
            'dateRange.start.year' => $startYear,
            'dateRange.start.month' => $startMonth,
            'dateRange.start.day' => $startDay,
            'dateRange.end.year' => $endYear,
            'dateRange.end.month' => $endMonth,
            'dateRange.end.day' => $endDay,
            'campaigns' => 'urn:li:sponsoredCampaign:'.$campaignId,
            'fields' => 'impressions,clicks,costInLocalCurrency,conversions',
        ]);

        return self::jsonOrNull($response);
    }

    /**
     * List creatives belonging to the given campaign.
     *
     * @return array<string, mixed>|null
     */
    public static function creatives(string $campaignId): ?array
    {
        $response = self::request('get', '/adCreativesV2', 'linkedin_ads_creatives', [
            'q' => 'search',
            'search.campaign.values[0]' => 'urn:li:sponsoredCampaign:'.$campaignId,
        ]);

        return self::jsonOrNull($response);
    }

    /**
     * Estimate the reachable audience count for a targeting facet set.
     *
     * @param  array<string, mixed>  $targeting
     * @return array<string, mixed>|null
     */
    public static function audienceCount(array $targeting): ?array
    {
        $response = self::request('post', '/audienceCountsV2', 'linkedin_ads_audience_count', body: [
            'audienceCriteria' => $targeting,
        ]);

        return self::jsonOrNull($response);
    }

    /**
     * Shared request/response handling: attaches the bearer token and the
     * required RestLi protocol header, catches transport failures, and
     * logs them rather than throwing.
     *
     * @param  'get'|'post'  $method
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     */
    private static function request(string $method, string $path, string $context, array $query = [], ?array $body = null): ?Response
    {
        $url = self::BASE_URL.'/'.self::version().$path;

        $headers = [
            'X-RestLi-Protocol-Version' => '2.0.0',
        ];

        if ($token = self::accessToken()) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        try {
            $request = Http::timeout(self::timeout())->withHeaders($headers);

            return $method === 'get' ? $request->get($url, $query) : $request->post($url, $body ?? []);
        } catch (Throwable $e) {
            self::logFailure($context, $url, $e);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function jsonOrNull(?Response $response): ?array
    {
        if ($response === null || ! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    private static function logFailure(string $context, string $target, Throwable $e): void
    {
        Log::warning('LinkedinAdsClient outbound fetch failed', [
            'context' => $context,
            'target' => $target,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }

    private static function accessToken(): ?string
    {
        $token = config('linkedin-ads.access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private static function version(): string
    {
        $version = config('linkedin-ads.version', 'v2');

        return is_string($version) && $version !== '' ? $version : 'v2';
    }

    private static function timeout(): int
    {
        return (int) config('linkedin-ads.timeout', 8);
    }
}

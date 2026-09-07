<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\LinkedinAds\LinkedinAdsClient;

it('lists ad accounts', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['elements' => [['id' => 1]]], 200),
    ]);

    expect(LinkedinAdsClient::accounts())->toBe(['elements' => [['id' => 1]]]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.linkedin.com/v2/adAccountsV2?q=search'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token')
            && $request->hasHeader('X-RestLi-Protocol-Version', '2.0.0');
    });
});

it('returns null when accounts fails', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response('', 401),
    ]);

    expect(LinkedinAdsClient::accounts())->toBeNull();
});

it('lists campaigns for an account', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['elements' => []], 200),
    ]);

    LinkedinAdsClient::campaigns('123456789');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.linkedin.com/v2/adCampaignsV2?q=search&search.account.values%5B0%5D=urn%3Ali%3AsponsoredAccount%3A123456789';
    });
});

it('creates a paused campaign', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['id' => 111], 201),
    ]);

    $result = LinkedinAdsClient::createCampaign(
        accountId: '123456789',
        campaignGroupId: '987654321',
        name: 'Q3 Launch',
    );

    expect($result)->toBe(['id' => 111]);

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/adCampaignsV2')
            && $request['account'] === 'urn:li:sponsoredAccount:123456789'
            && $request['campaignGroup'] === 'urn:li:sponsoredCampaignGroup:987654321'
            && $request['name'] === 'Q3 Launch'
            && $request['type'] === 'SPONSORED_UPDATES'
            && $request['costType'] === 'CPC'
            && $request['unitCost'] === ['amount' => 5.00, 'currencyCode' => 'USD']
            && $request['dailyBudget'] === ['amount' => 100.00, 'currencyCode' => 'USD']
            && $request['status'] === 'PAUSED';
    });
});

it('updates a campaign status', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response([], 200),
    ]);

    LinkedinAdsClient::updateCampaignStatus('111', 'ACTIVE');

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/adCampaignsV2/111')
            && $request['patch']['$set']['status'] === 'ACTIVE';
    });
});

it('fetches campaign analytics for a date range', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['elements' => []], 200),
    ]);

    LinkedinAdsClient::campaignAnalytics(
        campaignId: '111',
        startYear: 2026, startMonth: 1, startDay: 1,
        endYear: 2026, endMonth: 1, endDay: 31,
    );

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/adAnalyticsV2')
            && str_contains($request->url(), 'q=analytics')
            && str_contains($request->url(), 'pivot=CAMPAIGN')
            && str_contains($request->url(), 'campaigns=urn%3Ali%3AsponsoredCampaign%3A111')
            && str_contains($request->url(), 'fields=impressions%2Cclicks%2CcostInLocalCurrency%2Cconversions');
    });
});

it('lists creatives for a campaign', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['elements' => []], 200),
    ]);

    LinkedinAdsClient::creatives('111');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.linkedin.com/v2/adCreativesV2?q=search&search.campaign.values%5B0%5D=urn%3Ali%3AsponsoredCampaign%3A111';
    });
});

it('estimates an audience count', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['elements' => [['value' => 12000]]], 200),
    ]);

    $targeting = ['include' => ['and' => [['or' => ['urn:li:adTargetingFacet:locations' => ['urn:li:geo:103644278']]]]]];

    $result = LinkedinAdsClient::audienceCount($targeting);

    expect($result)->toBe(['elements' => [['value' => 12000]]]);

    Http::assertSent(function (Request $request) use ($targeting) {
        return str_contains($request->url(), '/audienceCountsV2')
            && $request['audienceCriteria'] === $targeting;
    });
});

it('omits the Authorization header when no access token is configured', function () {
    config()->set('linkedin-ads.access_token', null);

    Http::fake([
        'api.linkedin.com/*' => Http::response([], 200),
    ]);

    LinkedinAdsClient::accounts();

    Http::assertSent(fn (Request $request) => ! $request->hasHeader('Authorization'));
});

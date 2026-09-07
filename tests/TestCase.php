<?php

namespace JeffersonGoncalves\LinkedinAds\Tests;

use JeffersonGoncalves\LinkedinAds\LinkedinAdsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LinkedinAdsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('linkedin-ads.access_token', 'fake-access-token');
        $app['config']->set('linkedin-ads.version', 'v2');
        $app['config']->set('linkedin-ads.timeout', 5);
    }
}

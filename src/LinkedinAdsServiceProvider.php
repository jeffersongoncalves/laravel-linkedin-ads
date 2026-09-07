<?php

namespace JeffersonGoncalves\LinkedinAds;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LinkedinAdsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('linkedin-ads')
            ->hasConfigFile();
    }
}

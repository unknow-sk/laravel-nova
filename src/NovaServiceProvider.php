<?php

namespace UnknowSk\Nova;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Statikbe\NovaTranslationManager\TranslationManager;
use UnknowSk\Nova\Commands\NovaCommand;

class NovaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-nova')
            ->hasConfigFile([
                'nova',
                'nova-chained-translation-manager',
            ])
            ->hasViews()
            ->hasMigrations([
            ])
            ->hasTranslations()
            ->runsMigrations()
            ->hasCommand(NovaCommand::class);
    }

    public function packageBooted()
    {
        $locales = array_keys(config('core.languages.supported', []));

        TranslationManager::setLocales($locales ?: [config('app.locale')]);
    }

    public function register()
    {
        $this->app->register(\UnknowSk\Nova\Providers\NovaServiceProvider::class);

        return parent::register();
    }
}

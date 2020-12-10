<?php

namespace Hudm\Wxa;

use Hudm\Wxa\Commands\SyncMemberInfo;
use Illuminate\Support\ServiceProvider;

class WechatAssistantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WechatAssistant::class, function ($app) {
            return new WechatAssistant(config('assistant.app_url'));
        });

        $this->app->alias(WechatAssistant::class, 'wechatAssistant');
    }

    /** @inheritDoc */
    public function provides()
    {
        return [WechatAssistant::class, 'wechatAssistant'];
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/assistant.php' => config_path('assistant.php')
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncMemberInfo::class,
            ]);
        }
    }
}

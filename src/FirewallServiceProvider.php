<?php 

namespace Uhowep\Firewall;

use Illuminate\Support\ServiceProvider;


class FirewallServiceProvider extends ServiceProvider
{

	public function boot()
	{
		$this->publishes([__DIR__.'/../config'=>config_path()],'firewall-config');
	}


	public function register()
	{
		$this->app->singleton('firewall', function ($app) {
			return new Firewall;
		});
	}
}
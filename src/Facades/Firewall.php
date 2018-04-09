<?php

namespace Uhowep\Firewall\Facades;

use Illuminate\Support\Facades\Facade;


class Firewall extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \Uhowep\Firewall\Firewall::class;
	}
}
<?php 

namespace Uhowep\Firewall\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use Uhowep\Firewall\Firewall;


class FirewallMiddleware
{
	/**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
	public function handle($request, Closure $next)
	{
		$firewall = new Firewall();
		$sign = 'test_sign';
		$interface = Route::current()->uri;
		$result = $firewall->authroize($sign, $interface);
		if ($result==false) {
			abort(403);
		} else {
			return $next($request);
		}
	}

}
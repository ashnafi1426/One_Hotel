<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TenantContext;

class IdentifyTenant
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Single hotel installation: tenant is always the authoritative hotel
        return $next($request);
    }
}

<?php

namespace App\Domains\Companies\Middleware;

use App\Domains\Companies\Services\CompanyTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        CompanyTenant::flush();
        app()->instance(CompanyTenant::ENFORCE, true);

        return $next($request);
    }
}

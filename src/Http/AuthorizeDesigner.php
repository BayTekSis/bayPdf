<?php

namespace BayPdf\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AuthorizeDesigner
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless($request->user() !== null, 401);
        Gate::authorize(config('baypdf.gate'));
        $locale = $request->query('locale', config('baypdf.locale'));
        app()->setLocale(in_array($locale, ['en', 'de', 'tr'], true) ? $locale : 'en');

        return $next($request);
    }
}

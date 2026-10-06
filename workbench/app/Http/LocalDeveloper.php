<?php

namespace Workbench\App\Http;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LocalDeveloper
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless(in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true), 403);
        Auth::setUser(new GenericUser(['id' => 'baypdf-local']));

        return $next($request);
    }
}

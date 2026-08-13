<?php

namespace App\Http\Middleware;

use App\Services\NotificationCenter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkSectionNotificationsRead
{
    public function handle(Request $request, Closure $next, string $section): Response
    {
        app(NotificationCenter::class)->markSectionRead($request->user(), $section);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOperationFeedback
{
    /**
     * Ensure every successful admin form operation provides visible feedback.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            ! $request->isMethodSafe()
            && ! $request->expectsJson()
            && $response instanceof RedirectResponse
            && ! $this->hasFeedback($request)
        ) {
            $request->session()->flash('success', 'Operation completed successfully.');
        }

        return $response;
    }

    private function hasFeedback(Request $request): bool
    {
        foreach (['success', 'status', 'error', 'fail', 'warning', 'info', 'errors'] as $key) {
            if ($request->session()->has($key)) {
                return true;
            }
        }

        return false;
    }
}

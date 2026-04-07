<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!str_contains($request->header('Accept-Encoding', ''), 'gzip')) {
            return $response;
        }
        if ($response->headers->get('Content-Encoding')) {
            return $response; // already encoded
        }
        if ($request->hasHeader('X-Livewire')) {
            return $response; // Livewire AJAX — skip compression
        }

        $contentType = $response->headers->get('Content-Type', '');

        $isJson      = str_contains($contentType, 'json');
        $isHtml      = str_starts_with($contentType, 'text/html');
        $isOtherText = !$isHtml && (str_starts_with($contentType, 'text/') || str_contains($contentType, 'javascript'));

        // HTML only safe to compress when Debugbar is not injecting (APP_DEBUG=false)
        $shouldCompress = $isJson || $isOtherText || ($isHtml && !config('app.debug'));

        if (!$shouldCompress) {
            return $response;
        }

        $content = $response->getContent();
        if (strlen($content) < 860) {
            return $response;
        }

        $compressed = gzencode($content, 6);
        if ($compressed === false || strlen($compressed) >= strlen($content)) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->remove('Transfer-Encoding');
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}

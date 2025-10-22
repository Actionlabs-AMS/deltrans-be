<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if CSP is enabled
        if (!config('csp.enabled', true)) {
            return $next($request);
        }

        // Generate nonce for this request
        $nonceLength = config('csp.nonce.length', 16);
        $nonce = base64_encode(random_bytes($nonceLength));
        
        // Store nonce in request for use in views
        $request->attributes->set('csp_nonce', $nonce);
        
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // Secure Content Security Policy with nonce
        $csp = $this->buildSecureCSP($nonce);
        
        // Set CSP header (report-only or enforce)
        if (config('csp.report_only', false)) {
            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
        } else {
            $response->headers->set('Content-Security-Policy', $csp);
        }
        
        // Strict Transport Security (only for HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Build secure Content Security Policy with nonce
     */
    private function buildSecureCSP(string $nonce): string
    {
        $directives = config('csp.directives', []);
        $development = config('csp.development.enabled', false);
        
        $cspDirectives = [];
        
        foreach ($directives as $directive => $sources) {
            $processedSources = [];
            
            foreach ($sources as $source) {
                if ($source === "'nonce-{nonce}'") {
                    $processedSources[] = "'nonce-{$nonce}'";
                } else {
                    $processedSources[] = $source;
                }
            }
            
            // Add development-specific directives if in development mode
            if ($development && isset($directives['development']['additional_directives'][$directive])) {
                $processedSources = array_merge($processedSources, $directives['development']['additional_directives'][$directive]);
            }
            
            $cspDirectives[] = $directive . ' ' . implode(' ', $processedSources);
        }
        
        return implode('; ', $cspDirectives);
    }
}

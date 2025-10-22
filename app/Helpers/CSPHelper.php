<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class CSPHelper
{
    /**
     * Get the CSP nonce for the current request
     */
    public static function getNonce(Request $request): string
    {
        return $request->attributes->get('csp_nonce', '');
    }

    /**
     * Generate nonce attribute for HTML elements
     */
    public static function nonce(Request $request): string
    {
        $nonce = self::getNonce($request);
        return $nonce ? "nonce=\"{$nonce}\"" : '';
    }

    /**
     * Generate script tag with nonce
     */
    public static function script(Request $request, string $content = ''): string
    {
        $nonce = self::getNonce($request);
        $nonceAttr = $nonce ? " nonce=\"{$nonce}\"" : '';
        
        if ($content) {
            return "<script{$nonceAttr}>{$content}</script>";
        }
        
        return "<script{$nonceAttr}></script>";
    }

    /**
     * Generate style tag with nonce
     */
    public static function style(Request $request, string $content = ''): string
    {
        $nonce = self::getNonce($request);
        $nonceAttr = $nonce ? " nonce=\"{$nonce}\"" : '';
        
        if ($content) {
            return "<style{$nonceAttr}>{$content}</style>";
        }
        
        return "<style{$nonceAttr}></style>";
    }
}

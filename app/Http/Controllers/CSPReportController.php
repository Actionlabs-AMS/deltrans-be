<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CSPReportController extends Controller
{
    /**
     * Handle CSP violation reports
     */
    public function report(Request $request)
    {
        $report = $request->all();
        
        // Log CSP violations for monitoring
        Log::warning('CSP Violation Report', [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'report' => $report,
            'timestamp' => now()->toISOString(),
        ]);
        
        // You can also store in database for analysis
        // CSPViolation::create([
        //     'user_agent' => $request->userAgent(),
        //     'ip_address' => $request->ip(),
        //     'violated_directive' => $report['violated-directive'] ?? null,
        //     'blocked_uri' => $report['blocked-uri'] ?? null,
        //     'document_uri' => $report['document-uri'] ?? null,
        //     'report' => json_encode($report),
        // ]);
        
        return response()->json(['status' => 'received'], 200);
    }
}

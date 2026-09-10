<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendCustomEmailRequest;
use App\Services\EmailService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Email",
 *     description="API endpoints for sending custom emails with optional file attachments"
 * )
 */
class EmailController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send a custom email with optional file attachment(s).
     *
     * @OA\Post(
     *     path="/api/emails/send",
     *     summary="Send a custom email with optional attachment",
     *     description="Sends an email using recipient, subject, and body provided by the client. Optional file attachment(s) can be uploaded in the same multipart request. Not tied to SOA, billing, or invoice.",
     *     tags={"Email"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"email", "subject", "body"},
     *                 @OA\Property(property="email", type="string", format="email", example="client@example.com", description="Recipient email address"),
     *                 @OA\Property(property="subject", type="string", example="Document request", description="Email subject"),
     *                 @OA\Property(property="body", type="string", example="<p>Please find the attached file.</p>", description="Email body (HTML allowed)"),
     *                 @OA\Property(property="cc", type="array", @OA\Items(type="string", format="email"), nullable=true, description="Optional CC recipients"),
     *                 @OA\Property(property="attachment", type="string", format="binary", nullable=true, description="Optional single file attachment (max 5MB; pdf, office, images, csv, txt, zip)"),
     *                 @OA\Property(property="attachments[]", type="array", @OA\Items(type="string", format="binary"), nullable=true, description="Optional multiple file attachments (max 5 files total, 5MB each)")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Email sent successfully", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example="Email sent successfully")
     *     )),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, ref="#/components/responses/GeneralError")
     * )
     */
    public function send(SendCustomEmailRequest $request)
    {
        try {
            $to = $request->validated('email');
            $subject = $request->validated('subject');
            $body = $request->validated('body');
            $cc = $request->validated('cc') ?? [];

            $files = $this->collectUploadedFiles($request);

            if (empty($files)) {
                $this->emailService->sendEmail($to, $subject, $body, $cc);
            } else {
                $this->emailService->sendEmailWithFileAttachment(
                    $to,
                    $subject,
                    $body,
                    null,
                    null,
                    'application/octet-stream',
                    $cc,
                    $files
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('[EmailController] Failed to send custom email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again later.',
            ], 500);
        }
    }

    /**
     * @return array<int, array{content:string,name:string,mime:string}>
     */
    private function collectUploadedFiles(SendCustomEmailRequest $request): array
    {
        $uploaded = [];

        /** @var UploadedFile|null $single */
        $single = $request->file('attachment');
        if ($single instanceof UploadedFile && $single->isValid()) {
            $uploaded[] = $single;
        }

        $multiple = $request->file('attachments', []);
        if ($multiple instanceof UploadedFile) {
            $multiple = [$multiple];
        }
        if (is_array($multiple)) {
            foreach ($multiple as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $uploaded[] = $file;
                }
            }
        }

        $files = [];
        foreach ($uploaded as $file) {
            $files[] = [
                'content' => file_get_contents($file->getRealPath()),
                'name' => $this->sanitizeFilename($file),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
            ];
        }

        return $files;
    }

    private function sanitizeFilename(UploadedFile $file): string
    {
        $original = basename((string) $file->getClientOriginalName());
        $original = preg_replace('/[^A-Za-z0-9._-]+/', '_', $original) ?: '';
        $original = trim($original, '._-');

        if ($original === '') {
            $extension = $file->getClientOriginalExtension();
            $original = 'attachment' . ($extension ? '.' . $extension : '');
        }

        return Str::limit($original, 180, '');
    }
}

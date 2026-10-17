<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DiagnosticsController extends Controller
{
    /**
     * Get mail configuration diagnostics
     * Only accessible to authenticated users for security
     */
    public function mailConfig(): JsonResponse
    {
        $config = [
            'mail_mailer' => [
                'env_value' => env('MAIL_MAILER', 'NOT SET'),
                'config_value' => config('mail.default'),
            ],
            'smtp_config' => [
                'host' => config('mail.mailers.smtp.host', 'NOT SET'),
                'port' => config('mail.mailers.smtp.port', 'NOT SET'),
                'encryption' => config('mail.mailers.smtp.scheme', 'NOT SET'),
                'username_set' => !empty(config('mail.mailers.smtp.username')),
                'password_set' => !empty(config('mail.mailers.smtp.password')),
                'timeout' => config('mail.mailers.smtp.timeout', 'NOT SET'),
            ],
            'mail_from' => [
                'address' => config('mail.from.address', 'NOT SET'),
                'name' => config('mail.from.name', 'NOT SET'),
            ],
            'app_config' => [
                'env' => config('app.env', 'NOT SET'),
                'debug' => config('app.debug'),
                'frontend_url' => config('app.frontend_url', 'NOT SET'),
            ],
            'queue_config' => [
                'connection' => config('queue.default', 'NOT SET'),
            ],
            'log_config' => [
                'channel' => config('logging.default', 'NOT SET'),
                'level' => config('logging.level', 'NOT SET'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info('[DiagnosticsController] Mail config requested', $config);

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Mail configuration diagnostics. Check backend logs for full details.',
        ]);
    }

    /**
     * Test email sending
     * Sends a test email to the authenticated user
     */
    public function testEmail(): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated or email not set',
                ], 401);
            }

            Log::info('[DiagnosticsController.testEmail] Testing email send', [
                'recipient' => $user->email,
                'mailer' => config('mail.default'),
            ]);

            // Send a simple test email
            Mail::raw('This is a test email from One Hotel system.', function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('One Hotel - Test Email')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('[DiagnosticsController.testEmail] Test email sent successfully', [
                'recipient' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $user->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('[DiagnosticsController.testEmail] Test email failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test email failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * إرسال حدث إلى جميع Webhooks المسجلة
     */
    public static function dispatch(string $event, array $payload): void
    {
        $webhooks = Webhook::forEvent($event)->get();

        foreach ($webhooks as $webhook) {
            try {
                self::send($webhook, $event, $payload);
            } catch (\Exception $e) {
                Log::warning("Webhook dispatch failed for #{$webhook->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * إرسال Webhook فردي
     */
    private static function send(Webhook $webhook, string $event, array $payload): void
    {
        $body = [
            'event'     => $event,
            'timestamp' => now()->toIso8601String(),
            'data'      => $payload,
        ];

        $signature = hash_hmac('sha256', json_encode($body), $webhook->secret);

        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'       => 'application/json',
                    'X-Webhook-Signature'=> $signature,
                    'X-Webhook-Event'    => $event,
                ])
                ->post($webhook->url, $body);

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $success = $response->successful();

            WebhookLog::create([
                'webhook_id'    => $webhook->id,
                'event'         => $event,
                'payload'       => $body,
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 2000),
                'success'       => $success,
                'duration_ms'   => $duration,
                'created_at'    => now(),
            ]);

            $webhook->update([
                'last_triggered_at' => now(),
                'failure_count'     => $success ? 0 : $webhook->failure_count + 1,
                'last_failed_at'    => $success ? $webhook->last_failed_at : now(),
            ]);

            // تعطيل Webhook بعد 10 فشل متتالي
            if ($webhook->failure_count >= 10) {
                $webhook->update(['is_active' => false]);
                Log::warning("Webhook #{$webhook->id} disabled after 10 consecutive failures");
            }
        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            WebhookLog::create([
                'webhook_id'    => $webhook->id,
                'event'         => $event,
                'payload'       => $body,
                'response_code' => 0,
                'response_body' => $e->getMessage(),
                'success'       => false,
                'duration_ms'   => $duration,
                'created_at'    => now(),
            ]);

            $webhook->increment('failure_count');
            $webhook->update(['last_failed_at' => now()]);
        }
    }

    /**
     * قائمة الأحداث المتاحة
     */
    public static function availableEvents(): array
    {
        return [
            'attendance.checkin'    => 'تسجيل حضور',
            'attendance.checkout'   => 'تسجيل انصراف',
            'attendance.overtime'   => 'عمل إضافي',
            'leave.created'         => 'طلب إجازة جديد',
            'leave.approved'        => 'موافقة على إجازة',
            'leave.rejected'        => 'رفض إجازة',
            'report.submitted'      => 'بلاغ سري جديد',
            'tampering.detected'    => 'اكتشاف تلاعب',
            'employee.created'      => 'إضافة موظف',
            'employee.deleted'      => 'حذف موظف',
        ];
    }
}

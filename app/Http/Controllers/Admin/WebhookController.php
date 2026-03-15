<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * عرض قائمة Webhooks
     */
    public function index()
    {
        $webhooks = Webhook::with('creator')->latest()->get();
        $availableEvents = WebhookService::availableEvents();

        return view('admin.webhooks', compact('webhooks', 'availableEvents'));
    }

    /**
     * إنشاء Webhook جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'url'    => 'required|url|max:500',
            'events' => 'required|array|min:1',
        ]);

        Webhook::create([
            'name'       => $request->name,
            'url'        => $request->url,
            'secret'     => bin2hex(random_bytes(32)),
            'events'     => $request->events,
            'is_active'  => true,
            'created_by' => session('admin_id'),
        ]);

        return back()->with('success', 'تم إنشاء Webhook بنجاح');
    }

    /**
     * تحديث Webhook
     */
    public function update(Request $request, Webhook $webhook)
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'url'    => 'required|url|max:500',
            'events' => 'required|array|min:1',
        ]);

        $webhook->update([
            'name'      => $request->name,
            'url'       => $request->url,
            'events'    => $request->events,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'تم تحديث Webhook');
    }

    /**
     * حذف Webhook
     */
    public function destroy(Webhook $webhook)
    {
        $webhook->delete();
        return back()->with('success', 'تم حذف Webhook');
    }

    /**
     * إعادة توليد المفتاح السري
     */
    public function regenerateSecret(Webhook $webhook)
    {
        $webhook->update([
            'secret'        => bin2hex(random_bytes(32)),
            'failure_count' => 0,
            'is_active'     => true,
        ]);

        return back()->with('success', 'تم إعادة توليد المفتاح السري');
    }

    /**
     * عرض سجل Webhook
     */
    public function logs(Webhook $webhook)
    {
        $logs = $webhook->logs()->latest('created_at')->paginate(50);
        return view('admin.webhook-logs', compact('webhook', 'logs'));
    }
}

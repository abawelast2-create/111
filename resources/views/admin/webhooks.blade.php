@extends('layouts.admin')

@section('content')

<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.webhooks') }}</span>
        <button type="button" class="btn btn-sm" onclick="document.getElementById('addWebhookModal').style.display='flex'">
            {{ __('messages.add_webhook') }}
        </button>
    </div>

    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>{{ __('messages.webhook_name') }}</th>
                    <th>{{ __('messages.webhook_url') }}</th>
                    <th>{{ __('messages.webhook_events') }}</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($webhooks as $webhook)
                    <tr>
                        <td><strong>{{ $webhook->name }}</strong></td>
                        <td style="font-size:.8rem;direction:ltr;text-align:right;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $webhook->url }}</td>
                        <td>
                            @foreach($webhook->events ?? [] as $event)
                                <span class="badge badge-blue" style="font-size:.7rem;margin:1px">{{ $event }}</span>
                            @endforeach
                        </td>
                        <td>
                            <span class="badge {{ $webhook->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $webhook->is_active ? 'فعال' : 'معطل' }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                <a href="{{ route('admin.webhooks.logs', $webhook) }}" class="btn btn-sm btn-secondary" style="font-size:.75rem;padding:4px 8px">السجل</a>
                                <form method="POST" action="{{ route('admin.webhooks.regenerate', $webhook) }}" style="margin:0" onsubmit="return confirm('سيتم إنشاء مفتاح سري جديد')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-secondary" style="font-size:.75rem;padding:4px 8px">مفتاح جديد</button>
                                </form>
                                <form method="POST" action="{{ route('admin.webhooks.destroy', $webhook) }}" style="margin:0" onsubmit="return confirm('حذف Webhook?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;font-size:.75rem;padding:4px 8px">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:var(--text3);padding:20px">لا توجد Webhooks</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Webhook Modal -->
<div id="addWebhookModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:16px">
    <div class="card" style="max-width:500px;width:100%;max-height:90vh;overflow-y:auto">
        <div class="card-header">
            <span class="card-title">{{ __('messages.add_webhook') }}</span>
            <button type="button" onclick="document.getElementById('addWebhookModal').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--text2)">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.webhooks.store') }}" style="padding:16px">
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('messages.webhook_name') }}</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('messages.webhook_url') }}</label>
                <input type="url" name="url" class="form-control" required placeholder="https://example.com/webhook" dir="ltr">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('messages.webhook_events') }}</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    @foreach(['attendance.checkin','attendance.checkout','leave.created','leave.approved','leave.rejected','report.submitted','tampering.detected','overtime.started','overtime.ended','employee.created'] as $event)
                        <label style="display:flex;gap:6px;align-items:center;font-size:.85rem;cursor:pointer">
                            <input type="checkbox" name="events[]" value="{{ $event }}"> {{ $event }}
                        </label>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="btn" style="width:100%">إنشاء</button>
        </form>
    </div>
</div>

@endsection

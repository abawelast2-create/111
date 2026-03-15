@extends('layouts.admin')

@section('content')

<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.webhook_logs') }} - {{ $webhook->name }}</span>
        <a href="{{ route('admin.webhooks.index') }}" class="btn btn-sm btn-secondary">رجوع</a>
    </div>

    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>الحدث</th>
                    <th>الرابط</th>
                    <th>الحالة</th>
                    <th>كود الاستجابة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td><span class="badge badge-blue" style="font-size:.75rem">{{ $log->event }}</span></td>
                        <td style="font-size:.8rem;direction:ltr;text-align:right;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $log->url }}</td>
                        <td>
                            <span class="badge {{ $log->success ? 'badge-green' : 'badge-red' }}">
                                {{ $log->success ? 'نجح' : 'فشل' }}
                            </span>
                        </td>
                        <td>{{ $log->response_code ?? '-' }}</td>
                        <td style="font-size:.85rem">{{ \Carbon\Carbon::parse($log->sent_at)->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:var(--text3);padding:20px">لا توجد سجلات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div style="padding:16px;display:flex;justify-content:center">
            {{ $logs->links() }}
        </div>
    @endif
</div>

@endsection

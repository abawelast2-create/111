@extends('layouts.admin')

@section('content')

<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.notifications') }}</span>
        <div style="display:flex;gap:8px;align-items:center">
            @if($unreadCount > 0)
                <span class="badge badge-orange">{{ $unreadCount }} {{ __('messages.unread') }}</span>
                <form method="POST" action="{{ route('admin.notifications.markAllRead') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-secondary">{{ __('messages.mark_all_read') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="notifications-list">
        @forelse($notifications as $notification)
            <div class="notification-item {{ !$notification->read_at ? 'unread' : '' }}" style="padding:16px;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:flex-start;{{ !$notification->read_at ? 'background:var(--bg2)' : '' }}">
                <div style="flex:1">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                        <strong style="font-size:.9rem">{{ $notification->title }}</strong>
                        <small style="color:var(--text3)">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <p style="margin:0;color:var(--text2);font-size:.85rem">{{ $notification->body }}</p>
                    @if($notification->data)
                        <small style="color:var(--text3)">
                            @if(isset($notification->data['employee_name']))
                                {{ $notification->data['employee_name'] }}
                            @endif
                        </small>
                    @endif
                </div>
                @if(!$notification->read_at)
                    <form method="POST" action="{{ route('admin.notifications.markRead', $notification) }}" style="margin:0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary" style="font-size:.75rem;padding:4px 10px">قراءة</button>
                    </form>
                @endif
            </div>
        @empty
            <div style="text-align:center;padding:40px;color:var(--text3)">
                <p>{{ __('messages.no_notifications') }}</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div style="padding:16px;display:flex;justify-content:center">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@endsection

@extends('layouts.admin')

@section('content')

<div class="card">
    <div class="card-header">
        <span class="card-title"><span class="card-title-bar"></span> {{ __('messages.backups') }}</span>
        <form method="POST" action="{{ route('admin.backups.create') }}" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-sm" onclick="this.disabled=true;this.form.submit()">
                <x-icon name="settings" :size="14"/> {{ __('messages.create_backup') }}
            </button>
        </form>
    </div>

    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الملف</th>
                    <th>{{ __('messages.backup_type') }}</th>
                    <th>{{ __('messages.backup_size') }}</th>
                    <th>التاريخ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td style="font-size:.85rem;direction:ltr;text-align:right">{{ $backup->filename }}</td>
                        <td>
                            <span class="badge {{ $backup->type === 'manual' ? 'badge-blue' : 'badge-green' }}">
                                {{ $backup->type === 'manual' ? __('messages.manual') : __('messages.auto') }}
                            </span>
                        </td>
                        <td>{{ $backup->size_formatted }}</td>
                        <td style="font-size:.85rem">{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <a href="{{ route('admin.backups.download', $backup) }}" class="btn btn-sm btn-secondary">{{ __('messages.download') }}</a>
                                <form method="POST" action="{{ route('admin.backups.destroy', $backup) }}" onsubmit="return confirm('هل أنت متأكد من الحذف؟')" style="margin:0">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;color:var(--text3);padding:20px">لا توجد نسخ احتياطية</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

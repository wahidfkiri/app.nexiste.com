<?php

namespace Vendor\User\Exports;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        $tenantId = (int) auth()->user()->tenant_id;

        $query = User::query()->with('roles');

        if (Schema::hasTable('tenant_user_memberships')) {
            $query
                ->join('tenant_user_memberships as tum', function ($join) use ($tenantId): void {
                    $join->on('users.id', '=', 'tum.user_id')
                        ->where('tum.tenant_id', '=', $tenantId)
                        ->where('tum.status', '=', 'active');
                })
                ->select([
                    'users.*',
                    'tum.role_in_tenant as role_in_tenant',
                    'tum.is_tenant_owner as is_tenant_owner',
                ]);
        } else {
            $query->where('users.tenant_id', $tenantId)->select('users.*');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            __('user::users.exports.id'),
            __('user::users.exports.name'),
            __('user::users.exports.email'),
            __('user::users.exports.phone'),
            __('user::users.exports.role'),
            __('user::users.exports.status'),
            __('user::users.exports.job_title'),
            __('user::users.exports.department'),
            __('user::users.exports.last_login'),
            __('user::users.exports.created_at'),
        ];
    }

    public function map($user): array
    {
        $roleLabels = config('user.tenant_roles', []);
        $statusLabels = config('user.user_statuses', []);

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? '-',
            $roleLabels[$user->role_in_tenant] ?? $user->role_in_tenant,
            $statusLabels[$user->status] ?? $user->status,
            $user->job_title ?? '-',
            $user->department ?? '-',
            $user->last_login_at?->format('d/m/Y H:i') ?? __('user::users.exports.never'),
            $user->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
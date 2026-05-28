<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\SecurityActivityLogRepository;

class SecurityActivityLogService
{
    public function __construct(
        protected SecurityActivityLogRepository $securityActivityLogRepository,
    ) {
    }

    public function log(
        ?User $actor,
        ?string $outletId,
        string $action,
        string $description,
        string $status = 'info',
        array $metadata = [],
        ?string $ipAddress = null,
    ): void {
        $this->securityActivityLogRepository->create([
            'outlet_id' => $outletId,
            'user_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_role' => $actor?->role?->type ?? $actor?->role?->name,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress,
            'status' => $status,
            'metadata' => $metadata ?: null,
        ]);
    }
}

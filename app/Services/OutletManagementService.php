<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\User;
use App\Repositories\OutletManagementRepository;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutletManagementService
{
    protected const DEFAULT_WORKFLOW_STATUSES = [
        'pending',
        'in_progress',
        'waiting_bar_approval',
        'ready',
        'completed',
    ];

    protected const DEFAULT_ROLE_TEMPLATES = [
        ['name' => 'Owner', 'type' => 'owner', 'is_active' => true],
        ['name' => 'Supervisor', 'type' => 'supervisor', 'is_active' => true],
        ['name' => 'Kasir', 'type' => 'kasir', 'is_active' => true],
        ['name' => 'Kitchen', 'type' => 'kitchen', 'is_active' => true],
        ['name' => 'Bar', 'type' => 'bar', 'is_active' => true],
    ];

    public function __construct(
        protected OutletManagementRepository $outletManagementRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanManage($actor);

        $resolvedFilters = [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => (string) ($filters['status'] ?? ''),
            'per_page' => (int) ($filters['per_page'] ?? 9),
        ];

        $periodStart = CarbonImmutable::today()->startOfMonth();
        $periodEnd = CarbonImmutable::today();

        $outlets = $this->outletManagementRepository->paginate($resolvedFilters);
        $this->hydrateOutletRows($outlets, $periodStart, $periodEnd);

        $summary = $this->buildSummary($periodStart, $periodEnd);

        return [
            'outlets' => $outlets,
            'summary' => $summary,
            'comparison' => [
                'leaderboard' => $this->buildLeaderboard($periodStart, $periodEnd),
            ],
            'filters' => $resolvedFilters,
            'period' => [
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'label' => 'Periode ' . $periodStart->locale('id')->translatedFormat('d M') . ' - '
                    . $periodEnd->locale('id')->translatedFormat('d M Y'),
            ],
        ];
    }

    public function create(array $payload, User $actor): void
    {
        $this->assertCanManage($actor);

        DB::transaction(function () use ($payload, $actor) {
            $outlet = $this->outletManagementRepository->create([
                ...$this->normalizePayload($payload),
                'is_active' => true,
            ]);

            $templates = $this->outletManagementRepository->getRoleTemplates($actor->outlet_id);

            if ($templates->isEmpty()) {
                $templates = collect(self::DEFAULT_ROLE_TEMPLATES);
            }

            $this->outletManagementRepository->createRoleTemplatesForOutlet($outlet->id, $templates);
        });
    }

    public function update(Outlet $outlet, array $payload, User $actor): void
    {
        $this->assertCanManage($actor);

        DB::transaction(function () use ($outlet, $payload) {
            $this->outletManagementRepository->update(
                $outlet,
                $this->normalizePayload($payload),
            );
        });
    }

    public function updateStatus(Outlet $outlet, array $payload, User $actor): void
    {
        $this->assertCanManage($actor);

        $isActive = (bool) $payload['is_active'];

        if (!$isActive && $outlet->is_active && $this->outletManagementRepository->countActiveOutlets() <= 1) {
            throw ValidationException::withMessages([
                'is_active' => 'Minimal harus ada satu outlet aktif.',
            ]);
        }

        $this->outletManagementRepository->update($outlet, [
            'is_active' => $isActive,
        ]);
    }

    protected function hydrateOutletRows(
        LengthAwarePaginator $outlets,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): void {
        $metrics = $this->outletManagementRepository->getPeriodMetricsForOutlets(
            $outlets->getCollection()->pluck('id')->all(),
            $periodStart,
            $periodEnd,
        );

        $outlets->setCollection(
            $outlets->getCollection()->map(function (Outlet $outlet) use ($metrics) {
                $periodMetric = $metrics->get($outlet->id);
                $monthlyRevenue = (float) ($periodMetric->total_revenue ?? 0);
                $monthlyOrders = (int) ($periodMetric->total_orders ?? 0);

                return [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'address' => $outlet->address,
                    'phone' => $outlet->phone,
                    'is_active' => (bool) $outlet->is_active,
                    'settings' => $this->normalizeStoredSettings($outlet->settings ?? []),
                    'stats' => [
                        'active_employees' => (int) ($outlet->active_users_count ?? 0),
                        'total_employees' => (int) ($outlet->users_count ?? 0),
                        'active_tables' => (int) ($outlet->active_tables_count ?? 0),
                        'total_tables' => (int) ($outlet->tables_count ?? 0),
                        'monthly_orders' => $monthlyOrders,
                        'monthly_revenue' => $monthlyRevenue,
                        'average_ticket' => $monthlyOrders > 0 ? $monthlyRevenue / $monthlyOrders : 0,
                    ],
                ];
            }),
        );
    }

    protected function buildSummary(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $summary = $this->outletManagementRepository->getSummary();
        $activeOutlets = $this->outletManagementRepository->getActiveOutlets();
        $metrics = $this->outletManagementRepository->getPeriodMetricsForOutlets(
            $activeOutlets->pluck('id')->all(),
            $periodStart,
            $periodEnd,
        );

        return [
            ...$summary,
            'monthly_orders' => (int) $metrics->sum(fn ($row) => (int) ($row->total_orders ?? 0)),
            'monthly_revenue' => (float) $metrics->sum(fn ($row) => (float) ($row->total_revenue ?? 0)),
        ];
    }

    protected function buildLeaderboard(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $activeOutlets = $this->outletManagementRepository->getActiveOutlets();
        $metrics = $this->outletManagementRepository->getPeriodMetricsForOutlets(
            $activeOutlets->pluck('id')->all(),
            $periodStart,
            $periodEnd,
        );

        $rows = $activeOutlets
            ->map(function (Outlet $outlet) use ($metrics) {
                $periodMetric = $metrics->get($outlet->id);

                return [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'active_employees' => (int) ($outlet->active_users_count ?? 0),
                    'monthly_orders' => (int) ($periodMetric->total_orders ?? 0),
                    'monthly_revenue' => (float) ($periodMetric->total_revenue ?? 0),
                ];
            })
            ->sortByDesc(fn (array $row) => [$row['monthly_revenue'], $row['monthly_orders']])
            ->values();

        $totalRevenue = (float) $rows->sum('monthly_revenue');

        return $rows
            ->take(3)
            ->map(function (array $row) use ($totalRevenue) {
                $row['revenue_share'] = $totalRevenue > 0
                    ? round(($row['monthly_revenue'] / $totalRevenue) * 100, 1)
                    : 0.0;

                return $row;
            })
            ->all();
    }

    protected function normalizePayload(array $payload): array
    {
        return [
            'name' => trim((string) $payload['name']),
            'address' => $this->nullableTrim($payload['address'] ?? null),
            'phone' => $this->nullableTrim($payload['phone'] ?? null),
            'settings' => $this->normalizeInputSettings($payload),
        ];
    }

    protected function normalizeInputSettings(array $payload): array
    {
        $workflowStatuses = collect(
            preg_split('/\r\n|\r|\n/', (string) ($payload['workflow_statuses'] ?? '')) ?: []
        )
            ->map(fn ($status) => trim((string) $status))
            ->filter()
            ->unique()
            ->values()
            ->take(10)
            ->all();

        if ($workflowStatuses === []) {
            $workflowStatuses = self::DEFAULT_WORKFLOW_STATUSES;
        }

        return [
            'workflow_statuses' => $workflowStatuses,
            'default_receipt_method' => $payload['default_receipt_method'] ?? 'print',
            'bar_approval_enabled' => (bool) ($payload['bar_approval_enabled'] ?? false),
            'customer_can_view_status' => (bool) ($payload['customer_can_view_status'] ?? true),
            'customer_can_edit_order' => (bool) ($payload['customer_can_edit_order'] ?? false),
            'tax_percentage' => (float) ($payload['tax_percentage'] ?? 0),
            'tax_is_inclusive' => (bool) ($payload['tax_is_inclusive'] ?? false),
        ];
    }

    protected function normalizeStoredSettings(array $settings): array
    {
        $workflowStatuses = collect($settings['workflow_statuses'] ?? self::DEFAULT_WORKFLOW_STATUSES)
            ->map(fn ($status) => trim((string) $status))
            ->filter()
            ->unique()
            ->values()
            ->take(10)
            ->all();

        if ($workflowStatuses === []) {
            $workflowStatuses = self::DEFAULT_WORKFLOW_STATUSES;
        }

        $defaultReceiptMethod = (string) ($settings['default_receipt_method'] ?? 'print');

        if (!in_array($defaultReceiptMethod, ['print', 'whatsapp', 'skip'], true)) {
            $defaultReceiptMethod = 'print';
        }

        return [
            'workflow_statuses' => $workflowStatuses,
            'default_receipt_method' => $defaultReceiptMethod,
            'bar_approval_enabled' => (bool) ($settings['bar_approval_enabled'] ?? false),
            'customer_can_view_status' => (bool) ($settings['customer_can_view_status'] ?? true),
            'customer_can_edit_order' => (bool) ($settings['customer_can_edit_order'] ?? false),
            'tax_percentage' => (float) ($settings['tax_percentage'] ?? 0),
            'tax_is_inclusive' => (bool) ($settings['tax_is_inclusive'] ?? false),
        ];
    }

    protected function nullableTrim(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : null;
    }

    protected function assertCanManage(User $actor): void
    {
        if ($actor->role?->type !== 'owner') {
            abort(403, 'Manajemen outlet hanya tersedia untuk owner.');
        }
    }
}

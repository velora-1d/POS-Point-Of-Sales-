<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\SalesReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SalesReportService
{
    protected const PAYMENT_METHODS = ['cash', 'qris', 'debit', 'ewallet', 'kasbon'];

    protected const ORDER_SOURCES = ['kasir', 'qr_meja', 'gofood', 'grabfood', 'shopeefood', 'maximfood'];

    public function __construct(
        protected SalesReportRepository $salesReportRepository,
    ) {
    }

    public function getReport(User $actor, array $filters = []): array
    {
        $this->assertCanRead($actor);

        $scopeOutletId = $this->resolveScopeOutletId($actor, $filters['outlet_id'] ?? null);
        $resolvedFilters = $this->resolveFilters($filters, $scopeOutletId, $actor);
        $orders = $this->salesReportRepository->getSettledOrdersSnapshot($resolvedFilters, $scopeOutletId);

        return [
            'summary' => $this->buildSummary($orders),
            'trend' => $this->buildTrend($orders),
            'breakdowns' => [
                'payments' => $this->buildPaymentBreakdown($orders),
                'sources' => $this->buildSourceBreakdown($orders),
            ],
            'transactions' => $this->transformPaginator(
                $this->salesReportRepository->paginateSettledOrders($resolvedFilters, $scopeOutletId),
            ),
            'filters' => $resolvedFilters,
            'referenceData' => $this->buildReferenceData($actor, $scopeOutletId),
            'scope' => [
                'viewer_role' => $actor->role?->type,
                'outlet_id' => $scopeOutletId,
                'date_basis' => 'created_at',
            ],
        ];
    }

    protected function resolveFilters(array $filters, ?string $scopeOutletId, User $actor): array
    {
        return [
            'start_date' => $filters['start_date'] ?? CarbonImmutable::today()->startOfMonth()->toDateString(),
            'end_date' => $filters['end_date'] ?? CarbonImmutable::today()->toDateString(),
            'outlet_id' => $actor->role?->type === 'owner' ? ($scopeOutletId ?? '') : ($scopeOutletId ?? ''),
            'source' => $filters['source'] ?? '',
            'payment_method' => $filters['payment_method'] ?? '',
            'search' => trim((string) ($filters['search'] ?? '')),
            'per_page' => max(5, min((int) ($filters['per_page'] ?? 10), 25)),
        ];
    }

    protected function buildSummary(Collection $orders): array
    {
        $totalRevenue = (float) $orders->sum(fn (Order $order) => (float) $order->total_amount);
        $orderCount = $orders->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $orderCount,
            'total_discount' => (float) $orders->sum(fn (Order $order) => (float) $order->discount_amount),
            'total_items_sold' => (int) round($orders->sum(fn (Order $order) => (float) ($order->total_items_quantity ?? 0))),
            'average_ticket' => $orderCount > 0 ? $totalRevenue / $orderCount : 0,
            'largest_transaction' => (float) $orders->max(fn (Order $order) => (float) $order->total_amount),
        ];
    }

    protected function buildTrend(Collection $orders): array
    {
        return $orders
            ->groupBy(fn (Order $order) => $order->created_at?->toDateString() ?? now()->toDateString())
            ->map(function (Collection $group, string $date) {
                return [
                    'date' => $date,
                    'orders' => $group->count(),
                    'revenue' => (float) $group->sum(fn (Order $order) => (float) $order->total_amount),
                ];
            })
            ->sortBy('date')
            ->values()
            ->all();
    }

    protected function buildPaymentBreakdown(Collection $orders): array
    {
        return collect(self::PAYMENT_METHODS)
            ->map(function (string $method) use ($orders) {
                $filtered = $orders->filter(
                    fn (Order $order) => data_get($order->metadata, 'payment.method') === $method,
                );

                return [
                    'method' => $method,
                    'orders' => $filtered->count(),
                    'amount' => (float) $filtered->sum(fn (Order $order) => (float) $order->total_amount),
                ];
            })
            ->filter(fn (array $row) => $row['orders'] > 0 || $row['amount'] > 0)
            ->values()
            ->all();
    }

    protected function buildSourceBreakdown(Collection $orders): array
    {
        return collect(self::ORDER_SOURCES)
            ->map(function (string $source) use ($orders) {
                $filtered = $orders->filter(fn (Order $order) => $order->source === $source);

                return [
                    'source' => $source,
                    'orders' => $filtered->count(),
                    'amount' => (float) $filtered->sum(fn (Order $order) => (float) $order->total_amount),
                ];
            })
            ->filter(fn (array $row) => $row['orders'] > 0 || $row['amount'] > 0)
            ->values()
            ->all();
    }

    protected function buildReferenceData(User $actor, ?string $scopeOutletId): array
    {
        return [
            'outlets' => $this->salesReportRepository->getOutlets(
                $actor->role?->type === 'owner' ? null : $scopeOutletId,
            ),
            'sources' => collect(self::ORDER_SOURCES)->map(fn (string $source) => [
                'value' => $source,
                'label' => $this->sourceLabel($source),
            ])->all(),
            'paymentMethods' => collect(self::PAYMENT_METHODS)->map(fn (string $method) => [
                'value' => $method,
                'label' => $this->paymentMethodLabel($method),
            ])->all(),
        ];
    }

    protected function transformPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Order $order) => $this->transformTransaction($order)),
        );

        return $paginator;
    }

    protected function transformTransaction(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'ordered_at' => $order->created_at?->toIso8601String(),
            'outlet' => $order->outlet ? [
                'id' => $order->outlet->id,
                'name' => $order->outlet->name,
            ] : null,
            'cashier' => $order->cashier ? [
                'id' => $order->cashier->id,
                'name' => $order->cashier->name,
            ] : null,
            'source' => $order->source,
            'type' => $order->type,
            'payment_method' => data_get($order->metadata, 'payment.method'),
            'items_sold' => (int) round((float) ($order->total_items_quantity ?? 0)),
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'total_amount' => (float) $order->total_amount,
        ];
    }

    protected function resolveScopeOutletId(User $actor, ?string $requestedOutletId): ?string
    {
        if ($actor->role?->type === 'owner') {
            return $requestedOutletId ?: null;
        }

        if (!$actor->outlet_id) {
            abort(403, 'User belum terhubung ke outlet aktif.');
        }

        return $actor->outlet_id;
    }

    protected function assertCanRead(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor'], true)) {
            abort(403, 'Laporan penjualan hanya tersedia untuk owner dan supervisor.');
        }
    }

    protected function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Cash',
            'qris' => 'QRIS',
            'debit' => 'Debit',
            'ewallet' => 'E-Wallet',
            'kasbon' => 'Kasbon',
            default => $method,
        };
    }

    protected function sourceLabel(string $source): string
    {
        return match ($source) {
            'kasir' => 'Kasir',
            'qr_meja' => 'QR Meja',
            'gofood' => 'GoFood',
            'grabfood' => 'GrabFood',
            'shopeefood' => 'ShopeeFood',
            'maximfood' => 'MaximFood',
            default => $source,
        };
    }
}

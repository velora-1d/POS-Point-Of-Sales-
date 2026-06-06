<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Expense;
use App\Models\User;
use App\Repositories\AttendanceShiftReportRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\SalesReportRepository;
use App\Repositories\TopProductReportRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ReportExportService
{
    protected const SALES_SOURCES = ['kasir', 'qr_meja', 'gofood', 'grabfood', 'shopeefood', 'maximfood'];

    protected const SALES_PAYMENT_METHODS = ['cash', 'qris', 'debit', 'ewallet', 'kasbon'];

    protected const INVENTORY_TYPES = ['all', 'product', 'raw_material'];

    protected const INVENTORY_STATUSES = ['all', 'healthy', 'low', 'out', 'inactive'];

    public function __construct(
        protected SalesReportService $salesReportService,
        protected OutletReportService $outletReportService,
        protected CashierReportService $cashierReportService,
        protected TopProductReportService $topProductReportService,
        protected InventoryReportService $inventoryReportService,
        protected AttendanceShiftReportService $attendanceShiftReportService,
        protected ExpenseService $expenseService,
        protected SalesReportRepository $salesReportRepository,
        protected ExpenseRepository $expenseRepository,
        protected AttendanceShiftReportRepository $attendanceShiftReportRepository,
        protected TopProductReportRepository $topProductReportRepository,
        protected SimplePdfExportService $simplePdfExportService,
    ) {
    }

    public function getPageData(User $actor): array
    {
        $this->assertCanAccess($actor);

        $scopeOutletId = $actor->role?->type === 'owner' ? null : $actor->outlet_id;
        $today = CarbonImmutable::today();

        return [
            'defaults' => [
                'report_type' => 'sales',
                'format' => 'pdf',
                'start_date' => $today->startOfMonth()->toDateString(),
                'end_date' => $today->toDateString(),
                'outlet_id' => $scopeOutletId ?? '',
                'user_id' => '',
                'category_id' => '',
                'category' => '',
                'source' => '',
                'payment_method' => '',
                'type' => 'all',
                'status' => 'all',
                'search' => '',
            ],
            'referenceData' => [
                'outlets' => $this->expenseRepository->getOutlets($scopeOutletId)->values()->all(),
                'employees' => $this->attendanceShiftReportRepository->getEmployees($scopeOutletId)->values()->all(),
                'productCategories' => $this->topProductReportRepository->getCategories($scopeOutletId)
                    ->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ])->values()->all(),
                'expenseCategories' => $this->expenseRepository->getCategories($scopeOutletId)->values()->all(),
                'sources' => collect(self::SALES_SOURCES)->map(fn (string $source) => [
                    'value' => $source,
                    'label' => $this->titleCase($source),
                ])->all(),
                'paymentMethods' => collect(self::SALES_PAYMENT_METHODS)->map(fn (string $method) => [
                    'value' => $method,
                    'label' => $this->paymentMethodLabel($method),
                ])->all(),
                'inventoryTypes' => collect(self::INVENTORY_TYPES)->map(fn (string $type) => [
                    'value' => $type,
                    'label' => $type === 'all' ? 'Semua Tipe' : $this->titleCase(str_replace('_', ' ', $type)),
                ])->all(),
                'inventoryStatuses' => collect(self::INVENTORY_STATUSES)->map(fn (string $status) => [
                    'value' => $status,
                    'label' => $status === 'all' ? 'Semua Status' : $this->titleCase($status),
                ])->all(),
            ],
            'reportTypes' => $this->availableReportTypes($actor),
            'formats' => [
                ['value' => 'pdf', 'label' => 'PDF'],
                ['value' => 'excel', 'label' => 'Excel (.csv)'],
            ],
            'notes' => [
                'excel' => 'Format spreadsheet saat ini diekspor sebagai CSV UTF-8 yang kompatibel dibuka di Microsoft Excel, LibreOffice, dan Google Sheets.',
                'pdf' => 'PDF saat ini dihasilkan native dari backend untuk ringkasan dan tabel laporan aktif tanpa dependency tambahan.',
            ],
        ];
    }

    public function download(User $actor, array $payload): Response
    {
        $this->assertCanAccess($actor);
        $document = $this->buildDocument($actor, $payload);
        $filenameBase = sprintf(
            '%s-%s-%s',
            $payload['report_type'],
            $document['meta']['Periode Mulai'] ?? CarbonImmutable::today()->toDateString(),
            $document['meta']['Periode Akhir'] ?? CarbonImmutable::today()->toDateString(),
        );

        if ($payload['format'] === 'excel') {
            return response()->streamDownload(
                function () use ($document) {
                    $stream = fopen('php://output', 'w');
                    fwrite($stream, "\xEF\xBB\xBF");
                    fputcsv($stream, [$document['title']]);

                    foreach ($document['meta'] as $label => $value) {
                        fputcsv($stream, [$label, $value]);
                    }

                    foreach ($document['sections'] as $section) {
                        fputcsv($stream, []);
                        fputcsv($stream, [$section['title']]);
                        fputcsv($stream, $section['columns']);

                        foreach ($section['rows'] as $row) {
                            fputcsv($stream, $row);
                        }
                    }

                    fclose($stream);
                },
                $filenameBase.'.csv',
                ['Content-Type' => 'text/csv; charset=UTF-8'],
            );
        }

        return response(
            $this->simplePdfExportService->render($document),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.pdf"',
            ],
        );
    }

    protected function buildDocument(User $actor, array $payload): array
    {
        return match ($payload['report_type']) {
            'sales' => $this->buildSalesDocument($actor, $payload),
            'outlets' => $this->buildOutletsDocument($actor, $payload),
            'cashiers' => $this->buildCashiersDocument($actor, $payload),
            'top_products' => $this->buildTopProductsDocument($actor, $payload),
            'inventory' => $this->buildInventoryDocument($actor, $payload),
            'attendance_shifts' => $this->buildAttendanceShiftDocument($actor, $payload),
            'expenses' => $this->buildExpensesDocument($actor, $payload),
            default => abort(404),
        };
    }

    protected function buildSalesDocument(User $actor, array $payload): array
    {
        $filters = [
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
            'outlet_id' => $payload['outlet_id'] ?? null,
            'source' => $payload['source'] ?? null,
            'payment_method' => $payload['payment_method'] ?? null,
            'search' => $payload['search'] ?? null,
        ];
        $report = $this->salesReportService->getReport($actor, $filters);
        $transactions = $this->salesReportRepository->getSettledOrdersSnapshot($report['filters'], $report['scope']['outlet_id']);

        return [
            'title' => 'Export Laporan Penjualan',
            'meta' => $this->buildMeta([
                'Periode Mulai' => $report['filters']['start_date'],
                'Periode Akhir' => $report['filters']['end_date'],
                'Outlet' => $this->resolveOutletLabel($report['referenceData']['outlets'], $report['filters']['outlet_id'] ?? ''),
                'Source' => $report['filters']['source'] ?: 'Semua source',
                'Metode Bayar' => $report['filters']['payment_method'] ?: 'Semua metode',
            ]),
            'sections' => [
                $this->summarySection([
                    'Total Revenue' => $this->currency($report['summary']['total_revenue']),
                    'Total Order' => (string) $report['summary']['total_orders'],
                    'Total Diskon' => $this->currency($report['summary']['total_discount']),
                    'Total Item' => (string) $report['summary']['total_items_sold'],
                    'Average Ticket' => $this->currency($report['summary']['average_ticket']),
                ]),
                [
                    'title' => 'Tren Harian',
                    'columns' => ['Tanggal', 'Order', 'Revenue'],
                    'rows' => collect($report['trend'])->map(fn (array $row) => [
                        $row['date'],
                        (string) $row['orders'],
                        $this->currency($row['revenue']),
                    ])->all(),
                ],
                [
                    'title' => 'Breakdown Metode Bayar',
                    'columns' => ['Metode', 'Order', 'Nominal'],
                    'rows' => collect($report['breakdowns']['payments'])->map(fn (array $row) => [
                        $this->paymentMethodLabel($row['method']),
                        (string) $row['orders'],
                        $this->currency($row['amount']),
                    ])->all(),
                ],
                [
                    'title' => 'Daftar Transaksi',
                    'columns' => ['Tanggal', 'Order', 'Outlet', 'Kasir', 'Source', 'Metode', 'Total'],
                    'rows' => $transactions->map(fn (Order $order) => [
                        optional($order->created_at)->toDateString() ?? '-',
                        $order->order_number,
                        $order->outlet?->name ?? '-',
                        $order->cashier?->name ?? '-',
                        $this->titleCase($order->source),
                        $this->paymentMethodLabel((string) data_get($order->metadata, 'payment.method', '-')),
                        $this->currency((float) $order->total_amount),
                    ])->all(),
                ],
            ],
        ];
    }

    protected function buildOutletsDocument(User $actor, array $payload): array
    {
        $report = $this->outletReportService->getReport($actor, [
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
        ]);

        return [
            'title' => 'Export Laporan Per Outlet',
            'meta' => $this->buildMeta([
                'Periode Mulai' => $report['filters']['start_date'],
                'Periode Akhir' => $report['filters']['end_date'],
            ]),
            'sections' => [
                $this->summarySection([
                    'Total Outlet' => (string) $report['summary']['total_outlets'],
                    'Outlet Aktif' => (string) $report['summary']['active_outlets'],
                    'Total Revenue' => $this->currency($report['summary']['total_revenue']),
                    'Total Order' => (string) $report['summary']['total_orders'],
                    'Average Ticket' => $this->currency($report['summary']['average_ticket']),
                ]),
                [
                    'title' => 'Performa Outlet',
                    'columns' => ['Outlet', 'Revenue', 'Order', 'Avg Ticket', 'Diskon', 'Growth Revenue'],
                    'rows' => collect($report['outlets'])->map(fn (array $row) => [
                        $row['name'],
                        $this->currency($row['current']['revenue']),
                        (string) $row['current']['orders'],
                        $this->currency($row['current']['average_ticket']),
                        $this->currency($row['current']['discount']),
                        $this->percentage($row['growth']['revenue_percentage']),
                    ])->all(),
                ],
            ],
        ];
    }

    protected function buildCashiersDocument(User $actor, array $payload): array
    {
        $report = $this->cashierReportService->getReport($actor, [
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
            'outlet_id' => $payload['outlet_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
        ]);

        return [
            'title' => 'Export Laporan Per Kasir',
            'meta' => $this->buildMeta([
                'Periode Mulai' => $report['filters']['start_date'],
                'Periode Akhir' => $report['filters']['end_date'],
                'Outlet' => $this->resolveOutletLabel($report['referenceData']['outlets'], $report['filters']['outlet_id'] ?? ''),
                'Kasir' => $this->resolveEmployeeLabel($report['referenceData']['cashiers'], $report['filters']['user_id'] ?? ''),
            ]),
            'sections' => [
                $this->summarySection([
                    'Kasir Aktif' => (string) $report['summary']['active_cashiers'],
                    'Total Revenue' => $this->currency($report['summary']['total_revenue']),
                    'Total Order' => (string) $report['summary']['total_orders'],
                    'Average Ticket' => $this->currency($report['summary']['average_ticket']),
                ]),
                [
                    'title' => 'Performa Kasir',
                    'columns' => ['Kasir', 'Outlet', 'Revenue', 'Order', 'Avg Ticket', 'Growth Revenue'],
                    'rows' => collect($report['cashiers'])->map(fn (array $row) => [
                        $row['name'],
                        $row['outlet']['name'] ?? '-',
                        $this->currency($row['current']['revenue']),
                        (string) $row['current']['orders'],
                        $this->currency($row['current']['average_ticket']),
                        $this->percentage($row['growth']['revenue_percentage']),
                    ])->all(),
                ],
            ],
        ];
    }

    protected function buildTopProductsDocument(User $actor, array $payload): array
    {
        $report = $this->topProductReportService->getReport($actor, [
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
            'outlet_id' => $payload['outlet_id'] ?? null,
            'category_id' => $payload['category_id'] ?? null,
        ]);

        return [
            'title' => 'Export Laporan Produk Terlaris',
            'meta' => $this->buildMeta([
                'Periode Mulai' => $report['filters']['start_date'],
                'Periode Akhir' => $report['filters']['end_date'],
                'Outlet' => $this->resolveOutletLabel($report['referenceData']['outlets'], $report['filters']['outlet_id'] ?? ''),
                'Kategori' => $this->resolveCategoryLabel($report['referenceData']['categories'], $report['filters']['category_id'] ?? ''),
            ]),
            'sections' => [
                $this->summarySection([
                    'Produk Aktif' => (string) $report['summary']['active_products'],
                    'Total Qty' => (string) $report['summary']['total_quantity'],
                    'Total Revenue' => $this->currency($report['summary']['total_revenue']),
                    'Avg Revenue / Produk' => $this->currency($report['summary']['average_revenue_per_product']),
                ]),
                [
                    'title' => 'Ranking Produk',
                    'columns' => ['Produk', 'Outlet', 'Kategori', 'Qty', 'Order', 'Revenue', 'Growth Qty'],
                    'rows' => collect($report['products'])->map(fn (array $row) => [
                        $row['name'],
                        $row['outlet']['name'] ?? '-',
                        $row['category']['name'] ?? '-',
                        (string) $row['current']['quantity'],
                        (string) $row['current']['orders'],
                        $this->currency($row['current']['revenue']),
                        $this->percentage($row['growth']['quantity_percentage']),
                    ])->all(),
                ],
            ],
        ];
    }

    protected function buildInventoryDocument(User $actor, array $payload): array
    {
        $report = $this->inventoryReportService->getReport($actor, [
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
            'outlet_id' => $payload['outlet_id'] ?? null,
            'type' => $payload['type'] ?? 'all',
            'status' => $payload['status'] ?? 'all',
        ]);

        return [
            'title' => 'Export Laporan Stok & Inventori',
            'meta' => $this->buildMeta([
                'Periode Mulai' => $report['filters']['start_date'],
                'Periode Akhir' => $report['filters']['end_date'],
                'Outlet' => $this->resolveOutletLabel($report['referenceData']['outlets'], $report['filters']['outlet_id'] ?? ''),
                'Tipe' => $report['filters']['type'],
                'Status' => $report['filters']['status'],
            ]),
            'sections' => [
                $this->summarySection([
                    'Tracked Products' => (string) $report['summary']['tracked_products'],
                    'Raw Materials Aktif' => (string) $report['summary']['active_raw_materials'],
                    'Low / Out Items' => (string) $report['summary']['low_or_out_items'],
                    'Nilai Stok' => $this->currency($report['summary']['estimated_stock_value']),
                    'Kerugian Expired' => $this->currency($report['summary']['expired_loss_estimate']),
                ]),
                [
                    'title' => 'Snapshot Inventori',
                    'columns' => ['Nama', 'Tipe', 'Outlet', 'Konteks', 'Stok', 'Min', 'Status', 'Nilai'],
                    'rows' => collect($report['inventory'])->map(fn (array $row) => [
                        $row['name'],
                        $this->titleCase(str_replace('_', ' ', $row['type'])),
                        $row['outlet']['name'] ?? '-',
                        $row['context'] ?? '-',
                        sprintf('%s %s', $row['current_stock'], $row['unit']),
                        (string) $row['minimum_stock'],
                        $this->titleCase($row['status']),
                        $this->currency($row['stock_value']),
                    ])->all(),
                ],
                [
                    'title' => 'Batch Expired',
                    'columns' => ['Nama', 'Tipe', 'Outlet', 'Qty', 'Batch', 'Expired', 'Estimasi Rugi'],
                    'rows' => collect($report['expiries'])->map(fn (array $row) => [
                        $row['name'],
                        $this->titleCase($row['type']),
                        $row['outlet']['name'] ?? '-',
                        (string) $row['quantity'],
                        $row['batch_code'] ?? '-',
                        $row['expired_at'] ?? '-',
                        $this->currency($row['estimated_loss']),
                    ])->all(),
                ],
            ],
        ];
    }

    protected function buildAttendanceShiftDocument(User $actor, array $payload): array
    {
        $report = $this->attendanceShiftReportService->getReport($actor, [
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
            'outlet_id' => $payload['outlet_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
        ]);

        return [
            'title' => 'Export Laporan Absensi & Shift',
            'meta' => $this->buildMeta([
                'Periode Mulai' => $report['filters']['start_date'],
                'Periode Akhir' => $report['filters']['end_date'],
                'Outlet' => $this->resolveOutletLabel($report['referenceData']['outlets'], $report['filters']['outlet_id'] ?? ''),
                'Karyawan' => $this->resolveEmployeeLabel($report['referenceData']['employees'], $report['filters']['user_id'] ?? ''),
            ]),
            'sections' => [
                $this->summarySection([
                    'Karyawan Terpantau' => (string) $report['summary']['employees_monitored'],
                    'Total Jadwal' => (string) $report['summary']['total_schedules'],
                    'Total Kehadiran' => (string) $report['summary']['total_attendances'],
                    'Attendance Rate' => $this->percentage($report['summary']['attendance_rate']),
                    'Shift Closed' => (string) $report['summary']['closed_shifts'],
                    'Revenue Shift' => $this->currency($report['summary']['total_shift_revenue']),
                ]),
                [
                    'title' => 'Rekap Karyawan',
                    'columns' => ['Karyawan', 'Outlet', 'Rate', 'Telat', 'Missing', 'Shift', 'Revenue Shift'],
                    'rows' => collect($report['employees'])->map(fn (array $row) => [
                        $row['name'],
                        $row['outlet']['name'] ?? '-',
                        $this->percentage($row['attendance']['attendance_rate']),
                        (string) $row['attendance']['late_days'],
                        (string) $row['attendance']['missing_days'],
                        (string) $row['shift']['closed_shifts'],
                        $this->currency($row['shift']['total_revenue']),
                    ])->all(),
                ],
                [
                    'title' => 'Missing Attendance',
                    'columns' => ['Tanggal', 'Karyawan', 'Outlet', 'Shift', 'Jam'],
                    'rows' => collect($report['missingAttendances'])->map(fn (array $row) => [
                        $row['date'] ?? '-',
                        $row['employee_name'],
                        $row['outlet_name'],
                        $row['shift_template_name'],
                        trim(($row['start_time'] ?? '--:--').' - '.($row['end_time'] ?? '--:--')),
                    ])->all(),
                ],
                [
                    'title' => 'Anomali Shift',
                    'columns' => ['Tanggal', 'Kasir', 'Outlet', 'Shift', 'Revenue', 'Selisih'],
                    'rows' => collect($report['shiftAnomalies'])->map(fn (array $row) => [
                        $row['opened_at'] ? CarbonImmutable::parse($row['opened_at'])->toDateString() : '-',
                        $row['employee_name'],
                        $row['outlet_name'],
                        $row['shift_template_name'],
                        $this->currency($row['total_revenue']),
                        $this->currency($row['cash_difference']),
                    ])->all(),
                ],
            ],
        ];
    }

    protected function buildExpensesDocument(User $actor, array $payload): array
    {
        $filters = [
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
            'outlet_id' => $payload['outlet_id'] ?? null,
            'category' => $payload['category'] ?? null,
            'search' => $payload['search'] ?? null,
        ];
        $report = $this->expenseService->getReport($actor, $filters);
        $expenses = $this->expenseRepository->getForPeriod(
            CarbonImmutable::parse($report['filters']['start_date']),
            CarbonImmutable::parse($report['filters']['end_date']),
            $report['filters']['outlet_id'] ?: null,
            $report['filters']['category'] ?: null,
        )->filter(function (Expense $expense) use ($report) {
            $search = trim((string) ($report['filters']['search'] ?? ''));

            if ($search === '') {
                return true;
            }

            $haystack = implode(' ', [
                $expense->category,
                $expense->description,
                $expense->notes,
            ]);

            return str_contains(strtolower($haystack), strtolower($search));
        })->values();

        return [
            'title' => 'Export Pengeluaran Operasional',
            'meta' => $this->buildMeta([
                'Periode Mulai' => $report['filters']['start_date'],
                'Periode Akhir' => $report['filters']['end_date'],
                'Outlet' => $this->resolveOutletLabel($report['referenceData']['outlets'], $report['filters']['outlet_id'] ?? ''),
                'Kategori' => $report['filters']['category'] ?: 'Semua kategori',
                'Pencarian' => $report['filters']['search'] ?: 'Tanpa pencarian',
            ]),
            'sections' => [
                $this->summarySection([
                    'Total Pengeluaran' => $this->currency($report['summary']['total_expenses']),
                    'Pengeluaran Sebelumnya' => $this->currency($report['summary']['previous_total_expenses']),
                    'Growth' => $this->percentage($report['summary']['growth_percentage']),
                    'Rata-rata Harian' => $this->currency($report['summary']['average_daily_expense']),
                    'Jumlah Entri' => (string) $report['summary']['entries_count'],
                ]),
                [
                    'title' => 'Breakdown Kategori',
                    'columns' => ['Kategori', 'Nominal', 'Jumlah Entri', 'Sebelumnya', 'Growth'],
                    'rows' => collect($report['categoryBreakdown'])->map(fn (array $row) => [
                        $this->titleCase($row['category']),
                        $this->currency($row['current_amount']),
                        (string) $row['current_count'],
                        $this->currency($row['previous_amount']),
                        $this->percentage($row['growth_percentage']),
                    ])->all(),
                ],
                [
                    'title' => 'Histori Pengeluaran',
                    'columns' => ['Tanggal', 'Kategori', 'Deskripsi', 'Outlet', 'Nominal', 'Dicatat Oleh'],
                    'rows' => $expenses->map(fn (Expense $expense) => [
                        $expense->expense_date?->toDateString() ?? '-',
                        $this->titleCase($expense->category),
                        $expense->description,
                        $expense->outlet?->name ?? '-',
                        $this->currency((float) $expense->amount),
                        $expense->creator?->name ?? '-',
                    ])->all(),
                ],
            ],
        ];
    }

    protected function availableReportTypes(User $actor): array
    {
        $all = [
            [
                'value' => 'sales',
                'label' => 'Laporan Penjualan',
                'filters' => ['outlet_id', 'source', 'payment_method', 'search'],
            ],
            [
                'value' => 'outlets',
                'label' => 'Laporan Per Outlet',
                'filters' => [],
                'owner_only' => true,
            ],
            [
                'value' => 'cashiers',
                'label' => 'Laporan Per Kasir',
                'filters' => ['outlet_id', 'user_id'],
            ],
            [
                'value' => 'top_products',
                'label' => 'Laporan Produk Terlaris',
                'filters' => ['outlet_id', 'category_id'],
            ],
            [
                'value' => 'inventory',
                'label' => 'Laporan Stok & Inventori',
                'filters' => ['outlet_id', 'type', 'status'],
            ],
            [
                'value' => 'attendance_shifts',
                'label' => 'Laporan Absensi & Shift',
                'filters' => ['outlet_id', 'user_id'],
            ],
            [
                'value' => 'expenses',
                'label' => 'Pengeluaran Operasional',
                'filters' => ['outlet_id', 'category', 'search'],
            ],
        ];

        return collect($all)
            ->filter(fn (array $report) => !($report['owner_only'] ?? false) || $actor->role?->type === 'owner')
            ->values()
            ->all();
    }

    protected function summarySection(array $summary): array
    {
        return [
            'title' => 'Ringkasan',
            'columns' => ['Metrik', 'Nilai'],
            'rows' => collect($summary)->map(fn (string $value, string $label) => [$label, $value])->values()->all(),
        ];
    }

    protected function buildMeta(array $meta): array
    {
        return array_filter($meta, fn ($value) => $value !== null && $value !== '');
    }

    protected function resolveOutletLabel(iterable $outlets, string $selectedOutletId): string
    {
        if ($selectedOutletId === '') {
            return 'Semua outlet';
        }

        $match = collect($outlets)->firstWhere('id', $selectedOutletId);

        return $this->extractLabel($match, 'name', 'Outlet terpilih');
    }

    protected function resolveEmployeeLabel(iterable $employees, string $selectedUserId): string
    {
        if ($selectedUserId === '') {
            return 'Semua karyawan';
        }

        $match = collect($employees)->firstWhere('id', $selectedUserId);

        return $this->extractLabel($match, 'name', 'Karyawan terpilih');
    }

    protected function resolveCategoryLabel(iterable $categories, string $selectedCategoryId): string
    {
        if ($selectedCategoryId === '') {
            return 'Semua kategori';
        }

        $match = collect($categories)->firstWhere('id', $selectedCategoryId);

        return $this->extractLabel($match, 'name', 'Kategori terpilih');
    }

    protected function currency(float|int $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    protected function percentage(?float $value): string
    {
        if ($value === null) {
            return 'N/A';
        }

        return number_format($value, 1, ',', '.').'%';
    }

    protected function titleCase(string $value): string
    {
        return collect(explode(' ', str_replace('_', ' ', $value)))
            ->map(fn (string $part) => ucfirst($part))
            ->implode(' ');
    }

    protected function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'qris' => 'QRIS',
            'ewallet' => 'E-Wallet',
            'cash' => 'Cash',
            'debit' => 'Debit',
            'kasbon' => 'Kasbon',
            default => $this->titleCase($method),
        };
    }

    protected function extractLabel(mixed $item, string $key, string $fallback): string
    {
        if (is_array($item)) {
            return (string) ($item[$key] ?? $fallback);
        }

        if (is_object($item) && isset($item->{$key})) {
            return (string) $item->{$key};
        }

        return $fallback;
    }

    protected function assertCanAccess(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor'], true)) {
            abort(403, 'Menu export laporan hanya tersedia untuk owner dan supervisor.');
        }
    }
}

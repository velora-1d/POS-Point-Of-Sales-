<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\PrinterConfig;
use App\Models\User;
use App\Repositories\PrinterConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrinterConfigService
{
    protected const PRINTER_TYPES = [
        [
            'value' => 'thermal',
            'label' => 'Thermal',
            'description' => 'Cocok untuk struk kasir harian dengan cetak cepat.',
        ],
        [
            'value' => 'dot_matrix',
            'label' => 'Dot Matrix',
            'description' => 'Cocok untuk kebutuhan invoice rangkap atau kertas continuous.',
        ],
    ];

    protected const CONNECTION_TYPES = [
        [
            'value' => 'usb',
            'label' => 'USB / Local Device',
            'description' => 'Aplikasi desktop mengarah ke printer lokal yang terpasang di device kasir.',
        ],
        [
            'value' => 'network',
            'label' => 'Network / LAN',
            'description' => 'Printer diakses via IP address dan port pada jaringan outlet.',
        ],
    ];

    protected const RECEIPT_METHODS = [
        [
            'value' => 'print',
            'label' => 'Print langsung',
            'description' => 'Setelah checkout, kasir diarahkan untuk mencetak struk fisik.',
        ],
        [
            'value' => 'whatsapp',
            'label' => 'Kirim WhatsApp',
            'description' => 'Default struk diarahkan ke WhatsApp jika nomor customer tersedia.',
        ],
        [
            'value' => 'skip',
            'label' => 'Lewati struk',
            'description' => 'Checkout selesai tanpa prompt cetak secara default.',
        ],
    ];

    public function __construct(
        protected PrinterConfigRepository $printerConfigRepository,
    ) {
    }

    public function getDashboard(User $actor, array $filters = []): array
    {
        $this->assertCanManage($actor);

        $scopedOutlets = $this->resolveScopedOutlets($actor);
        $selectedOutlet = $this->resolveSelectedOutlet($actor, $scopedOutlets, $filters['outlet_id'] ?? null);
        $selectedOutletId = $selectedOutlet['id'] ?? null;
        $storedConfig = $selectedOutletId
            ? $this->printerConfigRepository->findByOutletId($selectedOutletId)
            : null;

        return [
            'outlets' => $scopedOutlets->map(function (Outlet $outlet) {
                return [
                    'id' => $outlet->id,
                    'name' => $outlet->name,
                    'is_active' => (bool) $outlet->is_active,
                    'has_config' => (bool) $outlet->printerConfig,
                    'receipt_method' => $this->resolveOutletReceiptMethod($outlet, $outlet->printerConfig),
                ];
            })->values()->all(),
            'selectedOutlet' => $selectedOutlet,
            'summary' => $this->buildSummary($scopedOutlets),
            'formDefaults' => $this->buildFormDefaults($selectedOutletId, $selectedOutlet, $storedConfig),
            'printerOptions' => [
                'types' => self::PRINTER_TYPES,
                'connections' => self::CONNECTION_TYPES,
                'receiptMethods' => self::RECEIPT_METHODS,
            ],
            'preview' => $this->buildPreviewPayload($selectedOutlet, $storedConfig),
            'filters' => [
                'outlet_id' => $selectedOutletId,
            ],
            'access' => [
                'canSelectOutlet' => $actor->role?->type === 'owner',
                'role' => $actor->role?->type,
            ],
        ];
    }

    public function saveConfig(array $payload, User $actor): string
    {
        $this->assertCanManage($actor);

        $outlet = $this->resolveManagedOutlet($actor, (string) $payload['outlet_id']);
        $normalized = $this->normalizePayload($payload);

        DB::transaction(function () use ($outlet, $normalized) {
            $this->printerConfigRepository->upsertByOutlet($outlet->id, $normalized);

            $settings = is_array($outlet->settings) ? $outlet->settings : [];
            $settings['default_receipt_method'] = $normalized['default_receipt_method'];

            $this->printerConfigRepository->updateOutletSettings($outlet->id, $settings);
        });

        return $outlet->id;
    }

    public function renderTestPrintHtml(User $actor, array $filters = []): string
    {
        $this->assertCanManage($actor);

        $scopedOutlets = $this->resolveScopedOutlets($actor);
        $selectedOutlet = $this->resolveSelectedOutlet($actor, $scopedOutlets, $filters['outlet_id'] ?? null);
        $selectedOutletId = $selectedOutlet['id'] ?? null;
        $storedConfig = $selectedOutletId
            ? $this->printerConfigRepository->findByOutletId($selectedOutletId)
            : null;

        $printerType = $storedConfig?->printer_type ?? 'thermal';
        $connectionType = $storedConfig?->connection_type ?? 'usb';
        $deviceLabel = $connectionType === 'network'
            ? trim((string) (($storedConfig?->ip_address ?? '-') . ':' . ($storedConfig?->port ?? '-')))
            : ($storedConfig?->device_name ?: 'Belum diatur');
        $receiptMethod = $storedConfig?->default_receipt_method
            ?? (($selectedOutlet['settings']['default_receipt_method'] ?? 'print'));

        // Load visual metadata
        $metadata = $storedConfig?->metadata ?? [];
        $template = $metadata['receipt_template'] ?? 'classic';
        $font = $metadata['receipt_font'] ?? 'sans';
        $color = $metadata['receipt_color'] ?? 'mono';
        $footerText = htmlspecialchars((string) ($metadata['receipt_footer'] ?? 'Terima kasih atas kunjungan Anda!'), ENT_QUOTES, 'UTF-8');
        $logoBase64 = $metadata['receipt_logo'] ?? null;

        // Font Family mapping
        $fontFamily = '"Inter", "Segoe UI", sans-serif';
        if ($font === 'serif') {
            $fontFamily = 'Georgia, "Times New Roman", serif';
        } elseif ($font === 'mono' || $printerType === 'dot_matrix') {
            $fontFamily = '"Courier New", Courier, monospace';
        }

        // Color mapping
        $accentColor = '#111827';
        if ($color === 'orange') {
            $accentColor = '#f97316';
        } elseif ($color === 'rose') {
            $accentColor = '#f43f5e';
        } elseif ($color === 'emerald') {
            $accentColor = '#10b981';
        }

        // Paper width & styling based on template
        $paperWidth = $printerType === 'dot_matrix' ? '92mm' : '80mm';
        $sheetStyle = 'padding: 18px 16px; border: 1px dashed #94a3b8; border-radius: 0;';
        $lineStyle = 'border-top: 1px dashed #cbd5e1;';
        
        if ($template === 'modern') {
            $sheetStyle = 'padding: 24px 20px; border: 1px solid #e2e8f0; border-radius: 20px;';
            $lineStyle = 'border-top: 2px solid #f1f5f9;';
        } elseif ($template === 'compact') {
            $paperWidth = '58mm';
            $sheetStyle = 'padding: 12px 10px; border: 1px dotted #cbd5e1; border-radius: 0; font-size: 11px;';
            $lineStyle = 'border-top: 1px dotted #cbd5e1;';
        }

        $outletName = htmlspecialchars((string) ($selectedOutlet['name'] ?? 'Outlet'), ENT_QUOTES, 'UTF-8');
        $printerTypeLabel = htmlspecialchars($printerType === 'dot_matrix' ? 'Dot Matrix' : 'Thermal', ENT_QUOTES, 'UTF-8');
        $connectionLabel = htmlspecialchars($connectionType === 'network' ? 'Network' : 'USB', ENT_QUOTES, 'UTF-8');
        $deviceText = htmlspecialchars($deviceLabel, ENT_QUOTES, 'UTF-8');
        $receiptText = htmlspecialchars(strtoupper((string) $receiptMethod), ENT_QUOTES, 'UTF-8');
        $printedAt = now()->timezone('Asia/Jakarta')->format('d M Y H:i:s');

        $logoHtml = $logoBase64 
            ? '<img src="' . $logoBase64 . '" style="max-height: 48px; max-width: 100%; margin-bottom: 12px; display: block; margin-left: auto; margin-right: auto; filter: grayscale(100%);" />'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Print {$outletName}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef2ff;
            font-family: {$fontFamily};
            color: #111827;
            padding: 24px;
        }
        .sheet {
            width: min(100%, {$paperWidth});
            margin: 0 auto;
            background: white;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
            {$sheetStyle}
        }
        .center { text-align: center; }
        .muted { color: #64748b; font-size: 11px; }
        .line {
            {$lineStyle}
            margin: 12px 0;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            line-height: 1.5;
        }
        .row strong { font-size: 13px; color: {$accentColor}; }
        .meta {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px dashed #cbd5e1;
            font-size: 11px;
            color: #475569;
        }
        @media print {
            body { background: white; padding: 0; }
            .sheet { margin: 0 auto; box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="center">
            {$logoHtml}
            <strong style="font-size:16px; color: {$accentColor};">{$outletName}</strong>
            <div class="muted">Preview test print konfigurasi printer menu #57</div>
        </div>

        <div class="line"></div>

        <div class="row"><span>No. Order</span><strong>TRX-TEST-0057</strong></div>
        <div class="row"><span>Waktu</span><span>{$printedAt}</span></div>
        <div class="row"><span>Kasir</span><span>Simulator POS Mentai</span></div>
        <div class="row"><span>Metode Struk</span><span>{$receiptText}</span></div>

        <div class="line"></div>

        <div class="row"><span>Mentai Rice</span><span>1 x 28.000</span></div>
        <div class="row"><span>Chicken Katsu</span><span>1 x 24.000</span></div>
        <div class="row"><span>Es Teh</span><span>2 x 8.000</span></div>

        <div class="line"></div>

        <div class="row"><span>Subtotal</span><strong>68.000</strong></div>
        <div class="row"><span>Service</span><span>6.800</span></div>
        <div class="row"><span>Pajak</span><span>7.480</span></div>
        <div class="row"><strong>Total</strong><strong>82.280</strong></div>

        <div class="line"></div>
        <div class="center" style="font-size: 11px; color: #475569; font-style: italic;">
            {$footerText}
        </div>

        <div class="meta">
            <div>Tipe printer: {$printerTypeLabel}</div>
            <div>Koneksi: {$connectionLabel}</div>
            <div>Target device: {$deviceText}</div>
            <div>Preview ini memakai browser print dialog sebagai simulasi, bukan direct print device.</div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
HTML;
    }

    protected function buildSummary(Collection $outlets): array
    {
        return [
            'total_outlets' => $outlets->count(),
            'configured_outlets' => $outlets->filter(fn (Outlet $outlet) => $outlet->printerConfig !== null)->count(),
            'network_printers' => $outlets->filter(fn (Outlet $outlet) => $outlet->printerConfig?->connection_type === 'network')->count(),
            'whatsapp_defaults' => $outlets->filter(function (Outlet $outlet) {
                return $this->resolveOutletReceiptMethod($outlet, $outlet->printerConfig) === 'whatsapp';
            })->count(),
        ];
    }

    protected function buildFormDefaults(
        ?string $outletId,
        ?array $selectedOutlet,
        ?PrinterConfig $storedConfig,
    ): array {
        $defaultReceiptMethod = $storedConfig?->default_receipt_method
            ?? ($selectedOutlet['settings']['default_receipt_method'] ?? 'print');

        $defaultMetadata = [
            'receipt_template' => 'classic',
            'receipt_font' => 'sans',
            'receipt_color' => 'mono',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
            'receipt_logo' => null
        ];

        return [
            'outlet_id' => $outletId,
            'printer_type' => $storedConfig?->printer_type ?? 'thermal',
            'connection_type' => $storedConfig?->connection_type ?? 'usb',
            'device_name' => $storedConfig?->device_name ?? '',
            'ip_address' => $storedConfig?->ip_address ?? '',
            'port' => $storedConfig?->port,
            'default_receipt_method' => $defaultReceiptMethod,
            'has_config' => $storedConfig !== null,
            'metadata' => array_merge($defaultMetadata, $storedConfig?->metadata ?? []),
        ];
    }

    protected function buildPreviewPayload(?array $selectedOutlet, ?PrinterConfig $storedConfig): array
    {
        $printerType = $storedConfig?->printer_type ?? 'thermal';
        $connectionType = $storedConfig?->connection_type ?? 'usb';
        $deviceLabel = $connectionType === 'network'
            ? (($storedConfig?->ip_address ?? 'Belum diatur') . ($storedConfig?->port ? ':' . $storedConfig->port : ''))
            : ($storedConfig?->device_name ?? 'Belum diatur');

        $defaultMetadata = [
            'receipt_template' => 'classic',
            'receipt_font' => 'sans',
            'receipt_color' => 'mono',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
            'receipt_logo' => null
        ];

        return [
            'outlet_name' => $selectedOutlet['name'] ?? '-',
            'printer_type' => $printerType,
            'connection_type' => $connectionType,
            'device_label' => $deviceLabel,
            'receipt_method' => $storedConfig?->default_receipt_method
                ?? ($selectedOutlet['settings']['default_receipt_method'] ?? 'print'),
            'has_config' => $storedConfig !== null,
            'metadata' => array_merge($defaultMetadata, $storedConfig?->metadata ?? []),
        ];
    }

    protected function resolveScopedOutlets(User $actor): Collection
    {
        $outlets = $this->printerConfigRepository->getOutlets();

        if ($actor->role?->type === 'owner') {
            return $outlets;
        }

        return $outlets->filter(fn (Outlet $outlet) => $outlet->id === $actor->outlet_id)->values();
    }

    protected function resolveSelectedOutlet(User $actor, Collection $outlets, ?string $requestedOutletId): ?array
    {
        if ($outlets->isEmpty()) {
            return null;
        }

        $selectedOutlet = null;

        if ($actor->role?->type === 'owner' && $requestedOutletId) {
            $selectedOutlet = $outlets->firstWhere('id', $requestedOutletId);
        }

        if ($actor->role?->type === 'supervisor') {
            $selectedOutlet = $outlets->firstWhere('id', $actor->outlet_id);
        }

        $selectedOutlet ??= $outlets->first();

        if (!$selectedOutlet instanceof Outlet) {
            return null;
        }

        $settings = is_array($selectedOutlet->settings) ? $selectedOutlet->settings : [];

        return [
            'id' => $selectedOutlet->id,
            'name' => $selectedOutlet->name,
            'is_active' => (bool) $selectedOutlet->is_active,
            'settings' => [
                'default_receipt_method' => $this->sanitizeReceiptMethod($settings['default_receipt_method'] ?? 'print'),
            ],
        ];
    }

    protected function resolveManagedOutlet(User $actor, string $outletId): Outlet
    {
        $outlet = $this->resolveScopedOutlets($actor)->firstWhere('id', $outletId);

        if (!$outlet instanceof Outlet) {
            abort(403, 'Outlet printer tidak berada dalam scope akun ini.');
        }

        return $outlet;
    }

    protected function resolveOutletReceiptMethod(Outlet $outlet, ?PrinterConfig $storedConfig): string
    {
        if ($storedConfig?->default_receipt_method) {
            return $this->sanitizeReceiptMethod($storedConfig->default_receipt_method);
        }

        $settings = is_array($outlet->settings) ? $outlet->settings : [];

        return $this->sanitizeReceiptMethod($settings['default_receipt_method'] ?? 'print');
    }

    protected function normalizePayload(array $payload): array
    {
        $connectionType = (string) $payload['connection_type'];

        return [
            'printer_type' => (string) $payload['printer_type'],
            'connection_type' => $connectionType,
            'device_name' => $connectionType === 'usb'
                ? $this->nullableTrim($payload['device_name'] ?? null)
                : null,
            'ip_address' => $connectionType === 'network'
                ? $this->nullableTrim($payload['ip_address'] ?? null)
                : null,
            'port' => $connectionType === 'network'
                ? (int) ($payload['port'] ?? 9100)
                : null,
            'default_receipt_method' => $this->sanitizeReceiptMethod($payload['default_receipt_method'] ?? 'print'),
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : null,
        ];
    }

    protected function sanitizeReceiptMethod(string $value): string
    {
        return in_array($value, ['print', 'whatsapp', 'skip'], true) ? $value : 'print';
    }

    protected function nullableTrim(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : null;
    }

    protected function assertCanManage(User $actor): void
    {
        if (!in_array($actor->role?->type, ['owner', 'supervisor'], true)) {
            abort(403, 'Konfigurasi printer hanya tersedia untuk owner dan supervisor.');
        }

        if ($actor->role?->type === 'supervisor' && !$actor->outlet_id) {
            abort(403, 'Supervisor belum terhubung ke outlet.');
        }
    }
}

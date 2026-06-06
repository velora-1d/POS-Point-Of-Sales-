<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Table;
use App\Services\TableManagementService;
use App\Services\TableQrConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class TableController extends Controller
{
    public function __construct(
        protected TableQrConfigService $tableQrConfigService,
        protected TableManagementService $tableManagementService,
    ) {
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $outletId = $user->outlet_id ?? Outlet::first()?->id;

        $tables = Table::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($outletId) {
            $qrConfig = $this->tableQrConfigService->getConfigForOutlet($outletId);
        $tables->each(function (Table $table) use ($qrConfig): void {
                $table->setAttribute(
                    'public_qr_url',
                    $this->tableQrConfigService->buildPublicMenuUrlForTable($table, $qrConfig),
                );
                // Sertakan remaining_capacity untuk frontend
                $table->append('remaining_capacity');
            });
        }

        return Inertia::render('Settings/Tables', [
            'tables'  => $tables,
            'success' => session('success'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $outletId = $user->outlet_id ?? Outlet::first()?->id;

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'category' => 'required|string|in:indoor,outdoor',
        ]);

        Table::create([
            'id' => (string) Str::uuid(),
            'outlet_id' => $outletId,
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'category' => $validated['category'],
            'qr_code' => Str::slug($validated['name']) . '-' . strtolower(Str::random(4)),
            'qr_session_token' => (string) Str::ulid(),
            'status' => 'available',
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Meja berhasil ditambahkan.');
    }

    public function update(Request $request, Table $table): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'category' => 'required|string|in:indoor,outdoor',
        ]);

        $table->update($validated);

        return redirect()->back()->with('success', 'Data meja berhasil diperbarui.');
    }

    public function destroy(Table $table): RedirectResponse
    {
        // We do soft delete or just set is_active to false
        $table->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Meja berhasil dihapus.');
    }

    public function clearTable(Request $request, Table $table): RedirectResponse
    {
        $user = auth()->user();

        // Pastikan meja milik outlet yang sama
        if ($table->outlet_id !== $user->outlet_id) {
            abort(403, 'Akses ditolak.');
        }

        $this->tableManagementService->clearTable($table, $user);

        return redirect()->back()->with('success', "Meja {$table->name} berhasil dibersihkan.");
    }
}

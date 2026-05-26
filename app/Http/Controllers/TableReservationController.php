<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTableReservationRequest;
use App\Http\Requests\UpdateTableReservationStatusRequest;
use App\Models\TableReservation;
use App\Services\TableReservationService;
use Illuminate\Http\RedirectResponse;

class TableReservationController extends Controller
{
    public function __construct(
        protected TableReservationService $tableReservationService,
    ) {
    }

    public function store(StoreTableReservationRequest $request): RedirectResponse
    {
        $this->tableReservationService->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('tables.layout')
            ->with('success', 'Reservasi meja berhasil dibuat.');
    }

    public function updateStatus(
        UpdateTableReservationStatusRequest $request,
        TableReservation $tableReservation,
    ): RedirectResponse {
        $this->tableReservationService->updateStatus(
            $tableReservation,
            $request->validated(),
            $request->user(),
        );

        $message = $request->validated()['status'] === 'completed'
            ? 'Reservasi meja berhasil ditandai selesai.'
            : 'Reservasi meja berhasil dibatalkan.';

        return redirect()
            ->route('tables.layout')
            ->with('success', $message);
    }
}

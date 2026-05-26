<?php

namespace App\Http\Controllers;

use App\Services\InventoryAlertService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryAlertController extends Controller
{
    public function __construct(
        protected InventoryAlertService $inventoryAlertService,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $this->inventoryAlertService->getLowStockSnapshot(
            $request->user(),
            24,
        );

        return Inertia::render('Alerts/LowStock', [
            'summary' => $data['summary'],
            'items' => $data['items'],
            'productItems' => $data['productItems'],
            'rawMaterialItems' => $data['rawMaterialItems'],
        ]);
    }
}

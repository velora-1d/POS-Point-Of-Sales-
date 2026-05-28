<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseKasbonOrderRequest;
use App\Http\Requests\MarkOrderReceiptRequest;
use App\Http\Requests\StoreOrderInstallmentRequest;
use App\Http\Requests\StorePreOrderRequest;
use App\Http\Requests\TransactionIndexRequest;
use App\Models\Order;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
    ) {
    }

    public function index(TransactionIndexRequest $request): Response
    {
        $data = $this->transactionService->getDashboard(
            $request->user(),
            $request->validated(),
        );

        return Inertia::render('Transactions/Index', [
            'summary' => $data['summary'],
            'kasbonOrders' => $data['kasbonOrders'],
            'preOrders' => $data['preOrders'],
            'historyOrders' => $data['historyOrders'],
            'filters' => $data['filters'],
            'referenceData' => $data['referenceData'],
            'success' => session('success'),
        ]);
    }

    public function storePreOrder(StorePreOrderRequest $request): RedirectResponse
    {
        $this->transactionService->createPreOrder(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Pre-order dengan down payment berhasil disimpan.');
    }

    public function closeKasbon(CloseKasbonOrderRequest $request, Order $order): RedirectResponse
    {
        $this->transactionService->closeAsKasbon(
            $order,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('kasir.order')
            ->with('success', 'Order berhasil ditutup sebagai kasbon.');
    }

    public function storeInstallment(StoreOrderInstallmentRequest $request, Order $order): RedirectResponse
    {
        $this->transactionService->payInstallment(
            $order,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Pembayaran cicilan berhasil dicatat.');
    }

    public function activatePreOrder(Order $order): RedirectResponse
    {
        $this->transactionService->activatePreOrder(
            $order,
            auth()->user(),
        );

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Pre-order berhasil diaktifkan ke antrian dapur.');
    }

    public function receipt(Order $order): Response
    {
        $data = $this->transactionService->getReceiptData(
            $order,
            auth()->user(),
        );

        return Inertia::render('Transactions/Receipt', [
            'order' => $data['order'],
            'outlet' => $data['outlet'],
            'whatsappLink' => $data['whatsappLink'],
            'success' => session('success'),
        ]);
    }

    public function markReceipt(MarkOrderReceiptRequest $request, Order $order): RedirectResponse
    {
        $this->transactionService->markReceipt(
            $order,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('transactions.receipt.show', $order)
            ->with('success', 'Metode struk berhasil dicatat.');
    }
}

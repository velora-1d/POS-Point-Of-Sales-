<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\FirebasePushService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckStaleOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:check-stale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pengecekan pesanan yang tertunda lama (waiting bar approval atau belum bayar) untuk dikirim push notification ke kasir/bar.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);

        // 1. Cari pesanan yang menunggu approval bar lebih dari 30 menit
        $waitingBarCount = Order::where('status', 'waiting_bar_approval')
            ->where('updated_at', '<=', $thirtyMinutesAgo)
            ->count();

        // 2. Cari pesanan aktif yang belum lunas lebih dari 30 menit
        // Kita exclude status cancelled dan completed
        $staleUnpaidCount = Order::whereNotIn('status', ['cancelled', 'completed'])
            ->where('created_at', '<=', $thirtyMinutesAgo)
            ->get()
            ->filter(function ($order) {
                return !$order->isPaidInFull();
            })
            ->count();

        if ($waitingBarCount > 0 || $staleUnpaidCount > 0) {
            $title = "🚨 Reminder Pesanan Tertunda";
            
            $messages = [];
            if ($waitingBarCount > 0) {
                $messages[] = "{$waitingBarCount} pesanan menunggu approval bar.";
            }
            if ($staleUnpaidCount > 0) {
                $messages[] = "{$staleUnpaidCount} pesanan aktif belum lunas.";
            }

            $body = implode(" ", $messages) . " Mohon segera dicek dan ditindak lanjuti.";

            FirebasePushService::sendToActiveCashiers($title, $body, [
                'type' => 'stale_orders_reminder',
                'waiting_bar' => $waitingBarCount,
                'unpaid' => $staleUnpaidCount
            ]);

            $this->info("Reminder terkirim: " . $body);
        } else {
            $this->info("Tidak ada pesanan tertunda yang lama.");
        }
    }
}

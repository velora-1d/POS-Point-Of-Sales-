<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected const TOTAL_BRIEF_MENUS = 62;

    protected const READY_MENU_IDS = [
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        10,
        11,
        12,
        13,
        14,
        15,
        16,
        17,
        18,
        19,
        20,
        21,
        22,
        23,
        24,
        25,
        26,
        27,
        28,
        29,
        30,
        31,
        32,
        33,
        34,
        35,
        36,
        37,
        38,
        39,
        40,
        41,
        42,
        43,
        44,
        45,
        46,
        47,
        48,
        49,
        50,
        51,
        52,
        53,
        54,
        55,
        56,
        57,
        58,
        59,
        60,
        61,
        62,
    ];

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $paymentMethods = ['qris'];

        if ($user) {
            try {
                $service = app(\App\Services\PaymentGatewayConfigService::class);
                $config = $service->resolvePakasirConfig($user->outlet_id);
                $paymentMethods = $config['active_payment_methods'] ?? ['qris'];
            } catch (\Exception $e) {
                // Keep default 'qris' if config fails/missing
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? $user->role->name : null,
                    'outlet' => $user->outlet ? $user->outlet->name : null,
                    'outlet_id' => $user->outlet_id,
                ] : null,
                'payment_methods' => $paymentMethods,
            ],
            'menuProgress' => [
                'totalMenus' => self::TOTAL_BRIEF_MENUS,
                'readyMenuIds' => self::READY_MENU_IDS,
                'readyCount' => count(self::READY_MENU_IDS),
                'progressPercentage' => (int) round(
                    (count(self::READY_MENU_IDS) / self::TOTAL_BRIEF_MENUS) * 100
                ),
            ],
        ];
    }
}

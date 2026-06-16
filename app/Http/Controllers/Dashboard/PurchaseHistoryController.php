<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BillingPurchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PurchaseHistoryController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $purchases = BillingPurchase::query()
            ->with(['product', 'duaList'])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(20);

        return view('dashboard.purchases', [
            'user' => $user,
            'purchases' => $purchases,
        ]);
    }
}

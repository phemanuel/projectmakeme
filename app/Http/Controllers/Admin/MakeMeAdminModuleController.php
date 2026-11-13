<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MakeMeAdminModuleController extends Controller
{
    public function wallets(): View
    {
        return view('admin.makeme.modules.index', [
            'heading' => 'Wallets & Credit Allocation',
            'eyebrow' => 'FINANCIAL CONTROL',
            'description' => 'Allocate shopping credits, review balances and track every adjustment.',
            'featureList' => ['Individual and bulk credit allocation', 'Transaction history and reversals', 'Balance reconciliation and audit trail'],
        ]);
    }

    public function orders(): View
    {
        return view('admin.makeme.modules.index', [
            'heading' => 'Orders & Collections',
            'eyebrow' => 'FULFILMENT DESK',
            'description' => 'Monitor purchases and control collection during the live show.',
            'featureList' => ['Participant order tracking', 'Order status and cancellation', 'Secure collection verification'],
        ]);
    }

    public function reports(): View
    {
        return view('admin.makeme.modules.index', [
            'heading' => 'Reports & Analytics',
            'eyebrow' => 'SHOW INSIGHTS',
            'description' => 'Review marketplace performance and export accurate operational records.',
            'featureList' => ['Credit issuance and usage', 'Stock and category performance', 'Order and collection exports'],
        ]);
    }
}

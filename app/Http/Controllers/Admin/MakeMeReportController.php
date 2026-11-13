<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeOrder;
use App\Models\MakeMeOrderItem;
use App\Models\MakeMeParticipant;
use App\Models\MakeMeProduct;
use App\Models\MakeMeStockMovement;
use App\Models\MakeMeWallet;
use App\Models\MakeMeWalletTransaction;
use App\Services\MakeMeActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MakeMeReportController extends Controller
{
    private function authorizeAction(string $permission): void
    {
        $admin = auth('web')->user();
        abort_unless(
            $admin && (int) $admin->user_type === 1 &&
            (int) $admin->user_status === 1 &&
            $admin->applicant_type !== 'WAITLIST' &&
            ($admin->hasRole('Super Admin', 'web') || $admin->can($permission)),
            403
        );
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();

        abort_if($from->diffInDays($to) > 366, 422, 'Select a date range of at most one year.');
        return [$from, $to];
    }

    private function report(Carbon $from, Carbon $to): array
    {
        // Wallet and product reports work even before Orders migrations are applied.
        $hasOrders = Schema::hasTable('makeme_orders') && Schema::hasTable('makeme_order_items');
        $orderCounts = collect();
        $orderCredits = 0;
        $orderCount = 0;
        $unitsSold = 0;

        if ($hasOrders) {
            $orders = MakeMeOrder::query()->whereBetween('created_at', [$from, $to]);
            $validOrders = MakeMeOrder::query()->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['placed', 'fulfilled']);
            $orderCounts = (clone $orders)->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')->pluck('total', 'status');
            $orderCredits = (clone $validOrders)->sum('total_credits');
            $orderCount = (clone $orders)->count();
            $unitsSold = MakeMeOrderItem::query()
                ->join('makeme_orders', 'makeme_orders.id', '=', 'makeme_order_items.order_id')
                ->whereBetween('makeme_orders.created_at', [$from, $to])
                ->whereIn('makeme_orders.status', ['placed', 'fulfilled'])
                ->sum('makeme_order_items.quantity');
        }

        $movements = MakeMeWalletTransaction::query()->whereBetween('created_at', [$from, $to]);
        $credited = (clone $movements)->whereIn('type', ['credit', 'refund'])->sum('amount');
        $debited = (clone $movements)->whereIn('type', ['debit', 'purchase'])->sum('amount');
        $manualCredits = (clone $movements)->where('type', 'credit')->sum('amount');
        $manualDebits = (clone $movements)->where('type', 'debit')->sum('amount');
        $purchaseCredits = (clone $movements)->where('type', 'purchase')->sum('amount');
        $refundCredits = (clone $movements)->where('type', 'refund')->sum('amount');

        $topProducts = collect();
        $daily = collect();
        if ($hasOrders) {
            $topProducts = MakeMeOrderItem::query()
                ->join('makeme_orders', 'makeme_orders.id', '=', 'makeme_order_items.order_id')
                ->whereBetween('makeme_orders.created_at', [$from, $to])
                ->whereIn('makeme_orders.status', ['placed', 'fulfilled'])
                ->select('makeme_order_items.product_id', 'makeme_order_items.product_name', 'makeme_order_items.sku')
                ->selectRaw('SUM(makeme_order_items.quantity) as units')
                ->selectRaw('SUM(makeme_order_items.line_total) as credits')
                ->groupBy('makeme_order_items.product_id', 'makeme_order_items.product_name', 'makeme_order_items.sku')
                ->orderByDesc('units')->limit(10)->get();

            $daily = MakeMeOrder::query()->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['placed', 'fulfilled'])
                ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(total_credits) as credits')
                ->groupByRaw('DATE(created_at)')->orderBy('day')->get();
        }

        $lowStock = MakeMeProduct::query()->where('is_active', true)
            ->where('stock_quantity', '<=', 5)->orderBy('stock_quantity')->limit(15)->get();

        return [
            'from' => $from, 'to' => $to, 'orders_ready' => $hasOrders,
            'summary' => [
                'participants_total' => MakeMeParticipant::count(),
                'participants_active' => MakeMeParticipant::where('status', 'active')->count(),
                'wallets_count' => MakeMeWallet::count(),
                'available_credits' => MakeMeWallet::sum('balance'),
                'products_active' => MakeMeProduct::where('is_active', true)->count(),
                'stock_units' => MakeMeProduct::where('is_active', true)->sum('stock_quantity'),
                'orders_total' => $orderCount,
                'orders_placed' => (int) ($orderCounts['placed'] ?? 0),
                'orders_fulfilled' => (int) ($orderCounts['fulfilled'] ?? 0),
                'orders_cancelled' => (int) ($orderCounts['cancelled'] ?? 0),
                'order_credits' => (int) $orderCredits,
                'units_sold' => (int) $unitsSold,
                'credits_in' => (int) $credited,
                'credits_out' => (int) $debited,
                'manual_credits' => (int) $manualCredits,
                'manual_debits' => (int) $manualDebits,
                'purchase_credits' => (int) $purchaseCredits,
                'refund_credits' => (int) $refundCredits,
            ],
            'topProducts' => $topProducts,
            'daily' => $daily,
            'lowStock' => $lowStock,
        ];
    }

    public function index(Request $request)
    {
        $this->authorizeAction('makeme.reports.view');
        [$from, $to] = $this->filters($request);
        $report = $this->report($from, $to);
        $canExport = auth('web')->user()->hasRole('Super Admin', 'web') ||
            auth('web')->user()->can('makeme.reports.export');

        return view('admin.makeme.reports.index', compact('report', 'canExport'));
    }

    public function export(Request $request)
    {
        $this->authorizeAction('makeme.reports.export');
        $validated = $request->validate([
            'section' => ['required', Rule::in(['summary', 'products', 'daily', 'low-stock'])],
        ]);
        [$from, $to] = $this->filters($request);
        $report = $this->report($from, $to);
        $section = $validated['section'];

        if ($section === 'summary') {
            $rows = [['Metric', 'Value']];
            foreach ($report['summary'] as $key => $value) {
                $rows[] = [str_replace('_', ' ', ucfirst($key)), (string) $value];
            }
        } elseif ($section === 'products') {
            $rows = [['Product ID', 'Product', 'SKU', 'Units Sold', 'Credits']];
            foreach ($report['topProducts'] as $item) {
                $rows[] = [$item->product_id, $item->product_name, $item->sku, $item->units, $item->credits];
            }
        } elseif ($section === 'daily') {
            $rows = [['Date', 'Orders', 'Credits']];
            foreach ($report['daily'] as $item) {
                $rows[] = [$item->day, $item->orders, $item->credits];
            }
        } else {
            $rows = [['Product ID', 'Product', 'SKU', 'Stock', 'Active']];
            foreach ($report['lowStock'] as $item) {
                $rows[] = [$item->id, $item->name, $item->sku, $item->stock_quantity, $item->is_active ? 'Yes' : 'No'];
            }
        }

        // Log the generation request before streaming begins. Does not imply the download completed.
        MakeMeActivityLogger::record('reports', 'exported', 'Reports CSV export generated', null, [
            'section' => $section,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'row_count' => count($rows) - 1,
        ]);

        $filename = sprintf('makeme-%s-%s-to-%s.csv', $section, $from->toDateString(), $to->toDateString());
        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                // Prevent CSV formula execution in spreadsheet applications.
                $safe = array_map(function ($value) {
                    $value = (string) ($value ?? '');
                    return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'" . $value : $value;
                }, $row);
                fputcsv($output, $safe);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

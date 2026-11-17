
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>ProjectMakeMe - Orders Report</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f5f7;
            color: #222;
            margin: 0;
            padding: 24px;
            font-size: 12px;
        }

        .sheet {
            max-width: 1200px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            border-bottom: 3px solid #f4a116;
            padding-bottom: 18px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0 0 5px;
            font-size: 23px;
        }

        .subtitle {
            margin: 0;
            color: #666;
        }

        .report-meta {
            text-align: right;
            line-height: 1.8;
        }

        .summary {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .summary strong {
            font-size: 16px;
        }

        .filters {
            background: #fff8eb;
            border: 1px solid #f0dfbd;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 9px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f4a116;
            color: #171717;
            font-weight: 700;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .items {
            white-space: normal;
            line-height: 1.5;
        }

        .muted {
            display: block;
            color: #777;
            margin-top: 4px;
        }

        .print-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .print-actions button {
            border: 0;
            border-radius: 7px;
            padding: 11px 18px;
            font-weight: bold;
            cursor: pointer;
        }

        .print-btn {
            background: #f4a116;
        }

        .close-btn {
            background: #e8e8eb;
        }

        .report-footer {
            margin-top: 25px;
            color: #777;
            font-size: 10px;
        }

        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }

            .sheet {
                max-width: none;
                padding: 0;
                border-radius: 0;
            }

            .print-actions {
                display: none;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .filters, .report-header {
                break-inside: avoid;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

<div class="sheet">

    <div class="print-actions">
        <button class="close-btn"
                onclick="window.close()">
            Close
        </button>

        <button class="print-btn"
                onclick="window.print()">
            Print Report
        </button>
    </div>

    <div class="report-header">
        <div>
            <h1>ProjectMakeMe Live</h1>
            <p class="subtitle">
                Inventory &amp; Orders Report
            </p>
        </div>

        <div class="report-meta">
            <strong>
                Generated:
                {{ now('Africa/Lagos')->format('d M Y, h:i A') }}
            </strong>
            <div>Timezone: WAT</div>
        </div>
    </div>

    <div class="summary">
        <div>
            Total Orders:
            <strong>{{ number_format($orders->count()) }}</strong>
        </div>

        <div>
            Total Units:
            <strong>
                {{ number_format($orders->sum(
                    fn ($order) => $order->items->sum('quantity')
                )) }}
            </strong>
        </div>

        <div>
            Total Credits:
            <strong>
                {{ number_format($orders->sum('total_credits')) }}
            </strong>
        </div>
    </div>

    <div class="filters">
        <strong>Applied Filters:</strong>

        <div>
            Search:
            {{ $filters['q'] ?? 'All Kreativs / Orders' }}
        </div>

        <div>
            Status:
            {{ ucfirst($filters['status'] ?? 'all') }}
        </div>

        <div>
            Received From:
            {{ $filters['received_from'] ?? 'Any date/time' }}
        </div>

        <div>
            Received Through:
            {{ $filters['received_to'] ?? 'Any date/time' }}
        </div>

        <div>
            Delivery Due Date:
            {{ $filters['delivery_date'] ?? 'Any date' }}
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Order Ref.</th>
            <th>Kreativ</th>
            <th>Items Ordered</th>
            <th>Units</th>
            <th>Credits</th>
            <th>Status</th>
            <th>Received (WAT)</th>
            <th>Delivery Due</th>
        </tr>
        </thead>

        <tbody>
        @forelse($orders as $order)

            @php
                $participant = $order->participant;

                $name = $participant
                    ? trim(implode(' ', array_filter([
                        $participant->first_name,
                        $participant->other_name,
                        $participant->surname,
                    ])))
                    : 'Unavailable Kreativ';

                $receivedAt = $order->created_at
                    ? $order->created_at
                        ->copy()
                        ->timezone('Africa/Lagos')
                    : null;

                $deliveryDate = $order->created_at
                    ? \App\Support\MakeMeDeliverySchedule::deliveryDate(
                        $order->created_at
                    )
                    : null;
            @endphp

            <tr>
                <td>
                    <strong>{{ $order->reference }}</strong>
                </td>

                <td>
                    {{ $name }}
                    <span class="muted">
                        Kreativ #{{ $order->participant_id }}
                    </span>
                </td>

                <td class="items">
                    @foreach($order->items as $item)
                        {{ $item->product_name }}
                        × {{ $item->quantity }}

                        @unless($loop->last)
                            <hr style="border:0;border-top:1px solid #eee">
                        @endunless
                    @endforeach
                </td>

                <td>
                    {{ number_format($order->items->sum('quantity')) }}
                </td>

                <td>
                    {{ number_format($order->total_credits) }}
                </td>

                <td>
                    {{ ucfirst($order->status) }}
                </td>

                <td>
                    {{ $receivedAt?->format('d M Y, h:i A') }}
                </td>

                <td>
                    {{ $deliveryDate
                        ? \Carbon\Carbon::parse($deliveryDate)
                            ->format('d M Y') . ' Evening'
                        : '—'
                    }}
                </td>
            </tr>

        @empty
            <tr>
                <td colspan="8"
                    style="text-align:center;padding:25px">
                    No orders match the selected filters.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="report-footer">
        ProjectMakeMe Live · NYFEW

        <p>
            Scheduled delivery dates are calculated using
            the 10:00 AM WAT cutoff. Actual fulfillment
            status is shown separately.
        </p>
    </div>

</div>

</body>
</html>

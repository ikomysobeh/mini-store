<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrdersExport implements WithMultipleSheets
{
    protected $filters;
    protected $includeOrderItems;

    public function __construct($filters = [], $includeOrderItems = true)
    {
        $this->filters = $filters;
        $this->includeOrderItems = $includeOrderItems;
    }

    public function sheets(): array
    {
        $sheets = [
            new OrdersSummarySheet($this->filters),
        ];

        if ($this->includeOrderItems) {
            $sheets[] = new OrderItemsDetailSheet($this->filters);
        }

        return $sheets;
    }
}

// Orders Summary Sheet
class OrdersSummarySheet implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Order::with(['customer.user', 'items.product', 'items.variant.color', 'items.variant.size']);

        // Apply filters
        if (isset($this->filters['search']) && $this->filters['search']) {
            $query->whereHas('customer.user', function($q) {
                $q->where('name', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
            })->orWhere('id', 'like', '%' . $this->filters['search'] . '%');
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['type']) && $this->filters['type']) {
            if ($this->filters['type'] === 'donation') {
                $query->where('is_donation', true);
            } elseif ($this->filters['type'] === 'purchase') {
                $query->where('is_donation', false);
            }
        }

        // Handle selected IDs for bulk export
        if (isset($this->filters['selected_ids']) && !empty($this->filters['selected_ids'])) {
            $query->whereIn('id', $this->filters['selected_ids']);
        }

        // Date range filters
        if (isset($this->filters['date_range']) && $this->filters['date_range']) {
            switch ($this->filters['date_range']) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', yesterday());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)
                        ->whereYear('created_at', now()->subMonth()->year);
                    break;
            }
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Order Type',
            'Status',
            'Total ($)',
            'Items Count',
            'Total Quantity',
            'Variant Items Count',
            'Donation Items Count',
            'Items Summary',
            'Variant Details',
            'Payment Method',
            'Payment Status',
            'Shipping Address',
            'Order Notes',
            'Created At',
            'Updated At',
            'Processing Time (Days)',
        ];
    }

    public function map($order): array
    {
        // Enhanced items details with variant information
        $itemsDetails = $order->items->map(function($item) {
            $details = $item->product_name;

            // Add variant information if available
            $variantInfo = [];
            if ($item->selected_color) {
                $variantInfo[] = "Color: {$item->selected_color}";
            }
            if ($item->selected_size) {
                $variantInfo[] = "Size: {$item->selected_size}";
            }
            if ($item->variant_id) {
                $variantInfo[] = "Variant ID: {$item->variant_id}";
            }

            if (!empty($variantInfo)) {
                $details .= " (" . implode(', ', $variantInfo) . ")";
            }

            $details .= " - Qty: {$item->quantity} × $" . number_format($item->price, 2);

            if ($item->is_donation_item) {
                $details .= " [DONATION]";
            }

            return $details;
        })->join('; ');

        // Variant details summary
        $variantDetails = $order->items->filter(function($item) {
            return $item->selected_color || $item->selected_size || $item->variant_id;
        })->map(function($item) {
            $variant = [];
            if ($item->selected_color) $variant[] = $item->selected_color;
            if ($item->selected_size) $variant[] = $item->selected_size;
            return implode('/', $variant) . " (×{$item->quantity})";
        })->join('; ');

        // Calculate processing time
        $processingDays = $order->created_at->diffInDays($order->updated_at);

        // Count different item types
        $variantItemsCount = $order->items->filter(function($item) {
            return $item->selected_color || $item->selected_size || $item->variant_id;
        })->count();

        $donationItemsCount = $order->items->where('is_donation_item', true)->count();
        $totalQuantity = $order->items->sum('quantity');

        return [
            $order->id,
            $order->customer->user->name ?? 'N/A',
            $order->customer->user->email ?? 'N/A',
            $order->customer->phone ?? 'N/A',
            $order->is_donation ? 'Donation' : 'Purchase',
            ucfirst($order->status),
            number_format($order->total, 2),
            $order->items->count(),
            $totalQuantity,
            $variantItemsCount,
            $donationItemsCount,
            $itemsDetails,
            $variantDetails ?: 'No Variants',
            ucfirst($order->payment_method ?? 'N/A'),
            ucfirst($order->payment_status ?? 'N/A'),
            $order->shipping_address ?? $order->customer->address ?? 'N/A',
            $order->notes ?? 'N/A',
            $order->created_at->format('Y-m-d H:i:s'),
            $order->updated_at->format('Y-m-d H:i:s'),
            $processingDays,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Order ID
            'B' => 20,  // Customer Name
            'C' => 25,  // Customer Email
            'D' => 15,  // Customer Phone
            'E' => 12,  // Order Type
            'F' => 12,  // Status
            'G' => 12,  // Total
            'H' => 10,  // Items Count
            'I' => 10,  // Total Quantity
            'J' => 12,  // Variant Items Count
            'K' => 12,  // Donation Items Count
            'L' => 50,  // Items Summary
            'M' => 30,  // Variant Details
            'N' => 15,  // Payment Method
            'O' => 12,  // Payment Status
            'P' => 30,  // Shipping Address
            'Q' => 25,  // Order Notes
            'R' => 18,  // Created At
            'S' => 18,  // Updated At
            'T' => 15,  // Processing Time
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold header
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
            // Style data rows
            'A2:T1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Orders Summary';
    }
}

// Order Items Detail Sheet
class OrderItemsDetailSheet implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Order::with(['customer.user', 'items.product', 'items.variant.color', 'items.variant.size']);

        // Apply the same filters as summary sheet
        if (isset($this->filters['search']) && $this->filters['search']) {
            $query->whereHas('customer.user', function($q) {
                $q->where('name', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
            })->orWhere('id', 'like', '%' . $this->filters['search'] . '%');
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['selected_ids']) && !empty($this->filters['selected_ids'])) {
            $query->whereIn('id', $this->filters['selected_ids']);
        }

        // Get orders and flatten order items
        $orderItems = collect();
        $orders = $query->latest()->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $orderItems->push((object)[
                    'order' => $order,
                    'item' => $item
                ]);
            }
        }

        return $orderItems;
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Customer Name',
            'Customer Email',
            'Order Status',
            'Order Type',
            'Item ID',
            'Product Name',
            'Product ID',
            'Variant ID',
            'Selected Color',
            'Color Hex Code',
            'Selected Size',
            'Quantity',
            'Unit Price ($)',
            'Total Price ($)',
            'Is Donation Item',
            'Product SKU',
            'Variant SKU',
            'Order Date',
            'Item Added Date',
        ];
    }

    public function map($item): array
    {
        $order = $item->order;
        $orderItem = $item->item;

        return [
            $order->id,
            $order->customer->user->name ?? 'N/A',
            $order->customer->user->email ?? 'N/A',
            ucfirst($order->status),
            $order->is_donation ? 'Donation' : 'Purchase',
            $orderItem->id,
            $orderItem->product_name,
            $orderItem->product_id ?? 'N/A',
            $orderItem->variant_id ?? 'N/A',
            $orderItem->selected_color ?? 'N/A',
            $orderItem->selected_color_hex ?? 'N/A',
            $orderItem->selected_size ?? 'N/A',
            $orderItem->quantity,
            number_format($orderItem->price, 2),
            number_format($orderItem->quantity * $orderItem->price, 2),
            $orderItem->is_donation_item ? 'Yes' : 'No',
            $orderItem->product->sku ?? 'N/A',
            $orderItem->variant->sku ?? 'N/A',
            $order->created_at->format('Y-m-d H:i:s'),
            $orderItem->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Order ID
            'B' => 20,  // Customer Name
            'C' => 25,  // Customer Email
            'D' => 12,  // Order Status
            'E' => 12,  // Order Type
            'F' => 10,  // Item ID
            'G' => 25,  // Product Name
            'H' => 10,  // Product ID
            'I' => 10,  // Variant ID
            'J' => 15,  // Selected Color
            'K' => 10,  // Color Hex
            'L' => 12,  // Selected Size
            'M' => 8,   // Quantity
            'N' => 10,  // Unit Price
            'O' => 12,  // Total Price
            'P' => 12,  // Is Donation
            'Q' => 15,  // Product SKU
            'R' => 15,  // Variant SKU
            'S' => 18,  // Order Date
            'T' => 18,  // Item Added Date
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold header
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
            // Style data rows
            'A2:T1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Order Items Detail';
    }
}

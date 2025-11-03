<?php

namespace App\Exports;

use App\Models\Product;
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

class ProductsExport implements WithMultipleSheets
{
    protected $filters;
    protected $includeVariants;

    public function __construct($filters = [], $includeVariants = true)
    {
        $this->filters = $filters;
        $this->includeVariants = $includeVariants;
    }

    public function sheets(): array
    {
        $sheets = [
            new ProductsSheet($this->filters),
        ];

        if ($this->includeVariants) {
            $sheets[] = new ProductVariantsSheet($this->filters);
        }

        return $sheets;
    }
}

// Products main sheet
class ProductsSheet implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Product::with(['category', 'variants.color', 'variants.size']);

        // Apply filters (same as before but enhanced)
        if (isset($this->filters['search']) && $this->filters['search']) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('slug', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        if (isset($this->filters['category']) && $this->filters['category']) {
            if (is_numeric($this->filters['category'])) {
                $query->where('category_id', $this->filters['category']);
            } else {
                $query->whereHas('category', function($q) {
                    $q->where('slug', $this->filters['category']);
                });
            }
        }

        if (isset($this->filters['status']) && $this->filters['status']) {
            switch ($this->filters['status']) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'donatable':
                    $query->where('is_donatable', true);
                    break;
                case 'not_donatable':
                    $query->where('is_donatable', false);
                    break;
                case 'low_stock':
                    $query->where(function($q) {
                        $q->where('stock', '<=', 10)->where('stock', '>', 0)->doesntHave('variants')
                            ->orWhereHas('variants', function($vq) {
                                $vq->where('stock', '<=', 10)->where('stock', '>', 0);
                            });
                    });
                    break;
                case 'out_of_stock':
                    $query->where(function($q) {
                        $q->where('stock', 0)->doesntHave('variants')
                            ->orWhereHas('variants', function($vq) {
                                $vq->where('stock', 0);
                            });
                    });
                    break;
                case 'has_variants':
                    $query->has('variants');
                    break;
                case 'no_variants':
                    $query->doesntHave('variants');
                    break;
            }
        }

        // Handle selected IDs for bulk export
        if (isset($this->filters['selected_ids']) && !empty($this->filters['selected_ids'])) {
            $query->whereIn('id', $this->filters['selected_ids']);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Name',
            'Slug',
            'Description',
            'Category',
            'Base Price ($)',
            'Base Stock',
            'Status',
            'Donatable',
            'Has Variants',
            'Variants Count',
            'Available Colors',
            'Available Sizes',
            'Total Stock',
            'Stock Status',
            'Price Range',
            'Total Value ($)',
            'Image URL',
            'Created At',
            'Updated At',
        ];
    }

    public function map($product): array
    {
        // Enhanced stock calculation
        $totalStock = $product->variants->count() > 0 ?
            $product->variants->sum('stock') :
            $product->stock;

        // Stock status
        $stockStatus = 'In Stock';
        if ($totalStock == 0) {
            $stockStatus = 'Out of Stock';
        } elseif ($totalStock <= 10) {
            $stockStatus = 'Low Stock';
        }

        // Price range for variants
        $priceRange = '';
        if ($product->variants->count() > 0) {
            $minPrice = $product->variants->min(function($variant) use ($product) {
                return $product->price + $variant->price_adjustment;
            });
            $maxPrice = $product->variants->max(function($variant) use ($product) {
                return $product->price + $variant->price_adjustment;
            });

            if ($minPrice == $maxPrice) {
                $priceRange = '$' . number_format($minPrice, 2);
            } else {
                $priceRange = '$' . number_format($minPrice, 2) . ' - $' . number_format($maxPrice, 2);
            }
        } else {
            $priceRange = '$' . number_format($product->price, 2);
        }

        // Available colors and sizes
        $availableColors = $product->variants->pluck('color.name')->unique()->implode(', ') ?: 'N/A';
        $availableSizes = $product->variants->pluck('size.name')->unique()->implode(', ') ?: 'N/A';

        // Calculate total value
        $totalValue = $product->variants->count() > 0 ?
            $product->variants->sum(function($variant) use ($product) {
                return ($product->price + $variant->price_adjustment) * $variant->stock;
            }) :
            $product->price * $product->stock;

        return [
            $product->id,
            $product->name,
            $product->slug,
            $product->description ?? 'N/A',
            $product->category->name ?? 'Uncategorized',
            number_format($product->price, 2),
            $product->stock,
            $product->is_active ? 'Active' : 'Inactive',
            $product->is_donatable ? 'Yes' : 'No',
            $product->variants->count() > 0 ? 'Yes' : 'No',
            $product->variants->count(),
            $availableColors,
            $availableSizes,
            $totalStock,
            $stockStatus,
            $priceRange,
            number_format($totalValue, 2),
            $product->image ? asset('storage/' . $product->image) : 'N/A',
            $product->created_at->format('Y-m-d H:i:s'),
            $product->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Product ID
            'B' => 25,  // Name
            'C' => 20,  // Slug
            'D' => 35,  // Description
            'E' => 15,  // Category
            'F' => 12,  // Base Price
            'G' => 10,  // Base Stock
            'H' => 10,  // Status
            'I' => 12,  // Donatable
            'J' => 12,  // Has Variants
            'K' => 12,  // Variants Count
            'L' => 25,  // Available Colors
            'M' => 20,  // Available Sizes
            'N' => 10,  // Total Stock
            'O' => 12,  // Stock Status
            'P' => 15,  // Price Range
            'Q' => 12,  // Total Value
            'R' => 30,  // Image URL
            'S' => 18,  // Created At
            'T' => 18,  // Updated At
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
                    'startColor' => ['rgb' => '4F46E5']
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
        return 'Products';
    }
}

// Product variants sheet
class ProductVariantsSheet implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Product::with(['category', 'variants.color', 'variants.size']);

        // Apply the same filters as main sheet
        if (isset($this->filters['search']) && $this->filters['search']) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $this->filters['search'] . '%')
                    ->orWhere('slug', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        if (isset($this->filters['category']) && $this->filters['category']) {
            if (is_numeric($this->filters['category'])) {
                $query->where('category_id', $this->filters['category']);
            } else {
                $query->whereHas('category', function($q) {
                    $q->where('slug', $this->filters['category']);
                });
            }
        }

        if (isset($this->filters['selected_ids']) && !empty($this->filters['selected_ids'])) {
            $query->whereIn('id', $this->filters['selected_ids']);
        }

        // Get products with variants and flatten variants
        $variants = collect();
        $products = $query->has('variants')->latest()->get();

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $variants->push((object)[
                    'product' => $product,
                    'variant' => $variant
                ]);
            }
        }

        return $variants;
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Product Name',
            'Category',
            'Variant ID',
            'SKU',
            'Color',
            'Color Hex',
            'Size',
            'Size Category',
            'Stock',
            'Base Price ($)',
            'Price Adjustment ($)',
            'Final Price ($)',
            'Total Value ($)',
            'Status',
            'Variant Active',
            'Created At',
            'Updated At',
        ];
    }

    public function map($item): array
    {
        $product = $item->product;
        $variant = $item->variant;

        $finalPrice = $product->price + $variant->price_adjustment;
        $totalValue = $finalPrice * $variant->stock;

        return [
            $product->id,
            $product->name,
            $product->category->name ?? 'Uncategorized',
            $variant->id,
            $variant->sku ?? 'N/A',
            $variant->color->name ?? 'N/A',
            $variant->color->hex_code ?? 'N/A',
            $variant->size->name ?? 'N/A',
            $variant->size->category_type ?? 'N/A',
            $variant->stock,
            number_format($product->price, 2),
            number_format($variant->price_adjustment, 2),
            number_format($finalPrice, 2),
            number_format($totalValue, 2),
            $product->is_active ? 'Active' : 'Inactive',
            $variant->is_active ? 'Active' : 'Inactive',
            $variant->created_at->format('Y-m-d H:i:s'),
            $variant->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Product ID
            'B' => 25,  // Product Name
            'C' => 15,  // Category
            'D' => 10,  // Variant ID
            'E' => 15,  // SKU
            'F' => 12,  // Color
            'G' => 10,  // Color Hex
            'H' => 10,  // Size
            'I' => 12,  // Size Category
            'J' => 8,   // Stock
            'K' => 10,  // Base Price
            'L' => 12,  // Price Adjustment
            'M' => 10,  // Final Price
            'N' => 12,  // Total Value
            'O' => 10,  // Status
            'P' => 12,  // Variant Active
            'Q' => 18,  // Created At
            'R' => 18,  // Updated At
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
            'A2:R1000' => [
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
        return 'Product Variants';
    }
}

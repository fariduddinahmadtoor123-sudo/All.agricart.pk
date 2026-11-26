<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintProfile extends Model
{
    public const CATEGORY_DOCUMENT = 'document';

    public const CATEGORY_RECEIPT = 'receipt';

    public const CATEGORY_LABEL = 'label';

    public const LABEL_KIND_PRODUCT = 'product';

    public const LABEL_KIND_PRICE = 'price';

    public const LABEL_KIND_BARCODE = 'barcode';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name_en',
        'name_ur',
        'category',
        'label_kind',
        'width_mm',
        'height_mm',
        'margin_mm',
        'orientation',
        'printer_hint',
        'is_system',
        'is_default',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width_mm' => 'float',
            'height_mm' => 'float',
            'margin_mm' => 'float',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function localizedName(): string
    {
        return filled($this->name_en) ? (string) $this->name_en : (string) $this->name_ur;
    }

    public function pageSizeCss(): string
    {
        $width = rtrim(rtrim(number_format((float) $this->width_mm, 2, '.', ''), '0'), '.');

        if ($this->height_mm === null) {
            return "{$width}mm auto";
        }

        $height = rtrim(rtrim(number_format((float) $this->height_mm, 2, '.', ''), '0'), '.');

        return "{$width}mm {$height}mm";
    }

    public function dimensionsLabel(): string
    {
        $width = rtrim(rtrim(number_format((float) $this->width_mm, 1, '.', ''), '0'), '.');

        if ($this->height_mm === null) {
            return "{$width} mm roll";
        }

        $height = rtrim(rtrim(number_format((float) $this->height_mm, 1, '.', ''), '0'), '.');

        return "{$width} × {$height} mm";
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_DOCUMENT => 'Document',
            self::CATEGORY_RECEIPT => 'Receipt',
            self::CATEGORY_LABEL => 'Label / barcode',
            default => (string) $this->category,
        };
    }
}

<?php

namespace App\Support\Printing;

use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeRenderer
{
    public function svg(string $code, float $widthFactor = 2.0, float $height = 60.0): string
    {
        $generator = new BarcodeGeneratorSVG;

        return $generator->getBarcode(
            $code,
            $generator::TYPE_CODE_128,
            $widthFactor,
            $height,
        );
    }
}

<?php

namespace App\Models;

use Database\Factories\ColorSchemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'page', 'card', 'soft', 'line', 'primary'])]
class ColorScheme extends Model
{
    /** @use HasFactory<ColorSchemeFactory> */
    use HasFactory;
}

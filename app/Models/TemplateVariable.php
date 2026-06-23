<?php

namespace App\Models;

use App\Enums\TemplateVariableType;
use Database\Factories\TemplateVariableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateVariable extends Model
{
    /** @use HasFactory<TemplateVariableFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'variable_type',
        'source_key',
        'example_value',
        'is_required',
        'is_sensitive',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variable_type' => TemplateVariableType::class,
            'is_required' => 'boolean',
            'is_sensitive' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

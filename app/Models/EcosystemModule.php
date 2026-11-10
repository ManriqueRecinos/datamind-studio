<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcosystemModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecosystem_id',
        'key',
        'name',
        'icon',
        'order_num',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_num' => 'integer',
    ];

    /**
     * Ecosistema padre al que pertenece este módulo.
     */
    public function ecosystem(): BelongsTo
    {
        return $this->belongsTo(Ecosystem::class);
    }

    /**
     * Interfaces / opciones operativas de este módulo.
     */
    public function interfaces(): HasMany
    {
        return $this->hasMany(EcosystemInterface::class, 'module_id')->orderBy('order_num');
    }
}

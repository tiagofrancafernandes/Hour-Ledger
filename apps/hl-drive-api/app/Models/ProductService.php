<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string|null $unit
 * @property numeric $default_price
 * @property numeric $default_quantity
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InvoiceItem> $invoiceItems
 * @property-read int|null $invoice_items_count
 * @method static Builder<static>|ProductService active()
 * @method static Builder<static>|ProductService newModelQuery()
 * @method static Builder<static>|ProductService newQuery()
 * @method static Builder<static>|ProductService query()
 * @method static Builder<static>|ProductService whereCreatedAt($value)
 * @method static Builder<static>|ProductService whereDefaultPrice($value)
 * @method static Builder<static>|ProductService whereDefaultQuantity($value)
 * @method static Builder<static>|ProductService whereDescription($value)
 * @method static Builder<static>|ProductService whereId($value)
 * @method static Builder<static>|ProductService whereIsActive($value)
 * @method static Builder<static>|ProductService whereName($value)
 * @method static Builder<static>|ProductService whereType($value)
 * @method static Builder<static>|ProductService whereUnit($value)
 * @method static Builder<static>|ProductService whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProductService extends Model
{
    use HasFactory;

    protected $table = 'products_services';

    protected $fillable = [
        'name',
        'description',
        'type',
        'unit',
        'default_price',
        'default_quantity',
        'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'default_quantity' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

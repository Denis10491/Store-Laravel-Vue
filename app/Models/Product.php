<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'imgPath',
        'categories_ids',
        'nutritional_id',
        'composition',
        'price'
    ];

    public function nutritional()
    {
        return $this->belongsTo(Nutritional::class);
    }

    public function products_on_orders()
    {
        return $this->hasMany(ProductsInOrders::class);
    }
}
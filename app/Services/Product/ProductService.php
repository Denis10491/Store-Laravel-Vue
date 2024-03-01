<?php

namespace App\Services\Product;

use App\Contracts\ProductServiceContract;
use App\Http\Requests\Product\ProductStatisticsMonthlyBestSellingRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\StoreReviewRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\UpdateReviewRequest;
use App\Models\Nutritional;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService implements ProductServiceContract
{
    protected Product $product;

    protected Review $review;

    public function store(StoreProductRequest $request): Product
    {
        return DB::transaction(static function () use ($request): Product {
            $path = $request->file('image')->storePublicly('images', 'public');
            Storage::disk('public')->url($path);

            $nutritional = Nutritional::query()->create($request->only('proteins', 'fats', 'carbohydrates'));

            return Product::query()->create([
                ...$request->only('name', 'description', 'composition', 'price'),
                'imgPath' => config('app.url').Storage::url($path),
                'nutritional_id' => $nutritional->id,
            ]);
        });
    }

    public function storeReview(StoreReviewRequest $request): Review
    {
        return auth()->user()->reviews()->create([
            ...$request->only('body', 'rating'),
            'product_id' => $this->product->id
        ]);
    }

    public function update(UpdateProductRequest $request): Product
    {
        return DB::transaction(function () use ($request): Product {
            if ($request->file('image')) {
                $path = $request->file('image')->storePublicly('images', 'public');
                Storage::disk('public')->url($path);
                $this->product->update(['imgPath' => 'storage/'.$path]);
            }

            if ($request->method() === 'PUT') {
                $this->product->update([
                    'name' => $request->str('name'),
                    'description' => $request->str('description'),
                    'composition' => $request->str('composition'),
                    'price' => $request->integer('price'),
                ]);
                $this->product->nutritional()->update([
                    'proteins' => $request->integer('proteins'),
                    'fats' => $request->integer('fats'),
                    'carbohydrates' => $request->integer('carbohydrates'),
                ]);
            } else {
                $this->product->update($request->only('name', 'description', 'composition', 'price'));
                $this->product->nutritional()->update($request->only('proteins', 'fats', 'carbohydrates'));
            }

            return $this->product;
        });
    }

    public function updateReview(UpdateReviewRequest $request): Review
    {
        if ($request->method() === 'PUT') {
            $this->review->update([
                'body' => $request->str('body'),
                'rating' => $request->integer('rating')
            ]);
        } else {
            $this->review->update($request->only('body', 'rating'));
        }
        
        return $this->review;
    }

    public function monthlyBestSelling(ProductStatisticsMonthlyBestSellingRequest $request
    ): Collection {
        $date = $request->integer('year').'-'.$request->integer('month');
        return Order::query()->whereBetween('created_at',
            [
                Carbon::parse($date)->startOfMonth(),
                Carbon::parse($date)->endOfMonth()
            ])
            ->with('products')
            ->selectRaw('order_product.product_id, SUM(order_product.count) as total_count')
            ->groupBy('order_product.product_id')->get();
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function setReview(Review $review): static
    {
        $this->review = $review;
        return $this;
    }
}

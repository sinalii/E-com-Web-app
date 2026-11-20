<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductReviewController extends Controller
{
    public function store(Request $request, string $product)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $query = DB::table('product')
            ->join('category', 'category.id', '=', 'product.category_id')
            ->where('product.is_active', true)
            ->whereNull('product.deleted_at')
            ->where('category.is_active', true);

        if (ctype_digit($product)) {
            $query->where('product.id', (int) $product);
        } else {
            $query->where('product.slug', $product);
        }

        $item = $query->select('product.id', 'product.slug')->firstOrFail();
        $existingReview = DB::table('review')
            ->where('product_id', $item->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingReview) {
            DB::table('review')->where('id', $existingReview->id)->update([
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'updated_at' => now(),
            ]);
            $message = 'Your review has been updated.';
        } else {
            DB::table('review')->insert([
                'user_id' => $request->user()->id,
                'product_id' => $item->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $message = 'Thanks for sharing your review.';
        }

        return redirect()->to(route('products.show', $item->slug ?: $item->id).'#product-reviews')
            ->with('success', $message);
    }
}

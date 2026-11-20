<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.marketplace', function ($view): void {
            $activeCategories = DB::table('category')->where('is_active', true)->orderBy('name')->get();
            $childrenByParent = $activeCategories->whereNotNull('parent_id')->groupBy('parent_id');
            $roots = $activeCategories->whereNull('parent_id')->values();

            $roots->each(function ($root) use ($childrenByParent): void {
                $root->children = $childrenByParent->get($root->id, collect())->values();
            });

            $view->with('categoryTree', $roots);
        });
    }
}

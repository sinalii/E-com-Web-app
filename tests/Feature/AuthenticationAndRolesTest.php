<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationAndRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedTinyInteger('role')->nullable();
            $table->boolean('is_admin')->nullable()->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_blocked')->default(false);
            $table->string('phone')->nullable();
            $table->string('profile')->nullable();
            $table->boolean('email_verify')->nullable();
            $table->string('address')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total_amount')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_default')->default(false);
        });
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('main_category_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('description')->nullable();
            $table->string('slug')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('product_variant', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('price')->nullable();
            $table->decimal('sale_price')->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('product_image', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('order_payment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->decimal('amount')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('payment', function (Blueprint $table) {
            $table->id();
            $table->string('method')->nullable();
            $table->string('transaction_id')->nullable();
        });
        Schema::create('brand', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
        });
        Schema::create('coupon', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value')->nullable();
            $table->decimal('minimum_amount')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
        });
        Schema::create('review', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('cart_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->integer('quantity')->default(0);
        });
        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
        });
        Schema::create('category_filter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('slug')->nullable();
        });
    }

    public function test_public_registration_always_creates_a_customer_and_signs_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New Customer',
            'email' => 'CUSTOMER@example.com',
            'password' => 'customer123',
            'password_confirmation' => 'customer123',
            'role' => User::ROLE_ADMIN,
            'is_admin' => true,
        ]);

        $user = User::where('email', 'customer@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_CUSTOMER, (int) $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('account'));
    }

    public function test_customer_login_goes_to_customer_dashboard_and_cannot_open_admin(): void
    {
        $user = $this->makeUser('customer@example.com', User::ROLE_CUSTOMER, false);

        $this->get(route('login'));
        $sessionIdBeforeLogin = session()->getId();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('account'));
        $this->assertNotSame($sessionIdBeforeLogin, session()->getId());
        $this->get(route('account'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_login_goes_to_admin_dashboard_and_customer_cannot_reach_admin(): void
    {
        $admin = $this->makeUser('admin@example.com', User::ROLE_ADMIN, true);

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(route('index'))->assertSee(route('admin.dashboard'))->assertSee('Admin Dashboard');
        foreach (['products', 'categories', 'brands', 'customers', 'orders', 'payments', 'coupons', 'inventory', 'reviews', 'reports', 'settings'] as $section) {
            $this->get(route('admin.section', $section))->assertOk();
        }
        $this->get(route('account'))->assertForbidden();

        auth()->logout();
        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
    }

    public function test_login_form_does_not_render_previous_email_or_password(): void
    {
        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'private@example.com',
            'password' => 'secret123',
        ])->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk()
            ->assertDontSee('value="private@example.com"', false)
            ->assertDontSee('value="secret123"', false);
    }

    public function test_admin_dashboard_requires_authentication_and_logout_clears_session(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $user = $this->makeUser('logout@example.com', User::ROLE_CUSTOMER, false);
        $logoutResponse = $this->actingAs($user)->post(route('logout'))->assertRedirect(route('index'));
        $this->assertStringContainsString('no-store', $logoutResponse->headers->get('Cache-Control'));
        $this->assertGuest();
    }

    public function test_admin_can_manage_real_inventory_and_customers_cannot(): void
    {
        $admin = $this->makeUser('stock-admin@example.com', User::ROLE_ADMIN, true);
        $productId = \Illuminate\Support\Facades\DB::table('product')->insertGetId(['name' => 'Stock test', 'sku' => 'STOCK-1']);
        $variantId = \Illuminate\Support\Facades\DB::table('product_variant')->insertGetId(['product_id' => $productId, 'sku' => 'STOCK-1-A', 'price' => 10, 'stock_quantity' => 2]);

        $this->actingAs($admin)->get(route('admin.section', 'inventory'))->assertOk()->assertSee('Stock test');
        $this->put(route('admin.resource.update', ['inventory', $variantId]), ['stock_quantity' => 17])->assertRedirect(route('admin.section', 'inventory'));
        $this->assertSame(17, (int) \Illuminate\Support\Facades\DB::table('product_variant')->where('id', $variantId)->value('stock_quantity'));

        $customer = $this->makeUser('stock-customer@example.com', User::ROLE_CUSTOMER, false);
        $this->actingAs($customer)->put(route('admin.resource.update', ['inventory', $variantId]), ['stock_quantity' => 99])->assertForbidden();
        $this->assertSame(17, (int) \Illuminate\Support\Facades\DB::table('product_variant')->where('id', $variantId)->value('stock_quantity'));
    }

    public function test_admin_can_create_edit_view_and_soft_delete_a_product(): void
    {
        $admin = $this->makeUser('catalog-admin@example.com', User::ROLE_ADMIN, true);
        $mainCategoryId = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Test main category', 'slug' => 'test-main-category', 'is_active' => true]);
        $categoryId = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Test category', 'slug' => 'test-category', 'parent_id' => $mainCategoryId, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.resource.store', 'products'), [
            'name' => 'Admin created product', 'sku' => 'ADMIN-1', 'main_category_id' => $mainCategoryId, 'subcategory_id' => $categoryId,
            'price' => 1250, 'stock_quantity' => 6, 'is_active' => 1,
        ])->assertRedirect(route('admin.section', 'products'));

        $productId = (int) \Illuminate\Support\Facades\DB::table('product')->where('sku', 'ADMIN-1')->value('id');
        $variant = \Illuminate\Support\Facades\DB::table('product_variant')->where('product_id', $productId)->first();
        $this->assertNotNull($variant);
        $this->get(route('admin.section', 'products'))->assertOk()->assertSee('Admin created product');

        $this->put(route('admin.resource.update', ['products', $productId]), [
            'name' => 'Updated admin product', 'sku' => 'ADMIN-1', 'main_category_id' => $mainCategoryId, 'subcategory_id' => $categoryId,
            'price' => 1450, 'stock_quantity' => 8, 'is_active' => 1,
        ])->assertRedirect(route('admin.section', 'products'));
        $this->assertSame('Updated admin product', \Illuminate\Support\Facades\DB::table('product')->where('id', $productId)->value('name'));

        $this->delete(route('admin.resource.destroy', ['products', $productId]))->assertRedirect(route('admin.section', 'products'));
        $this->assertNotNull(\Illuminate\Support\Facades\DB::table('product')->where('id', $productId)->value('deleted_at'));
    }

    public function test_product_category_dropdown_filters_children_and_saves_the_selected_pair(): void
    {
        $admin = $this->makeUser('product-category-admin@example.com', User::ROLE_ADMIN, true);
        $mainA = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
        $mainB = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Fashion', 'slug' => 'fashion', 'is_active' => true]);
        $emptyMain = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Food', 'slug' => 'food', 'is_active' => true]);
        $subA = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Phones', 'slug' => 'phones', 'parent_id' => $mainA, 'is_active' => true]);
        $subA2 = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Laptops', 'slug' => 'laptops', 'parent_id' => $mainA, 'is_active' => true]);
        $subB = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Clothing', 'slug' => 'clothing', 'parent_id' => $mainB, 'is_active' => true]);
        \Illuminate\Support\Facades\DB::table('category')->insert(['name' => 'Inactive phones', 'slug' => 'inactive-phones', 'parent_id' => $mainA, 'is_active' => false]);

        $response = $this->actingAs($admin)->get(route('admin.categories.subcategories', $mainA));
        $response->assertOk()->assertExactJson([
            ['id' => $subA2, 'name' => 'Laptops'], ['id' => $subA, 'name' => 'Phones'],
        ]);
        $this->get(route('admin.categories.subcategories', $emptyMain))->assertOk()->assertExactJson([]);

        $productId = \Illuminate\Support\Facades\DB::table('product')->insertGetId([
            'name' => 'Existing phone', 'sku' => 'EXISTING-PHONE', 'main_category_id' => $mainA, 'category_id' => $subA, 'is_active' => true,
        ]);
        $editResponse = $this->get(route('admin.section', ['products', 'edit' => $productId]))->assertOk()
            ->assertSee('id="product-main-category"', false)
            ->assertSee('data-selected-subcategory="'.$subA.'"', false)
            ->assertSee('value="'.$mainA.'" selected', false);
        preg_match('/<select id="product-main-category"[^>]*>(.*?)<\/select>/s', $editResponse->getContent(), $mainSelect);
        $this->assertStringContainsString('Electronics', $mainSelect[1] ?? '');
        $this->assertStringContainsString('Fashion', $mainSelect[1] ?? '');
        $this->assertStringNotContainsString('Phones', $mainSelect[1] ?? '');

        $this->post(route('admin.resource.store', 'products'), [
            'name' => 'Mismatched product', 'sku' => 'MISMATCHED', 'main_category_id' => $mainA, 'subcategory_id' => $subB,
            'price' => 500, 'stock_quantity' => 2, 'is_active' => 1,
        ])->assertSessionHasErrors('subcategory_id');

        $this->post(route('admin.resource.store', 'products'), [
            'name' => 'Admin laptop', 'sku' => 'ADMIN-LAPTOP', 'main_category_id' => $mainA, 'subcategory_id' => $subA2,
            'price' => 500, 'stock_quantity' => 2, 'is_active' => 1,
        ])->assertRedirect(route('admin.section', 'products'));
        $this->assertDatabaseHas('product', [
            'sku' => 'ADMIN-LAPTOP', 'main_category_id' => $mainA, 'category_id' => $subA2,
        ]);

        $this->put(route('admin.resource.update', ['products', $productId]), [
            'name' => 'Existing phone', 'sku' => 'EXISTING-PHONE', 'main_category_id' => $mainB, 'subcategory_id' => $subB,
            'price' => 750, 'stock_quantity' => 4, 'is_active' => 1,
        ])->assertRedirect(route('admin.section', 'products'));
        $this->assertDatabaseHas('product', [
            'id' => $productId, 'main_category_id' => $mainB, 'category_id' => $subB,
        ]);
    }

    public function test_admin_can_manage_main_and_subcategories_with_images_on_one_page(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('category-admin@example.com', User::ROLE_ADMIN, true);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/pLsAAAAASUVORK5CYII=');

        $this->actingAs($admin)->post(route('admin.resource.store', 'categories'), [
            'category_type' => 'main', 'name' => 'Electronics', 'slug' => '', 'is_active' => 1,
            'image' => UploadedFile::fake()->createWithContent('electronics.png', $png),
        ])->assertRedirect(route('admin.section', 'categories'));

        $main = \Illuminate\Support\Facades\DB::table('category')->where('name', 'Electronics')->first();
        $this->assertNotNull($main);
        $this->assertNull($main->parent_id);
        $this->assertSame('electronics', $main->slug);
        Storage::disk('public')->assertExists($main->image_path);

        $this->post(route('admin.resource.store', 'categories'), [
            'category_type' => 'subcategory', 'name' => 'Mobile Phones', 'slug' => '', 'parent_id' => $main->id, 'is_active' => 1,
            'image' => UploadedFile::fake()->createWithContent('mobile.png', $png),
        ])->assertRedirect(route('admin.section', 'categories'));

        $sub = \Illuminate\Support\Facades\DB::table('category')->where('name', 'Mobile Phones')->first();
        $this->assertNotNull($sub);
        $this->assertSame((int) $main->id, (int) $sub->parent_id);
        Storage::disk('public')->assertExists($sub->image_path);

        $this->get(route('admin.section', ['categories', 'edit' => $sub->id]))->assertOk()
            ->assertSee('Current subcategory image')
            ->assertSee(asset('storage/'.$sub->image_path), false);

        $this->get(route('admin.section', 'categories'))->assertOk()
            ->assertSee('Add New Category')->assertSee('Add New Subcategory')
            ->assertSee('Electronics')->assertSee('Mobile Phones')
            ->assertSee(asset('storage/'.$main->image_path), false)
            ->assertSee(asset('storage/'.$sub->image_path), false);

        $this->put(route('admin.resource.update', ['categories', $sub->id]), [
            'category_type' => 'subcategory', 'name' => 'Mobile Phones', 'slug' => $sub->slug,
            'parent_id' => $main->id, 'is_active' => 0,
        ])->assertRedirect(route('admin.section', 'categories'));
        $updatedSub = \Illuminate\Support\Facades\DB::table('category')->where('id', $sub->id)->first();
        $this->assertSame($sub->image_path, $updatedSub->image_path);
        $this->assertFalse((bool) $updatedSub->is_active);

        $this->put(route('admin.resource.update', ['categories', $sub->id]), [
            'category_type' => 'subcategory', 'name' => 'Mobile Phones', 'slug' => $sub->slug,
            'parent_id' => $main->id, 'is_active' => 0,
            'image' => UploadedFile::fake()->createWithContent('mobile-new.png', $png),
        ])->assertRedirect(route('admin.section', 'categories'));
        $replacementPath = \Illuminate\Support\Facades\DB::table('category')->where('id', $sub->id)->value('image_path');
        $this->assertNotSame($sub->image_path, $replacementPath);
        Storage::disk('public')->assertMissing($sub->image_path);
        Storage::disk('public')->assertExists($replacementPath);

        $this->delete(route('admin.resource.destroy', ['categories', $sub->id]))
            ->assertRedirect(route('admin.section', 'categories'));
        $this->assertDatabaseMissing('category', ['id' => $sub->id]);
        Storage::disk('public')->assertMissing($replacementPath);

        $this->delete(route('admin.resource.destroy', ['categories', $main->id]))
            ->assertRedirect(route('admin.section', 'categories'));
        $this->assertDatabaseMissing('category', ['id' => $main->id]);
        Storage::disk('public')->assertMissing($main->image_path);
    }

    public function test_category_image_is_required_and_subcategory_parent_must_be_a_main_category(): void
    {
        $admin = $this->makeUser('category-validation@example.com', User::ROLE_ADMIN, true);
        $this->actingAs($admin)->post(route('admin.resource.store', 'categories'), [
            'category_type' => 'main', 'name' => 'No image', 'is_active' => 1,
        ])->assertSessionHasErrors('image');

        $subcategory = \Illuminate\Support\Facades\DB::table('category')->insertGetId([
            'name' => 'Existing child', 'slug' => 'existing-child', 'parent_id' => 900, 'is_active' => true,
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/pLsAAAAASUVORK5CYII=');
        $this->post(route('admin.resource.store', 'categories'), [
            'category_type' => 'subcategory', 'name' => 'Invalid parent', 'parent_id' => $subcategory, 'is_active' => 1,
            'image' => UploadedFile::fake()->createWithContent('invalid-parent.png', $png),
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_customer_management_search_filters_details_orders_edit_and_status_controls(): void
    {
        $admin = $this->makeUser('customer-module-admin@example.com', User::ROLE_ADMIN, true);
        $customer = $this->makeUser('ada@example.com', User::ROLE_CUSTOMER, false);
        $other = $this->makeUser('grace@example.com', User::ROLE_CUSTOMER, false);
        \Illuminate\Support\Facades\DB::table('user')->where('id', $customer->id)->update(['name' => 'Ada Lovelace']);
        \Illuminate\Support\Facades\DB::table('user')->where('id', $other->id)->update(['is_blocked' => true]);

        \Illuminate\Support\Facades\DB::table('address')->insert([
            'user_id' => $customer->id, 'contact_name' => 'Ada Lovelace', 'phone' => '0771234567',
            'address_line1' => '12 Main Street', 'city' => 'Colombo', 'country' => 'Sri Lanka', 'is_default' => true,
        ]);
        $orderId = \Illuminate\Support\Facades\DB::table('order')->insertGetId([
            'user_id' => $customer->id, 'status' => 'shipped', 'total_amount' => 3250, 'created_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.section', ['customers', 'search' => 'Ada']))
            ->assertOk()->assertSee('Ada Lovelace')->assertDontSee('grace@example.com');
        $this->get(route('admin.section', ['customers', 'status' => 'blocked']))
            ->assertOk()->assertSee('grace@example.com')->assertDontSee('ada@example.com');

        $this->get(route('admin.customer.show', $customer->id))->assertOk()
            ->assertSee('12 Main Street')->assertSee('Colombo')->assertSee('#'.$orderId)->assertSee('Shipped');

        $this->get(route('admin.section', ['customers', 'edit' => $customer->id]))->assertOk()
            ->assertSee('ada@example.com')->assertSee('Customer name');
        $this->put(route('admin.resource.update', ['customers', $customer->id]), [
            'name' => 'Ada Byron', 'email' => 'ada.byron@example.com', 'phone' => '0777654321', 'address' => 'New legacy address',
        ])->assertRedirect(route('admin.section', 'customers'));
        $this->assertDatabaseHas('user', [
            'id' => $customer->id, 'name' => 'Ada Byron', 'email' => 'ada.byron@example.com', 'address' => 'New legacy address',
        ]);
        $this->assertDatabaseHas('order', ['id' => $orderId, 'user_id' => $customer->id, 'status' => 'shipped']);

        $this->patch(route('admin.customer.status', $customer->id), ['action' => 'deactivate'])
            ->assertRedirect();
        $this->assertDatabaseHas('user', ['id' => $customer->id, 'is_active' => false]);
        $this->patch(route('admin.customer.status', $customer->id), ['action' => 'activate'])->assertRedirect();
        $this->patch(route('admin.customer.status', $customer->id), ['action' => 'block'])->assertRedirect();
        $this->assertDatabaseHas('user', ['id' => $customer->id, 'is_blocked' => true]);
        $this->patch(route('admin.customer.status', $customer->id), ['action' => 'unblock'])->assertRedirect();
        $this->assertDatabaseHas('user', ['id' => $customer->id, 'is_blocked' => false]);
    }

    public function test_deactivated_and_blocked_customers_cannot_sign_in_or_use_customer_routes(): void
    {
        $inactive = $this->makeUser('inactive@example.com', User::ROLE_CUSTOMER, false);
        \Illuminate\Support\Facades\DB::table('user')->where('id', $inactive->id)->update(['is_active' => false]);
        $this->post(route('login.store'), ['email' => $inactive->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $blocked = $this->makeUser('blocked@example.com', User::ROLE_CUSTOMER, false);
        \Illuminate\Support\Facades\DB::table('user')->where('id', $blocked->id)->update(['is_blocked' => true]);
        $this->post(route('login.store'), ['email' => $blocked->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($inactive)->get(route('account'))->assertForbidden();
    }

    private function makeUser(string $email, int $role, bool $admin): User
    {
        return User::forceCreate([
            'name' => $admin ? 'Admin' : 'Customer',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'role' => $role,
            'is_admin' => $admin,
        ]);
    }
}

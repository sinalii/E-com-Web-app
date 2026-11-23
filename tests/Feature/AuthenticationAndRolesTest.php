<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
        Schema::create('product', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
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
            $table->boolean('is_active')->default(true);
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
        $categoryId = \Illuminate\Support\Facades\DB::table('category')->insertGetId(['name' => 'Test category', 'slug' => 'test-category', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.resource.store', 'products'), [
            'name' => 'Admin created product', 'sku' => 'ADMIN-1', 'category_id' => $categoryId,
            'price' => 1250, 'stock_quantity' => 6, 'is_active' => 1,
        ])->assertRedirect(route('admin.section', 'products'));

        $productId = (int) \Illuminate\Support\Facades\DB::table('product')->where('sku', 'ADMIN-1')->value('id');
        $variant = \Illuminate\Support\Facades\DB::table('product_variant')->where('product_id', $productId)->first();
        $this->assertNotNull($variant);
        $this->get(route('admin.section', 'products'))->assertOk()->assertSee('Admin created product');

        $this->put(route('admin.resource.update', ['products', $productId]), [
            'name' => 'Updated admin product', 'sku' => 'ADMIN-1', 'category_id' => $categoryId,
            'price' => 1450, 'stock_quantity' => 8, 'is_active' => 1,
        ])->assertRedirect(route('admin.section', 'products'));
        $this->assertSame('Updated admin product', \Illuminate\Support\Facades\DB::table('product')->where('id', $productId)->value('name'));

        $this->delete(route('admin.resource.destroy', ['products', $productId]))->assertRedirect(route('admin.section', 'products'));
        $this->assertNotNull(\Illuminate\Support\Facades\DB::table('product')->where('id', $productId)->value('deleted_at'));
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

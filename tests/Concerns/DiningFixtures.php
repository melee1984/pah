<?php

namespace Tests\Concerns;

use App\MerchantBranchUser;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\Services\DiningRoom;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

trait DiningFixtures
{
    public function createDiningDomainSchema(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('mobile')->nullable();
            $table->string('api_token')->nullable();
            $table->unsignedBigInteger('account_type_id')->nullable();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('restaurant_name');
            $table->text('description')->nullable();
            $table->string('mobile')->nullable();
            $table->string('img')->nullable();
            $table->string('banner')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('store_open')->default(true);
            $table->boolean('qr_ordering_enabled')->default(false);
            $table->boolean('addup')->default(false);
            $table->decimal('pahatud_discount', 8, 2)->nullable();
            $table->decimal('pahatud_comm_discount', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('mobile')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_category_id')->nullable();
            $table->string('name');
            $table->string('identifier')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('slug')->nullable();
            $table->decimal('price', 8, 2);
            $table->decimal('markdown_price', 8, 2)->nullable();
            $table->decimal('price_comm', 8, 2)->default(0);
            $table->decimal('markdown_price_comm', 8, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->string('img')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('product_location_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('partner_location_id')->index();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'partner_location_id']);
        });

        Schema::create('product_item_header', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_multiple')->default(false);
            $table->unsignedInteger('sorting')->default(0);
            $table->timestamps();
        });

        Schema::create('product_item_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_header_id');
            $table->string('title');
            $table->decimal('price', 8, 2)->default(0);
            $table->decimal('price_comm', 8, 2)->default(0);
            $table->decimal('markdown_price', 8, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_method', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('class')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('available_in_application')->default(true);
            $table->boolean('available_in_qr')->default(true);
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id');
            $table->string('fullname')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('order_no')->nullable();
            $table->text('notes')->nullable();
            $table->string('pos_request_hash', 64)->nullable();
            $table->boolean('active')->default(false);
            $table->string('ip_address')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->string('discount_code')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('distance_rate', 8, 2)->default(0);
            $table->string('duration')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('delivery_date')->nullable();
            $table->string('delivery_time')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('partner_location_address_id')->nullable();
            $table->string('fulfillment_type')->default('delivery');
            $table->unsignedBigInteger('dining_table_id')->nullable();
            $table->string('order_source', 40)->nullable();
            $table->unsignedBigInteger('qr_ordering_code_id')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('qty')->default(0);
            $table->decimal('variance_total', 8, 2)->default(0);
            $table->text('variance_content')->nullable();
            $table->unsignedTinyInteger('is_not_available')->default(0);
            $table->text('instruction')->nullable();
            $table->decimal('price_comm_total', 8, 2)->default(0);
            $table->decimal('variance_total_comm_total', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('cart_id');
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedInteger('order_status_id')->nullable();
            $table->unsignedInteger('status_id')->nullable();
            $table->unsignedInteger('booking_status_id')->nullable();
            $table->unsignedBigInteger('accepted_by_store_id')->nullable();
            $table->dateTime('store_accepted_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_process', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('status_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('library_status', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->integer('sorting')->default(1);
        });
        Schema::create('library_booking_status', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
        });
        if (! Schema::hasTable('rider')) {
            Schema::create('rider', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
            });
        }
        Schema::create('cart_user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->string('address_1')->nullable();
            $table->string('mobile')->nullable();
            $table->string('lat')->nullable();
            $table->string('long')->nullable();
            $table->timestamps();
        });
    }

    public function seedDiningAccounts(): array
    {
        foreach ([1 => 'Order Placed', 2 => 'Order Accepted', 3 => 'Order Processing', 4 => 'Ready for Pickup', 8 => 'Cancelled', 9 => 'Completed'] as $id => $title) {
            DB::table('library_status')->insert(['id' => $id, 'title' => $title]);
        }
        Role::findOrCreate('partner', 'web');
        $users = [];
        foreach (['owner', 'cashier', 'kitchen'] as $role) {
            $user = User::forceCreate([
                'name' => ucfirst($role).' Preview', 'firstname' => ucfirst($role), 'lastname' => 'Preview',
                'email' => $role.'@dining.test', 'password' => bcrypt('dining-preview'),
                'mobile' => '09170000000', 'api_token' => $role.'-preview-token',
            ]);
            $user->assignRole($role === 'owner' ? 'partner' : $role);
            $users[$role] = $user;
        }
        DB::table('partners')->insert(['id' => 1, 'user_id' => $users['owner']->id, 'restaurant_name' => 'Pahatud Test Kitchen', 'active' => true, 'store_open' => true, 'qr_ordering_enabled' => true, 'addup' => false]);
        DB::table('partner_location')->insert([
            ['id' => 1, 'partner_id' => 1, 'address_1' => 'Main branch', 'city' => 'Davao City', 'active' => true],
            ['id' => 2, 'partner_id' => 1, 'address_1' => 'Uptown branch', 'city' => 'Davao City', 'active' => true],
        ]);
        foreach (['cashier', 'kitchen'] as $role) {
            MerchantBranchUser::create(['user_id' => $users[$role]->id, 'partner_id' => 1, 'partner_location_id' => 1, 'active' => true]);
        }
        for ($i = 1; $i <= 9; $i++) {
            DB::table('partner_location_tables')->insert(['id' => $i, 'partner_location_id' => 1, 'name' => 'Table '.str_pad($i, 2, '0', STR_PAD_LEFT), 'capacity' => 4, 'active' => true, 'is_available' => true]);
        }
        DB::table('partner_location_tables')->insert(['id' => 20, 'partner_location_id' => 2, 'name' => 'Private Uptown Table', 'capacity' => 4, 'active' => true, 'is_available' => true]);
        DB::table('payment_method')->insert([
            ['id' => 1, 'title' => 'Card', 'active' => true, 'available_in_qr' => true],
            ['id' => 2, 'title' => 'GCash', 'active' => true, 'available_in_qr' => true],
            ['id' => 3, 'title' => 'Cash on Delivery', 'active' => true, 'available_in_qr' => true],
        ]);
        DB::table('products')->insert(['id' => 1, 'user_id' => $users['owner']->id, 'title' => 'Davao Chicken Inasal', 'price' => 165, 'price_comm' => 0, 'active' => true]);

        return $users;
    }

    public function diningOrder(int $table = 1, int $qty = 1, string $status = 'new'): Orders
    {
        $branch = $table === 20 ? 2 : 1;
        $cart = Cart::create([
            'partner_id' => 1, 'session_id' => (string) Str::uuid(),
            'partner_location_address_id' => $branch, 'fulfillment_type' => 'dine_in', 'dining_table_id' => $table,
        ]);
        $cart->forceFill(['order_no' => 'T'.str_pad($cart->id, 5, '0', STR_PAD_LEFT)])->save();
        $cart->details()->create(['item_id' => 1, 'qty' => $qty, 'price' => 165, 'instruction' => 'No onions', 'variance_content' => serialize([['name' => 'Extra rice']])]);
        $order = Orders::create(['cart_id' => $cart->id, 'partner_id' => 1, 'submitted_at' => now(), 'order_status_id' => 1]);
        $actor = User::where('email', 'kitchen@dining.test')->value('id');
        foreach (['preparing', 'ready', 'served'] as $next) {
            if ($status === 'new') {
                break;
            }
            app(DiningRoom::class)->transition($order->id, 1, $branch, $next, $actor);
            if ($next === $status) {
                break;
            }
        }

        return $order->fresh();
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        // Regular customer
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Categories
        $categories = collect([
            'Electronics' => ['Smartphones', 'Laptops', 'Accessories'],
            'Clothing' => ['Men', 'Women', 'Kids'],
            'Home & Garden' => ['Furniture', 'Kitchen', 'Decor'],
            'Sports' => ['Fitness', 'Outdoor', 'Team Sports'],
        ])->map(function ($children, $parentName, ) {
            static $sortOrder = 0;
            $parent = Category::factory()->create([
                'name' => $parentName,
                'sort_order' => $sortOrder++,
            ]);

            foreach ($children as $childName) {
                Category::factory()->create([
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'sort_order' => $sortOrder++,
                ]);
            }

            return $parent;
        });

        $allCategories = Category::whereNotNull('parent_id')->pluck('id')->toArray();

        // Sellers with shops and products
        $sellers = User::factory(5)->seller()->create();

        foreach ($sellers as $seller) {
            $shop = Shop::factory()->create([
                'user_id' => $seller->id,
            ]);

            $products = Product::factory(rand(8, 15))->create([
                'shop_id' => $shop->id,
                'category_id' => fn () => fake()->randomElement($allCategories),
            ]);

            // Some draft products
            Product::factory(rand(1, 3))->draft()->create([
                'shop_id' => $shop->id,
                'category_id' => fn () => fake()->randomElement($allCategories),
            ]);
        }

        // One pending shop
        $pendingSeller = User::factory()->seller()->create();
        Shop::factory()->pending()->create([
            'user_id' => $pendingSeller->id,
        ]);

        // Customers with orders
        $customers = User::factory(10)->create();
        $shops = Shop::where('status', 'approved')->with('products')->get();

        foreach ($customers as $customer) {
            $orderCount = rand(0, 4);

            for ($i = 0; $i < $orderCount; $i++) {
                $shop = $shops->random();
                $shopProducts = $shop->products->where('status', 'active');

                if ($shopProducts->isEmpty()) {
                    continue;
                }

                $orderProducts = $shopProducts->random(min(rand(1, 3), $shopProducts->count()));

                $subtotal = 0;
                $items = [];

                foreach ($orderProducts as $product) {
                    $qty = rand(1, 3);
                    $itemSubtotal = $product->price * $qty;
                    $subtotal += $itemSubtotal;

                    $items[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_image' => $product->primaryImage?->path,
                        'quantity' => $qty,
                        'unit_price' => $product->price,
                        'subtotal' => $itemSubtotal,
                    ];
                }

                $shippingFee = fake()->randomElement([0, 5.00, 10.00]);
                $status = fake()->randomElement(['pending', 'paid', 'processing', 'shipped', 'completed', 'completed']);

                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(\Illuminate\Support\Str::random(8)),
                    'user_id' => $customer->id,
                    'shop_id' => $shop->id,
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'total' => $subtotal + $shippingFee,
                    'shipping_name' => $customer->name,
                    'shipping_phone' => fake()->phoneNumber(),
                    'shipping_address' => fake()->address(),
                    'payment_method' => fake()->randomElement(['credit_card', 'bank_transfer']),
                    'paid_at' => in_array($status, ['paid', 'processing', 'shipped', 'completed']) ? now()->subDays(rand(1, 30)) : null,
                    'notes' => fake()->optional(0.2)->sentence(),
                ]);

                foreach ($items as $item) {
                    $order->items()->create($item);
                }
            }
        }
    }
}
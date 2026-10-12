<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Canned Goods' => [
                ['Argentina Corned Beef', '150', 'g', '48.00'],
                ['Century Tuna Flakes', '180', 'g', '43.00'],
                ['555 Sardines in Tomato Sauce', '155', 'g', '25.00'],
                ['Ligo Sardines in Green', '155', 'g', '25.00'],
                ['Spam Classic', '340', 'g', '180.00'],
                ['Luncheon Meat', '350', 'g', '80.00'],
                ['Whole Mushrooms', '400', 'g', '72.00'],
                ['Sweet Corn Kernels', '425', 'g', '48.00'],
                ['Fruit Cocktail', '836', 'g', '115.00'],
                ['Condensed Milk', '300', 'ml', '42.00'],
            ],
            'Frozen Foods' => [
                ['Chicken Nuggets', '500', 'g', '165.00'],
                ['Pork Siomai', '500', 'g', '125.00'],
                ['Beef Tapa', '500', 'g', '245.00'],
                ['French Fries', '1', 'kg', '185.00'],
                ['Mixed Vegetables', '500', 'g', '95.00'],
                ['Fish Fillet', '500', 'g', '160.00'],
                ['Pork Hotdogs', '1', 'kg', '185.00'],
                ['Pork Dumplings', '400', 'g', '135.00'],
                ['Beef Burger Patties', '500', 'g', '170.00'],
                ['Squid Rings', '500', 'g', '180.00'],
            ],
            'Dairy & milk' => [
                ['Fresh Milk', '1', 'l', '95.00'],
                ['Chocolate Milk', '250', 'ml', '25.00'],
                ['Yogurt Drink', '200', 'ml', '28.00'],
                ['Plain Yogurt', '500', 'g', '105.00'],
                ['Cheddar Cheese', '165', 'g', '79.00'],
                ['Salted Butter', '225', 'g', '115.00'],
                ['Margarine', '250', 'g', '45.00'],
                ['Cream Cheese', '165', 'g', '92.00'],
                ['Full Cream Milk Powder', '300', 'g', '138.00'],
                ['Evaporated Milk', '370', 'ml', '38.00'],
            ],
            'Beverages' => [
                ['Cola Drink', '1.5', 'l', '75.00'],
                ['Lemon-Lime Soda', '1.5', 'l', '72.00'],
                ['Orange Soda', '1.5', 'l', '72.00'],
                ['Lemon Iced Tea', '500', 'ml', '32.00'],
                ['Apple Juice', '1', 'l', '88.00'],
                ['Orange Juice', '1', 'l', '92.00'],
                ['Instant Coffee Mix', '200', 'g', '115.00'],
                ['Ground Coffee', '250', 'g', '175.00'],
                ['Green Tea Bags', '20', 'pcs', '95.00'],
                ['Energy Drink', '250', 'ml', '38.00'],
            ],
            'Rice & Rice Products' => [
                ['Jasmine Rice', '1', 'kg', '68.00'],
                ['Sinandomeng Rice', '1', 'kg', '62.00'],
                ['Dinorado Rice', '1', 'kg', '74.00'],
                ['Brown Rice', '1', 'kg', '82.00'],
                ['Glutinous Rice', '1', 'kg', '78.00'],
                ['Rice Flour', '500', 'g', '42.00'],
                ['Rice Vermicelli', '250', 'g', '35.00'],
                ['Rice Paper Wrappers', '200', 'g', '68.00'],
                ['Rice Porridge Mix', '50', 'g', '18.00'],
                ['Rice Cakes', '300', 'g', '55.00'],
            ],
            'Instant Noodles' => [
                ['Chicken Instant Noodles', '70', 'g', '15.00'],
                ['Beef Instant Noodles', '70', 'g', '15.00'],
                ['Hot and Spicy Instant Noodles', '75', 'g', '17.00'],
                ['Pancit Canton Original', '80', 'g', '16.00'],
                ['Pancit Canton Chilimansi', '80', 'g', '16.00'],
                ['Cup Noodles Seafood', '60', 'g', '32.00'],
                ['Cup Noodles Beef', '60', 'g', '32.00'],
                ['Instant Mami Noodles', '55', 'g', '12.00'],
                ['Instant Sotanghon Soup', '60', 'g', '18.00'],
                ['Instant La Paz Batchoy', '70', 'g', '20.00'],
            ],
            'Snacks' => [
                ['Potato Chips Original', '55', 'g', '35.00'],
                ['Barbecue Corn Chips', '100', 'g', '28.00'],
                ['Cheese Puffs', '60', 'g', '25.00'],
                ['Salted Peanuts', '100', 'g', '32.00'],
                ['Trail Mix', '150', 'g', '95.00'],
                ['Dried Mango Slices', '100', 'g', '78.00'],
                ['Chocolate Wafer', '40', 'g', '18.00'],
                ['Butter Cookies', '150', 'g', '45.00'],
                ['Salted Crackers', '250', 'g', '42.00'],
                ['Caramel Popcorn', '100', 'g', '38.00'],
            ],
            'Condiments & Sauces' => [
                ['Tomato Ketchup', '320', 'g', '48.00'],
                ['Banana Ketchup', '320', 'g', '42.00'],
                ['Soy Sauce', '350', 'ml', '28.00'],
                ['Cane Vinegar', '350', 'ml', '24.00'],
                ['Oyster Sauce', '300', 'g', '58.00'],
                ['Mayonnaise', '220', 'ml', '68.00'],
                ['Sweet Chili Sauce', '300', 'ml', '55.00'],
                ['Caesar Salad Dressing', '250', 'ml', '82.00'],
                ['Garlic Chili Oil', '150', 'ml', '65.00'],
                ['Mustard', '200', 'g', '52.00'],
            ],
            'Personal Care' => [
                ['Bath Soap', '135', 'g', '32.00'],
                ['Body Wash', '400', 'ml', '125.00'],
                ['Toothpaste', '150', 'g', '78.00'],
                ['Toothbrush', '1', 'pcs', '45.00'],
                ['Shampoo', '350', 'ml', '115.00'],
                ['Hair Conditioner', '350', 'ml', '118.00'],
                ['Deodorant Roll-On', '50', 'ml', '95.00'],
                ['Body Lotion', '250', 'ml', '135.00'],
                ['Facial Cleanser', '100', 'ml', '145.00'],
                ['Hand Sanitizer', '100', 'ml', '58.00'],
            ],
            'Household Cleaning' => [
                ['Dishwashing Liquid', '400', 'ml', '58.00'],
                ['Laundry Detergent Powder', '1', 'kg', '105.00'],
                ['Liquid Laundry Detergent', '1', 'l', '145.00'],
                ['Fabric Conditioner', '1', 'l', '98.00'],
                ['Bleach', '1', 'l', '52.00'],
                ['All-Purpose Cleaner', '500', 'ml', '88.00'],
                ['Glass Cleaner', '500', 'ml', '95.00'],
                ['Toilet Bowl Cleaner', '500', 'ml', '82.00'],
                ['Disinfectant Spray', '350', 'ml', '125.00'],
                ['Scrub Sponge', '3', 'pcs', '35.00'],
            ],
        ];

        $sampleCount = array_sum(array_map('count', $groups));
        if ($sampleCount !== 100) {
            throw new RuntimeException("Expected 100 sample products, found {$sampleCount}.");
        }

        DB::transaction(function () use ($groups): void {
            $categories = Category::all()->keyBy(fn (Category $category) => Str::lower(trim($category->name)));
            $dairyCategory = $categories->get('dairy & milk') ?? $categories->get('dairy');
            if ($dairyCategory) {
                $categories->put('dairy & milk', $dairyCategory);
                $categories->put('dairy', $dairyCategory);
            }

            $existingProducts = Product::all()->mapWithKeys(fn (Product $product) => [
                $this->productKey($product->category_id, $product->name, $product->size, $product->unit) => true,
            ]);

            foreach ($groups as $categoryName => $products) {
                $category = $categories->get(Str::lower(trim($categoryName)));
                if (! $category) {
                    throw new RuntimeException("Missing product sample category: {$categoryName}.");
                }

                foreach ($products as [$name, $size, $unit, $price]) {
                    $key = $this->productKey($category->id, $name, $size, $unit);
                    if ($existingProducts->has($key)) {
                        continue;
                    }

                    Product::create([
                        'category_id' => $category->id,
                        'name' => $name,
                        'size' => $size,
                        'unit' => $unit,
                        'barcode' => null,
                        'quantity' => 0,
                        'price' => $price,
                        'expiry_date' => null,
                    ]);

                    $existingProducts->put($key, true);
                }
            }
        });
    }

    private function productKey(int $categoryId, string $name, ?string $size, ?string $unit): string
    {
        return implode('|', [
            $categoryId,
            Str::lower(trim($name)),
            Str::lower(trim((string) $size)),
            Str::lower(trim((string) $unit)),
        ]);
    }
}

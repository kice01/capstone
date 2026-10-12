<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreCategorySeeder extends Seeder
{
    /**
     * Add a practical grocery and convenience-store category catalog.
     * Existing categories and their IDs are kept; blank descriptions are filled in.
     */
    public function run(): void
    {
        $categories = [
            'Canned Goods' => 'Canned and preserved foods such as vegetables, meat, fish, and soups.',
            'Frozen Foods' => 'Foods stored frozen, including ready meals, meat, seafood, and vegetables.',
            'Dairy' => 'Milk, cream, butter, cheese, yogurt, and other refrigerated dairy products.',
            'Beverages' => 'Packaged drinks that do not belong to a more specific beverage category.',
            'Bottled Water' => 'Packaged drinking water, mineral water, and purified water.',
            'Soft Drinks' => 'Carbonated soft drinks, soda, and sparkling flavored drinks.',
            'Juice & Juice Drinks' => 'Fruit juices, juice drinks, and other non-carbonated fruit beverages.',
            'Coffee & Tea' => 'Coffee, tea, cocoa, and related hot beverage products.',
            'Energy & Sports Drinks' => 'Energy drinks, electrolyte drinks, and sports hydration beverages.',
            'Fresh Fruits' => 'Fresh whole fruits sold individually or in packs.',
            'Fresh Vegetables' => 'Fresh vegetables, roots, and other uncooked produce.',
            'Meat' => 'Fresh or chilled beef, pork, and other non-poultry meats.',
            'Poultry' => 'Chicken, turkey, and other poultry products.',
            'Seafood' => 'Fresh, chilled, or packaged fish and shellfish.',
            'Bread & Bakery' => 'Bread, rolls, pastries, cakes, and other baked goods.',
            'Breakfast Foods' => 'Breakfast mixes, spreads, and ready-to-eat morning foods.',
            'Cereals & Grains' => 'Breakfast cereals, oats, and grain products other than rice or pasta.',
            'Rice & Rice Products' => 'Rice, rice flour, and packaged rice-based foods.',
            'Pasta & Pasta Sauce' => 'Dried or fresh pasta and sauces intended for pasta dishes.',
            'Instant Noodles' => 'Instant noodle packs, cups, and quick-cook noodle meals.',
            'Cooking Oils' => 'Cooking oils, shortening, and other cooking fats.',
            'Condiments & Sauces' => 'Ketchup, soy sauce, vinegar, dressings, dips, and table sauces.',
            'Spices & Seasonings' => 'Herbs, spices, salt, pepper, and seasoning blends.',
            'Snacks' => 'Packaged savory snacks that do not fit a more specific snack category.',
            'Chips & Crisps' => 'Potato chips, corn chips, and similar crunchy snack foods.',
            'Candy & Chocolate' => 'Chocolate bars, sweets, chewing gum, and confectionery.',
            'Biscuits & Cookies' => 'Sweet biscuits, cookies, wafers, and packaged baked snacks.',
            'Crackers' => 'Savory crackers and crispbread products.',
            'Nuts & Dried Fruits' => 'Packaged nuts, seeds, and dried fruit snacks.',
            'Ready-to-Eat Meals' => 'Prepared meals and foods that can be eaten with little or no cooking.',
            'Ice Cream & Frozen Desserts' => 'Ice cream, frozen treats, and other frozen desserts.',
            'Eggs' => 'Fresh shell eggs and packaged egg products.',
            'Baby Food' => 'Infant and toddler foods, cereals, purees, and formula.',
            'Baby Care' => 'Diapers, wipes, and other everyday baby-care products.',
            'Personal Care' => 'Everyday personal hygiene and grooming products.',
            'Oral Care' => 'Toothpaste, toothbrushes, mouthwash, and dental-care products.',
            'Hair Care' => 'Shampoo, conditioner, hair treatments, and styling products.',
            'Skin Care' => 'Soaps, lotions, moisturizers, and other skin-care products.',
            'Household Cleaning' => 'Surface cleaners, dishwashing products, and household disinfectants.',
            'Laundry Supplies' => 'Laundry detergent, fabric softener, bleach, and stain removers.',
            'Paper Products' => 'Tissue, toilet paper, paper towels, and disposable paper goods.',
            'Pet Food' => 'Packaged food and treats for household pets.',
            'School & Office Supplies' => 'Pens, paper, notebooks, and basic school or office supplies.',
            'Kitchen Supplies' => 'Food storage, utensils, and small everyday kitchen essentials.',
            'Baking Ingredients' => 'Flour, sugar, baking mixes, and other ingredients for baking.',
            'Health & Wellness' => 'Non-prescription wellness and first-aid essentials.',
            'Vitamins & Supplements' => 'Packaged vitamins, minerals, and dietary supplements.',
            'Household Essentials' => 'Useful household items that do not fit cleaning or paper categories.',
            'Home & Garden' => 'Basic home, garden, and outdoor care products.',
            'Party & Gift Supplies' => 'Gift wrap, greeting cards, decorations, and party supplies.',
        ];

        DB::transaction(function () use ($categories): void {
            $existing = Category::all()->keyBy(fn (Category $category) => Str::lower(trim($category->name)));
            $aliases = [
                'Dairy' => 'Dairy & milk',
            ];

            foreach ($categories as $name => $description) {
                $key = Str::lower(trim($name));
                $aliasKey = isset($aliases[$name]) ? Str::lower(trim($aliases[$name])) : null;
                $category = ($aliasKey ? $existing->get($aliasKey) : null) ?? $existing->get($key);

                if (! $category) {
                    $category = Category::create(['name' => $name, 'description' => $description]);
                    $existing->put($key, $category);
                    continue;
                }

                if (blank($category->description)) {
                    $category->update(['description' => $description]);
                }
            }
        });
    }
}

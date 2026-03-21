<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Expense categories (16)
            ['name' => ['ru' => 'Продукты', 'en' => 'Groceries'], 'type' => TransactionType::Expense, 'icon' => '🛒', 'color' => '#10B981', 'sort_order' => 1],
            ['name' => ['ru' => 'Кафе и рестораны', 'en' => 'Dining out'], 'type' => TransactionType::Expense, 'icon' => '🍽️', 'color' => '#F59E0B', 'sort_order' => 2],
            ['name' => ['ru' => 'Транспорт', 'en' => 'Transport'], 'type' => TransactionType::Expense, 'icon' => '🚗', 'color' => '#3B82F6', 'sort_order' => 3],
            ['name' => ['ru' => 'Жильё', 'en' => 'Housing'], 'type' => TransactionType::Expense, 'icon' => '🏠', 'color' => '#8B5CF6', 'sort_order' => 4],
            ['name' => ['ru' => 'Коммунальные', 'en' => 'Utilities'], 'type' => TransactionType::Expense, 'icon' => '💡', 'color' => '#EC4899', 'sort_order' => 5],
            ['name' => ['ru' => 'Здоровье', 'en' => 'Health'], 'type' => TransactionType::Expense, 'icon' => '💊', 'color' => '#EF4444', 'sort_order' => 6],
            ['name' => ['ru' => 'Красота', 'en' => 'Beauty'], 'type' => TransactionType::Expense, 'icon' => '💅', 'color' => '#F472B6', 'sort_order' => 7],
            ['name' => ['ru' => 'Одежда', 'en' => 'Clothing'], 'type' => TransactionType::Expense, 'icon' => '👕', 'color' => '#6366F1', 'sort_order' => 8],
            ['name' => ['ru' => 'Образование', 'en' => 'Education'], 'type' => TransactionType::Expense, 'icon' => '📚', 'color' => '#0EA5E9', 'sort_order' => 9],
            ['name' => ['ru' => 'Развлечения', 'en' => 'Entertainment'], 'type' => TransactionType::Expense, 'icon' => '🎮', 'color' => '#A855F7', 'sort_order' => 10],
            ['name' => ['ru' => 'Подписки', 'en' => 'Subscriptions'], 'type' => TransactionType::Expense, 'icon' => '📱', 'color' => '#14B8A6', 'sort_order' => 11],
            ['name' => ['ru' => 'Подарки', 'en' => 'Gifts'], 'type' => TransactionType::Expense, 'icon' => '🎁', 'color' => '#F43F5E', 'sort_order' => 12],
            ['name' => ['ru' => 'Путешествия', 'en' => 'Travel'], 'type' => TransactionType::Expense, 'icon' => '✈️', 'color' => '#0284C7', 'sort_order' => 13],
            ['name' => ['ru' => 'Домашние животные', 'en' => 'Pets'], 'type' => TransactionType::Expense, 'icon' => '🐾', 'color' => '#D97706', 'sort_order' => 14],
            ['name' => ['ru' => 'Связь и интернет', 'en' => 'Phone & Internet'], 'type' => TransactionType::Expense, 'icon' => '📶', 'color' => '#7C3AED', 'sort_order' => 15],
            ['name' => ['ru' => 'Прочие расходы', 'en' => 'Other expenses'], 'type' => TransactionType::Expense, 'icon' => '📦', 'color' => '#6B7280', 'sort_order' => 16],

            // Income categories (4)
            ['name' => ['ru' => 'Зарплата', 'en' => 'Salary'], 'type' => TransactionType::Income, 'icon' => '💰', 'color' => '#22C55E', 'sort_order' => 1],
            ['name' => ['ru' => 'Фриланс', 'en' => 'Freelance'], 'type' => TransactionType::Income, 'icon' => '💻', 'color' => '#06B6D4', 'sort_order' => 2],
            ['name' => ['ru' => 'Инвестиции', 'en' => 'Investments'], 'type' => TransactionType::Income, 'icon' => '📈', 'color' => '#8B5CF6', 'sort_order' => 3],
            ['name' => ['ru' => 'Прочие доходы', 'en' => 'Other income'], 'type' => TransactionType::Income, 'icon' => '💵', 'color' => '#10B981', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name->ru' => $category['name']['ru'], 'is_system' => true],
                [
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'is_system' => true,
                    'sort_order' => $category['sort_order'],
                ],
            );
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $faqs = [
        [
            'question' => 'Do you deliver fresh flowers in Hyderabad?',
            'answer' => 'Yes, Manidvipa Flowers delivers fresh flowers, puja flowers, garlands and flower gifts across serviceable Hyderabad areas.',
        ],
        [
            'question' => 'Can I order flowers for daily puja?',
            'answer' => 'Yes, you can order daily puja flowers, leaves and custom puja flower boxes based on availability.',
        ],
        [
            'question' => 'How do I confirm flower availability?',
            'answer' => 'Flower availability changes daily. You can check the product page or contact Manidvipa Flowers on WhatsApp before placing a time-sensitive order.',
        ],
        [
            'question' => 'Can I request bulk flowers or decoration support?',
            'answer' => 'Yes, bulk flowers, garlands and decoration support can be requested for homes, temples, weddings and events.',
        ],
    ];

    public function up(): void
    {
        foreach ($this->faqs as $faq) {
            DB::table('faqs')->updateOrInsert(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'status' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('faqs')
            ->whereIn('question', array_column($this->faqs, 'question'))
            ->delete();
    }
};

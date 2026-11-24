<?php

declare(strict_types=1);

namespace Modules\Situs\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optional English and Arabic versions of the school's website texts (D-109): questions and answers
 * (topic, question, answer) and the short news. Empty means the Indonesian text shows.
 */
class AddTranslationsToSiteContent extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('site_questions', [
            'topic_en'    => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'answer'],
            'topic_ar'    => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'topic_en'],
            'question_en' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'topic_ar'],
            'question_ar' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'question_en'],
            'answer_en'   => ['type' => 'TEXT', 'null' => true, 'after' => 'question_ar'],
            'answer_ar'   => ['type' => 'TEXT', 'null' => true, 'after' => 'answer_en'],
        ]);

        $this->forge->addColumn('site_news', [
            'text_en' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'text'],
            'text_ar' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'text_en'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('site_questions', ['topic_en', 'topic_ar', 'question_en', 'question_ar', 'answer_en', 'answer_ar']);
        $this->forge->dropColumn('site_news', ['text_en', 'text_ar']);
    }
}

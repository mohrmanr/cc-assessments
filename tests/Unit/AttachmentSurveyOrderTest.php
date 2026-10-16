<?php

namespace Tests\Unit;

use App\Support\AttachmentSurvey;
use PHPUnit\Framework\TestCase;

class AttachmentSurveyOrderTest extends TestCase
{
    public function test_sections_are_general_then_mother_then_father(): void
    {
        $items = [
            ['id' => 'mother_1', 'target' => 'mother', 'text' => 'Mother'],
            ['id' => 'father_1', 'target' => 'father', 'text' => 'Father'],
            ['id' => 'general_1', 'target' => 'general', 'text' => 'General'],
        ];

        $this->assertSame(
            ['general', 'mother', 'father'],
            array_keys(AttachmentSurvey::groupByTarget($items))
        );
    }

    public function test_partner_and_best_friend_items_are_dropped(): void
    {
        $items = [
            ['id' => 'partner_1', 'target' => 'partner', 'text' => 'Partner'],
            ['id' => 'best_friend_1', 'target' => 'best_friend', 'text' => 'Best friend'],
            ['id' => 'mother_1', 'target' => 'mother', 'text' => 'Mother'],
        ];

        $kept = AttachmentSurvey::withoutRetiredTargets($items);

        $this->assertSame(['mother_1'], array_column($kept, 'id'));
    }
}

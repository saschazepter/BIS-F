<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\AdminNotification\SendAdminNotification;
use App\Models\User;
use App\Services\Event\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\FeatureTestCase;

class EventSuggestionTelegramTest extends FeatureTestCase
{
    use RefreshDatabase, WithFaker;

    public function test_suggestion_dispatches_admin_notification_job(): void
    {
        Queue::fake();

        $suggestion = new EventService()->suggestEvent(
            user: User::factory()->create(),
            name: $this->faker->name,
            begin: now(),
            end: now()->addDay(),
        );

        Queue::assertPushed(SendAdminNotification::class);
        $this->assertDatabaseHas('event_suggestions', ['id' => $suggestion->id]);
    }

    public function test_suggestion_is_persisted_even_when_no_notification_channels_configured(): void
    {
        Queue::fake();

        $suggestion = new EventService()->suggestEvent(
            user: User::factory()->create(),
            name: $this->faker->name,
            begin: now(),
            end: now()->addDay(),
        );

        $this->assertDatabaseHas('event_suggestions', ['id' => $suggestion->id]);
    }
}

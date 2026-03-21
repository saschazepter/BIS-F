<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enum\EventRejectionReason;
use App\Models\Event;
use App\Models\EventSuggestion;
use App\Models\Station;
use App\Models\User;
use App\Notifications\EventSuggestionProcessed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\FeatureTestCase;

class AdminEventControllerTest extends FeatureTestCase
{
    use RefreshDatabase;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moderator = User::factory()->create();
        $this->moderator->givePermissionTo([
            'view-backend',
            'view-events',
            'accept-events',
            'deny-events',
            'create-events',
            'update-events',
            'delete-events',
        ]);
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('admin.events'))->assertRedirect();
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.events'))
            ->assertForbidden();
    }

    public function test_index_renders_events_in_correct_buckets(): void
    {
        $future = Event::factory()->create(['checkin_start' => now()->addDays(5)->toDateString(), 'checkin_end' => now()->addDays(10)->toDateString()]);
        $current = Event::factory()->create(['checkin_start' => now()->subDay()->toDateString(), 'checkin_end' => now()->addDay()->toDateString()]);
        $past = Event::factory()->create(['checkin_start' => now()->subDays(10)->toDateString(), 'checkin_end' => now()->subDay()->toDateString()]);

        $response = $this->actingAs($this->moderator)->get(route('admin.events'));

        $response->assertOk();
        $response->assertViewHas('events_future', fn ($p) => $p->contains('id', $future->id));
        $response->assertViewHas('events_current', fn ($p) => $p->contains('id', $current->id));
        $response->assertViewHas('events_past', fn ($p) => $p->contains('id', $past->id));
    }

    public function test_index_filters_by_search_query(): void
    {
        $match = Event::factory()->create(['name' => 'Berliner Bahnhofsfest', 'checkin_start' => now()->addDay()->toDateString(), 'checkin_end' => now()->addDays(3)->toDateString()]);
        $noMatch = Event::factory()->create(['name' => 'Hamburger Hafengeburtstag', 'checkin_start' => now()->addDay()->toDateString(), 'checkin_end' => now()->addDays(3)->toDateString()]);

        $response = $this->actingAs($this->moderator)->get(route('admin.events', ['query' => 'Berliner']));

        $response->assertViewHas('events_future', fn ($p) => $p->contains('id', $match->id));
        $response->assertViewHas('events_future', fn ($p) => !$p->contains('id', $noMatch->id));
    }

    public function test_suggestions_page_shows_open_suggestions(): void
    {
        $open = EventSuggestion::factory()->create(['processed' => false, 'end' => now()->addDays(5)->toDateString()]);
        $closed = EventSuggestion::factory()->create(['processed' => true, 'end' => now()->addDays(5)->toDateString()]);

        $response = $this->actingAs($this->moderator)->get(route('admin.events.suggestions'));

        $response->assertOk();
        $response->assertViewHas('suggestions', fn ($s) => $s->contains('id', $open->id) && !$s->contains('id', $closed->id));
    }

    public function test_suggestion_creation_page_renders(): void
    {
        $suggestion = EventSuggestion::factory()->create(['processed' => false]);

        $this->actingAs($this->moderator)
            ->get(route('admin.events.suggestions.accept', $suggestion->id))
            ->assertOk()
            ->assertViewHas('eventSuggestion', fn ($s) => $s->id === $suggestion->id);
    }

    public function test_deny_suggestion_marks_as_processed_and_notifies_user(): void
    {
        Notification::fake();
        config(['services.telegram.admin.active' => false]);

        $suggestion = EventSuggestion::factory()->create(['processed' => false]);

        $this->actingAs($this->moderator)
            ->post(route('admin.events.suggestions.deny'), [
                'id' => $suggestion->id,
                'rejectionReason' => EventRejectionReason::DEFAULT->value,
            ])
            ->assertRedirect(route('admin.events.suggestions'));

        $this->assertDatabaseHas('event_suggestions', ['id' => $suggestion->id, 'processed' => true]);
        Notification::assertSentTo($suggestion->user, EventSuggestionProcessed::class);
    }

    public function test_deny_suggestion_requires_valid_rejection_reason(): void
    {
        $suggestion = EventSuggestion::factory()->create(['processed' => false]);

        $this->actingAs($this->moderator)
            ->post(route('admin.events.suggestions.deny'), [
                'id' => $suggestion->id,
                'rejectionReason' => 'invalid-reason',
            ])
            ->assertSessionHasErrors('rejectionReason');
    }

    public function test_accept_suggestion_creates_event_and_marks_processed(): void
    {
        Notification::fake();
        config(['services.telegram.admin.active' => false]);

        $suggestion = EventSuggestion::factory()->create(['processed' => false]);

        $this->actingAs($this->moderator)
            ->post(route('admin.events.suggestions.accept.do'), [
                'suggestionId' => $suggestion->id,
                'name' => 'Accepted Event',
                'begin' => now()->addDays(1)->toDateString(),
                'end' => now()->addDays(5)->toDateString(),
            ])
            ->assertRedirect(route('admin.events.suggestions'));

        $this->assertDatabaseHas('events', ['name' => 'Accepted Event']);
        $this->assertDatabaseHas('event_suggestions', ['id' => $suggestion->id, 'processed' => true]);
        Notification::assertSentTo($suggestion->user, EventSuggestionProcessed::class);
    }

    public function test_user_cannot_accept_own_suggestion(): void
    {
        $suggestion = EventSuggestion::factory()->create([
            'processed' => false,
            'user_id' => $this->moderator->id,
        ]);

        $this->actingAs($this->moderator)
            ->post(route('admin.events.suggestions.accept.do'), [
                'suggestionId' => $suggestion->id,
                'name' => 'Self-accepted',
                'begin' => now()->addDay()->toDateString(),
                'end' => now()->addDays(5)->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('events', ['name' => 'Self-accepted']);
    }

    public function test_create_event_stores_record_and_redirects(): void
    {
        $this->actingAs($this->moderator)
            ->post('/admin/events/create', [
                'name' => 'New Test Event',
                'checkin_start' => now()->addDay()->toDateString(),
                'checkin_end' => now()->addDays(5)->toDateString(),
            ])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseHas('events', ['name' => 'New Test Event']);
    }

    public function test_create_event_validates_required_fields(): void
    {
        $this->actingAs($this->moderator)
            ->post('/admin/events/create', [])
            ->assertSessionHasErrors(['name', 'checkin_start', 'checkin_end']);
    }

    public function test_edit_updates_event_and_redirects(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->moderator)
            ->post("/admin/events/edit/{$event->id}", [
                'name' => 'Updated Name',
                'checkin_start' => now()->addDay()->toDateString(),
                'checkin_end' => now()->addDays(5)->toDateString(),
            ])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Updated Name']);
    }

    public function test_render_edit_page_returns_view(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->moderator)
            ->get(route('admin.events.edit', $event->id))
            ->assertOk()
            ->assertViewHas('event', fn ($e) => $e->id === $event->id);
    }

    public function test_edit_keeps_existing_station_when_name_unchanged(): void
    {
        $station = Station::factory()->create(['name' => 'Testbahnhof']);
        $event = Event::factory()->create(['station_id' => $station->id]);

        $this->actingAs($this->moderator)
            ->post("/admin/events/edit/{$event->id}", [
                'name' => 'Updated Name',
                'checkin_start' => now()->addDay()->toDateString(),
                'checkin_end' => now()->addDays(5)->toDateString(),
                'nearest_station_name' => 'Testbahnhof',
            ])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseHas('events', ['id' => $event->id, 'station_id' => $station->id]);
    }

    public function test_create_returns_error_when_station_not_found(): void
    {
        Http::fake(['https://api.transitous.org/*' => Http::response([], 200)]);

        $this->actingAs($this->moderator)
            ->post('/admin/events/create', [
                'name' => 'Event With Unknown Station',
                'checkin_start' => now()->addDay()->toDateString(),
                'checkin_end' => now()->addDays(5)->toDateString(),
                'nearest_station_name' => 'Nirgendwo Hbf',
            ])
            ->assertRedirect()
            ->assertSessionHas('alert-danger');

        $this->assertDatabaseMissing('events', ['name' => 'Event With Unknown Station']);
    }

    public function test_edit_returns_error_when_station_not_found(): void
    {
        Http::fake(['https://api.transitous.org/*' => Http::response([], 200)]);

        $event = Event::factory()->create(['station_id' => null]);

        $this->actingAs($this->moderator)
            ->post("/admin/events/edit/{$event->id}", [
                'name' => 'Updated',
                'checkin_start' => now()->addDay()->toDateString(),
                'checkin_end' => now()->addDays(5)->toDateString(),
                'nearest_station_name' => 'Nirgendwo Hbf',
            ])
            ->assertRedirect()
            ->assertSessionHas('alert-danger');
    }

    public function test_accept_suggestion_returns_error_when_station_not_found(): void
    {
        Http::fake(['https://api.transitous.org/*' => Http::response([], 200)]);

        $suggestion = EventSuggestion::factory()->create(['processed' => false]);

        $this->actingAs($this->moderator)
            ->post(route('admin.events.suggestions.accept.do'), [
                'suggestionId' => $suggestion->id,
                'name' => 'Event With Unknown Station',
                'begin' => now()->addDay()->toDateString(),
                'end' => now()->addDays(5)->toDateString(),
                'nearest_station_name' => 'Nirgendwo Hbf',
            ])
            ->assertRedirect()
            ->assertSessionHas('alert-danger');

        $this->assertDatabaseMissing('events', ['name' => 'Event With Unknown Station']);
    }

    public function test_delete_event_removes_record_and_redirects(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->moderator)
            ->post(route('admin.events.delete'), ['id' => $event->id])
            ->assertRedirect(route('admin.events'));

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_delete_event_requires_existing_event(): void
    {
        $this->actingAs($this->moderator)
            ->post(route('admin.events.delete'), ['id' => 99999])
            ->assertSessionHasErrors('id');
    }
}

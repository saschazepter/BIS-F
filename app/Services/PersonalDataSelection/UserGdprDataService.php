<?php

namespace App\Services\PersonalDataSelection;

use App\Http\Controllers\Backend\User\TokenController;
use App\Models\Event;
use App\Models\EventSuggestion;
use App\Models\Mention;
use App\Models\User;
use App\Models\WebhookCreationRequest;
use Illuminate\Support\Facades\DB;
use Spatie\PersonalDataExport\PersonalDataSelection;

class UserGdprDataService
{
    public function __invoke(PersonalDataSelection $personalDataSelection, User $data): void {
        $this->addUserPersonalData($personalDataSelection, $data);
    }

    private function addUserPersonalData(PersonalDataSelection $personalDataSelection, User $data): void {
        $user                      = $data->toArray();
        $user['email']             = $data->email;
        $user['email_verified_at'] = $data->email_verified_at;
        $user['privacy_ack_at']    = $data->privacy_ack_at;
        $user['last_login']        = $data->last_login;
        $user['created_at']        = $data->created_at;
        $user['updated_at']        = $data->updated_at;

        $webhooks = $data->webhooks()->with('events')->get();
        $webhooks = $webhooks->map(function($webhook) {
            $webhook['created_at'] = $webhook->created_at;
            $webhook['updated_at'] = $webhook->updated_at;
            $webhook['client_id']  = (int) $webhook->oauth_client_id ?? null;
            unset($webhook['url']);
            return $webhook;
        });


        if ($data->avatar && file_exists(public_path('/uploads/avatars/' . $data->avatar))) {
            $personalDataSelection
                ->addFile(public_path('/uploads/avatars/' . $data->avatar));
        }

        $personalDataSelection
            ->add('user.json', $user)
            ->add('notifications.json', $data->notifications()->get()->toJson())
            ->add('likes.json', $data->likes()->get()->toJson())
            ->add('social_profile.json', $data->socialProfile()->with('mastodonserver')->get())
            ->add('event_suggestions.json', EventSuggestion::where('user_id', $data->id)->get()->toJson())
            ->add('events.json', Event::where('approved_by', $data->id)->get()->toJson())
            ->add('webhooks.json', $webhooks)
            ->add(
                'webhook_creation_requests.json',
                WebhookCreationRequest::where('user_id', $data->id)->get()->toJson()
            )
            ->add('tokens.json', TokenController::index($data)->toJson())
            ->add('ics_tokens.json', $data->icsTokens()->get()->toJson())
            ->add(
                'password_resets.json',
                DB::table('password_resets')->select(['email', 'created_at'])->where('email', $data->email)->get()
            )
            ->add('apps.json', $data->oAuthClients()->get()->toJson())
            ->add('follows.json', DB::table('follows')->where('user_id', $data->id)->get())
            ->add('followings.json', DB::table('follows')->where('follow_id', $data->id)->get())
            ->add('blocks.json', DB::table('user_blocks')->where('user_id', $data->id)->get())
            ->add('mutes.json', DB::table('user_mutes')->where('user_id', $data->id)->get())
            ->add('muted_by.json', DB::table('user_mutes')->where('muted_id', $data->id)->get())
            ->add('follow_requests.json', DB::table('follow_requests')->where('user_id', $data->id)->get())
            ->add('follows_requests.json', DB::table('follow_requests')->where('follow_id', $data->id)->get())
            ->add('sessions.json', $data->sessions()->get()->toJson())
            ->add('home.json', $data->home()->get()->toJson())
            ->add('hafas_trips.json', DB::table('hafas_trips')->where('user_id', $data->id)->get())
            ->add('mentions.json', Mention::where('mentioned_id', $data->id)->get()->toJson())
            ->add('roles.json', $data->roles()->get()->toJson())
            ->add(
                'activity_log.json',
                DB::table('activity_log')->where('causer_type', get_class($data))->where('causer_id', $data->id)->get()
            )
            ->add('permissions.json', $data->permissions()->get()->toJson())
            ->add('statuses.json', $data->statuses()->with('tags')->get())
            ->add(
                'reports.json',
                DB::table('reports')
                  ->select('subject_type', 'subject_id', 'reason', 'description', 'reporter_id')
                  ->where('reporter_id', $data->id)
                  ->get()
            );
    }
}

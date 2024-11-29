<?php

namespace App\Services\PersonalDataSelection;

use App\Http\Controllers\Backend\User\TokenController;
use App\Models\User;
use App\Services\PersonalDataSelection\Exporters\ActivityLogExporter;
use App\Services\PersonalDataSelection\Exporters\Base\Exporter;
use App\Services\PersonalDataSelection\Exporters\BlocksExporter;
use App\Services\PersonalDataSelection\Exporters\EventExporter;
use App\Services\PersonalDataSelection\Exporters\EventSuggestionsExporter;
use App\Services\PersonalDataSelection\Exporters\FollowingsExporter;
use App\Services\PersonalDataSelection\Exporters\FollowRequestsExporter;
use App\Services\PersonalDataSelection\Exporters\FollowsExporter;
use App\Services\PersonalDataSelection\Exporters\FollowsRequestsExporter;
use App\Services\PersonalDataSelection\Exporters\HafasTripsExporter;
use App\Services\PersonalDataSelection\Exporters\MentionExporter;
use App\Services\PersonalDataSelection\Exporters\MutesExporter;
use App\Services\PersonalDataSelection\Exporters\PasswordResetsExporter;
use App\Services\PersonalDataSelection\Exporters\ReportsExporter;
use App\Services\PersonalDataSelection\Exporters\TrustedUsersExporter;
use App\Services\PersonalDataSelection\Exporters\WebhookCreationRequestExporter;
use Spatie\PersonalDataExport\PersonalDataSelection;

class UserGdprDataService
{
    public function __invoke(PersonalDataSelection $personalDataSelection, User $data): void {
        $this->addUserPersonalData($personalDataSelection, $data);
    }

    private function addUserPersonalData(PersonalDataSelection $personalDataSelection, User $userModel): void {
        $userData = $userModel->only([
                                         'name', 'username', 'home_id', 'private_profile', 'default_status_visibility',
                                         'default_status_sensitivity', 'prevent_index', 'privacy_hide_days', 'language',
                                         'timezone', 'friend_checkin', 'likes_enabled', 'points_enabled', 'mapprovider',
                                         'email', 'email_verified_at', 'privacy_ack_at',
                                         'last_login', 'created_at', 'updated_at'
                                     ]);

        $webhooks = $userModel->webhooks()->with('events')->get();
        $webhooks = $webhooks->map(function($webhook) {
            return $webhook->only([
                                      'oauth_client_id', 'created_at', 'updated_at'
                                  ]);
        });


        if ($userModel->avatar && file_exists(public_path('/uploads/avatars/' . $userModel->avatar))) {
            $personalDataSelection
                ->addFile(public_path('/uploads/avatars/' . $userModel->avatar));
        }

        $personalDataSelection
            ->add('user.json', $userData)
            ->add('notifications.json', $userModel->notifications()->get()->toJson()) //TODO: columns definieren
            ->add('likes.json', $userModel->likes()->get()->toJson()) //TODO: columns definieren
            ->add('social_profile.json', $userModel->socialProfile()->with('mastodonserver')->get()) //TODO: columns definieren
            ->add('webhooks.json', $webhooks)
            ->add('tokens.json', TokenController::index($userModel)->toJson()) //TODO: columns definieren
            ->add('ics_tokens.json', $userModel->icsTokens()->get()->toJson())                                          //TODO: columns definieren
            ->add('apps.json', $userModel->oAuthClients()->get()->toJson()) //TODO: columns definieren
            ->add('sessions.json', $userModel->sessions()->get()->toJson()) //TODO: columns definieren
            ->add('home.json', $userModel->home()->get()->toJson())                                                               //TODO: columns definieren
            ->add('roles.json', $userModel->roles()->get()->toJson())                                                             //TODO: columns definieren
            ->add('permissions.json', $userModel->permissions()->get()->toJson()) //TODO: columns definieren
        ;
        $exporter = new Exporter($personalDataSelection, $userModel);
        $exporter->export([
                              //StatusExporter::class,
                              FollowRequestsExporter::class,
                              FollowsRequestsExporter::class,
                              FollowsExporter::class,
                              FollowingsExporter::class,
                              HafasTripsExporter::class,
                              BlocksExporter::class,
                              MutesExporter::class,
                              ReportsExporter::class,
                              TrustedUsersExporter::class,
                              ActivityLogExporter::class,
                              PasswordResetsExporter::class,
                              EventExporter::class,
                              EventSuggestionsExporter::class,
                              WebhookCreationRequestExporter::class,
                              MentionExporter::class,
                          ]);
    }
}

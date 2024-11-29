<?php

namespace App\Services\PersonalDataSelection;

use App\Models\User;
use App\Services\PersonalDataSelection\Exporters\ActivityLogExporter;
use App\Services\PersonalDataSelection\Exporters\AppsExporter;
use App\Services\PersonalDataSelection\Exporters\Base\Exporter;
use App\Services\PersonalDataSelection\Exporters\BlocksExporter;
use App\Services\PersonalDataSelection\Exporters\EventExporter;
use App\Services\PersonalDataSelection\Exporters\EventSuggestionsExporter;
use App\Services\PersonalDataSelection\Exporters\FollowingsExporter;
use App\Services\PersonalDataSelection\Exporters\FollowRequestsExporter;
use App\Services\PersonalDataSelection\Exporters\FollowsExporter;
use App\Services\PersonalDataSelection\Exporters\FollowsRequestsExporter;
use App\Services\PersonalDataSelection\Exporters\HafasTripsExporter;
use App\Services\PersonalDataSelection\Exporters\HomeExporter;
use App\Services\PersonalDataSelection\Exporters\IcsTokenExporter;
use App\Services\PersonalDataSelection\Exporters\LikesExporter;
use App\Services\PersonalDataSelection\Exporters\MentionExporter;
use App\Services\PersonalDataSelection\Exporters\MutesExporter;
use App\Services\PersonalDataSelection\Exporters\NotificationsExporter;
use App\Services\PersonalDataSelection\Exporters\PasswordResetsExporter;
use App\Services\PersonalDataSelection\Exporters\PermissionExporter;
use App\Services\PersonalDataSelection\Exporters\ReportsExporter;
use App\Services\PersonalDataSelection\Exporters\RoleExporter;
use App\Services\PersonalDataSelection\Exporters\SessionExporter;
use App\Services\PersonalDataSelection\Exporters\SocialProfileExporter;
use App\Services\PersonalDataSelection\Exporters\StatusExporter;
use App\Services\PersonalDataSelection\Exporters\TokenExporter;
use App\Services\PersonalDataSelection\Exporters\TrustedUsersExporter;
use App\Services\PersonalDataSelection\Exporters\UserDataExporter;
use App\Services\PersonalDataSelection\Exporters\WebhookCreationRequestExporter;
use App\Services\PersonalDataSelection\Exporters\WebhookExporter;
use Spatie\PersonalDataExport\PersonalDataSelection;

class UserGdprDataService
{
    public function __invoke(PersonalDataSelection $personalDataSelection, User $data): void {
        $this->addUserPersonalData($personalDataSelection, $data);
    }

    private function addUserPersonalData(PersonalDataSelection $personalDataSelection, User $userModel): void {
        if ($userModel->avatar && file_exists(public_path('/uploads/avatars/' . $userModel->avatar))) {
            $personalDataSelection->addFile(public_path('/uploads/avatars/' . $userModel->avatar));
        }

        $exporter = new Exporter($personalDataSelection, $userModel);
        $exporter->export([
                              StatusExporter::class,
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
                              SocialProfileExporter::class,
                              TokenExporter::class,
                              IcsTokenExporter::class,
                              AppsExporter::class,
                              SessionExporter::class,
                              NotificationsExporter::class,
                              LikesExporter::class,
                              HomeExporter::class,
                              RoleExporter::class,
                              PermissionExporter::class,
                              UserDataExporter::class,
                              WebhookExporter::class,
                          ]);
    }
}

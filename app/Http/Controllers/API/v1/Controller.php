<?php

namespace App\Http\Controllers\API\v1;

use App\DataProviders\DataProviderBuilder;
use App\DataProviders\DataProviderInterface;
use App\Models\OAuthClient;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * @OA\Info(
 *      version="1.0.0 - alpha",
 *      title="Träwelling API",
 *      description="Träwelling user API description. This is an incomplete documentation with still many errors. The API is currently not yet stable. Endpoints are still being restructured. Both the URL and the request or body can be changed. Breaking changes will be announced on GitHub: https://github.com/Traewelling/traewelling/blob/develop/API_CHANGELOG.md",
 *      @OA\Contact(
 *          email="support@traewelling.de"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="https://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 *
 * @OA\Server (
 *     url="https://traewelling.de/api/v1",
 *     description="Production Server"
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_ENDPOINT,
 *      description="This instance"
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Logging in, creating Accounts, etc."
 * )
 * @OA\Tag(
 *     name="Checkin",
 *     description="Checkin related endpoints. Regular process is departures -> trip -> checkin"
 * )
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API Endpoints of Dashboard"
 * )
 * @OA\Tag(
 *     name="Events",
 *     description="Events that users can check in to"
 * )
 * @OA\Tag(
 *     name="Notifications",
 *     description="Get notifications and mark them as read or unread"
 * )
 * @OA\Tag(
 *     name="Status",
 *     description="Endpoints for accessing and manipulating Statusses and their additional data"
 * )
 * @OA\Tag(
 *     name="Likes",
 *     description="Likes regarding a single status"
 * )
 * @OA\Tag(
 *     name="User",
 *     description="Information regarding users"
 * )
 * @OA\Tag(
 *     name="User/Follow",
 *     description="Follow and unfollow users, manage your followers"
 * )
 * @OA\Tag(
 *     name="User/Hide and Block",
 *     description="Mute and block users"
 * )
 * @OA\Tag(
 *     name="Leaderboard",
 *     description="Leaderboard related endpoints"
 * )
 * @OA\Tag(
 *     name="Statistics",
 *     description="Statistics related endpoints"
 * )
 * @OA\Tag(
 *     name="Settings",
 *     description="User/Profile-Settings"
 * )
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Manage Webhooks for third party applications"
 * )
 * @OA\Tag(
 *     name="Report",
 *     description="Report a Status, Event or User to the admins"
 * )
 */
class Controller extends \App\Http\Controllers\Controller
{
    protected DataProviderInterface $dataProvider;

    public function __construct() {
        $this->middleware(function($request, $next) {
            $this->dataProvider = (new DataProviderBuilder())->build(null, Auth::user());

            return $next($request);
        });
    }

    public function sendResponse(
        $data = null,
        int $code = 200,
        ?array $additional = null
    ): JsonResponse {
        $disclaimer = [
            'message'       => 'APIv1 is not officially released for use and is also not fully documented. Use at your own risk. Data fields may change at any time without notice.',
            'documentation' => 'https://traewelling.de/api/documentation',
            'changelog'     => 'https://github.com/Traewelling/traewelling/blob/develop/API_CHANGELOG.md',
        ];
        if ($data === null) {
            return response()->json(
                data:   [
                            'disclaimer' => $disclaimer,
                            'status'     => 'success',
                        ],
                status: $code
            );
        }
        $response = [
            'disclaimer' => $disclaimer,
            'data'       => $data,
        ];
        $response = $additional ? array_merge($response, $additional) : $response;
        return response()->json($response, $code);
    }

    public function sendError(
        array|string|null $error = null,
        int               $code = 404,
        ?array            $additional = null,
        ?Exception        $exception = null
    ): JsonResponse {
        $response = [
            'message' => $error,
        ];

        if ($exception !== null && config('app.debug')) {
            $response['exception'] = [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'code'    => $exception->getCode(),
                'trace'   => $exception->getTrace(),
            ];
        }


        $response = $additional ? array_merge($response, ["meta" => $additional]) : $response;
        return response()->json($response, $code);
    }

    public static function getCurrentOAuthClient(): OAuthClient|null {
        try {
            return request()?->user()?->token()?->client;
        } catch (Throwable) {
            return null;
        }
    }

    protected function getUserOrSelf(string|int $userIdOrSelf): Authenticatable {
        if ($userIdOrSelf === 'self') {
            return auth()->user();
        }
        return User::findOrFail($userIdOrSelf);
    }
}

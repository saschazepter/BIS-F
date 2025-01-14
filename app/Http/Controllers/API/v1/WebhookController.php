<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Backend\WebhookController as WebhookBackend;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebhookController extends Controller
{
    /**
     * @OA\Get(
     *     path="/webhooks",
     *     operationId="getWebhooks",
     *     tags={"Webhooks"},
     *     summary="Get webhooks for current user.",
     *     description="Returns all webhooks which are created for the current user.",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     ref="#/components/schemas/Webhook"
     *                 )
     *             ),
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     security={
     *         {"passport": {}}, {"token": {}}
     *     }
     * )
     */
    public function index(): AnonymousResourceCollection {
        return WebhookResource::collection(Webhook::where('user_id', auth()->id())->get());
    }

    /**
     * @OA\Get(
     *      path="/webhooks/{id}",
     *      operationId="getSingleWebhook",
     *      tags={"Webhooks"},
     *      summary="Get single webhook",
     *      description="Returns a single webhook Object, if user is authorized to see it",
     *      @OA\Parameter (
     *          name="id",
     *          in="path",
     *          description="Webhook-ID",
     *          example=1337,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data",
     *                      ref="#/components/schemas/Webhook"
     *              ),
     *          )
     *       ),
     *       @OA\Response(response=400, description="Bad request"),
     *       @OA\Response(response=404, description="No webhook found or unauthorized for this id"),
     *       security={
     *           {"passport": {}}, {"token": {}}
     *       }
     *     )
     */
    public function show(int $webhookId): WebhookResource|JsonResponse {
        $webhook = Webhook::where('user_id', auth()->id())
                          ->where('id', '=', $webhookId)
                          ->first();
        if ($webhook == null) {
            return $this->sendError('No webhook found for this id.');
        }
        return new WebhookResource($webhook);
    }

    /**
     * @OA\Delete(
     *      path="/webhooks/{id}",
     *      operationId="deleteWebhook",
     *      tags={"Webhooks"},
     *      summary="Delete a webhook if the user is authorized to do",
     *      description="",
     *      @OA\Parameter (
     *          name="id",
     *          in="path",
     *          description="Status-ID",
     *          example=1337,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(response=204, description="Webhook deleted."),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="No webhook found for this id"),
     *      @OA\Response(response=403, description="User or application not authorized to delete this webhook"),
     *      security={
     *          {"passport": {}}, {"token": {}}
     *      }
     *     )
     */
    public function destroy(int $webhookId): JsonResponse {
        try {
            $webhook = Webhook::findOrFail($webhookId);
            $this->authorize('delete', $webhook);
            $webhook->delete();
            return response()->json(null, 204);
        } catch (AuthorizationException) {
            return $this->sendError('You are not allowed to delete this webhook', 403);
        } catch (ModelNotFoundException) {
            return $this->sendError('No webhook found for this id.');
        }
    }
}

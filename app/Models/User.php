<?php

namespace App\Models;

use App\Enum\DataProvider;
use App\Enum\MapProvider;
use App\Enum\StatusVisibility;
use App\Enum\User\FriendCheckinSetting;
use App\Exceptions\RateLimitExceededException;
use App\Http\Controllers\Backend\Social\MastodonProfileDetails;
use App\Jobs\SendVerificationEmail;
use Carbon\Carbon;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\HasApiTokens;
use Mastodon;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

/**
 * // properties
 * @property int                  id
 * @property string               username
 * @property string               name
 * @property string               avatar
 * @property string               email
 * @property Carbon               email_verified_at
 * @property string               password
 * @property int                  home_id
 * @property Carbon               privacy_ack_at
 * @property StatusVisibility     default_status_visibility
 * @property boolean              private_profile
 * @property boolean              prevent_index
 * @property boolean              likes_enabled
 * @property boolean              points_enabled
 * @property MapProvider          mapprovider
 * @property string               data_provider
 * @property string               timezone
 * @property FriendCheckinSetting friend_checkin
 * @property int                  privacy_hide_days
 * @property string               language
 * @property Carbon               last_login
 * @property int                  points
 * @property boolean              userInvisibleToMe
 * @property string               mastodonUrl
 * @property int                  train_distance
 * @property int                  train_duration
 * @property boolean              following
 * @property boolean              followPending
 * @property boolean              muted
 * @property boolean              isAuthUserBlocked
 * @property boolean              isBlockedByAuthUser
 *
 * // relationships
 * @property Collection           trainCheckins
 * @property SocialLoginProfile   socialProfile
 * @property Station              home
 * @property Collection           likes
 * @property Collection           follows
 * @property Collection           blockedUsers
 * @property Collection           blockedByUsers
 * @property Collection           mutedUsers
 * @property Collection           followRequests
 * @property Collection           userFollowers
 * @property Collection           userFollowings
 * @property Collection           sessions
 * @property Collection           icsTokens
 * @property Collection           webhooks
 * @property Collection           notifications
 * @property Collection           statuses
 * @property Collection           trustedUsers
 * @property Collection           trustedByUsers
 *
 *
 * @todo rename home_id to home_station_id
 * @todo rename mapprovider to map_provider
 * @mixin Builder
 */
class User extends Authenticatable
{

    use Notifiable, HasApiTokens, HasFactory, HasRoles, HasPermissions, MustVerifyEmail;

    protected $fillable = [
        'username', 'name', 'avatar', 'email', 'email_verified_at', 'password', 'home_id', 'privacy_ack_at',
        'default_status_visibility', 'likes_enabled', 'points_enabled', 'private_profile', 'prevent_index',
        'privacy_hide_days', 'language', 'last_login', 'mapprovider', 'timezone', 'friend_checkin', 'data_provider',
    ];
    protected $hidden   = [
        'password', 'remember_token', 'email', 'email_verified_at', 'privacy_ack_at',
        'home_id', 'avatar', 'social_profile', 'created_at', 'updated_at', 'userInvisibleToMe'
    ];
    protected $appends  = [
        'points', 'userInvisibleToMe', 'mastodonUrl', 'train_distance', 'train_duration',
        'following', 'followPending', 'muted', 'isAuthUserBlocked', 'isBlockedByAuthUser',
    ];
    protected $casts    = [
        'id'                        => 'integer',
        'email_verified_at'         => 'datetime',
        'privacy_ack_at'            => 'datetime',
        'home_id'                   => 'integer',
        'private_profile'           => 'boolean',
        'likes_enabled'             => 'boolean',
        'points_enabled'            => 'boolean',
        'default_status_visibility' => StatusVisibility::class,
        'prevent_index'             => 'boolean',
        'privacy_hide_days'         => 'integer',
        'last_login'                => 'datetime',
        'mapprovider'               => MapProvider::class,
        'data_provider'             => DataProvider::class,
        'timezone'                  => 'string',
        'friend_checkin'            => FriendCheckinSetting::class,
    ];

    public function getTrainDistanceAttribute(): float {
        return Checkin::where('user_id', $this->id)->sum('distance');
    }

    public function trainCheckins(): HasMany {
        return $this->hasMany(Checkin::class, 'user_id', 'id');
    }

    /**
     * Since duration is a cached and calculated value, it can happen that some checkins are not included in the sum.
     * @return float
     */
    public function getTrainDurationAttribute(): float {
        return Checkin::where('user_id', $this->id)->sum('duration');
    }

    public function socialProfile(): HasOne {
        if ($this->hasOne(SocialLoginProfile::class)->count() == 0) {
            SocialLoginProfile::create(['user_id' => $this->id]);
        }
        return $this->hasOne(SocialLoginProfile::class);
    }

    public function home(): HasOne {
        return $this->hasOne(Station::class, 'id', 'home_id');
    }

    public function likes(): HasMany {
        return $this->hasMany(Like::class);
    }

    public function follows(): BelongsToMany {
        return $this->belongsToMany(__CLASS__, 'follows', 'user_id', 'follow_id');
    }

    public function blockedUsers(): BelongsToMany {
        return $this->belongsToMany(__CLASS__, 'user_blocks', 'user_id', 'blocked_id');
    }

    public function blockedByUsers(): BelongsToMany {
        return $this->belongsToMany(__CLASS__, 'user_blocks', 'blocked_id', 'user_id');
    }

    public function mutedUsers(): BelongsToMany {
        return $this->belongsToMany(__CLASS__, 'user_mutes', 'user_id', 'muted_id');
    }

    public function followRequests(): HasMany {
        return $this->hasMany(FollowRequest::class, 'follow_id', 'id');
    }

    /**
     * @deprecated use ->userFollowers instead to get the users directly
     */
    public function followers(): HasMany {
        return $this->hasMany(Follow::class, 'follow_id', 'id');
    }

    /**
     * @deprecated use ->userFollowing instead to get the users directly
     */
    public function followings(): HasMany {
        return $this->hasMany(Follow::class, 'user_id', 'id');
    }

    public function sessions(): HasMany {
        return $this->hasMany(Session::class);
    }

    public function icsTokens(): HasMany {
        return $this->hasMany(IcsToken::class, 'user_id', 'id');
    }

    public function webhooks(): HasMany {
        return $this->hasMany(Webhook::class);
    }

    public function getPointsAttribute(): int {
        return Checkin::whereIn('status_id', $this->statuses()->select('id'))
                      ->where('departure', '>=', Carbon::now()->subDays(7)->toIso8601String())
                      ->select('points')
                      ->sum('points');
    }

    public function statuses(): HasMany {
        return $this->hasMany(Status::class);
    }

    public function trustedUsers(): HasMany {
        return $this->hasMany(TrustedUser::class, 'user_id', 'id')
                    ->with(['trusted'])
                    ->where(function($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    });
    }

    public function trustedByUsers(): HasMany {
        return $this->hasMany(TrustedUser::class, 'trusted_id', 'id')
                    ->with(['user'])
                    ->where(function($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    });
    }

    public function userFollowings(): BelongsToMany {
        return $this->belongsToMany(__CLASS__, 'follows', 'user_id', 'follow_id');
    }

    public function userFollowers(): BelongsToMany {
        return $this->belongsToMany(__CLASS__, 'follows', 'follow_id', 'user_id');
    }

    /**
     * @untested
     * @todo test
     */
    public function userFollowRequests(): BelongsToMany {
        return $this->belongsToMany(__CLASS__, 'follow_requests', 'follow_id', 'user_id');
    }

    /**
     * @deprecated -> replaced by $user->can(...) / $user->cannot(...) / request()->user()->can(...) /
     *             request()->user()->cannot(...)
     */
    public function getUserInvisibleToMeAttribute(): bool {
        return !request()?->user()?->can('view', $this);
    }

    public function getFollowingAttribute(): bool {
        return (auth()->check() && $this->followers->contains('user_id', auth()->user()->id));
    }

    public function getFollowPendingAttribute(): bool {
        return (auth()->check() && $this->followRequests->contains('user_id', auth()->user()->id));
    }

    public function getMutedAttribute(): bool {
        return auth()->check() && auth()->user()->mutedUsers->contains('id', $this->id);
    }

    public function getFollowedByAttribute(): bool {
        return (auth()->check() && $this->followings->contains('follow_id', auth()->user()->id));
    }

    /**
     * The auth-user is blocked by $this user. auth-user can not see $this's statuses.
     */
    public function getIsAuthUserBlockedAttribute(): bool {
        return auth()->check() && $this->blockedUsers->contains('id', auth()->user()->id);
    }

    /**
     * The auth-user has blocked $this user. $this can not see auth-user's statuses.
     */
    public function getIsBlockedByAuthUserAttribute(): bool {
        return auth()->check() && $this->blockedByUsers->contains('id', auth()->user()->id);
    }

    public function getMastodonUrlAttribute(): ?string {
        return (new MastodonProfileDetails($this))->getProfileUrl();
    }

    /**
     * Get the entity's notifications.
     *
     * @return MorphMany
     */
    public function notifications(): MorphMany {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    /**
     * @throws RateLimitExceededException
     */
    public function sendEmailVerificationNotification(): void {
        Log::info(sprintf("Attempting to send verification email for user#%s w/ mail %s", $this->id, $this->email));

        $executed = RateLimiter::attempt(
            key:          'verification-mail-sent-' . $this->email,
            maxAttempts:  1,
            callback: function() {
                SendVerificationEmail::dispatch($this);
                Log::info(sprintf(
                              "Sent the verification email for user#%s w/ mail %s successfully.",
                              $this->id,
                              $this->email
                          ));
            },
            decaySeconds: 5 * 60,
        );

        if (!$executed) {
            Log::info(sprintf(
                          "Sending the verification email for user#%s w/mail %s was rate-limited.",
                          $this->id,
                          $this->email
                      ));
            throw new RateLimitExceededException();
        }
    }

    /**
     * Laravel default function (e.g. for notifications)
     * @return string
     */
    public function preferredLocale(): string {
        return $this->language;
    }

    protected function getDefaultGuardName(): string {
        return 'web';
    }
}

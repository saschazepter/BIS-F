@extends('layouts.app')

@section('title', $user->name)
@section('canonical', route('profile', ['username' => $user->username]))

@if($user->prevent_index)
    @section('meta-robots', 'noindex')
@else
    @section('meta-description', __('description.profile', [
        'username' => $user->name,
        'kmAmount' => number($user->train_distance / 1000, 0),
        'hourAmount' => number($user->train_duration / 60, 0)
    ]))
@endif

@section('content')
    @php /** @var \App\Models\User $user */ @endphp
    <div class="px-md-4 py-md-5 py-4 mt-n4 profile-banner">
        <div class="container">
            <img alt="{{ __('settings.picture') }}"
                 src="{{ \App\Http\Controllers\Backend\User\ProfilePictureController::getUrl($user) }}"
                 class="float-end img-thumbnail rounded-circle img-fluid profile-picture"/>
            <div class="text-white px-md-4">
                <h1 class="card-title h1-responsive font-bold mb-0 profile-name">
                    <strong>
                        {{ $user->name }}
                        @if($user->private_profile)
                            <i class="fas fa-user-lock"></i>
                        @endif
                    </strong>
                </h1>
                <span
                    class="d-flex flex-column flex-md-row justify-content-md-start align-items-md-center gap-md-2 gap-1 pt-1 pb-2 pb-md-0 small">
                    <small class="font-weight-light profile-tag">
                        {{ '@'. $user->username }}
                        @if($user->followedBy)
                            <span class="badge text-bg-success">
                                {{__('profile.follows-you')}}
                            </span>
                        @endif
                    </small>
                </span>
                <div class="d-flex py-3 flex-row justify-content-md-start align-items-md-center gap-1 ">
                    @include('profile.partials.actions')
                </div>

                @if(!$user->isAuthUserBlocked && !$user->isBlockedByAuthUser && !$user->muted)
                    <span class="profile-stats">
                        <span class="font-weight-bold"><i class="fa fa-route d-inline"></i>&nbsp;{{ number($user->train_distance / 1000) }}</span>
                        <span class="small font-weight-lighter">km</span>
                        <span class="font-weight-bold ps-sm-2"><i class="fa fa-stopwatch d-inline"></i>&nbsp;{!! durationToSpan(secondsToDuration($user->train_duration * 60)) !!}</span>
                        @if($user->points_enabled || auth()->check() && auth()->user()->points_enabled)
                            <span class="font-weight-bold ps-sm-2">
                                <i class="fa fa-dice-d20 d-inline"></i>&nbsp;{{ $user->points }}
                            </span>
                            <span class="small font-weight-lighter">
                                {{__('profile.points-abbr')}}
                            </span>
                        @endif
                        @if($user->mastodonUrl)
                            <span class="font-weight-bold ps-sm-2">
                                <a href="{{ $user->mastodonUrl }}" rel="me" class="text-white" target="_blank">
                                    <i class="fab fa-mastodon d-inline"></i>
                                </a>
                            </span>
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </div>
    <div class="container">
        @include('profile.partials.body')
        @include('includes.edit-modal')
        @include('includes.delete-modal')
    </div>
@endsection

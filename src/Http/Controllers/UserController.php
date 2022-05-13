<?php

namespace Codewiser\Folks\Http\Controllers;

use Codewiser\Folks\Contracts\CreatesNewUsers;
use Codewiser\Folks\Contracts\UpdatesUserProfileInformation;
use Codewiser\Folks\Contracts\UserProviderContract;
use Codewiser\Folks\Folks;
use Codewiser\Folks\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    protected UserProviderContract $userProvider;

    public function __construct(UserProviderContract $userProvider)
    {
        parent::__construct();

        $this->userProvider = $userProvider;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', $this->userProvider->className());

        $users = $this->userProvider->builder($request->user());

        return UserResource::collection($users->paginate())
            ->additional([
                'abilities' => [
                    'create' => Gate::allows('create', $this->userProvider->className())
                ],
                'schema' => $this->userProvider->schema()
            ]);
    }

    protected function user(Request $request, $user): Model
    {
        return $this->userProvider->builder($request->user())->findOrFail($user);
    }

    protected function resource(Model $user): UserResource
    {
        return UserResource::make($user)
            ->additional([
                'abilities' => [
                    'update' => Gate::allows('update', $user),
                    'delete' => Gate::allows('delete', $user),
                    'restore' => Gate::allows('restore', $user),
                    'forceDelete' => Gate::allows('forceDelete', $user),
                ],
                'schema' => $this->userProvider->schema()
            ]);
    }

    public function show(Request $request, $user)
    {
        $user = $this->user($request, $user);

        $this->authorize('view', $user);

        return $this->resource($user);
    }

    public function store(Request $request, CreatesNewUsers $creator)
    {
        $users = $this->userProvider->builder($request->user());

        $this->authorize('create', $this->userProvider->className());

        $user = $creator->create($request->all());

        return $this->resource($user)
            ->toResponse($request)
            ->setStatusCode(201);
    }

    public function update(Request $request, $user, UpdatesUserProfileInformation $updater)
    {
        $user = $this->user($request, $user);

        $this->authorize('update', $user);

        $updater->update($user, $request->all());

        return $this->resource($user);
    }

    public function destroy(Request $request, $user)
    {
        $user = $this->user($request, $user);

        if (method_exists($user, 'trashed')) {
            // Soft Delete
            if ($user->trashed()) {
                $this->authorize('forceDelete', $user);
                $user->forceDelete();

                // Completely destroyed
                return response()
                    ->json()
                    ->setStatusCode(204);
            } else {
                $this->authorize('delete', $user);
                $user->delete();

                // Soft deleted
                return $this->resource($user);
            }
        } else {
            // Hard Delete
            $this->authorize('delete', $user);
            $user->delete();

            // Completely destroyed
            return response()
                ->json()
                ->setStatusCode(204);
        }
    }

    public function restore(Request $request, $user)
    {
        $user = $this->user($request, $user);

        $this->authorize('restore', $user);

        if (method_exists($user, 'restore')) {
            // May be restored
            $user->restore();
        }

        return $this->resource($user);
    }
}

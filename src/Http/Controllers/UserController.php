<?php

namespace Codewiser\Folks\Http\Controllers;

use Codewiser\Folks\Contracts\CreatesNewUsers;
use Codewiser\Folks\Contracts\UpdatesUserProfileInformation;
use Codewiser\Folks\Folks;
use Codewiser\Folks\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', Folks::$usersClass);

        $users = Folks::getUsersBuilder($request->user())->paginate();

        return UserResource::collection($users)
            ->additional([
                'abilities' => [
                    'create' => Gate::allows('create', Folks::$usersClass)
                ],
                'schema' => Folks::$usersSchema
            ]);
    }

    protected function user(Request $request, $user): Model
    {
        return Folks::getUsersBuilder($request->user())->findOrFail($user);
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
                'schema' => Folks::$usersSchema
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
        $this->authorize('create', Folks::$usersClass);

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

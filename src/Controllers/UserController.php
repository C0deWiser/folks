<?php

namespace Codewiser\Rpac\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return config('rpac.models.user')
                    ::with('roles')
//                    ->abonents($request->has('abonents'))
//                    ->search($request->input('q'))
                    ->paginate($request->paginate ?: config('rest.paginate') ?: 50);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // TODO validate userStoreRequest

        $data = $request->input();
        $roles = []; // TODO default
        if(isset($data['_method'])) unset($data['_method']);
        if(isset($data['roles'])) {
            $roles = array_column($data['roles'], 'id');//collect($request->input('roles'))->pluck('id');//
            unset($data['roles']);
        }

        $password = $data['password'] ?? bin2hex(random_bytes(4)); // Str::random(8);
        $data['password'] = bcrypt($password);

        $user = config('rpac.models.user')::create($data);
        if(count($roles)) $user->roles()->attach($roles);

        // TODO event -> mail -> password

        return $this->show($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return $user->load('roles');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // TODO validate userStoreRequest

        $data = $request->input();
        $roles = [];
        if(isset($data['id'])) unset($data['id']);
        if(isset($data['_method'])) unset($data['_method']);
        if(isset($data['updated_at'])) unset($data['updated_at']);
        if(isset($data['roles'])) {
            $roles = array_column($data['roles'], 'id');//collect($request->input('roles'))->pluck('id');//
            unset($data['roles']);
        }

        if(isset($data['password'])) {
            $password = $data['password'];
            $data['password'] = bcrypt($password);
        }

        $api_token = null;
        if(isset($data['api_token'])) {
            $api_token = $data['api_token'];
            unset($data['updated_at']);
        }
        if(count($data)) {
            $user->update($data);
            if($api_token) {
                $user->api_token = $api_token;
                $user->save();
            }
        }
        if(count($roles)) $user->roles()->sync($roles);

        return $this->show($user);
    }

    /**
     * Store a newly created resource from exists specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function clone(Request $request, User $user)
    {
        $data = $this->show($user)->toArray();
        if(isset($data['id'])) unset($data['id']);

        // TODO get/generate clone unique name, email
        if(isset($data['name'])) $data['name'] = 'clone_' . $data['name'];
        if(isset($data['email'])) $data['email'] = 'clone_' . $data['email'];
        if(isset($data['api_token'])) $data['api_token'] = Str::random(60);
        // TODO unique OR empty fields ?? // email, api_token, phone?, password?; created_at; updated_at;

        $request->merge($data);

        return $this->store($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->roles()->detach();
        $user->delete();////???//$user->forceDelete();//
        return $user->deleted_at;
    }
}

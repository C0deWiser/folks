<?php

namespace Codewiser\Rpac\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Codewiser\Rpac\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $permissions = Permission::with('roles')->get();
        return $permissions->groupBy('entity');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // TODO validate
        $data = $request->validate([
            'role' => 'required',
            'entity' => 'required',
            'action' => 'required|alpha_dash|regex:/[0-9a-zA-Z_\-]+/',
            'name' => '',
        ]);
        //$data = $request->input();

        $role = $data['role'];
        unset($data['role']);

        if(!isset($data['name']) || !trim($data['name'])) $data['name'] = $data['action'] . ' ' . substr($data['entity'], -1 * strpos($data['entity'], '\\') - 1);// . ' ' . $data['entity'];

        $permission = Permission::create($data);
        $permission->roles()->sync($role);

        return $this->show($permission);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        return $permission->load('roles');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        // TODO validate
        $data = $request->validate([
            'role' => '',
            'entity' => 'required',
            'action' => 'alpha_dash|regex:/[0-9a-zA-Z_\-]+/',
            'name' => 'required',
        ]);
        //$data = $request->input();

        $role = $data['role'] ?? null;

        $permission->loadCount(['roles' => function($query) use ($role) {
            $query->where('roles.id', '=', $role);
        }]);

        if($role) {
            unset($data['role']);

            //$permission->roles()->sync($role);
            if($permission->roles_count) {
                $permission->roles()->detach($role);
            }
            else {
                $permission->roles()->attach($role);
            }
        }

        //$permission->update($data);
        if(@$data['action'] && @$data['name']) {

            $permission->update(['action'=>$data['action'],'name'=>$data['name']]);
        }
        //else return $data;

        return $this->show($permission);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        $permission->roles()->detach();
        $permission->delete();
        return ['success'=>true, 'action'=>'delete', 'permission'=>$permission];
    }
}

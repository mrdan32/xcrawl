<?php

namespace App\Http\Controllers;

use App\Workspace;
use App\User;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $workspaces = Workspace::get();

        return view('workspaces.index', compact('workspaces'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('workspaces.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'=>'required|max:255',
            'status'=>'required',
        ]);

        $workspace = Workspace::create($request->only('name', 'status')); //

        return redirect()->route('workspaces.index')
            ->with('flash_message',
             'Workspace '. $workspace->name.' added!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Workspace  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return redirect('workspaces');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Workspace  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $workspace = Workspace::findOrFail($id);
        $users = User::whereNotIn('id', $workspace->users->pluck('id'))->get()->pluck('email', 'id')->prepend('Select user...', '');
        $roles = Role::get()->pluck('name', 'id')->prepend('Select role...', '');

        return view('workspaces.edit', compact('workspace', 'users', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Workspace  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $workspace = Workspace::findOrFail($id);

        //Validate name, status fields
        $this->validate($request, [
            'name'=>'required|max:255',
            'status'=>'required',
        ]);
        $input = $request->only(['name', 'status']);
        $workspace->fill($input)->save();

        if ($request['user_id'] && $request['role_id']) {
            $workspace->users()->attach($request['user_id'], ['role_id' => $request['role_id']]);

            \App\UserState::create([
                'user_id' => $request['user_id'],
                'workspace_id' => $id,
                'role_id' => $request['role_id'],
                'created_at' => Carbon::now()
            ]);
        }

        return redirect()->route('workspaces.index')
            ->with('flash_message',
             'Workspace successfully edited.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Workspace  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $workspace = Workspace::findOrFail($id);
        $workspace->delete();

        return redirect()->route('workspaces.index')
            ->with('flash_message',
             'Workspace successfully deleted.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Workspace  $id
     * @param  \App\User       $id
     * @return \Illuminate\Http\Response
     */
    public function user($workspace_id, $user_id)
    {
        $workspace = Workspace::findOrFail($workspace_id);
        $workspace->users()->detach($user_id);

        return redirect()->route('workspaces.index')
            ->with('flash_message',
             'Workspace successfully modified.');
    }

    public function setCurrent($workspace_id, $user_id, $role_id)
    {
        $user = User::findOrFail($user_id);
        $workspace = Workspace::findOrFail($workspace_id);
        $role = Role::findOrFail($role_id);

        \App\UserState::where('user_id', $user_id)->update([
            'workspace_id' => $workspace_id,
            'role_id' => $role_id,
        ]);

        return redirect()->route('home')
            ->with('flash_message',
             'Workspace successfully set.');
    }
}

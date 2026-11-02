<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Acl;
use App\Models\AclAction;
use App\Models\AclController;
use App\Models\UserGroup;
use App\Policies\AclPolicy;
use Illuminate\Http\Request;

/** Permission matrix for one user group (replaces userGroup/access in the Yii admin). */
class AccessController extends Controller
{
    public function edit(int $id)
    {
        $group = UserGroup::findOrFail($id);
        $modules = AclController::where('status', 1)->orderBy('title')->get();
        $actions = AclAction::orderBy('id')->get()->groupBy('controller_id');
        $granted = Acl::where('group_id', $id)->where('access', 1)->get()
            ->mapWithKeys(fn ($r) => [strtolower($r->controller).'|'.strtolower($r->actions) => true]);

        return view('admin.access', compact('group', 'modules', 'actions', 'granted'));
    }

    public function update(Request $request, int $id)
    {
        $group = UserGroup::findOrFail($id);
        abort_if($group->id === 1, 422, 'Super Users always have full access.');

        $posted = (array) $request->input('perm', []);

        foreach (AclController::all() as $module) {
            foreach (AclAction::where('controller_id', $module->id)->get() as $action) {
                $access = isset($posted[$module->id][$action->action]) ? 1 : 0;

                $row = Acl::where('group_id', $id)->where('controller', $module->controller)->where('actions', $action->action)->first();
                if ($row) {
                    $row->update(['access' => $access]);
                } else {
                    Acl::create([
                        'group_id' => $id, 'controller' => $module->controller, 'actions' => $action->action,
                        'action_title' => $action->title, 'access' => $access, 'controller_type' => 1,
                    ]);
                }
            }
        }

        AclPolicy::forget();

        return back()->with('success', "Permissions saved for {$group->title}.");
    }
}

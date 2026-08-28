<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AppUserController extends Controller
{
    public function index(Request $request)
    {
        $query = AppUser::query();
        if ($kw = $request->query('kw')) {
            $query->where(function ($q) use ($kw) {
                $q->where('username', 'like', "%{$kw}%")
                  ->orWhere('email', 'like', "%{$kw}%");
            });
        }
        $users = $query->latest()->paginate(30)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new AppUser()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'    => 'required|string|max:64|unique:app_users,username',
            'password'    => 'required|string|min:6|max:128',
            'email'       => 'nullable|email',
            'expires_at'  => 'nullable|date',
            'max_devices' => 'nullable|integer|min:0',
        ]);
        $data['password'] = Hash::make($data['password']);
        AppUser::create($data);
        return redirect()->route('admin.users.index')->with('ok', '账号已创建');
    }

    public function edit(AppUser $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, AppUser $user)
    {
        $data = $request->validate([
            'email'       => 'nullable|email',
            'password'    => 'nullable|string|min:6|max:128',
            'expires_at'  => 'nullable|date',
            'is_banned'   => 'nullable|boolean',
            'max_devices' => 'nullable|integer|min:0',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $data['is_banned'] = $request->boolean('is_banned');
        $user->update($data);

        return redirect()->route('admin.users.index')->with('ok', '账号已更新');
    }

    /** 手动续期 */
    public function extend(Request $request, AppUser $user)
    {
        $days = (int) $request->validate(['days' => 'required|integer|min:1'])['days'];
        $user->extendDays($days);
        return back()->with('ok', "已延长 {$days} 天");
    }

    /** 解绑机器码 */
    public function unbind(AppUser $user)
    {
        $user->update(['machine_code' => null]);
        return back()->with('ok', '已解绑设备');
    }

    public function destroy(AppUser $user)
    {
        $user->delete();
        return back()->with('ok', '账号已删除');
    }
}

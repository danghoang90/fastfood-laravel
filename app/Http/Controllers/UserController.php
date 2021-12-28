<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\EditUserRequest;
use App\Http\Services\LoginService;
use App\Models\Role;
use App\Models\User;
//use Illuminate\Auth\Access\Gate;
use http\Client\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\LoginRequest;

class UserController extends Controller
{
    private $user;
    private $role;
    private $loginService;

    public function __construct(User $user, Role $role,LoginService $loginService)
    {
        $this->user = $user;
        $this->role = $role;
        $this->loginService = $loginService;
    }

    public function index()
    {
        $listUser = User::with('roles')->get();
        $data = [
            'status' => 'success',
            'data' => $listUser
        ];
        return response()->json($data);
    }

    public function create()
    {
        $roles = $this->role->all();
        return view('backend.users.add', compact('roles'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();
            $user->roles()->sync($request->role);
            DB::commit();
            $data = [
                'status' => 'success',
                'message' => 'Add User Successfully'
            ];
            return response()->json($data);
        }catch (\Exception $exception) {
            DB::rollBack();
        }
        return redirect()->route('users.index')->with('success', 'Thêm thành công người dùng !');
    }

    public function destroy($id)
    {
//        if (Gate::allows('user-crud')) {
            $user = User::findOrFail($id);
            if(!$user) {
                $data = [
                    'status' =>'errors',
                    'message' =>'user not exits'
                ];
            }else {
                $user->roles()->detach();
                $user->delete();
                $data = [
                    'status' =>'success',
                    'message' =>'user delete successfully',
                    'users' => User::all()
                ];
            }
//            dd($user->role);
//            if ($user->role === 'Admin') {
//                return back()->with('error', 'Không được xoá chính bạn và Admin !');
//            }

            return response()->json($data);
//        }else {
//            abort(403);
//        }

    }

    public function update($id)
    {
//        if (Gate::allows('user-crud')) {
            $user = User::findOrFail($id);
            $roles = Role::all();
            $data = [
              'status' => 'success',
              'data' => $user
            ];
            return response()->json($data);
//        }else {
//            abort(403);
//        }

    }
    public function edit(EditUserRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();
            $user->roles()->sync($request->role);
            DB::commit();
            $data = [
                'status' => 'success',
                'message' => 'Add User Successfully'
            ];
            return response()->json($data);
        }catch (\Exception $exception) {
            DB::rollBack();
        }
        return redirect()->route('users.index')->with('success', 'Update thành công');
    }

    public function showFormChangePassword(){
        return view('backend.users.change-password');
    }


    public function changePassword(Request $request)
    {
        $user = Auth::user();
        $currentPassword = $user->password;
        $request->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:3|different:currentPassword',
            'confirmPassword' => 'required|same:newPassword',

        ]);
        if(!Hash::check($request->currentPassword, $currentPassword)){
            return redirect()->back()->with('error', 'Bạn đã nhập sai mật khẩu!');
        }
        $user->password = Hash::make($request->newPassword);
        $user->save();
        return redirect()->route('users.login')->with('success', 'Đổi password thành công');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    function showFormLogin()
    {
        return view('backend.users.login');
    }

    function login(LoginRequest $request)
    {
        $this->validate($request, [
            'email' => 'required|email:filter',
            'password' => 'required'
        ]);

        if ($this->loginService->checkLogin($request)) {
            return redirect()->route('users.index');
        }
        return back()->with('error', 'Tài khoản hoặc mật khẩu không chính xác!');
    }

    public function roles()
    {
        $roles = Role::all();
        return response()->json($roles);
    }
}

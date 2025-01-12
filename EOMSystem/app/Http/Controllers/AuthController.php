<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
      /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {

        $user = User::where('email',$request->input('email'))->first()->status;
        $credentials = request(['email', 'password']);
        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Invalid Account'], 401);
        }else{
            if($user=='accepted'){
                return $this->respondWithToken($token);
            }
            return response()->json(['error' => 'Invalid Account'], 401);
        }
    }

    public function editUser(Request $request,$id){
        $request->validate([
            'photo'=>'required|max:1999'
        ]);
        $user = User::find($id);

        if($request->hasFile('photo')){

            $file_name = $user->photo;
            $file_path = public_path('storage/userPhoto/'.$file_name);
            unlink($file_path);
            $user->delete();

            $fileNameWithExt = $request->file('photo')->getClientOriginalName();
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('photo')->getClientOriginalExtension();
            $fileNameToStore = $fileName.'_'.time().'.'.$extension;
            $path = $request->file('photo')->storeAs('public/userPhoto',$fileNameToStore);
        }else{
            return 'error';
        }

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = $request->input('password');
        $user->birthday = $request->input('birthday');
        $user->college = $request->input('college');
        $user->department = $request->input('department');
        $user->photo = $fileNameToStore;
        $user->save();

        return response()->json(['message'=>'Account Updated']);
    }

    public function deleteUser($id){
        $user = User::findOrFail($id);
        $fileName = $user->photo;
        $file_path = public_path('storage/userPhoto/'.$fileName);

        unlink($file_path);
        $user->delete();
        return $user->photo.' deleted.';
    }

    public function getUsers(){
        return response()->json(User::all(),200);
    }

    public function getUserById($id){
        $user = DB::table('users')
        ->where('id', '=', $id)
        ->get();
        if(is_null($user)){
            return response()->json(['message'=>'Query not found']);
        }
        return response()->json($user);
    }

    public function signup(Request $request){
        $validated = $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:8',
            'password_confirmation'=>'required|same:password',
            'birthday'=>'required',
            'college'=>'required',
            'department'=>'required',
            'photo'=>'required|mimes:jpeg,png,bmp,tiff|max:1999'
        ]);

        if($request->hasFile('photo')){
            $fileNameWithExt = $request->file('photo')->getClientOriginalName();
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('photo')->getClientOriginalExtension();
            $fileNameToStore = $fileName.'_'.time().'.'.$extension;
            $path = $request->file('photo')->storeAs('public/userPhoto',$fileNameToStore);
        }else{
            return 'error';
        }

        $user = new User;
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = $request->input('password');
        $user->birthday = $request->input('birthday');
        $user->college = $request->input('college');
        $user->department = $request->input('department');
        $user->photo = $fileNameToStore;
        $user->save();
        return response()->json(['message'=>'User Created'],200);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}

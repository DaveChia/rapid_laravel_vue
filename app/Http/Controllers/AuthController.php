<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;
use DB;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {   
        // TEMP commented
        // $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Returns a cookie containing a JWT token if user is authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);

        if ($request->cookie('libraryAuth')) {
            return response()->json(['error' => 'Unauthorized','Login' => 'Fail']);
        }

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized','Login' => 'Fail']);
        }

        $useremail = $credentials['email'];
        $userpassword = $credentials['password'];
        $userId = Auth::id();

        $result=DB::table('sessions')
                ->join('users', 'users.id', '=', 'sessions.user_id')
                ->where('sessions.user_id', $userId)
                ->select(
                    'sessions.user_id','users.name')
                ->get();

        $result2=DB::table('users')
                ->where('id', $userId)
                ->select(
                    'name')
                ->get();
  
        $username = $result2[0]->name;

        if(count($result) === 0){
            $result2 = DB::table('sessions')->upsert(
                    [['sessionid' => $token, 'user_id' => $userId, 'datecreated' => time()]], 
                    ['user_id'], ['sessionid','datecreated']);
        }else{
            $result3 = DB::table('sessions')
                    ->where('user_id', $userId)
                    ->update(['sessionid' => $token, 'datecreated' => time()]);
        }

        return response()->json(['Login' => 'Success','userid' => $userId,'username' => $username])
                        ->cookie('libraryAuth',$token,60);
    }

    /**
     * Log the user out (Invalidate the token and unset cookie).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $cookiekey = $request->cookie('libraryAuth');
        $userId = $request['userid'];

        DB::table('sessions')->where('user_id', $userId)
            ->where('sessionid', $cookiekey)
            ->delete();
    
        return response()->json(['message' => 'Successfully logged out'])
                        ->cookie('libraryAuth','',-1);
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

}
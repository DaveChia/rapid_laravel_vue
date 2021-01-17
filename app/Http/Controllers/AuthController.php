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
     * Get a JWT via given credentials.
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
            'sessions.user_id','users.name'
        )->get();

        $result2=DB::table('users')
        ->where('id', $userId)
        ->select(
            'name'
        )->get();
  
        $username = $result2[0]->name;

        // return $test;
        // $username = $result[0]->name;

        if(count($result) === 0){
            $result2 = DB::table('sessions')->upsert(
            [['sessionid' => $token, 'user_id' => $userId, 'datecreated' => time()]], 
            ['user_id'], ['sessionid','datecreated']);
        }else{
            $result3 = DB::table('sessions')
                ->where('user_id', $userId)
                ->update(['sessionid' => $token, 'datecreated' => time()]);
        }

        return response()->json(['Login' => 'Success','userid' => $userId,'username' => $username])->cookie('libraryAuth',$token,60);
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
    public function logout(Request $request)
    {
        $cookiekey = $request->cookie('libraryAuth');
        $userId = $request['userid'];

        // $result=DB::table('sessions')
        // ->where('sessionid', $cookiekey)
        // ->where('user_id', $userId)
        // ->select(
        //     'user_id'
        // )->get();

        DB::table('sessions')->where('user_id', $userId)->where('sessionid', $cookiekey)->delete();
        
        
        // return $request['userid'];

        return response()->json(['message' => 'Successfully logged out'])->cookie('libraryAuth','',-1);
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

    // /**
    //  * Get the token array structure.
    //  *
    //  * @param  string $token
    //  *
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // protected function respondWithToken($token)
    // {
    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type' => 'bearer',
    //         'expires_in' => auth()->factory()->getTTL() * 60
    //     ]);
    // }

    // public function test(Request $request)
    // {
    //     $cookiekey = $request->cookie('libraryAuth');
       

    //     return '54321'
    // }
}
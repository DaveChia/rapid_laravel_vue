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
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // $useremail = $credentials['email'];
        // $userpassword = $credentials['password'];
        $userId = Auth::id();
        $newsessionid = session()->getId();

        $result=DB::table('sessions')
        ->where('user_id', $userId)
        ->select(
            'user_id'
        )->get();

        if(count($result) === 0){
            $result2 = DB::table('sessions')->upsert(
            [['sessionid' => $newsessionid, 'user_id' => $userId, 'datecreated' => time()]], 
            ['user_id'], ['sessionid','datecreated']);
        }
        else{
            $result3 = DB::table('sessions')
                ->where('user_id', $userId)
                ->update(['sessionid' => $newsessionid, 'datecreated' => time()]);
        }
        session([$userId => $newsessionid]);
        $request->session()->put('key','value');
        return $this->respondWithToken($token);
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
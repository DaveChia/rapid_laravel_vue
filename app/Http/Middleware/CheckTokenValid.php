<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;
use DB;

use Illuminate\Http\Request;

class CheckTokenValid
{
    public function handle($request, Closure $next)
    {

        $token = $request->cookie('libraryAuth');

        if(!$token){
            return response()->json(['error' => 'Session Expired']);
        }

        $result=DB::table('sessions')
                ->where('sessionid', $token)
                ->select(
                    'datecreated')
                ->get();
        
        $checktokenexpiry = 0;
           
        if(count($result)>0){
            $checktokenexpiry = time() - $result[0]->datecreated ;
        }

        if(count($result)===0 || $checktokenexpiry >= 3600){
            return response()->json(['error' => 'Session Expired'])->cookie('libraryAuth','',-1);
        }

        return $next($request);

    }
}
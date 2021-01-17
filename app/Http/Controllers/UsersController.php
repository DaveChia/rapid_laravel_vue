<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use DB;

class UsersController extends Controller
{
    /**
     * Get all books data from the database
     *
     * @return Object
     */
    public function getallbookslist(Request $request)
    {

       if ($this->checktoken($request) !== 'validtoken') {
            return $this->checktoken($request);
       }

        $users = DB::table('lib_book_list AS bl')
        ->join('lib_book_genre AS bg', 'bl.genreid', '=', 'bg.id')
        ->join('lib_book_bookwithbookshelf AS bb', 'bb.bookid', '=', 'bl.id')
        ->join('lib_book_bookshelf AS bs', 'bs.id', '=', 'bb.bookshelfid')
        ->select(
            'bl.id', 'bl.bookname', 'bl.currentstock', 'bl.bookcoverimage', 'bl.booksummary', 'bg.genrename', 'bl.isbn',
            DB::raw('CONCAT(bs.rackname, "-", bs.racklevel, "-", bs.rackcolumn) AS "shelfname"')
        )->get();
       
        return $users;
    }

    /**
     * Get dued book list, dued book counts, and calculates total dued payment amount of the input user
     *
     * @return Object
     */
    public function getduedlist(Request $request)
    {

        if ($this->checktoken($request) !== 'validtoken') {
            return $this->checktoken($request);
        }

        $output = [];
        $now = time();
        $overduecharges = 0.00;
        $perdayfees = 1.50;
    
        $duedlist = DB::table('lib_book_loans AS bl')
                ->select(
                    'bl.bookid', 'bl.dateborrowed',
                    DB::raw('DATEDIFF(FROM_UNIXTIME(UNIX_TIMESTAMP())'),
                    DB::raw('FROM_UNIXTIME(bl.datedued)) AS "daysoverdued"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datedued), "%M %d %Y") AS "datedued"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datecollected), "%M %d %Y") AS "datecollected"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datereturned), "%M %d %Y") AS "datereturned"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.dateborrowed), "%M %d %Y") AS "dateborrowed"')
                )->where('bl.userid', $_GET['userid']
                )->where(function($query) {
                    $query->where('bl.loanstatus', 4)
                          ->orWhere('bl.loanstatus', 6);
                })
                ->get();
        
        foreach ($duedlist as $duedbook) {
            $overduecharges += ($perdayfees * $duedbook->daysoverdued);
        } 
    
        $output['duedbooksdata'] = $duedlist;
        $output['overduecharges'] = number_format($overduecharges, 2);
        $output['duedbookscount'] = count($duedlist);
    
        return $output;
    }

    /**
     * Get list of loans transaction of the input user
     *
     * @return Object
     */
    public function getloanlist(Request $request)
    {
        if ($this->checktoken($request) !== 'validtoken') {
            return $this->checktoken($request);
        }

        $output = [];

        $loanlist = DB::table('lib_book_loans AS bl')
                ->join('lib_book_list AS blist', 'bl.bookid', '=', 'blist.id')
                ->select(
                    'bl.bookid', 'bl.dateborrowed','blist.bookname','blist.bookcoverimage','bl.loanstatus',
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datedued), "%M %d %Y") AS "datedued"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.dateduepaid), "%M %d %Y") AS "dateduepaid"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datecollected), "%M %d %Y") AS "datecollected"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datereturned), "%M %d %Y") AS "datereturned"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.dateborrowed), "%M %d %Y") AS "dateborrowed"')
                )->where('bl.userid', $_GET['userid']
                )->get();
    
        $output['loanbooksdata'] = $loanlist;
        $output['loanbookscount'] = count($loanlist);
    
        return $output;
    }

    // /**
    //  * Search for book by bookname or genre
    //  *
    //  * @return Object
    //  */
    // public function searchbooks()
    // {
    //     $output = [];

    //     $searchbookinput = '%' . $_GET['bookname']. '%';
    //     $searchgenreinput = '%' . $_GET['bookgenre']. '%';
    
    //     $searchresults = DB::table('lib_book_list AS bl')
    //                     ->join('lib_book_genre AS bg', 'bl.genreid', '=', 'bg.id')
    //                     ->select(
    //                             'bl.id', 'bl.bookname', 'bl.currentstock', 'bl.bookcoverimage', 'bl.booksummary', 'bg.genrename'
    //                     )->where('bl.bookname', 'LIKE' , $searchbookinput
    //                     )->orWhere('bg.genrename', 'LIKE' , $searchgenreinput
    //                     )->get();
    
    //     $output['searchresults'] = $searchresults;
    
    
    //     return $output;
    // }

    /**
     * Process book loaning
     *
     * @return Object
     */
    public function loanbook(Request $request)
    {
        if ($this->checktoken($request) !== 'validtoken') {
            return $this->checktoken($request);
        }

        $output = [];
    
        $bookidinput = $request->input('bookid');
        $useridinput =$request->input('userid');

        $loanwithsamebook = DB::table('lib_book_loans')
                            ->select(
                                'id'
                            )->where('userid', $useridinput
                            )->where('bookid', $bookidinput
                            )->whereIn('loanstatus', [1,2,4,8]
                            )->get();

	if (count($loanwithsamebook) === 0) {

        $remainingbookcount = DB::table('lib_book_list')
                            ->select(
                                'currentstock'
                            )->where('id', $bookidinput
                            )->where('currentstock', '>', 0
                            )->get();

            if (count($remainingbookcount) > 0) {

                $updatestocks = DB::table('lib_book_list')
                    ->where('id', $bookidinput)
                    ->decrement('currentstock');

                if ($updatestocks > 0) {
                    $output['loanresult'] = DB::table('lib_book_loans')->insert([
                            'userid' => $useridinput,
                            'bookid' => $bookidinput,
                            'dateborrowed' => time(),
                            'datecreated' => time(),
                            'datemodified' => time(),
                            'loanstatus' => 1
                    ]);
                }

            }else{
                $output['loanresult'] = 'Not Available';
            }

    }else{
        $output['loanresult'] = 'Book Already Loaned';
    }

    return $output;
}

    /**
     * Process due payment
     *
     * @return Object
     */
    public function paydues(Request $request)
    {
        if ($this->checktoken($request) !== 'validtoken') {
            return $this->checktoken($request);
        }
        
        $output = [];

        $useridinput = $request->input('userid');
    
        $update1 = DB::table('lib_book_loans')
                ->where('userid', $useridinput)
                ->where('loanstatus', 6)
                ->update(['loanstatus' => 7, 'dateduepaid' => time()]);
    
        $update2 = DB::table('lib_book_loans')
                  ->where('userid', $useridinput)
                  ->where('loanstatus', 4)
                  ->update(['loanstatus' => 8, 'dateduepaid' => time()]);
    
        if($update1>0 || $update2>0){
            $output['results'] = true;
        }else{
            $output['results'] = false;
        }
	    return $output;
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

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function checktoken($request)
    {
        $token = $request->cookie('libraryAuth');

        if(!$token ){
            return response()->json(['error' => 'Session Expired1']);
        }

        $result=DB::table('sessions')
                ->where('sessionid', $token)
                ->select(
                    'datecreated'
                )->get();
        
        $checktokenexpiry = 0;
           
        if(count($result)>0){
            $checktokenexpiry = time() - $result[0]->datecreated ;
        }

        if(count($result)===0 || $checktokenexpiry >= 3600){
            return response()->json(['error' => 'Session Expired2'])->cookie('libraryAuth','',-1);
        }

        return 'validtoken';

    }
}
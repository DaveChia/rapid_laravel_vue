<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class LibrarianController extends Controller
{
    /**
     * Get all books data from the database
     *
     * @return Object
     */
    public function organizeloans(Request $request)
    {
        if ($this->checktoken($request) !== 'validtoken') {
            return $this->checktoken($request);
        }

        $output = [];

        $user = DB::table('users')->select('name')->where('id', $_GET['userid'])->first();
        
        if($_GET['loantype'] == 'loan'){
            $loanlist = DB::table('lib_book_loans AS bl')
                            ->join('lib_book_list AS blist', 'bl.bookid', '=', 'blist.id')
                            ->join('lib_book_bookwithbookshelf AS bb', 'bb.bookid', '=', 'blist.id')
                            ->join('lib_book_bookshelf AS bs', 'bs.id', '=', 'bb.bookshelfid')
                            ->select(
                                'bl.bookid', 'bl.dateborrowed', 'blist.bookname', 'blist.bookcoverimage', 'bl.loanstatus', 'blist.currentstock', 'bl.id AS loanid',
                                DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datereturned), "%M %d %Y") AS "datereturned"'),
                                DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.dateborrowed), "%M %d %Y") AS "dateborrowed"'),
                                DB::raw('CONCAT(bs.rackname, "-", bs.racklevel, "-", bs.rackcolumn) AS "shelfname"')
                                )->where('bl.userid', $_GET['userid']
                                )->where('bl.loanstatus', 1
                                )->get();

        }else if($_GET['loantype'] == 'return'){
            $loanlist = DB::table('lib_book_loans AS bl')
            ->join('lib_book_list AS blist', 'bl.bookid', '=', 'blist.id')
            ->join('lib_book_bookwithbookshelf AS bb', 'bb.bookid', '=', 'blist.id')
            ->join('lib_book_bookshelf AS bs', 'bs.id', '=', 'bb.bookshelfid')
            ->select(
                'bl.bookid', 'bl.dateborrowed', 'blist.bookname', 'blist.bookcoverimage', 'bl.loanstatus', 'blist.currentstock', 'bl.id AS loanid',
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datedued), "%M %d %Y") AS "datedued"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.dateduepaid), "%M %d %Y") AS "dateduepaid"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datecollected), "%M %d %Y") AS "datecollected"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.dateborrowed), "%M %d %Y") AS "dateborrowed"'),
                DB::raw('CONCAT(bs.rackname, "-", bs.racklevel, "-", bs.rackcolumn) AS "shelfname"')
                )->where('bl.userid', $_GET['userid']
                )->whereIn('bl.loanstatus', [2,4,8]
                )->get();

        }else{
            $loanlist = DB::table('lib_book_loans AS bl')
            ->join('lib_book_list AS blist', 'bl.bookid', '=', 'blist.id')
            ->join('lib_book_bookwithbookshelf AS bb', 'bb.bookid', '=', 'blist.id')
            ->join('lib_book_bookshelf AS bs', 'bs.id', '=', 'bb.bookshelfid')
            ->select(
                'bl.bookid', 'bl.dateborrowed', 'blist.bookname', 'blist.bookcoverimage', 'bl.loanstatus', 'blist.currentstock', 'bl.id AS loanid',
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datedued), "%M %d %Y") AS "datedued"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.dateduepaid), "%M %d %Y") AS "dateduepaid"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datecollected), "%M %d %Y") AS "datecollected"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.datereturned), "%M %d %Y") AS "datereturned"'),
                    DB::raw('DATE_FORMAT(FROM_UNIXTIME(bl.dateborrowed), "%M %d %Y") AS "dateborrowed"'),
                DB::raw('CONCAT(bs.rackname, "-", bs.racklevel, "-", bs.rackcolumn) AS "shelfname"')
                )->where('bl.userid', $_GET['userid']
                )->get();
        }
        

        $output['userresult'] = $user;
        $output['loanresult'] = $loanlist;

        return $output;
    }

    /**
     * Get all books data from the database
     *
     * @return Object
     */
    public function updateloan(Request $request)
    {
        if ($this->checktoken($request) !== 'validtoken') {
            return $this->checktoken($request);
        }
        $output = [];

        $useridinput = $request->input('userid');
        $bookidsinput = $request->input('bookids');

        $update = DB::table('lib_book_loans')
                ->where('userid', $useridinput)
                ->whereIn('id', $bookidsinput)
                ->update(['loanstatus' => 2, 'datecollected' => time(), 'datemodified' => time()]);

        if($update>0 ){
            $output['results'] = true;
        }else{
            $output['results'] = false;
        }
        return $output;
    }
    
    /**
     * Get all books data from the database
     *
     * @return Object
     */
    public function updatereturn(Request $request)
    {
        if ($this->checktoken($request) !== 'validtoken') {
            return $this->checktoken($request);
        }
        
        $output = [];

        $useridinput = $request->input('userid');
        $bookidsinput = $request->input('bookids');

        $update1 = DB::table('lib_book_loans')
                ->where('userid', $useridinput)
                ->where('loanstatus',2)
                ->whereIn('id', $bookidsinput)
                ->update(['loanstatus' => 3, 'datereturned' => time(), 'datemodified' => time()]);

               

        $update2 = DB::table('lib_book_loans')
                ->where('userid', $useridinput)
                ->where('loanstatus',8)
                ->whereIn('id', $bookidsinput)
                ->update(['loanstatus' => 7, 'datereturned' => time(), 'datemodified' => time()]);	
                
                
               

        if($update1>0 || $update2>0){
            $output['results'] = true;
            $updatestocks = DB::table('lib_book_list AS blist')
            ->join('lib_book_loans AS bl', 'bl.bookid', '=', 'blist.id')
            ->whereIn('bl.id', $bookidsinput)
            ->increment('currentstock');
            
        }else{
            $output['results'] = false;
        }
        return $output;
    }

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
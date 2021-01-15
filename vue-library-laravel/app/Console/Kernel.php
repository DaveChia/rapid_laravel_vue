<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
       
        // Scheduled tasks for checking and assiging overdued loan statuses, cancelling of uncollected books
        $schedule->call(function () {

            $loansettings = DB::table('lib_admin_settings')
                            ->select('settingvalue')
                            ->where('id', 1)
                            ->orWhere('id', 2)->get();

            DB::table('lib_book_loans')
                ->where('loanstatus', 1)
                ->where(DB::raw('DATEDIFF(FROM_UNIXTIME(UNIX_TIMESTAMP()),FROM_UNIXTIME(dateborrowed))'), '>', $loansettings[0]->settingvalue)
                ->update(['loanstatus' => 5, 'datecancelled' => time()]);

            DB::table('lib_book_loans')
                ->where('loanstatus', 2)
                ->where(DB::raw('DATEDIFF(FROM_UNIXTIME(UNIX_TIMESTAMP()),FROM_UNIXTIME(datecollected))'), '>', $loansettings[1]->settingvalue)
                ->update(['loanstatus' => 4, 'datedued' => time()]);


        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

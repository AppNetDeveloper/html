<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // ... otros comandos ...
        Commands\MqttSubscriber::class,
        Commands\MqttSubscriberLocal::class,
        Commands\ReadModbus::class,
        Commands\PublishToMqttServer1::class,
        Commands\PublishToMqttServer2::class,
        Commands\ReadSensors::class,
        Commands\TcpClient::class,
        Commands\MqttShiftSubscriber::class,
        Commands\CalculateOptimalProductionTime::class,
        Commands\CalculateProductionDowntime::class,
        Commands\CalculateProductionMonitorOee::class,
        Commands\CheckShiftList::class,
        Commands\ConnectWhatsApp::class,
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

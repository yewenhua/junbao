<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;

class SendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send {param}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command send mail';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //模板邮件
        Mail::send('mail', ['name'=>'command send mail '.$this->argument('param')], function($message){
            $message->from('ye_goodluck@aliyun.com', 'cat');
            $message->subject('have a command');
            $message->to('2574522520@qq.com');
        });
    }
}

<?php

namespace App\Traits;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

trait Sendmail{
    public function send($blade, $title, $email, $body)
    {
        //fyxzqqtxnxkrintw
        $config = array(
            'driver'     => getConfig('mail_driver'),
            'host'       => getConfig('smtp_host'),
            'port'       => getConfig('smtp_port'),
            'from'       => array('address' => getConfig('email_send'), 'name' => getConfig('email_name_send')),
            'encryption' => getConfig('smtp_encryption'),
            'username'   => getConfig('smtp_email'),
            'password'   => getConfig('smtp_password')
          );
        Config::set('mail', $config);
        $data = array("name"=>$title,"body"=>$body,'email'=>$email);
        Mail::send($blade, ['data'=>$data] , function($message) use ($title,$data){
            $message->to($data['email'])->subject($title);
            $message->from($data['email'],$title);
        });
    }
}

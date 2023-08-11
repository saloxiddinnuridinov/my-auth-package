<?php

namespace My\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;

class MainNotificationController extends Controller
{
    public function mail($email, $data,$page = 'mail')
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = env('MAIL_HOST');
        $mail->SMTPAuth = TRUE;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            )
        );
        $mail->Username = env('MAIL_USERNAME');
        $mail->Password = env('MAIL_PASSWORD');
        $mail->Port = env('MAIL_PORT');
        $mail->SMTPSecure = env('MAIL_ENCRYPTION');
        $mail->setFrom(env('MAIL_USERNAME'), $data['title']);
        $mail->Subject = $data['title'];
        $message = view("$page",['model'=>$data])->render();
        $mail->MsgHTML($message);
        $mail->addAddress($email, $data['name']);
        $mail->send();
        return $email;
    }
}

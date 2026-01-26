<?php

declare(strict_types=1);

namespace App\Themes\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contact\Models\Contact;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer;
use Validator;

class ContactController extends Controller
{
    public function subcribe(Request $request)
    {
        if (getConfig('recaptcha_status')) {
            $context = stream_context_create([
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query([
                        'secret' => getConfig('recaptcha_secret_key'),
                        'response' => request('recaptcha'),
                    ]),
                ],
            ]);
            $result = json_decode(file_get_contents(env('GOOGLEURL'), false, $context));
            if ($result->success != true) {
                return response()->json([
                    'status' => false,
                    'message' => 'Quá thời gian xử lý! Xin vui lòng thử lại.',
                ]);
            }
        }
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'content' => 'required',
            'name' => 'required',
        ], [
            'phone.required' => 'Bạn chưa nhập số điện thoại.',
            'content.required' => 'Bạn chưa viết đánh giá.',
            'name.required' => 'Bạn chưa nhập tên.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $id = Contact::insertGetId([
            'name' => $request->name,
            'phone' => $request->phone,
            'content' => $request->content,
            'status' => '0',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($id > 0) {
            $data = ['name' => $request->name, 'email' => '', 'phone' => $request->phone, 'content' => $request->content];
            $this->sendMail($data);

            return response()->json([
                'status' => true,
                'message' => 'Cám ơn bạn đã gửi liên hệ! Chúng tôi sẽ sớm phản hồi lại cho bạn.',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra! Xin vui lòng thử lại',
            ]);
        }
    }

    public function contact(Request $req)
    {
        if (getConfig('recaptcha_status')) {
            $context = stream_context_create([
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query([
                        'secret' => getConfig('recaptcha_secret_key'),
                        'response' => request('recaptcha'),
                    ]),
                ],
            ]);
            $result = json_decode(file_get_contents(env('GOOGLEURL'), false, $context));
            if ($result->success != true) {
                return redirect()->back()->with('error', 'Quá thời gian xử lý, xin vui lòng thử lại');
            }
        }
        $this->validate($req, [
            'name' => 'required',
            'phone' => 'required',
            'content' => 'required',
        ], [
            'name.required' => 'Bạn chưa nhập họ tên',
            'phone.required' => 'Bạn chưa nhập số điện thoại',
            'content.required' => 'Bạn chưa nhập nội dung',
        ]);
        $id = Contact::insertGetId([
            'name' => $req->name,
            'email' => $req->email,
            'phone' => $req->phone,
            'content' => $req->content,
            'status' => '0',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($id > 0) {
            $data = ['name' => $req->name, 'email' => $req->email, 'phone' => $req->phone, 'content' => $req->content];
            $this->sendMail($data);

            return redirect()->back()->with('success', 'Gửi liên hệ thành công! Chúng tôi sẽ sớm phản hồi lại cho bạn.');
        } else {
            return redirect()->back()->with('error', 'Có lỗi xảy ra trong quá trình gửi, xin vui lòng thử lại');
        }
    }

    public function template($info)
    {
        $data['info'] = $info;

        return view('Website::email.contact', $data);
    }

    public function sendMail($info)
    {
        $mail = new PHPMailer\PHPMailer;
        $mail->IsSMTP();  // telling the class to use SMTP
        $mail->Host = getConfig('smtp_host'); // host smtp để gửi mail
        $mail->Port = getConfig('smtp_port'); // cổng để gửi mail
        $mail->SMTPSecure = getConfig('smtp_encryption'); // Phương thức mã hóa thư - ssl hoặc tls
        $mail->SMTPAuth = true;
        $mail->Username = getConfig('smtp_email'); // SMTP username
        $mail->Password = getConfig('smtp_password'); // SMTP password
        $mail->From = getConfig('emai_name_send');
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = 'Liên hệ';
        $body = $this->template($info);
        $mail->MsgHTML($body);
        $mail->SetFrom(getConfig('emai_name_send'), getConfig('email_send'));
        $mail->AddReplyTo(getConfig('reply_email'), getConfig('reply_name'));
        $mail->AddAddress(getConfig('reply_email'), getConfig('reply_name'));
        if ($mail->Send()) {
            return 'TRUE';
        } else {
            return 'FALSE';
        }
    }
}

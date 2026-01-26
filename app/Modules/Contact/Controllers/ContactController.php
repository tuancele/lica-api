<?php

declare(strict_types=1);

namespace App\Modules\Contact\Controllers;

use App\Config;
use App\Http\Controllers\Controller;
use App\Modules\Contact\Models\Contact;
use Illuminate\Http\Request;
use Mail;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        active('contact', 'list');
        $data['contacts'] = Contact::where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->where('name', 'like', '%'.$request->get('keyword').'%')->orWhere('email', 'like', '%'.$request->get('keyword').'%')->orWhere('phone', 'like', '%'.$request->get('keyword').'%');
            }
        })->orderBy('id', 'desc')->paginate(20);

        return view('Contact::index', $data);
    }

    public function view($id)
    {
        active('contact', 'list');
        $contact = Contact::find($id);
        if (! isset($contact) && empty($contact)) {
            return redirect('admin/contact');
        }
        Contact::where('id', $id)->update([
            'status' => '1',
        ]);
        $data['contact'] = $contact;

        return view('Contact::view', $data);
    }

    public function forward($id)
    {
        active('contact', 'list');
        $contact = Contact::find($id);
        if (! isset($contact) && empty($contact)) {
            return redirect('admin/contact');
        }
        $data['contact'] = $contact;

        return view('Contact::forward', $data);
    }

    public function reply($id)
    {
        active('contact', 'list');
        $contact = Contact::find($id);
        if (! isset($contact) && empty($contact)) {
            return redirect('admin/contact');
        }
        $data['contact'] = $contact;

        return view('Contact::reply', $data);
    }

    public function create()
    {
        active('contact', 'create');

        return view('Contact::create');
    }

    public function postReply(Request $req)
    {
        $contact = Contact::find($req->id);
        if (isset($contact) && ! empty($contact)) {
            $data = [
                'subject' => 'Thư phản hồi',
                'email' => $contact->email,
                'name' => $contact->name,
                'content' => $req->content,
            ];
            $this->sendMail($data);

            return response()->json([
                'status' => 'success',
                'alert' => 'Gửi phản hồi thành công!',
                'url' => '/admin/contact',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Không tồn tại thư phản hồi!']],
            ]);
        }
    }

    public function sendMail($data)
    {
        $config = Config::where('code', 'email')->first();
        $valemail = json_decode($config->content);
        Mail::send('admin.email.contact', $data, function ($message) use ($valemail, $data) {
            $message->to($data['email'], $data['name'])
                ->subject($data['subject']);
            $message->from($valemail->email_send, $valemail->name_send);
        });
    }

    public function postForward(Request $req)
    {
        $data = [
            'subject' => 'Thư phản hồi',
            'email' => $req->email,
            'name' => $req->name,
            'content' => $req->content,
        ];
        $this->sendMail($data);

        return response()->json([
            'status' => 'success',
            'alert' => 'Gửi thư thành công!',
            'url' => '/admin/contact',
        ]);
    }

    public function postCreate(Request $req)
    {
        $data = [
            'subject' => $req->subject,
            'email' => $req->email,
            'name' => $req->name,
            'content' => $req->content,
        ];
        $this->sendMail($data);

        return response()->json([
            'status' => 'success',
            'alert' => 'Gửi thư thành công!',
            'url' => '/admin/contact',
        ]);
    }

    public function delete(Request $request)
    {
        $data = Contact::findOrFail($request->id)->delete();
        if ($request->page != '') {
            $url = '/admin/contact?page='.$request->page;
        } else {
            $url = '/admin/contact';
        }

        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url,
        ]);
    }

    public function action(Request $request)
    {
        $check = $request->checklist;
        if (! isset($check) && empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Chưa chọn dữ liệu cần thao tác!']],
            ]);
        }
        $action = $request->action;
        if ($action == 0) {
            foreach ($check as $key => $value) {
                Contact::where('id', $value)->update([
                    'status' => '0',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Đánh dấu chưa đọc thành công!',
                'url' => '/admin/contact',
            ]);
        } elseif ($action == 1) {
            foreach ($check as $key => $value) {
                Contact::where('id', $value)->update([
                    'status' => '1',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Đánh dấu đã đọc thành công!',
                'url' => '/admin/contact',
            ]);
        } else {
            foreach ($check as $key => $value) {
                Contact::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa liên hệ thành công!',
                'url' => '/admin/contact',
            ]);
        }
    }
}

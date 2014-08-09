<?php
namespace Hand\Controllers;

use Zeuxisoo\Core\Validator;
use Hand\Abstracts\Controller;
use Hand\Helpers\Paginate;
use Hand\Models;

class Message extends Controller {

    public function __construct() {
        parent::__construct();

        $this->app_config = $this->slim->config('app.config');
    }

    public function create() {
        if ($this->slim->request->isPost() === true) {
            $username = $this->slim->request->post('username');
            $subject  = $this->slim->request->post('subject');
            $content  = $this->slim->request->post('content');

            $validator = Validator::factory($this->slim->request->post());
            $validator->add('username', 'Please enter username')->rule('required')
                      ->add('subject', 'Please enter subject')->rule('required')
                      ->add('content', 'Please enter content')->rule('required');

            $valid_type    = 'error';
            $valid_message = '';

            if ($validator->inValid() === true) {
                $valid_message = $validator->firstError();
            }else{
                $user = Models\User::where('username', $username)->first(['id']);

                if (empty($user) === true) {
                    $valid_message = 'Can not found user';
                }else{
                    Models\Message::create([
                        'sender_id'   => $_SESSION['user']['id'],
                        'receiver_id' => $user->id,
                        'category'    => $this->app_config['default']['message']['create_status'],
                        'subject'     => $subject,
                        'content'     => $content
                    ]);

                    $valid_type    = 'success';
                    $valid_message = 'Message sent';
                }
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor('message.create'));
        }else{
            $username   = $this->slim->request->get('username');
            $message_id = $this->slim->request->get('message_id');

            $default_subject  = "";
            $default_username = "";

            if (empty($username) === false) {
                $default_username = $username;
            }

            if (empty($message_id) === false) {
                $message = Models\Message::whereReceiverId($_SESSION['user']['id'])->with('sender')->find($message_id, ['id', 'sender_id', 'subject']);

                $default_subject  = "Reply: ".$message->subject;
                $default_username = $message->sender->username;
            }

            $this->slim->render('message/create.html', [
                'default_subject'  => $default_subject,
                'default_username' => $default_username,
            ]);
        }
    }

    public function manage() {
        $model_message = Models\Message::whereReceiverId($_SESSION['user']['id']);

        $total     = $model_message->count('id');
        $paginate  = Paginate::instance(['count' => $total, 'size' => 12]);
        $messages  = $model_message->take(12)->skip($paginate->offset)->with('sender')->orderBy('created_at', 'desc')->get([
            'id', 'subject', 'sender_id', 'have_read', 'category', 'created_at'
        ]);

        $this->slim->render('message/manage.html', [
            'messages'  => $messages,
            'paginate'  => $paginate->buildPageBar([
                'type' => Paginate::TYPE_BACK_NEXT,
            ])
        ]);
    }

    public function delete($message_id) {
        $message = Models\Message::whereReceiverId($_SESSION['user']['id'])->find($message_id, ['id']);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($message) === true) {
            $valid_message = 'Can not found message';
        }else{
            $message->delete();

            $valid_type    = 'success';
            $valid_message = 'Deleted message';
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('message.manage'));
    }

    public function detail($message_id) {
        $message = Models\Message::whereReceiverId($_SESSION['user']['id'])->with('sender')->find($message_id, [
            'id', 'subject', 'sender_id', 'created_at', 'content'
        ]);

        if (empty($message) === true) {
            $this->slim->flash('error', 'Can not found message');
            $this->slim->redirect($this->slim->urlFor('message.manage'));
        }else{
            $message->update(['have_read' => 1]);

            $this->slim->render('message/detail.html', [
                'message' => $message,
            ]);
        }
    }

    public function unread($message_id) {
        $message = Models\Message::whereReceiverId($_SESSION['user']['id'])->with('sender')->find($message_id, ['id']);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($message) === true) {
            $valid_message = 'Can not found message';
        }else{
            $message->update(['have_read' => 0]);

            $valid_type    = 'success';
            $valid_message = 'The message was marked as unread';
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('message.manage'));
    }

    public function unread_number() {
        $response = $this->slim->response();
        $response['Content-Type'] = 'application/json';
        $response->write(json_encode([
            'number' => Models\Message::whereReceiverId($_SESSION['user']['id'])->whereHaveRead(false)->count(),
        ]));
    }

}

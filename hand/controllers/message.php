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
                $user = Models\User::where('username', $username)->first();

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
            $this->slim->render('message/create.html');
        }
    }

    public function manage() {
        $total     = Models\Message::where('receiver_id', $_SESSION['user']['id'])->count();
        $paginate  = Paginate::instance(['count' => $total, 'size' => 12]);
        $messages = Models\Message::where('receiver_id', $_SESSION['user']['id'])->take(12)->skip($paginate->offset)->with('sender')->orderBy('created_at', 'desc')->get();

        $this->slim->render('message/manage.html', [
            'messages'  => $messages,
            'paginate'  => $paginate->buildPageBar([
                'type' => Paginate::TYPE_BACK_NEXT,
            ])
        ]);
    }

    public function delete($message_id) {
        $message = Models\Message::find($message_id);

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
        $message = Models\Message::with('sender')->find($message_id);

        if (empty($message) === true) {
            $this->slim->flash('error', 'Can not found message');
            $this->slim->redirect($this->slim->urlFor('message.manage'));
        }else{
            $this->slim->render('message/detail.html', [
                'message' => $message,
            ]);
        }
    }

}

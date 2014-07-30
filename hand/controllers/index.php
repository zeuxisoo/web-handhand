<?php
namespace Hand\Controllers;

use Zeuxisoo\Core\Validator;
use Hand\Abstracts\Controller;
use Hand\Helpers\Secure;
use Hand\Helpers\Authorize;
use Hand\Helpers\Paginate;
use Hand\Models;

class Index extends Controller {

    public function index() {
        $category   = $this->slim->request->get('category', 0);
        $property   = $this->slim->request->get('property', 0);
        $model_item = Models\Item::status('publish');

        $param_keys = ['category', 'property'];
        foreach($param_keys as $param_key) {
            if (empty($$param_key) === false) {
                $model_item = $model_item->where($param_key, $$param_key);
                break;
            }
        }

        $total    = $model_item->count();
        $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
        $items    = $model_item->take(12)->skip($paginate->offset)->with('images')->get();

        $this->slim->render('index/index.html', [
            'items' => $items,
            'paginate' => $paginate->buildPageBar([
                'type' => Paginate::TYPE_BACK_NEXT,
            ])
        ]);
    }

    public function signup() {
        if ($this->slim->request->isPost() === true) {
            $username = $this->slim->request->post('username');
            $email    = $this->slim->request->post('email');
            $password = $this->slim->request->post('password');

            $validator = Validator::factory($this->slim->request->post());
            $validator->add('username', 'Please enter username')->rule('required')
                      ->add('email', 'Please enter email')->rule('required')
                      ->add('password', 'Please enter password')->rule('required')
                      ->add('email', 'Invalid email address')->rule('valid_email')
                      ->add('password', 'Password length must more than 8 chars')->rule('min_length', 8)
                      ->add('username', 'Username only support A-Z,a-z,0-9 and _')->rule('match_pattern', '/^[A-Za-z0-9_]+$/')
                      ->add('username', 'Username length must more than 4 chars')->rule('min_length', 4);

            $valid_type    = 'error';
            $valid_message = '';

            if ($validator->inValid() === true) {
                $valid_message = $validator->firstError();
            }else if (Models\User::where('username', $username)->first() !== null) {
                $valid_message = 'Username already exists.';
            }else if (Models\User::where('email', $email)->first() !== null) {
                $valid_message = 'Email already exists.';
            }else{
                $user = Models\User::create([
                    'username' => $username,
                    'email'    => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                ]);

                Models\UserSettings::create([
                    'user_id' => $user->id,
                ]);

                $valid_type    = 'success';
                $valid_message = 'Thank for you registeration. Your account already created.';
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor('index.signup'));
        }else{
            $this->slim->render('index/signup.html');
        }
    }

    public function signin() {
        if ($this->slim->request->isPost() === true) {
            $account  = $this->slim->request->post('account');
            $password = $this->slim->request->post('password');
            $remember = $this->slim->request->post('remember');

            $validator = Validator::factory($this->slim->request->post());
            $validator->add('account', 'Please enter account')->rule('required')
                      ->add('password', 'Please enter password')->rule('required');

            $valid_type     = 'error';
            $valid_message  = '';
            $valid_redirect = "index.signin";

            if ($validator->inValid() === true) {
                $valid_message = $validator->firstError();
            }else{
                if (strpos($account, '@') === false) {
                    $user = Models\User::where('username', $account)->first();
                }else{
                    $user = Models\User::where('email', $account)->first();
                }

                if (empty($user->username) === true) {
                    $valid_message = 'The user not exists.';
                }else if (password_verify($password, $user->password) === false) {
                    $valid_message = 'Password not match.';
                }else{
                    if ($remember === 'y') {
                        $config       = $this->slim->config('app.config');
                        $signin_token = Secure::randomString();

                        $this->slim->setCookie(
                            $config['remember']['name'],
                            Secure::createKey($user->id, $signin_token, $config['cookie']['secret_key']),
                            time() + $config['remember']['life_time']
                        );
                    }

                    Authorize::initLoginSession($user);

                    $valid_type     = "success";
                    $valid_message  = "Welcome back!";
                    $valid_redirect = "index.index";
                }
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor($valid_redirect));
        }else{
            $this->slim->render('index/signin.html');
        }
    }

    public function signout() {
        unset($_SESSION['user']);

        $config = $this->slim->config('app.config');

        $this->slim->deleteCookie($config['remember']['name']);
        $this->slim->redirect($this->slim->urlFor('index.index'));
    }

}

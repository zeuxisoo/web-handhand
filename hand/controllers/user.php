<?php
namespace Hand\Controllers;

use Zeuxisoo\Core\Validator;
use Hand\Abstracts\Controller;
use Hand\Models;

class User extends Controller {

    public function account() {
        if ($this->slim->request->isPost() === true) {
            $username = $this->slim->request->post('username');
            $email    = $this->slim->request->post('email');

            $valid_type     = 'error';
            $valid_message  = 'No changes';
            $valid_redirect = "user.account";

            $update_fields = [];

            if (empty($username) === false) {
                if (Models\User::where('username', $username)->first() !== null) {
                    $valid_message = 'Username already exists.';
                }else{
                    $update_fields['username'] = $username;
                }
            }

            if (empty($email) === false) {
                if (Models\User::where('email', $email)->first() !== null) {
                    $valid_message = 'Email already exists.';
                }else{
                    $update_fields['email'] = $email;
                }
            }

            if (empty($update_fields) === false) {
                Models\User::find($_SESSION['user']['id'])->update($update_fields);

                $valid_type     = 'success';
                $valid_message  = 'Your account info was updated. Please sign in again.';
                $valid_redirect = "index.signout";
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor($valid_redirect));
        }else{
            $this->slim->render('user/account.html');
        }
    }

    public function password() {
        if ($this->slim->request->isPost() === true) {
            $old_password     = $this->slim->request->post('old_password');
            $new_password     = $this->slim->request->post('new_password');
            $confirm_password = $this->slim->request->post('confirm_password');

            $validator = Validator::factory($this->slim->request->post());
            $validator->add('old_password', 'Please enter old password')->rule('required')
                      ->add('new_password', 'Please enter new password')->rule('required')
                      ->add('confirm_password', 'Please enter confirm password')->rule('required')
                      ->add('new_password', 'New password can not match confirm password')->rule('match_field', 'confirm_password');

            $valid_type    = 'error';
            $valid_message = '';
            $valid_redirect = "user.password";

            if ($validator->inValid() === true) {
                $valid_message = $validator->firstError();
            }else{
                $user = Models\User::find($_SESSION['user']['id']);

                if (password_verify($old_password, $user->password) === false) {
                    $valid_message = 'Password incorrect!';
                }else{
                    $user->update([
                        'password' => password_hash($new_password, PASSWORD_BCRYPT)
                    ]);

                    $valid_type    = 'success';
                    $valid_message = 'Your password was updated. Please sign in again.';
                    $valid_redirect = "index.signout";
                }
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor($valid_redirect));
        }else{
            $this->slim->render('user/password.html');
        }
    }

}

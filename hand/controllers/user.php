<?php
namespace Hand\Controllers;

use Zeuxisoo\Core\Validator;
use Hand\Abstracts\Controller;
use Hand\Helpers\Paginate;
use Hand\Models;

class User extends Controller {

    public function profile($username) {
        $user = Models\User::where('username', $username)->first();

        if (empty($user) === true) {
            $this->slim->flash('error', 'Can not found user');
            $this->slim->redirect($this->slim->urlFor('index.index'));
        }else{
            $tab = $this->slim->request->get('tab', 'publish');

            $bookmarks = Models\ItemBookmark::where('user_id', $user->id)->get();
            $counters  = Models\Item::selectRaw("
                SUM(status='publish') AS publish_count,
                SUM(status='trade') AS trade_count
            ")->where('user_id', $user->id)->where(function($query) {
                $query->where('status', 'publish')->orWhere('status', 'trade');
            })->first();

            if ($tab == 'publish') {
                $total    = Models\Item::status('publish')->count('id');
                $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
                $items    = Models\Item::status('publish')->take(12)->skip($paginate->offset)->with('images')->get();
            }else{
                $bookmark_item_ids = $bookmarks->lists('item_id');

                $total    = Models\Item::whereIn('id', $bookmark_item_ids)->count('id');
                $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
                $items    = Models\Item::status('publish')->whereIn('id', $bookmark_item_ids)->take(12)->skip($paginate->offset)->with('images')->get();
            }

            $this->slim->render('user/profile.html', [
                'user'  => $user,
                'items' => $items,
                'counter' => [
                    'publishs'  => $counters->publish_count,
                    'trades'    => $counters->trade_count,
                    'bookmarks' => $bookmarks->count(),
                ],
                'paginate' => $paginate->buildPageBar([
                    'type' => Paginate::TYPE_BACK_NEXT,
                ])
            ]);
        }
    }

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

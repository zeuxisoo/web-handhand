<?php
namespace Hand\Controllers;

use Zeuxisoo\Core\Validator;
use Hand\Abstracts\Controller;
use Hand\Helpers\Paginate;
use Hand\Models;

class User extends Controller {

    public function profile($username) {
        $user = Models\User::where('username', $username)->first(['id', 'username', 'created_at']);

        if (empty($user) === true) {
            $this->slim->flash('error', 'Can not found user');
            $this->slim->redirect($this->slim->urlFor('index.index'));
        }else{
            $tab = $this->slim->request->get('tab', 'publish');

            $view_file = 'user/profile/default.html';
            $bookmarks = Models\ItemBookmark::whereUserId($user->id)->get(['id', 'item_id']);
            $counters  = Models\Item::selectRaw("
                SUM(status='publish') AS publish_count,
                SUM(status='done') AS done_count
            ")->whereUserId($user->id)->where(function($query) {
                $query->where('status', 'publish')->orWhere('status', 'done');
            })->first(['publish_count', 'done_count']);

            switch($tab) {
                case 'publish':
                    $total    = Models\Item::whereStatus('publish')->whereUserId($user->id)->count('id');
                    $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
                    $items    = Models\Item::whereStatus('publish')->whereUserId($user->id)->take(12)->skip($paginate->offset)->with('images')->get(['id', 'title']);
                    break;
                case 'bookmark':
                    $bookmark_item_ids = $bookmarks->lists('item_id');

                    $total    = Models\Item::whereIn('id', $bookmark_item_ids)->whereUserId($user->id)->count('id');
                    $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
                    $items    = Models\Item::whereStatus('publish')->whereIn('id', $bookmark_item_ids)->whereUserId($user->id)->take(12)->skip($paginate->offset)->with('images')->get(['id', 'title']);
                    break;
                case 'rate':
                    $view_file = 'user/profile/rate.html';

                    $total    = Models\Item::whereStatus('done')->whereUserId($user->id)->count('id');
                    $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
                    $items    = Models\Item::whereStatus('done')->whereUserId($user->id)->take(12)->skip($paginate->offset)->with([
                        'images',
                        'trade' => function($query) {
                            $query->with('user');
                        }
                    ])->get(['id', 'title', 'price', 'category', 'property']);
                    break;
            }

            $this->slim->render($view_file, [
                'user'  => $user,
                'items' => $items,
                'counter' => [
                    'publishs'  => $counters->publish_count,
                    'dones'     => $counters->done_count,
                    'bookmarks' => $bookmarks->count(),
                ],
                'paginate' => $paginate->buildPageBar([
                    'type' => Paginate::TYPE_BACK_NEXT,
                ])
            ]);
        }
    }

    public function ban($username) {
        $user = Models\User::where('username', $username)->first(['id']);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($user) === true) {
            $valid_message = 'Can not found user';
        }else if ($this->isAdmin() === false) {
            $valid_message = 'You can not ban this user if you are not admin';
        }else{
            // Hide the publish items
            Models\Item::whereIn('id', $user->items()->whereStatus('publish')->get()->modelKeys())->update(['status' => 'hide']);

            // Ban user
            $user->update(['status' => 'banned']);

            $valid_type    = 'success';
            $valid_message = 'The user was banned';
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('index.index'));
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
                }else if (preg_match('/^[A-Za-z0-9_]+$/', $username) == false) {
                    $valid_message = 'Username only support A-Z,a-z,0-9 and _';
                }else if (strlen($username) < 4) {
                    $valid_message = 'Username length must more than 4 chars';
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

    function settings() {
        if ($this->slim->request->isPost() === true) {
            $settings = $this->slim->request->post('settings', []);

            // Merge default settings
            $settings = array_merge([
                'notify_trade'   => 0,
                'notify_comment' => 0,
            ], $settings);

            // Save to database
            Models\UserSettings::where('user_id', $_SESSION['user']['id'])->update($settings);

            // Update current settings in session
            foreach($settings as $name => $value) {
                $_SESSION['user']['settings'][$name] = $value;
            }

            $this->slim->flash('success', 'Your settings was updated');
            $this->slim->redirect($this->slim->urlFor('user.settings'));
        }else{
            $settings = Models\UserSettings::where('user_id', $_SESSION['user']['id'])->first();

            $this->slim->render('user/settings.html', [
                'settings' => $settings,
            ]);
        }
    }

}

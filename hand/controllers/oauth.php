<?php
namespace Hand\Controllers;

use Hybridauth\Hybridauth;
use Hybridauth\Endpoint;
use Hand\Abstracts\Controller;
use Hand\Models;
use Hand\Helpers\Authorize;
use Hand\Helpers\Secure;

class OAuth extends Controller {

    public function connect($provider_name) {
        $app_config  = $this->slim->config('app.config');
        $site_url    = $this->slim->config('app.site_url');
        $auth_config = array_merge($app_config['oauth'], [
            'base_url'   => $site_url.$this->slim->urlFor('oauth.callback'),
            'debug_mode' => $app_config['default']['debug'],
        ]);

        try {
            $hybridauth = new Hybridauth($auth_config);
            $adapter    = $hybridauth->authenticate($provider_name); // Callback > Continue

            $tokens       = serialize((array) $adapter->getTokens());
            $user_profile = $adapter->getUserProfile();
            $provider_uid = $user_profile->getIdentifier();
            $email        = $user_profile->getEmail();
            $display_name = $user_profile->getDisplayName();
            $first_name   = $user_profile->getFirstName();
            $last_name    = $user_profile->getLastName();
            $profile_url  = $user_profile->getProfileURL();
            $website_url  = $user_profile->getWebSiteURL();
            $photo_url    = $user_profile->getPhotoURL();

            // Check connected social network
            $user_connection = Models\UserConnection::whereProviderName($provider_name)->whereProviderUid($provider_uid)->first();

            // If connected, just find out user
            if (empty($user_connection) === false) {
                $user = $user_connection->user;
            }else{
                // If not connected, try to find user record by connection email
                $user = Models\User::whereEmail($email)->first();

                // If the email is not registered, create user account
                if (empty($user->email) === true) {
                    $user = Models\User::create([
                        'username' => Secure::shortUsername(join('/', [$provider_name, $provider_uid, $email, uniqid(), time()]))[0],
                        'email'    => $email,
                        'password' => password_hash(Secure::randomString(36), PASSWORD_BCRYPT),
                        'status'   => 'active',
                    ]);

                    Models\UserSettings::create([
                        'user_id' => $user->id,
                    ]);
                }

                // Create new connection and link connection to this user;
                $user_connection = Models\UserConnection::create([
                    'user_id'       => $user->id,
                    'provider_name' => $provider_name,
                    'provider_uid'  => $provider_uid,
                    'email'         => $email,
                    'display_name'  => $display_name,
                    'first_name'    => $first_name,
                    'last_name'     => $last_name,
                    'profile_url'   => $profile_url,
                    'website_url'   => $website_url,
                    'photo_url'     => $photo_url,
                    'tokens'        => $tokens,
                ]);
            }

            // Init login session
            Authorize::initLoginSession($user);
            Authorize::initLoginProviderName($provider_name);

            // Redirect to index page
            $this->slim->redirect($this->slim->urlFor('index.index'));
        }catch(\Exception $e) {
            if ($app_config['default']['debug'] === true) {
                echo $e;
            }else{
                $this->slim->flash('error', 'Unknown error when conect to provider');
                $this->slim->redirect($this->slim->urlFor('index.signin'));
            }
        }
    }

    public function callback() {
        $error             = $this->slim->request->get('error');
        $error_reason      = $this->slim->request->get('error_reason');
        $error_description = $this->slim->request->get('error_description');

        if (empty($error) === false) {
            $this->slim->flash('error', "Authentication failed! You canceled the authentication");
            $this->slim->redirect($this->slim->urlFor('index.signin'));
        }else{
            $endpoint = new Endpoint();
            $endpoint->process();
        }
    }

}

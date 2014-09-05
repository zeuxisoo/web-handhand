<?php
namespace Hand;

use Slim\Slim;
use Slim\Extras;
use Slim\Views;
use Slim\Middleware;
use Illuminate\Database;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Hand\Middlewares\Route;
use Hand\Middlewares\TurboLinks;
use Hand\Helpers\View;
use Hand\Hooks\SessionManager;
use Hand\Hooks\AssetManager;

class App {

    protected $slim   = null;
    protected $config = [];

    public function __construct($config) {
        if (is_file($config) === true && file_exists($config) === true) {
            $this->config = require($config);
        }else{
            throw new \Exception('Can not load the default config file');
        }
    }

    public function registerAutoload() {
        spl_autoload_register(function($_class) {
            $file_path = str_replace('\\', DIRECTORY_SEPARATOR, $_class);
            $path_info = pathinfo($file_path);
            $directory = strtolower($path_info['dirname']);

            $filename_underscore = preg_replace('/\B([A-Z])/', '_$1', $path_info['filename']);

            $class_file_normal     = WWW_ROOT.'/'.$directory.DIRECTORY_SEPARATOR.strtolower($path_info['filename']).'.php';
            $class_file_underscore = WWW_ROOT.'/'.$directory.DIRECTORY_SEPARATOR.strtolower($filename_underscore).'.php';

            if (is_file($class_file_normal) === true) {
                require_once $class_file_normal;
            }else if (is_file($class_file_underscore) === true) {
                require_once $class_file_underscore;
            }
        });
    }

    public function registerDatabase() {
        $capsule_manager = new Database\Capsule\Manager();
        $capsule_manager->addConnection($this->config['database']);
        $capsule_manager->setAsGlobal();
        $capsule_manager->bootEloquent();

        return $capsule_manager;
    }

    public function registerSlim() {
        $this->slim = new Slim(array(
            'debug'               => $this->config['default']['debug'],
            'view'                => new Views\Twig(),
            'cookies.encrypt'     => true,
            'cookies.lifetime'    => $this->config['cookie']['life_time'],
            'cookies.path'        => $this->config['cookie']['path'],
            'cookies.domain'      => $this->config['cookie']['domain'],
            'cookies.secure'      => $this->config['cookie']['secure'],
            'cookies.httponly'    => $this->config['cookie']['httponly'],
            'cookies.secret_key'  => $this->config['cookie']['secret_key'],
            'cookies.cipher'      => MCRYPT_RIJNDAEL_256,
            'cookies.cipher_mode' => MCRYPT_MODE_CBC,
        ));
    }

    public function registerSlimMiddleware() {
        $this->slim->add(new Extras\Middleware\CsrfGuard());
        $this->slim->add(new TurboLinks());
    }

    public function registerSlimView() {
        $view = $this->slim->view();
        $view->twigTemplateDirs = [APP_ROOT.'/views'];
        $view->parserOptions    = [
            'charset'          => 'utf-8',
            'cache'            => realpath(CACHE_ROOT.'/view'),
            'auto_reload'      => true,
            'strict_variables' => false,
            'autoescape'       => true
        ];
        $view->parserExtensions = [
            new Views\TwigExtension(),
            new View(),
        ];
    }

    public function registerLocale() {
        $view = $this->slim->view();

        $this->slim->container->singleton('locale', function() {
            $translator = new Translator('en_US', new MessageSelector());
            $translator->setFallbackLocales(['en_US']);
            $translator->addLoader('array', new ArrayLoader());

            foreach(glob(LOCALE_ROOT.'/*') as $locale_path) {
                foreach(glob($locale_path.'/*') as $file_path) {
                    $resource = require $file_path;
                    $translator->addResource('array', $resource, basename($locale_path));
                }
            }

            return $translator;
        });

        array_push($view->parserExtensions, new TranslationExtension($this->slim->locale));
    }

    public function registerSlimRoutes() {
        $this->slim->get('/', '\Hand\Controllers\Index:index')->name('index.index');
        $this->slim->map('/signup', '\Hand\Controllers\Index:signup')->name('index.signup')->via('GET', 'POST');
        $this->slim->map('/signin', '\Hand\Controllers\Index:signin')->name('index.signin')->via('GET', 'POST');
        $this->slim->get('/signout', '\Hand\Controllers\Index:signout')->name('index.signout');

        $this->slim->group('/user', function() {
            $this->slim->get('/profile/:username', '\Hand\Controllers\User:profile')->name('user.profile');
            $this->slim->get('/ban/:username', Route::requireLogin(), '\Hand\Controllers\User:ban')->name('user.ban');

            $this->slim->map('/account', Route::requireLogin(), '\Hand\Controllers\User:account')->name('user.account')->via('GET', 'POST');
            $this->slim->map('/password', Route::requireLogin(), '\Hand\Controllers\User:password')->name('user.password')->via('GET', 'POST');
            $this->slim->map('/settings', Route::requireLogin(), '\Hand\Controllers\User:settings')->name('user.settings')->via('GET', 'POST');

            $this->slim->group('/item', function() {
                $this->slim->map('/create', Route::requireLogin(), '\Hand\Controllers\Users\Item:create')->name('user.item.create')->via('GET', 'POST');
                $this->slim->get('/manage', Route::requireLogin(), '\Hand\Controllers\Users\Item:manage')->name('user.item.manage');
                $this->slim->get('/delete/:item_id', Route::requireLogin(), '\Hand\Controllers\Users\Item:delete')->name('user.item.delete');
                $this->slim->map('/edit/:item_id/detail', Route::requireLogin(), '\Hand\Controllers\Users\Item:edit_detail')->name('user.item.edit.detail')->via('GET', 'POST');
                $this->slim->map('/edit/:item_id/image/upload', Route::requireLogin(), '\Hand\Controllers\Users\Item:edit_image_upload')->name('user.item.edit.image.upload')->via('GET', 'POST');
                $this->slim->map('/edit/:item_id/image/manage', Route::requireLogin(), '\Hand\Controllers\Users\Item:edit_image_manage')->name('user.item.edit.image.manage')->via('GET', 'POST');
                $this->slim->get('/edit/:item_id/image/delete/:item_image_id', Route::requireLogin(), '\Hand\Controllers\Users\Item:edit_image_delete')->name('user.item.edit.image.delete');
                $this->slim->get('/trade/:item_id/cancel', Route::requireLogin(), '\Hand\Controllers\Users\Item:trade_cancel')->name('user.item.trade.cancel');
                $this->slim->get('/trade/:item_id/done', Route::requireLogin(), '\Hand\Controllers\Users\Item:trade_done')->name('user.item.trade.done');
            });
        });

        $this->slim->group('/item', function() {
            $this->slim->get('/detail/:item_id', '\Hand\Controllers\Item:detail')->name('item.detail');
            $this->slim->post('/detail/:item_id/comment', Route::requireLogin(), '\Hand\Controllers\Item:detail_comment')->name('item.detail.comment');
            $this->slim->get('/bookmark/:item_id/create', Route::requireLogin(),'\Hand\Controllers\Item:bookmark_create' )->name('item.bookmark.create');
            $this->slim->get('/bookmark/:item_id/delete', Route::requireLogin(),'\Hand\Controllers\Item:bookmark_delete' )->name('item.bookmark.delete');
            $this->slim->get('/trade/:item_id', Route::requireLogin(), '\Hand\Controllers\Item:trade')->name('item.trade');
            $this->slim->get('/block/:item_id', Route::requireLogin(), '\Hand\Controllers\Item:block')->name('item.block');
        });

        $this->slim->group('/bookmark', function() {
            $this->slim->get('/', Route::requireLogin(), '\Hand\Controllers\Bookmark:index')->name('bookmark.index');
        });

        $this->slim->group('/message', function() {
            $this->slim->map('/create', Route::requireLogin(), '\Hand\Controllers\Message:create')->name('message.create')->via('GET', 'POST');
            $this->slim->get('/manage', Route::requireLogin(), '\Hand\Controllers\Message:manage')->name('message.manage');
            $this->slim->get('/delete/:message_id', Route::requireLogin(), '\Hand\Controllers\Message:delete')->name('message.delete');
            $this->slim->get('/detail/:message_id', Route::requireLogin(), '\Hand\Controllers\Message:detail')->name('message.detail');
            $this->slim->get('/unread/:message_id', Route::requireLogin(), '\Hand\Controllers\Message:unread')->name('message.unread');
            $this->slim->get('/unread_number', Route::requireLogin(), '\Hand\Controllers\Message:unread_number')->name('message.unread_number');
        });

        $this->slim->group('/search', function() {
            $this->slim->map('/', '\Hand\Controllers\Search:index')->name('search.index')->via('GET', 'POST');
            $this->slim->get('/result', '\Hand\Controllers\Search:result')->name('search.result');
        });

        $this->slim->group('/trade', function() {
            $this->slim->get('/', Route::requireLogin(), '\Hand\Controllers\Trade:index')->name('trade.index');
            $this->slim->get('/rate/:item_id', Route::requireLogin(), '\Hand\Controllers\Trade:rate')->name('trade.rate');
            $this->slim->post('/done/:item_id', Route::requireLogin(), '\Hand\Controllers\Trade:done')->name('trade.done');
        });

        $this->slim->group('/oauth', function() {
            $this->slim->get('/connect/:provider_name', '\Hand\Controllers\OAuth:connect')->name('oauth.connect');
            $this->slim->get('/callback', '\Hand\Controllers\OAuth:callback')->name('oauth.callback');
        });
    }

    public function registerSlimConfig() {
        $request   = $this->slim->request();
        $site_url  = $request->getUrl().$request->getRootUri();

        $this->slim->config('app.config',   $this->config);
        $this->slim->config('app.site_url', $site_url);
    }

    public function registerSlimHook() {
        $this->slim->hook('slim.before', function() {
            $asset_manager = new AssetManager();
            $asset_manager->makeCSS();
            $asset_manager->makeJS();
        });

        $this->slim->hook('slim.before.dispatch', function() {
            $session_manager = new SessionManager();
            $session_manager->setLoginSession();
            $session_manager->checkBannedUser();

            $this->slim->view()->setData('config',  $this->config);
            $this->slim->view()->setData('session', $_SESSION);
        });

        $this->slim->hook('slim.after.dispatch', function() {
            if ($this->config['default']['debug'] === true) {
                if ($this->slim->response()->headers()->get('Content-Type') !== 'application/json') {
                    d(Database\Capsule\Manager::connection()->getQueryLog());
                }
            }
        });
    }

    public function run() {
        $this->slim->run();
    }

}

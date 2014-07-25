<?php
namespace Hand;

use Slim\Slim;
use Slim\Extras;
use Slim\Views;
use Slim\Middleware;
use Illuminate\Database;
use Hand\Middlewares\Route;
use Hand\Helpers\View;

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

            $class_file_normal     = WWW_ROOT.'/'.$directory.DIRECTORY_SEPARATOR.$path_info['filename'].'.php';
            $class_file_underscore = WWW_ROOT.'/'.$directory.DIRECTORY_SEPARATOR.$filename_underscore.'.php';

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
            'debug'              => $this->config['default']['debug'],
            'view'               => new Views\Twig(),
            'cookies.secret_key' => $this->config['cookie']['secret_key'],
            'cookies.encrypt'    => true,
            'cookies.cipher'     => MCRYPT_RIJNDAEL_256
        ));
    }

    public function registerSlimMiddleware() {
        $this->slim->add(new Middleware\SessionCookie($this->config['cookie']));
        $this->slim->add(new Extras\Middleware\CsrfGuard());
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

    public function registerSlimRoutes() {
        $this->slim->get('/', Route::reloadUserSession(), '\Hand\Controllers\Index:index')->name('index.index');
        $this->slim->map('/signup', Route::reloadUserSession(), '\Hand\Controllers\Index:signup')->name('index.signup')->via('GET', 'POST');
        $this->slim->map('/signin', Route::reloadUserSession(), '\Hand\Controllers\Index:signin')->name('index.signin')->via('GET', 'POST');
        $this->slim->get('/signout', Route::reloadUserSession(), '\Hand\Controllers\Index:signout')->name('index.signout');

        $this->slim->group('/user', function() {
            $this->slim->map('/account', Route::requireLogin(), '\Hand\Controllers\User:account')->name('user.account')->via('GET', 'POST');
            $this->slim->map('/password', Route::requireLogin(), '\Hand\Controllers\User:password')->name('user.password')->via('GET', 'POST');

            $this->slim->group('/item', function() {
                $this->slim->map('/create', Route::requireLogin(), '\Hand\Controllers\Users\Item:create')->name('user.item.create')->via('GET', 'POST');
                $this->slim->get('/manage', Route::requireLogin(), '\Hand\Controllers\Users\Item:manage')->name('user.item.manage');
                $this->slim->get('/delete/:item_id', Route::requireLogin(), '\Hand\Controllers\Users\Item:delete')->name('user.item.delete');
                $this->slim->map('/edit/:item_id/detail', Route::requireLogin(), '\Hand\Controllers\Users\Item:edit_detail')->name('user.item.edit.detail')->via('GET', 'POST');
                $this->slim->map('/edit/:item_id/image/upload', Route::requireLogin(), '\Hand\Controllers\Users\Item:edit_image_upload')->name('user.item.edit.image.upload')->via('GET', 'POST');
                $this->slim->map('/edit/:item_id/image/manage', Route::requireLogin(), '\Hand\Controllers\Users\Item:edit_image_manage')->name('user.item.edit.image.manage')->via('GET', 'POST');
                $this->slim->get('/edit/:item_id/image/delete/:item_image_id', Route::requireLogin(), '\Hand\Controllers\Users\Item:edit_image_delete')->name('user.item.edit.image.delete');
            });
        });

        $this->slim->group('/item', function() {
            $this->slim->get('/detail/:item_id', Route::reloadUserSession(), '\Hand\Controllers\Item:detail')->name('item.detail');
            $this->slim->post('/detail/:item_id/comment', Route::requireLogin(), '\Hand\Controllers\Item:detail_comment')->name('item.detail.comment');
        });
    }

    public function registerSlimConfig() {
        $request   = $this->slim->request();
        $site_url  = $request->getUrl().$request->getRootUri();

        $this->slim->config('app.config',   $this->config);
        $this->slim->config('app.site_url', $site_url);
    }

    public function registerSlimHook() {
        $this->slim->hook('slim.before.dispatch', function() {
            $this->slim->view()->setData('config',  $this->config);
            $this->slim->view()->setData('session', $_SESSION);
        });
    }

    public function run() {
        $this->slim->run();
    }

}

<?php
namespace Hand\Middlewares;

class CloudFlareSSL extends \Slim\Middleware {

    public function call() {
        $app = $this->app;
        if (isset($app->environment['HTTP_X_FORWARDED_PROTO']) === true && strtolower($app->environment['HTTP_X_FORWARDED_PROTO']) == "https") {
            $app->environment['slim.url_scheme'] = "https";
            $app->environment['SERVER_PORT'] = "443";
        }

        $this->next->call();
    }

}

<?php
namespace Hand\Middlewares;

class Turbolinks extends \Slim\Middleware {

    const REQUEST_METHOD_COOKIE_ATTR_NAME = 'request_method';
    const ORIGIN_REQUEST_HEADER           = 'X-XHR-Referer';
    const ORIGIN_RESPONSE_HEADER          = 'Location';
    const REDIRECT_RESPONSE_HEADER        = 'X-XHR-Redirected-To';
    const REDIRECT_SESSION_ATTR_NAME      = '_turbolinks_redirect_to';

    public function call() {
        $app = $this->app;

        if ($this->canHandleRedirect($app->request) === false) {
            $this->next->call();
        }else{
            $session = isset($_SESSION) === true ? $_SESSION : [];

            if (array_key_exists(self::REDIRECT_SESSION_ATTR_NAME, $session) === true) {
                $redirect_to = $session[self::REDIRECT_SESSION_ATTR_NAME];

                unsset($session[self::REDIRECT_SESSION_ATTR_NAME]);

                $app->response->headers->set(self::REDIRECT_RESPONSE_HEADER, $redirect_to);
            }

            if ($app->response->isRedirect() && $app->response->headers->has('Location')) {
                $_SESSION[self::REDIRECT_SESSION_ATTR_NAME] = $app->response->headers->get('Location');
            }

            $this->next->call();
        }
    }

    private function addRequestMethodCookie($request, $response) {
        $response->setCookie(self::REQUEST_METHOD_COOKIE_ATTR_NAME, $request->getMethod());
    }

    private function modifyStatusCode($request, $response) {
        if ($request->headers->has(self::ORIGIN_REQUEST_HEADER) === true) {
            if ($response->headers->has(self::ORIGIN_RESPONSE_HEADER) === true) {
                if ($this->haveSameOrigin($request, $response) === false) {
                    $app->response->setStatus(403);
                }
            }
        }
    }

    private function canHandleRedirect($request) {
        $session = isset($_SESSION) === true ? $_SESSION : null;

        return $session instanceof SessionInterface && $request->headers->has(self::ORIGIN_REQUEST_HEADER);
    }

    private function getUrlOrigin($url) {
        return [
            parse_url($url, PHP_URL_SCHEME),
            parse_url($url, PHP_URL_HOST),
            parse_url($url, PHP_URL_PORT),
        ];
    }

    private function haveSameOrigin($request, $response) {
        $request_origin = $this->getUrlOrigin($request->headers->get(self::ORIGIN_REQUEST_HEADER));
        $response_origin = $this->getUrlOrigin($response->headers->get(self::ORIGIN_RESPONSE_HEADER));

        return $request_origin == $response_origin;
    }

}

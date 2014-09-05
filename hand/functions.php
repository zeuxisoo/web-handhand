<?php
use Slim\Slim;

function locale($message, $arguments = array(), $domain = null, $locale = null) {
    if (null === $domain) {
        $domain = 'messages';
    }

    return Slim::getInstance()->locale->trans($message, $arguments, $domain, $locale);
}

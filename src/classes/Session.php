<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';


class Session
{

    public function __construct()
    {
        ini_set('session.use_only_cookies', 1);
        ini_set('session.hash_function', 'sha512');
        ini_set('session.hash_bits_per_character', 5);

        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            $cookieParams["lifetime"],
            $cookieParams["path"],
            $cookieParams["domain"],
            false, // set to true for HTTPS connections;
            true
        );
        session_name('__Oracular');
        session_start();
        session_regenerate_id(true);
    }

    public function isAdminLoggedIn(): bool
    {
        return true;
    }

}
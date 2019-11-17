<?php

namespace OracularApp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;
use OracularApp\Exceptions\UserNotFoundException;

class Session
{
    const SESSION_LOGGED_IN_KEY = 'LOGGED_IN';
    const SESSION_ADMIN_LOGGED_IN_KEY = 'ADMIN_LOGGED_IN';
    const SESSION_ADMIN = 'LOGGED.ADMIN';
    const SESSION_USER = 'LOGGED.USER';

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
        return $this->sessionSet() && isset($_SESSION[self::SESSION_ADMIN_LOGGED_IN_KEY]) && $_SESSION[self::SESSION_ADMIN_LOGGED_IN_KEY] === 1;
    }

    private function sessionSet(): bool
    {
        return (isset($_SESSION[self::SESSION_LOGGED_IN_KEY])) &&
            (isset($_SESSION[self::SESSION_USER]) || isset($_SESSION[self::SESSION_ADMIN]));
    }

    public function isUserLoggedIn(): bool
    {
        return $this->sessionSet() && !$this->isAdminLoggedIn();
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool
     * @throws UserNotFoundException
     */
    public function adminLogin(string $email, string $password): bool
    {
        try {
            $admin = new Admin(null, $email);
            if ($admin->login($password) === true) {
                $_SESSION[self::SESSION_LOGGED_IN_KEY] = 1;
                $_SESSION[self::SESSION_ADMIN_LOGGED_IN_KEY] = 1;
                $_SESSION[self::SESSION_ADMIN] = $admin->adminID;
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new UserNotFoundException("Invalid credentials or No such admin.");
        }
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool
     * @throws UserNotFoundException
     */
    public function userLogin(string $email, string $password): bool
    {
        try {
            $user = new User(null, $email);
            if ($user->login($password)) {
                $_SESSION[self::SESSION_LOGGED_IN_KEY] = 1;
                $_SESSION[self::SESSION_USER] = $user->userID;
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new UserNotFoundException("Invalid credentials or No such user.");
        }
    }

}
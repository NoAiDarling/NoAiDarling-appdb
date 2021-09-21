<?php

/*
 * session.php - session handler functions
 * sessions are stored in memcached
 * http://www.danga.com/memcached/
 */

class session
{
    // defines
    private $name;
    public $msg;

    // create session object
    public function __construct ($name, $server = "", $expire = 30)
    {
        // set name for this session
        $this->name = $name;

        // require server setting
        if (empty($server))
            trigger_error("No Session Server Defined!", E_USER_ERROR);

        // define options for sessions
        ini_set('session.name', $this->name);
        ini_set('session.use_cookies', true);
        ini_set('session.use_only_cookies', true);

        // use memcached
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', "{$server}");

        // default lifetime on session cookie expiration (default 30 days)
        ini_set('session.gc_maxlifetime', (60 * 60 * 24 * $expire));
        session_set_cookie_params((60 * 60 * 24 * $expire), '/');

        // start the loaded session
        if (!session_start())
        {
            // session failed to start
            trigger_error("Unable to Connect to Session Server", E_USER_ERROR);
        }
    }

    // register variables into session (dynamic load and save of vars)
    public function register ($var)
    {
        global $$var;

        // load $var into memory
        if (isset($_SESSION[$var]))
            $$var = $_SESSION[$var];

        // store var into session
        $_SESSION[$var] =& $$var;
    }

    // destroy session
    public function destroy ()
    {
        session_destroy();
    }

    // add alert message to buffer that will be displayed on the Next page view of the same user in html class
    public function addmsg ($text, $color = "black")
    {
        if (!isset($_SESSION['_msg']))
            $_SESSION['_msg'] = array();
        $_SESSION['_msg'][] = array(
                                     'msg'   => $text,
                                     'color' => $color
                                    );
    }

    // add alert message that will be displayed on the current page output in html class
    public function alert ($text, $color = "black")
    {
        $this->msg[] = array(
                             'msg'   => $text,
                             'color' => $color
                            );
    }

    // clear session messages
    public function purgemsg ()
    {
        $this->msg[] = array();
        $_SESSION['_msg'][] = array();
    }

    // output msg_buffer and clear it.
    public function dumpmsgbuffer ()
    {
        if (isset($_SESSION['_msg']) and is_array($_SESSION['_msg']))
        {
            foreach ($_SESSION['_msg'] as $alert)
            {
                $this->msg[] = $alert;
            }
        }
        $_SESSION['_msg'] = array();
    }
}
// end session

?>

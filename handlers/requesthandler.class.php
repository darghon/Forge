<?php
namespace Forge;

/**
 * Class that handles all incoming parameters, and offers a easy to use interface for them.
 */
class RequestHandler implements IStage
{

    protected $method = null;
    protected $user_ip = null;
    protected $user_agent = null;
    protected $session_id = null;
    protected $referrer = null;
    protected $post_vars = array();
    protected $get_vars = array();
    protected $files_vars = array();

    public function initialize()
    {
        $this->retrieveData();

        //save all parameters
        $this->parseGet();
        $this->parsePost();
        $this->parseFiles();
    }

    public function deploy()
    {
        //Possible actions for deploy are unique session registration, + ip & user_agent
        Debug::Notice('Request', 'Client: ' . $this->user_ip . ' is requesting a page with ' . $this->user_agent);
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getParameters()
    {
        return array_merge($this->get_vars, $this->post_vars);
    }

    public function getFiles()
    {
        return $this->files_vars;
    }

    public function getPostParameters()
    {
        return $this->post_vars;
    }

    public function getGetParemeters()
    {
        return $this->get_vars;
    }

    public function getParameter($key, $default = null)
    {
        $tmp = array_merge($this->get_vars, $this->post_vars);
        return isset($tmp[$key]) ? $tmp[$key] : $default;
    }

    public function getPostParameter($key, $default = null)
    {
        return isset($this->post_vars[$key]) ? $this->post_vars[$key] : $default;
    }

    public function getGetParameter($key, $default = null)
    {
        return isset($this->get_vars[$key]) ? $this->get_vars[$key] : $default;
    }

    public function setParameter($key, $value = null)
    {
        return ($this->get_vars[$key] = $value);
    }

    public function getSessionID()
    {
        return $this->session_id;
    }

    /**
     * @return null
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * @return null
     */
    public function getUserIp()
    {
        return $this->user_ip;
    }

    /**
     * @return string|null
     */
    public function getReferrer()
    {
        return $this->referrer;
    }

    protected function retrieveData()
    {
        $this->method = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? 'AJAX' : $_SERVER["REQUEST_METHOD"];
        $this->user_ip = $_SERVER['REMOTE_ADDR'];
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $this->referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        $this->session_id = isset($_REQUEST['phpsessid']) ? $_REQUEST['phpsessid'] : (isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : null);
        if (isset($_COOKIE) && (isset($_COOKIE['language']) || array_key_exists('language', $_COOKIE))) Session::language($_COOKIE['language']);
    }

    protected function parseGet()
    {
        foreach ($_GET as $key => $entry) {
            $this->get_vars[$key] = $entry;
        }
    }

    protected function parsePost()
    {
        foreach ($_POST as $key => $entry) {
            $this->post_vars[$key] = $entry;
        }
    }

    protected function parseFiles()
    {
        foreach ($_FILES as $key => $entry) {
            $this->files_vars[$key] = $entry;
        }
    }

}

?>

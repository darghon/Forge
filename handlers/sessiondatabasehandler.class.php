<?php
namespace Forge;

/**
 * Session Database Handler is a wrapper that handles an alternative method of storing session data.
 * This instance will attempt to store the data in the database, so that, in a multiple server setup,
 * a session is maintained per user, even if they swap from frontend server.
 *
 * @author Darghon
 */
class SessionDatabaseHandler implements ISessionHandler
{

    /**
     * Lifetime of a session object in seconds
     *
     * @var Integer
     */
    protected $max_life_time = null;
    /**
     * SessionData object
     *
     * @var SessionData
     */
    protected $session = null;

    /**
     * Public construction initializes the session max lifetime variable.
     * This variable is retrieved from the php.ini settings
     */
    public function __construct()
    {
        $this->max_life_time = get_cfg_var('session.gc_maxlifetime');
    }

    /**
     * This function is triggered when a Session is started. This function loads a session object into memory.
     * And set/get actions on the session will communicate with this reference.
     *
     * @param String $save_path
     * @param String $session_name
     *
     * @return Boolean true
     */
    public function open($save_path, $session_name)
    {
        return $this->_check();
    }

    /**
     * Private function that is used to check if the session object has been loaded.
     * If not, this function (re)loads the session from the database.
     *
     * @return Boolean $session_loaded
     */
    protected function _check()
    {
        if ($this->session !== null) return true;

        $session_id = isset($_REQUEST['phpsessid']) ? $_REQUEST['phpsessid'] : (isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '');

        $this->session = &SessionData::Find()->byActiveSessionID($session_id);
        if ($this->session->getSessionKey() == '')
            $this->session->setSessionKey($session_id);
        if ($this->session->getExpireDate() <= date('Y-m-d H:i:s', strtotime('+5 minutes'))) {
            $this->session->setExpireDate(date('Y-m-d H:i:s', strtotime('now') + $this->max_life_time));
            $this->session->persist();
        }

        return $this->session instanceOf SessionData ? true : false;
    }

    /**
     * This function is triggered when a Session is closed.
     * This function will remove the internal reference to the session object.
     *
     * @return Boolean true
     */
    public function close()
    {
        $this->session = null;

        return true;
    }

    /**
     * This function is triggered when a value is retrieved from the session.
     * Retrieving a value from the session will NOT reset the lifetime counter
     *
     * @param String $session_id
     *
     * @return Mixed Session_Data
     */
    public function get($session_id)
    {
        return $this->_check() ? unserialize($this->session->getData()) : trigger_error('Can not load Session');
    }

    /**
     * This function is triggered when a value is set in the session.
     * Setting or updating a value in the session will renew the session lifetime counter.
     *
     * @param String $session_id
     * @param Mixed  $data
     */
    public function set($session_id, $data)
    {
        if ($this->_check()) {
            $this->session->setData(serialize($data));
            $this->session->setExpireDate(date('Y-m-d H:i:s', strtotime('now') + $this->max_life_time));
            $this->session->persist();
        } else trigger_error('Can not load Session');
    }

    /**
     * This function is triggered when a session is destroyed.
     * This function removes the session object from the database.
     *
     * @param String $id
     *
     * @return Boolean
     */
    public function destroy($id)
    {
        if ($this->_check()) $this->session->delete();
        $this->session = null;

        return true;
    }

    /**
     * This function is triggered when the garbage collector destroys the object.
     * This function will trigger a cleanup procedure in the database.
     *
     * @param Integer $maxlifetime
     */
    public function clean($maxlifetime)
    {
        SessionData::Find()->clean();
    }

    /**
     * Generic function that is triggered when the SessionDatabaseHandler is destroyed.
     */
    public function __destroy()
    {
        foreach ($this as $key => $var)
            unset($this->$key);
        unset($this);
    }

}

?>

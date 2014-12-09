<?php
namespace Forge;
/**
 * Any class that implements this interface can be registered as a security user class.
 * This class will be used to authenticate and check credentials of the logged in user.
 *
 * @author Darghon
 */
interface ISecurity
{

    CONST USER_GUEST = 0;
    CONST USER_SECURE = 1;


    /**
     * Public function to check if the active user has specific credentials
     *
     * @param String|Array $cred
     */
    public function hasCredentials($cred);

    /**
     * Public function to set the credentials of the active user
     *
     * @param Array $credentials
     */
    public function setCredentials(array $cred);

    /**
     * Public function to get the credentials of the active user
     */
    public function getCredentials();

    /**
     * Public function to remove 1 or more credentials from the active user
     */
    public function removeCredentials(array $cred);

    /**
     * Public method to add an attribute to the active user
     *
     * @param String $attribute_name
     * @param Mixed  $attribute_value
     */
    public function setAttribute($attr, $value);

    /**
     * Public method to add more than 1 attribute at once
     *
     * @param Array $attributes
     */
    public function setAttributes(array $attr);

    /**
     * Public method to retrieve an attribute of the active user
     *
     * @param String $attribute_name
     * @param Mixed  $default_value
     */
    public function getAttribute($attr, $default_value);

    /**
     * Destroy the active user object (logout)
     */
    public function destroy();

}

?>

<?php
namespace Forge;
/**
 * Any class that implements this interface can be registered to the generator as a builder.
 *
 * @author Darghon
 */
interface IGenerator
{
    /**
     * Public construct method receiving a single parameter,
     * which represents all possible parameters join in an array
     */
    public function __construct($args = []);

    /**
     * Public destroy method that will be triggered when the class gets unset, or destroyed
     */
    public function __destroy();

    /**
     * The method that the generator class will apply. This method must ALWAYS exist, and return a Boolean on success
     * or fail.
     *
     * @return boolean
     */
    public function generate();

}

?>

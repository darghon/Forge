<?php
namespace Forge;

/**
 * Public interface that needs to be implemented in custom stage handlers.
 * If a stage does not implement this interface, it wouldn't be able to be registered as a stage handler.
 */
interface IStage{
	
	/**
	 * This function is executed during the construction of the stage
	 */
	public function initialize();
	
	/**
	 * Once the stage is ready to be executed, it will be deployed by this method.
	 */
	public function deploy();
	
}

?>

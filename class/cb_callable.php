<?php

/*
 * @version 1.0
 **/

if(!class_exists('CB_Callable')){
	class CB_Callable {
		
		/**
		 * Constructor function.
		 * 
		 * @access public
		 * @since 1.0
		 * @return void
		 */
		public function __construct($callable, $args) {
			$this->callable = $callable;
			$this->args = $args;
		}

		/**
		 * call
		 * 
		 * @access public
		 * @return mixed
		 */
		public function call(){
			$args = func_get_args();
			foreach ($this->args as $arg) {
				$args[] = $arg;
			}
			return call_user_func_array($this->callable, $args);
		}
	}
}

?>
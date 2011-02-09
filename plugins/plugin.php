<?php

/** Adminer customization allowing usage of plugins
* @author Jakub Vrana, http://www.vrana.cz/
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
class AdminerPlugin extends Adminer {
	var $plugins;
	
	/**
	* @param array
	*/
	function AdminerPlugin($plugins) {
		$this->plugins = $plugins;
		// it is possible to use ReflectionObject in PHP 5 to find out which plugins defines which methods at once
	}
	
	function _applyPlugin($function, $args) {
		foreach ($this->plugins as $plugin) {
			if (method_exists($plugin, $function)) {
				foreach ($args as $key => $val) {
					$args[$key] = &$args[$key]; // allows modification of parameters
				}
				$return = call_user_func_array(array($plugin, $function), $args);
				if (isset($return)) {
					return $return;
				}
			}
		}
		return call_user_func_array(array($this, "parent::$function"), $args);
	}
	
	function _appendPlugin($function, $args) {
		$return = call_user_func_array(array($this, "parent::$function"), $args);
		foreach ($this->plugins as $plugin) {
			if (method_exists($plugin, $function)) {
				$return += call_user_func_array(array($plugin, $function), $args);
			}
		}
		return $return;
	}
	
	// appendPlugin
	
	function dumpFormat() {
		$args = func_get_args();
		return $this->_appendPlugin(__FUNCTION__, $args);
	}
	
	function dumpOutput() {
		$args = func_get_args();
		return $this->_appendPlugin(__FUNCTION__, $args);
	}

	function editFunctions() {
		$args = func_get_args();
		return $this->_appendPlugin(__FUNCTION__, $args);
	}

	// applyPlugin
	
	function name() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function credentials() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function permanentLogin() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function database() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function headers() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function loginForm() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function login() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function tableName() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function fieldName() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLinks() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function foreignKeys() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function backwardKeys() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function backwardKeysPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectQuery() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function rowDescription() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function rowDescriptions() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectVal() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function editVal() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectColumnsPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectSearchPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectOrderPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLimitPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLengthPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectActionPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectEmailPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectColumnsProcess() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectSearchProcess() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectOrderProcess() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLimitProcess() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLengthProcess() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectEmailProcess() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function messageQuery() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function editInput() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function processInput() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function dumpTable() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function dumpData() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function dumpHeaders() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function homepage() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function navigation() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function tablesPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

}

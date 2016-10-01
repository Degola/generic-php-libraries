<?php
/**
 * This file contains the EntityGetData trait
 *
 * @author Sebastian Lagemann <degola@groar.com>
 */

namespace Groar\Generic\Traits\DataModel;

/**
 * Class EntityGetData
 * This trait contains methods to deliver an easy way to return key/value arrays from objects with private properties
 * and corresponding get-methods
 *
 * @package Groar\Generic\Traits\DataModel
 */
trait EntityGetData
{
	/**
	 * can be used within classes which has private properties and corresponding get methods to return the data
	 * this method will allow to get a whole array with all corresponding properties and values back
	 *
	 * @param bool $addNullValues
	 * @return array
	 */
	public function getData($addNullValues = true) {
		$result  = [];
		$methods = $this->getClassPropertiesWithPrefixedMethods('get');
		foreach($methods AS $property => $method) {
			if(method_exists($this, $method)) {
				$value = $this->{$method}();
				if (!$addNullValues && is_null($value)) {
					continue;
				}
				if (is_scalar($value) || is_array($value)) {
					$result[$property] = $value;
				} elseif(is_object($value)) {
					$result[$property] = $value->getData($addNullValues);
				} elseif ($addNullValues) {
					$result[$property] = null;
				}
			}
		}
		return $result;
	}

	/**
	 * returns a list of methods based on class properties which have the corresponding given prefixed method
	 *
	 * @param string $prefix
	 * @return array
	 */
	public final function getClassPropertiesWithPrefixedMethods($prefix = 'get') {
		$list = array();
		foreach(array_keys(get_object_vars($this)) AS $property) {
			$method = $prefix.str_replace('_', '', $property);
			if(method_exists($this, $method)) {
				$list[$property] = $method;
			}
		}
		return $list;
	}
}

?>

<?php

namespace App\Internal\Helper;

class ArrayHelper
{
	/**
	 * Returns an array indexed by specified $key
	 *
	 * @param array $array
	 * @param string $key
	 */
	public static function getIndexed(array $array, $key)
	{
		$result = array();
		
		foreach ($array as $value) {
			if (isset($value[$key])) {
				$result[$value[$key]] = $value;
			}
		}
		return $result;
	}
	
    /**
     * Return only the specified key/value pairs from the given array
     *
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function only(array $array, array $keys)
    {
        return array_intersect_key($array, array_flip($keys));
    }
}


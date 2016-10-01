<?php
/**
 * this file contains the array helper class
 *
 * @author Sebastian Lagemann <degola@groar.com>
 */

namespace Groar\Generic;

/**
 * Class ArrayHelper
 * helps with common tasks of traversing and modifying arrays
 *
 * @package Groar\Generic
 *
 */
class ArrayHelper
{
    const
        UTF8_CONVERSION = "UTF-8";

    /**
     * contains a singleton instance of ArrayHelper
     *
     * @var ArrayHelper
     */
    private static $instance;

    /**
     * Factories an ArrayHelper object as Singleton to make the helper methods within the code easier accessible
     *
     * @return ArrayHelper
     */
    public static function Factory()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * we prefer workiing with objects than with arrays since the accessibility is way easier (-> vs. ['key']) but
     * objects are always references which makes it necessary to copy data structures to avoid changing unexpected data,
     * e.g. read only configuration variables are cached in an instance but should never affected by changes from underlaying
     * processes
     *
     * @param $data
     * @return misc
     */
    public function dereference($data)
    {
        if (is_array($data)) {
            foreach ($data AS $key => $value) {
                if (is_object($value)) {
                    $data[$key] = clone($value);
                } elseif (is_array($value) || is_object($value)) {
                    $data[$key] = static::dereference($value);
                }
            }
        } elseif (is_object($data)) {
            $data = clone($data);
            if ($data instanceof \stdClass) {
                foreach ($data AS $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $data->{$key} = $this->dereference($value);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * avoids the problem that array_merge_recursive is returning an array as soon as there are duplicate keys,
     * this method overwrites in worst case value but it's the more expected behavior than changing
     * the data format / values of the keys in deep-nested arrays
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public function arrayMergeRecursiveDistinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * we're making use of trees within a hash thru single or multiple character separated field names, however, this is a flat table
     * stored which we have to resolve back to a tree to make use of it, therefore we receive the whole
     * key/value flat table and rebuild it into one big nested array
     *
     * @param array $data contains the flat table data
     * @param string $separator specifies how the data keys in the first level are separated
     * @param string $prefix contains the prefix of the data keys which have to be removed
     * @return array
     */
    public function getTreeFromFlatTable(array $data, $separator, $prefix = null)
    {
        $result = array();
        foreach ($data AS $key => $value) {
            $key = substr($key, 0, strlen($prefix)) === $prefix ? substr($key, strlen($prefix) + 1) : $key;
            $key = ltrim($key, $separator);
            $result = $this->arrayMergeRecursiveDistinct(
                $this->getTreeElementFromFlatTable(explode($separator, $key), $value),
                $result
            );

        }
        return $result;
    }

    /**
     * do the recursion of the flat table keys and assign values
     *
     * @param array $list
     * @param $value
     * @param array $result contains the result for the recursion of going the tree down the nodes
     * @return array
     */
    public function getTreeElementFromFlatTable(array $list, $value, array $result = array())
    {
        if (sizeof($list) == 1) {
            $result[array_shift($list)]['_value'] = $value;
        } elseif (sizeof($list) > 1) {
            $result[array_shift($list)] = $this->getTreeElementFromFlatTable($list, $value, $result);
        }
        return $result;
    }

    /**
     * returns an array with same keys but the given column in the second dimension
     *
     * @param array $data
     * @param $column string
     * @return array
     */
    public function getColumnsFromArray(array $data, $column)
    {
        array_walk($data, function (&$value, $key, $return) {
            if (isset($value[$return])) {
                $value = $value[$return];
            } else {
                $value = null;
            }
        }, $column);
        return $data;
    }

    /**
     * returns a tree if given columns exists also in nested nodes
     *
     * @param array $data
     * @param $column
     * @return array
     */
    public function getColumnsFromMultiArray(array $data, $column, $returnObjects = false)
    {
        $newData = array();
        foreach ($data AS $key => $value) {
            if (
                $key === $column &&
                (
                    is_scalar($value) || (
                        is_object($value) &&
                        $returnObjects === true
                    )
                )
            ) {
                $newData = $value;
            } elseif ($key !== $column && !is_scalar($value)) {
                $newData[$key] = $this->getColumnsFromMultiArray($value, $column, $returnObjects);
            }
        }
        return $newData;
    }

    /**
     * converts object to array recursively
     *
     * @param $object
     * @param array $keyReplacements ["search string": "replacement string", ...]
     * @return array
     */
    public function convertObjectToArray($object, array $keyReplacements = array())
    {
        if (is_object($object)) {
            $object = (array)$object;
        }

        if (is_array($object)) {
            $new = array();
            foreach ($object AS $key => $value) {
                $key = str_replace(array_keys($keyReplacements), array_values($keyReplacements), $key);
                $new[$key] = $this->convertObjectToArray($value);
            }
        } else {
            $new = $object;
        }
        return $new;
    }

    /**
     * Provides a flat array from an array of objects.
     * Needed for the validation functions.
     *
     * @param        $objectArray
     * @param String $property
     *
     * @return array
     */
    public function convertPropertyFromArrayOfObjectsToFlatArray(array $objectArray, $property)
    {
        $result = [];
        foreach ($objectArray as $key => $value) {
            $result[$key] = $value->{$property};
        }
        return $result;
    }

    /**
     * Validates the existence of certain keys within a given array.
     *
     * @param array $array
     * @param array $requiredKeys
     * @return bool
     */
    public static function validateArray(array $array, array $requiredKeys)
    {
        foreach ($requiredKeys as $req) {
            if (!array_key_exists($req, $array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Recursive function that encodes all values of the input array to UTF-8.
     * The reason was that json_encode() returned false when there was a malformed UTF-8 error for the input array.
     * Returns the encoded to UTF-8 array.
     *
     * @param array $objectArray
     * @return array
     */
    public function encodeArrayToUTF8(array $objectArray)
    {
        $returnArray = array();
        foreach ($objectArray as $key => $value) {
            if (is_array($value)) {
                $returnArray[$key] = $this->encodeArrayToUTF8($value);
                continue;
            }
            $returnArray[$key] = mb_convert_encoding($value, self::UTF8_CONVERSION);
        }
        return $returnArray;
    }
}

?>

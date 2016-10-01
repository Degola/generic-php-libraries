<?php
/**
 * This file contains the EntityGetData trait
 *
 * @author Sebastian Lagemann <degola@groar.com>
 */

namespace Groar\Generic\Traits\DataModel;

/**
 * Class EntityMerge
 * This trait contains a method to merge two objects which have methods to deliver an easy way to return key/value arrays from objects with private properties and
 * corresponding get-methods
 *
 * @package Groar\Generic\Traits\DataModel
 */
trait EntityMandatoryFields
{
    /**
     * can be used within classes which has private properties and corresponding get methods to return the data
     * this method will allow to merge subsequent objects which implements the getData method
     *
     * @param string $check
     * @return bool
     */
    public final function validateMandatoryFields($check = 'is_null')
    {
        foreach ($this->mandatoryFields AS $field) {
            if ($check($this->{"get" . $field}())) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns a string list of all Mandatory fields which are missing
     *
     * @param string $check
     * @return mixed
     */
    public final function getMissingMandatoryFields($check = 'is_null')
    {
        $missingFields = [];
        foreach ($this->mandatoryFields AS $field) {
            if ($check($this->{"get" . $field}())) {
                $missingFields[] = $field;
            }
        }
        return implode(', ', $missingFields);
    }

}
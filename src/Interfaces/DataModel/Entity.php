<?php
/**
 * Created by PhpStorm.
 * User: degola
 * Date: 21.09.15
 * Time: 19:18
 */

namespace Groar\Generic\Interfaces\DataModel;

interface Entity
{
    /**
     * returns unique identifier
     *
     * @return string|integer
     */
    public function getUniqueIdentifier();

    public function getData();

    /**
     * can be used within classes which has private properties and corresponding get methods to return the data
     * this method will allow to merge subsequent objects which implements the getData method
     *
     * @return Entity
     */
    public function merge(Entity $entity);
}

?>
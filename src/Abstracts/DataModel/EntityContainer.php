<?php
/**
 * Created by PhpStorm.
 * User: degola
 * Date: 21.09.15
 * Time: 22:34
 */

namespace Groar\Generic\Abstracts\DataModel;

use Groar\Generic\Interfaces\DataModel\Entity;

abstract class EntityContainer implements \IteratorAggregate, \Countable {
    const ENTITY_CLASS = null;
    /**
     * @var array
     */
    private $list = array();

    /**
     * @return EntityContainer
     */
    public static function Factory(array $entities = array()) {
        $entityClass = static::ENTITY_CLASS;
        $container = new static();
        foreach($entities AS $entity) {
            if(is_array($entity) || (is_object($entity) && $entity instanceof \stdClass)) {
                $container->add($entityClass::Factory((array)$entity));
            } elseif($entity instanceof Entity) {
                $container->add($entity);
            } else {
                throw new EntityContainer_Exception('invalid entity value', EntityContainer_Exception::INVALID_ENTITY_VALUE);
            }
        }
        return $container;
    }


    public function exists($entities) {
        if(is_array($entities)) {
            foreach ($entities AS $entity) {
                if (!array_key_exists($entity, $this->list)) {
                    return false;
                }
            }
            return true;
        } elseif(is_scalar($entities)) {
            if(isset($this->list[$entities])) {
                return true;
            }
            return false;
        } elseif($entities instanceof Entity) {
            if(is_array($this->list)) {
                return array_key_exists($entities->getUniqueIdentifier(), $this->list);
            }
            return false;
        }
        return false;
    }

    /**
     * @param string|integer $identifier
     * @return bool|Entity
     */
    public function get($identifier) {
        if(isset($this->list[$identifier])) {
            return $this->list[$identifier];
        }
        return false;
    }
    public function add(Entity $entity) {
        $this->list[$entity->getUniqueIdentifier()] = $entity;
        return $this;
    }
    public function delete(Entity $entity) {
        unset($this->list[$entity->getUniqueIdentifier()]);
        return $this;
    }

    public function count() {
        return sizeof($this->list);
    }
    public function getFirst() {
        return reset($this->list);
    }

    /**
     * returns data which should be use by entity merge
     *
     * @param bool $addNullValues
     * @return array
     */
    public function getData($addNullValues = true) {
        $result = array();
        if(is_array($this->list)) {
            foreach ($this->list AS $entity) {
                /**
                 * @var $entity Entity
                 */
                $result[] = $entity->getData($addNullValues);
            }
        }
        return $result;
    }
    public function getIterator() {
        return new \ArrayIterator($this->list);
    }

    /**
     * can be used within classes which has private properties and corresponding get methods to return the data
     * this method will allow to merge subsequent objects which implements the getData method
     *
     * @return EntityContainer
     */
    public function merge(EntityContainer $container) {
        foreach($container AS $element) {
            /**
             * @var $element Entity
             */
            if(!$this->exists($element)) {
                $this->add($element);
            } else {
                $element = $this->get($element->getUniqueIdentifier())->merge($element);
                $this->delete($element);
                $this->add($element);
            }
        }
        return $this;
    }
}


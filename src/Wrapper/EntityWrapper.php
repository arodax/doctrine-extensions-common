<?php

/*
 * This file is part of the arodax/doctrine-extensions-common package.
 *
 * (c) ARODAX  <info@arodax.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arodax\Doctrine\Extensions\Common\Wrapper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;

/**
 * @deprecated Use Arodax\Doctrine\Extensions\Tree\Common\Wrapper\EntityWrapper
 * from arodax/doctrine-extensions-tree package. This package will be no longer needed since v4.0.0 version.
 *
 * Wraps entity or proxy for more convenient
 * manipulation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityWrapper extends AbstractWrapper
{
    /**
     * Entity identifier
     *
     * @var array
     */
    private $identifier;

    /**
     * True if entity or proxy is loaded
     *
     * @var boolean
     */
    private $initialized = false;

    /**
     * Wrap entity
     *
     * @param object                      $entity
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct($entity, EntityManagerInterface $em)
    {
        $this->om = $em;
        $this->object = $entity;
        $this->meta = $em->getClassMetadata(get_class($this->object));
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyValue($property)
    {
        $this->initialize();

        return $this->meta->getReflectionProperty($property)->getValue($this->object);
    }

    /**
     * {@inheritDoc}
     */
    public function setPropertyValue($property, $value)
    {
        $this->initialize();
        $this->meta->getReflectionProperty($property)->setValue($this->object, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasValidIdentifier()
    {
        return (null !== $this->getIdentifier());
    }

    /**
     * {@inheritDoc}
     */
    public function getRootObjectName()
    {
        return $this->meta->rootEntityName;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier($single = true)
    {
        if (null === $this->identifier) {
            if ($this->object instanceof Proxy) {
                $uow = $this->om->getUnitOfWork();
                if ($uow->isInIdentityMap($this->object)) {
                    $this->identifier = $uow->getEntityIdentifier($this->object);
                } else {
                    $this->initialize();
                }
            }
            if (null === $this->identifier) {
                $this->identifier = array();
                $incomplete = false;
                foreach ($this->meta->identifier as $name) {
                    $this->identifier[$name] = $this->getPropertyValue($name);
                    if (null === $this->identifier[$name]) {
                        $incomplete = true;
                    }
                }
                if ($incomplete) {
                    $this->identifier = null;
                }
            }
        }
        if ($single && is_array($this->identifier)) {
            return reset($this->identifier);
        }

        return $this->identifier;
    }

    /**
     * Initialize the entity if it is proxy
     * required when is detached or not initialized
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->object instanceof Proxy) {
                if (!$this->object->__isInitialized__) {
                    $this->object->__load();
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmbeddedAssociation($field)
    {
        return false;
    }
}

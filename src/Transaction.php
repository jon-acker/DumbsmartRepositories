<?php

namespace carlosV2\DumbsmartRepositories;

use carlosV2\DumbsmartRepositories\Exception\CacheMissException;
use carlosV2\DumbsmartRepositories\Exception\MetadataNotFoundException;
use carlosV2\DumbsmartRepositories\Exception\RepositoryNotFoundException;

class Transaction
{
    /**
     * @var MetadataManager
     */
    private $mm;

    /**
     * @var RepositoryManager
     */
    private $rm;

    /**
     * @var array
     */
    private $cache;

    /**
     * @param MetadataManager   $mm
     * @param RepositoryManager $rm
     */
    public function __construct(MetadataManager $mm, RepositoryManager $rm)
    {
        $this->mm = $mm;
        $this->rm = $rm;
        $this->cache = [];
    }

    /**
     * @param object $object
     *
     * @return Reference
     *
     * @throws MetadataNotFoundException
     * @throws RepositoryNotFoundException
     */
    public function save($object)
    {
        try {
            $reference = $this->getCachedDataByObject($object);
        } catch (CacheMissException $e) {
            $reference = $this->mm->getMetadataForObject($object)->getReferenceForObject($object);
            $this->setCachedData($reference->getClassName(), $reference->getId(), $reference);

            $object = clone $object;
            $this->mm->getMetadataForObject($object)->prepareToSave($this, $object);
            $this->rm->getRepositoryForObject($object)->save($object);
        }

        return $reference;
    }

    /**
     * @param Reference $reference
     *
     * @return object
     *
     * @throws MetadataNotFoundException
     */
    public function findByReference(Reference $reference)
    {
        try {
            $object = $this->getCachedDataByReference($reference);
        } catch (CacheMissException $e) {
            if ($object = $this->rm->getRepositoryForClassName($reference->getClassName())->findById($reference->getId())) {
                $object = clone $object;
                $this->setCachedData($reference->getClassName(), $reference->getId(), $object);
                $this->mm->getMetadataForObject($object)->prepareToLoad($this, $object);
            }
        }

        return $object;
    }

    /**
     * @param string $className
     *
     * @return object[]
     *
     * @throws RepositoryNotFoundException
     */
    public function getAll($className)
    {
        return array_map(function ($object) {
            return $this->findByReference($this->mm->getMetadataForObject($object)->getReferenceForObject($object));
        }, $this->rm->getRepositoryForClassName($className)->getAll());
    }

    /**
     * @param object $object
     *
     * @return object
     *
     * @throws MetadataNotFoundException
     */
    private function getCachedDataByObject($object)
    {
        return $this->getCachedDataByReference(
            $this->mm->getMetadataForObject($object)->getReferenceForObject($object)
        );
    }

    /**
     * @param Reference $reference
     *
     * @return object
     *
     * @throws CacheMissException
     */
    private function getCachedDataByReference(Reference $reference)
    {
        return $this->getCachedDataByClassNameAndId($reference->getClassName(), $this->stringify($reference->getId()));
    }

    /**
     * @param string $className
     * @param string $id
     *
     * @return object
     *
     * @throws CacheMissException
     */
    private function getCachedDataByClassNameAndId($className, $id)
    {
        if (!array_key_exists($className, $this->cache)) {
            throw new CacheMissException();
        }

        if (!array_key_exists($id, $this->cache[$className])) {
            throw new CacheMissException();
        }

        return $this->cache[$className][$id];
    }

    /**
     * @param string $className
     * @param string $id
     * @param object $object
     */
    private function setCachedData($className, $id, $object)
    {
        if (!array_key_exists($className, $this->cache)) {
            $this->cache[$className] = [];
        }

        $this->cache[$className][$this->stringify($id)] = $object;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    private function stringify($data)
    {
        return md5(serialize($data));
    }
}

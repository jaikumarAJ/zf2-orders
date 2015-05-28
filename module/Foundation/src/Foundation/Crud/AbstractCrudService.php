<?php

namespace Foundation\Crud;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Foundation\AbstractRepository;
use Foundation\Entity\EntityInterface;
use Foundation\Exception\ValidationException;
use InvalidArgumentException;
use Foundation\AbstractService as Service;
use Foundation\Exception\NotFoundException;
use Zend\Paginator\Paginator;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Parameters;
use Foundation\Crud\AbstractCrudRepository as CrudRepository;
use Foundation\AbstractFilter as Filter;

abstract class AbstractCrudService extends Service
{
    protected $repository;
    protected $filter;

    /**
     * @param ServiceLocatorInterface $serviceManager
     * @param AbstractCrudRepository $repository
     * @param Filter $filter
     */
    public function __construct(ServiceLocatorInterface $serviceManager, CrudRepository $repository, Filter $filter)
    {
        parent::__construct($serviceManager);

        $this->repository = $repository;
        $this->filter = $filter;
    }

    /**
     * Hydrates
     * @param array $data
     * @return EntityInterface
     */
    protected function hydrateData(array $data)
    {
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        $hydrator = new DoctrineObject($em);
        $entityClass = $this->repository->getClassName();

        return $hydrator->hydrate($data, new $entityClass());
    }

    /**
     * @param $data
     * @return bool
     * @throws ValidationException
     */
    public function save($data)
    {
        $this->filter->setData($data);

        if (!$this->filter->isValid()) {
            throw new ValidationException($this->translate('validation.generic_failed'), $this->filter->getMessages());
        }

        // Hydrates the filtered data into corresponding Entity Object
        $entity = $this->hydrateData($this->filter->getValues());
        $this->repository->save($entity);

        return true;
    }

    /**
     * Fetches list of Doctrine entites from the repository with Pagination
     *
     * @param Parameters $query
     * @return array
     * @throws NotFoundException
     */
    public function fetchList(Parameters $query)
    {
        $max = (int)$query->get('max', AbstractRepository::PAGINATION_MAX_ROWS);
        $page = (int)$query->get('page', 1);

        if ($page < 1) {
            throw new InvalidArgumentException($this->translate('exception.invalid_page'));
        }

        $doctrinePaginator = $this->repository->fetchList();

        $paginator = new Paginator(new DoctrinePaginator($doctrinePaginator));
        $paginator
            ->setCurrentPageNumber($page)
            ->setItemCountPerPage($max);

        $items = $paginator->getIterator()->getArrayCopy();
        $total = $paginator->getTotalItemCount();

        $noOfPages = $paginator->count();

        if ($total > 0) {
            if ($page > $noOfPages) {
                throw new NotFoundException($this->translate('exception.page_not_found'));
            }
        }

        return compact('items', 'total', 'paginator');
    }

    /**
     * @param $id
     * @throws NotFoundException
     */
    public function remove($id)
    {
        $entity = $this->fetch($id);
        $this->repository->remove($entity);
    }

    /**
     * @param $id
     * @return object
     * @throws NotFoundException
     */
    public function fetch($id)
    {
        $entity = $this->repository->find($id);
        if ($entity === null) {
            throw new NotFoundException($this->translate('exception.item_not_found'));
        }

        return $entity;
    }

}

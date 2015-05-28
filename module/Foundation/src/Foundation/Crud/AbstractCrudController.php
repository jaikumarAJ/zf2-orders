<?php

namespace Foundation\Crud;

use Zend\View\Model\ViewModel;
use Foundation\AbstractController as Controller;
use Foundation\Crud\AbstractCrudService as CrudService;

abstract class AbstractCrudController extends Controller
{
    protected $service;

    /**
     * Returns the title of the resource associated with this controller
     *
     * @return string
     */
    protected abstract function getResourceTitle();

    /**
     * @param AbstractCrudService $service
     */
    public function __construct(CrudService $service)
    {
        $this->service = $service;
    }

    /**
     * Returns the base uri of the resource associated with this controller
     * from the router config
     *
     * @return string|null
     */
    protected function getBaseUri()
    {
        // get router config
        $routesConfig = $this->getServiceLocator()->get('config')['router']['routes'];
        $matchedRouteName = $this->getRouteName();

        // Get the base route
        if (isset($routesConfig[$matchedRouteName])) {
            $baseRoute = $routesConfig[$matchedRouteName];
        } else {
            $baseRoute = substr($matchedRouteName, 0, strpos($matchedRouteName, '/'));
            $baseRoute = $routesConfig[$baseRoute];
        }

        $uri = $baseRoute['options']['route'];

        return $uri;
    }


    /**
     * Default action if none provided.
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        // fetch list with pagination
        $data = $this->service->fetchList($this->getBaseUri(), $this->getRequest()->getQuery());

        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $paginationControl = $viewHelperManager->get('paginationControl');

        $data['pagination'] = $paginationControl(
            $data['paginator'], 'Sliding', 'crud/pagination', [
            'baseUri' => $this->getBaseUri()
        ]);

        // Title for the resource list
        $data['title'] = sprintf('%s List', $this->getResourceTitle());

        return new ViewModel($data);
    }

    /**
     * @return array|\Zend\Http\Response
     */
    public function addAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() && $this->service->save($request->getPost())) {
            return $this->redirectToIndex();
        }

        return [
            'title' => sprintf('Create New %s', $this->getResourceTitle()),
            'form' => $this->service->prepareForm($request->getUri()->getPath()),
        ];
    }

    /**
     * @return array|\Zend\Http\Response
     */
    public function editAction()
    {
        $entity = $this->service->fetch($this->params('id'));
        $request = $this->getRequest();

        $this->service->bindToForm($entity);

        if ($request->isPost() && $this->service->save($request->getPost())) {
            return $this->redirectToIndex();
        }

        return [
            'title' => sprintf('Edit %s', $this->getResourceTitle()),
            'form' => $this->service->prepareForm($request->getUri()->getPath()),
            'item' => $entity,
        ];
    }

    /**
     * @return array|\Zend\Http\Response
     */
    public function deleteAction()
    {
        $item = $this->service->fetch($this->params('id'));
        $this->service->remove($item);

        return $this->redirectToIndex();
    }

    /**
     * Redirects to the Index action (base uri)
     *
     * @return \Zend\Http\Response
     */
    public
    function redirectToIndex()
    {
        return $this->redirect()->toUrl($this->getBaseUri());
    }

    /**
     * @return string
     */
    protected
    function getRouteName()
    {
        return $this->getEvent()->getRouteMatch()->getMatchedRouteName();
    }
}
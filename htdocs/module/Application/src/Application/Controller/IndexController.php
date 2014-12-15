<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Doctrine\Common\Collections\ArrayCollection;
use DoctrineModule\Paginator\Adapter\Collection as Adapter;
use Zend\Paginator\Paginator;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;

use Doctrine\Common\Collections\Criteria;

// @TODO - Doctrine\Common\Persistence\ObjectManager instead of Doctrine\ORM\EntityManager
// @TODO - Doctrine\Common\Persistence\ObjectRepository instead of Doctrine\ORM\EntityRepository

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function createUserAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $user = new \Application\Entity\User();
        $user->setFullName('Marco Pivetta');

        $objectManager->persist($user);
        $objectManager->flush();

        die(var_dump($user->getId())); // yes, I'm lazy
    }

    public function persistMultipleObjectsAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $user1 = new \Application\Entity\User();
        $user1->setFullName('Marco Pivetta');
        $objectManager->persist($user1);

        $user2 = new \Application\Entity\User();
        $user2->setFullName('Michaël Gallego');
        $objectManager->persist($user2);

        $user3 = new \Application\Entity\User();
        $user3->setFullName('Kyle Spraggs');
        $objectManager->persist($user3);

        $objectManager->flush();
    }

    public function retrieveObjectAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $user1 = $objectManager->find('Application\Entity\User', 1);

        var_dump($user1->getFullName()); // Marco Pivetta

        $user2 = $objectManager
            ->getRepository('Application\Entity\User')
            ->findOneBy(array('fullName' => 'Michaël Gallego'));

        var_dump($user2->getFullName()); // Michaël Gallego
    }

    public function updateObjectAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $user = $objectManager->find('Application\Entity\User', 1);

        $user->setFullName('Guilherme Blanco2');

        $objectManager->flush();
    }

    public function deleteObjectAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $user = $objectManager->find('Application\Entity\User', 1);

        $objectManager->remove($user);

        $objectManager->flush();
    }

    public function persistantAssociationsAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $user = new \Application\Entity\User();
        $user->setFullName('Marco Pivetta');
        $objectManager->persist($user);

        $address = new \Application\Entity\Address();
        $address->setCity('Frankfurt');
        $address->setCountry('Germany');
        $objectManager->persist($address);

        $project = new \Application\Entity\Project();
        $project->setName('Doctrine ORM');
        $objectManager->persist($project);

        $user->setAddress($address);
        $user->getProjects()->add($project);
        $objectManager->flush();
    }

    public function retrieveAssociationsAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $user = $objectManager->find('Application\Entity\User', 1);

        var_dump($user->getAddress()->getCity()); // Frankfurt
        var_dump($user->getProjects()->first()->getName()); // Doctrine ORM
    }

    public function paginatorAdapterAction()
    {
        // Create a Doctrine Collection
        $collection = new ArrayCollection(range(1, 101));

        // Create the paginator itself
        $paginator = new Paginator(new Adapter($collection));

        $paginator
            ->setCurrentPageNumber(1)
            ->setItemCountPerPage(5);

        $view = new ViewModel();
        $view->paginator = $paginator;

        return $view;

//        $this->paginationControl($paginator,
//            'Sliding',
//            'my_pagination_control.phtml');
    }

    public function paginatorAdapterORMAction()
    {

        $em = null;
        // Create a Doctrine Collection
        $query = $em->createQuery('SELECT f FROM Foo f JOIN f.bar b');

        // Create the paginator itself
        $paginator = new Paginator(
            new DoctrinePaginator(new ORMPaginator($query))
        );

        $paginator
            ->setCurrentPageNumber(1)
            ->setItemCountPerPage(5);
    }

    public function objectExistsValidatorAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $repository = $objectManager
            ->getRepository('Application\Entity\User');

        $validator = new \DoctrineModule\Validator\ObjectExists(array(
            'object_repository' => $repository,
            'fields' => array('email')
        ));

        var_dump($validator->isValid('test@example.com'));
        var_dump($validator->isValid(array(
            'email' => 'test@example.com'
        )));
    }

    public function cacheAdaptersAction()
    {
        $zendCache = new \Zend\Cache\Storage\Adapter\Memory();

        $cache = new \DoctrineModule\Cache\ZendStorageCache($zendCache);

        $doctrineCache = new \Doctrine\Common\Cache\ArrayCache();
        $options = new \Zend\Cache\Storage\Adapter\AdapterOptions();

        $cache = new \DoctrineModule\Cache\DoctrineCacheStorage(
            $options,
            $doctrineCache
        );
    }

    public function hydratorAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $hydrator = new DoctrineObject(
            $objectManager,
            'Application\Entity\City'
        );

        $city = new Application\Entity\City();
        $data = array('name' => 'Frankfurt');

        $city = $hydrator->hydrate($data, $city);

        echo $city->getName(); // prints "Frankfurt"

        $dataArray = $hydrator->extract($city);
        echo $dataArray['name']; // prints "Frankfurt"
    }

    public function hydrator2Action()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $hydrator = new DoctrineObject(
            $objectManager,
            'Application\Entity\City'
        );

        $city = new Application\Entity\City();
        $data = array('country' => 123);

        $city = $hydrator->hydrate($data, $city);

        var_dump($city->getCountry());
        // prints class Country#1 (1) {
        //   protected $name => string(5) "Germany"
        // }
    }

    public function formAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $form->add(array(
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'name' => 'user',
            'options' => array(
                'object_manager' => $objectManager,
                'target_class' => 'Module\Entity\User',
                'property' => 'fullName',
                'is_method' => true,
                'find_method' => array(
                    'name' => 'findBy',
                    'params' => array(
                        'criteria' => array('active' => 1),
                        'orderBy' => array('lastName' => 'ASC'),
                    ),
                ),
            ),
        ));
    }

    public function collectionsCriteriaAction()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $user1 = $objectManager->find('Application\Entity\User', 1);
        $user2 = $objectManager->find('Application\Entity\User', 2);
        $user3 = $objectManager->find('Application\Entity\User', 3);

        $collection = new ArrayCollection(array($user1, $user2, $user3));
        $criteria = new Criteria();
        $criteria->andWhere(
            $criteria->expr()->gt(
                'lastLogin',
                new \DateTime('-1 day')
            )
        );

        $recentVisitors = $collection->matching($criteria);

        $recentVisitors = $em
            ->getRepository('Application\Entity\Users')
            ->matching($criteria);
    }
}
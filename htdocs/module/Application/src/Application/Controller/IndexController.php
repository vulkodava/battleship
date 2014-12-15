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

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function createUser()
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

    public function persistMultiopleObjects()
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

    public function retrieveObject()
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

    public function updateObject()
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $user = $objectManager->find('Application\Entity\User', 1);

        $user->setFullName('Guilherme Blanco');

        $objectManager->flush();
    }

    public function deleteObject()
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

        $user = new User();
        $user->setFullName('Marco Pivetta');
        $objectManager->persist($user);

        $address = new Address();
        $address->setCity('Frankfurt');
        $address->setCountry('Germany');
        $objectManager->persist($address);

        $project = new Project();
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
}

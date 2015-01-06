<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15-1-6
 * Time: 3:30
 */

namespace Battleship\Listener;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;


class Game implements ListenerAggregateInterface, ServiceLocatorAwareInterface {
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceManager;

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents = $events->getSharedManager();
        $this->listeners[] = $sharedEvents->attach(
            '*',
            'runtime_error',
            array($this, 'onRuntimeError'),
            100
        );
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener)
        {
            if ($events->detach($listener))
            {
                unset($this->listeners[$index]);
            }
        }
    }

    public function onRuntimeError(EventInterface $e)
    {

    }

    /**
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceManager = $serviceLocator;
    }

    /**
     *
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceManager;
    }

    public function createBattleField($event)
    {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
        $params = $event->getParams();
        $field = new Field();
        $field->setSizeX($params['x']);
        $field->setSizeY($params['y']);
        $field->setCreatedAt(new \DateTime());
        $objectManager->persist($field);
        $objectManager->flush();
        $this->getEventManager()->trigger('battleship.createField', null, $field);
    }
}
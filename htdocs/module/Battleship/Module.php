<?php
namespace Battleship;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;
use ZendService\ReCaptcha\Exception;


class Module
{
    public function onBootstrap(MvcEvent $event)
    {
        $eventManager = $event->getApplication()->getEventManager();
        $app = $event->getApplication();
        // get the shared events manager
        $sem = $app->getEventManager()->getSharedManager();

        $sem->attach('Battleship\Controller\IndexController', 'battleship.createNewGameStart', array(new \Battleship\Listener\Game(), 'createBattleField'));

        $sm = $event->getApplication()->getServiceManager();
        $config = $sm->get('config');

        if (array_key_exists('listeners', $config))
        {
            $listeners = $config['listeners'];
            foreach ($listeners as $curListener)
            {
                $listener = $sm->get($curListener);
                $eventManager->attach($listener);
            }
        }

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}

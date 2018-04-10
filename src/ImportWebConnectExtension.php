<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect;

use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;
use Bolt\Extension\TwoKings\ImportWebConnect\Nut;
use Pimple as Container;

/**
 * ImportWebConnect extension class.
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class ImportWebConnectExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     *
     * Extending the backend menu:
     *
     * You can provide new Backend sites with their own menu option and template.
     *
     * Here we will add a new route to the system and register the menu option in the backend.
     *
     * You'll find the new menu option under "Extras".
     */
    protected function registerMenuEntries()
    {
        /*
         * Define a menu entry object and register it:
         *   - Route http://example.com/bolt/extensions/importwebconnect
         *   - Menu label 'Import WebConnect'
         *   - Menu icon a Font Awesome small child
         *   - Required Bolt permissions 'settings'
         */
        $adminMenuEntry = (new MenuEntry('importwebconnect-backend-page', 'importwebconnect'))
            ->setLabel('Import WebConnect')
            ->setIcon('fa:child')
            ->setPermission('extensions')
        ;

        return [$adminMenuEntry];
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceProviders()
    {
        return [
            $this,
            new Provider\ImportWebConnectProvider($this->getConfig())
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendControllers()
    {
        return [
            '/extensions/importwebconnect/cursussen' => new Controller\ImportWebConnectCursussenController(),
            '/extensions/importwebconnect/events' => new Controller\ImportWebConnectEventsController(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerNutCommands(Container $container)
    {
        return [
            new Nut\ImportWebConnectCommand($container),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates' => [
                'position'  => 'prepend',
                'namespace' => 'importwebconnect'
            ]
        ];
    }


    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        return [
            'remote' => [
                'enabled' => false,
                'host' => 'https://academie.backend.webconnect.nl',
                'uri' => '/webservice/academie.php',
                'headers' => [
                    'Content-type' => 'application/json'
                ],
                'username' => 'set this in local config',
                'password' => 'set this in local config',
            ],
            'acceptance' => [
                'enabled' => true,
                'host' => 'https://academie.backend.webconnect.nl',
                'uri' => '/webservice/academie.php',
                'headers' => [
                    'Content-type' => 'application/json'
                ],
                'username' => 'set this in local config',
                'password' => 'set this in local config',
            ],
            'target' => [
                'contenttype' => 'cursussen',
                'ownerid' => 3,
                'active' => 'published',
                'inactive' => 'held',
                'planningcontenttype' => 'planningen',
                'docentencontenttype' => 'docenten',
                'eventscontenttype' => 'events'
            ]
        ];
    }

}

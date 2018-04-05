<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect;

use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;

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
}

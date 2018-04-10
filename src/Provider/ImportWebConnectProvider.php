<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Bolt\Extension\TwoKings\ImportWebConnect\Service\ImportWebConnectCursussenService;
use Bolt\Extension\TwoKings\ImportWebConnect\Service\ImportWebConnectEventsService;

/**
 * ImportWebConnect service provider.
 *
 * @author Néstor de Dios Fernández <nestor@twokings.nl>
 */
class ImportWebConnectProvider implements ServiceProviderInterface
{
    /** @var array */
    private $config;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['importwebconnect.config'] = $app->share(
            function () {
                return $this->config;
            }
        );

        $app['importwebconnect.cursussen.service'] = $app->share(
            function (Application $app) {
                return new ImportWebConnectCursussenService(
                     $app,
                     $app['guzzle.client'],
                     $app['logger.system']
                 );
            }
         );

         $app['importwebconnect.events.service'] = $app->share(
            function (Application $app) {
                return new ImportWebConnectEventsService(
                     $app,
                     $app['guzzle.client'],
                     $app['logger.system']
                 );
            }
         );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}

<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Bolt\Extension\TwoKings\ImportWebConnect\Service\ImportWebConnectService;

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

        $app['importwebconnect.service'] = $app->share(
            function (Application $app) {
                return new ImportWebConnectService(
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

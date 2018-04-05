<?php

namespace Bolt\Extension\TwoKings\ImportWebConnect\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

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
        $app['importwebconnect.service'] = $app->share(
           function ($app) {
               return new Service\ImportWebConnectService($this->config);
           }
        );

        $app['importwebconnect.config'] = $app->share(
            function () {
                return $this->config;
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

<?php

declare(strict_types=1);

namespace Imhotep\Framework\Bootstrap;

use Imhotep\Facades\Facade;
use Imhotep\Facades\FacadeLoader;
use Imhotep\Framework\Application;

class RegisterFacades
{
    /**
     * Create bootstrap for facades
     *
     * @param Application $app
     */
    public function __construct(
        protected Application $app
    ){}

    public function bootstrap(): void
    {
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($this->app);

        FacadeLoader::getInstance(
            $this->app,
            $this->app['config']->get('app.aliases', [])
        )->register();
    }
}
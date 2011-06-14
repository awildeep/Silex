<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Extension;

use Silex\Application;
use Silex\ExtensionInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;

class DoctrineExtension implements ExtensionInterface
{
    public function register(Application $app)
    {
        
        if (isset($app['db.options'])) {
            $app['db.options'] = array_replace(array(
                'driver'   => 'pdo_mysql',
                'dbname'   => null,
                'host'     => 'localhost',
                'user'     => 'root',
                'password' => null,
            ), isset($app['db.options']) ? $app['db.options'] : array());

            $app['db'] = $app->share(function () use($app) {
                return DriverManager::getConnection($app['db.options'], $app['db.config'], $app['db.event_manager']);
            });

            $app['db.config'] = $app->share(function () {
                return new Configuration();
            });

            $app['db.event_manager'] = $app->share(function () {
                return new EventManager();
            });
        } elseif (isset($app['dbs']) && is_array($app['dbs'])) {
            foreach ($app['dbs'] as $connectionName => $connectionOptions) {
                $app[$connectionName] = $app->share(function () use($app, $connectionOptions, $connectionName) {
                    return DriverManager::getConnection($connectionOptions, $app[$connectionName.'.config'], $app[$connectionName.'.event_manager']);
                });
                $app[$connectionName.'.config'] = $app->share(function () {
                    return new Configuration();
                });

                $app[$connectionName.'.event_manager'] = $app->share(function () {
                    return new EventManager();
                });
            }
        } else {
            throw new Exception ('DoctrineExtension requires options to be configured.');
        }
        

        if (isset($app['db.dbal.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\\DBAL', $app['db.dbal.class_path']);
        }

        if (isset($app['db.common.class_path'])) {
            $app['autoloader']->registerNamespace('Doctrine\\Common', $app['db.common.class_path']);
        }
    }
}

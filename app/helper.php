<?php
/*
|--------------------------------------------------------------------------
| Common function method
|--------------------------------------------------------------------------
*/

use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerInterface;

/**
 * 容器实例
 *
 * @return ContainerInterface
 */
function di(): ContainerInterface
{
    return ApplicationContext::getContainer();
}

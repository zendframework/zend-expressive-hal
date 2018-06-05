<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-hal for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-hal/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Hal\LinkGenerator;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

use function sprintf;

class ExpressiveUrlGeneratorFactory
{
    /** @var string */
    private $urlHelperServiceName;

    /**
     * Allow serialization
     */
    public static function __set_state(array $data) : self
    {
        return new self(
            $data['urlHelperServiceName'] ?? UrlHelper::class
        );
    }

    /**
     * Vary behavior based on the URL helper service name.
     */
    public function __construct(string $urlHelperServiceName = UrlHelper::class)
    {
        $this->urlHelperServiceName = $urlHelperServiceName;
    }

    public function __invoke(ContainerInterface $container) : ExpressiveUrlGenerator
    {
        if (! $container->has($this->urlHelperServiceName)) {
            throw new RuntimeException(sprintf(
                '%s requires a %s in order to generate a %s instance; none found',
                __CLASS__,
                $this->urlHelperServiceName,
                ExpressiveUrlGenerator::class
            ));
        }

        return new ExpressiveUrlGenerator(
            $container->get($this->urlHelperServiceName),
            $container->has(ServerUrlHelper::class) ? $container->get(ServerUrlHelper::class) : null
        );
    }
}

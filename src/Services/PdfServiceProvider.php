<?php

namespace Themes\AbstractPdfTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\PdfSafe\PdfSafeExtension;
use Symfony\Component\HttpFoundation\RequestStack;

class PdfServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return void
     */
    public function register(Container $container)
    {
        $container->extend('twig.extensions', function ($extensions, $c) {
            /** @var RequestStack $requestStack */
            $requestStack = $c['requestStack'];
            if ($requestStack->getMasterRequest() !== null) {
                $extensions->add(new PdfSafeExtension($requestStack->getMasterRequest()->getSchemeAndHttpHost()));
            }
            return $extensions;
        });

        $container->extend('twig.loaderFileSystem', function (\Twig_Loader_Filesystem $loader, $c) {
            $loader->prependPath(dirname(__DIR__) . '/Resources/views', 'AbstractPdfTheme');

            return $loader;
        });
    }
}

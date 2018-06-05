<?php

namespace Themes\AbstractPdfTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\PdfSafe\PdfSafeExtension;
use Symfony\Component\HttpFoundation\Request;

class PdfServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->extend('twig.extensions', function ($extensions, $c) {
            /** @var Request $request */
            $request = $c['request'];
            $extensions->add(new PdfSafeExtension($request->getSchemeAndHttpHost()));

            return $extensions;
        });

        $container->extend('twig.loaderFileSystem', function (\Twig_Loader_Filesystem $loader, $c) {
            $loader->addPath(dirname(__DIR__) . '/Resources/views', 'AbstractPdfTheme');

            return $loader;
        });
    }
}

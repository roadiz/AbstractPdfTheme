<?php

namespace Themes\AbstractPdfTheme\Controllers;

use PHPPdf\Cache\CacheImpl;
use PHPPdf\Core\Configuration\LoaderImpl;
use PHPPdf\Core\FacadeBuilder;
use PHPPdf\Exception\RuntimeException;
use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait PdfControllerTrait
{
    /**
     * @return \PHPPdf\Core\Facade
     */
    protected function getPdfFacade()
    {
        $loader = new LoaderImpl();
        $loader->setFontFile($this->getXmlFonts());
        $builder = FacadeBuilder::create($loader);
        $facade = $builder->build();

        if ($this->get('kernel')->isProdMode()) {
            if (function_exists('xcache_set') && defined( CacheImpl::class . '::ENGINE_XCACHE')) {
                $facade->setCache(new CacheImpl('XCache'));
                $builder->setUseCacheForStylesheetConstraint(true);
            } elseif (function_exists('apcu_fetch') && defined( CacheImpl::class . '::ENGINE_APCU')) {
                $facade->setCache(new CacheImpl('Apcu'));
                $builder->setUseCacheForStylesheetConstraint(true);
            } elseif (function_exists('apc_fetch')) {
                $facade->setCache(new CacheImpl(CacheImpl::ENGINE_APC));
                $builder->setUseCacheForStylesheetConstraint(true);
            } else {
                $facade->setCache(new CacheImpl(CacheImpl::ENGINE_FILE));
                $builder->setUseCacheForStylesheetConstraint(true);
            }
        }

        return $facade;
    }

    /**
     * @return string
     */
    protected function getPdfTemplate()
    {
        return '@AbstractPdfTheme/pdf/base.xml.twig';
    }

    /**
     * @return string
     */
    protected function getPdfStylesheetPath()
    {
        return $this->getResourcesFolder() . '/config/pdf_stylesheet.xml';
    }

    /**
     * @return string
     */
    protected function getPdfFontsTemplate()
    {
        return '@AbstractPdfTheme/pdf/fonts.xml.twig';
    }

    /**
     * @param NodesSources|null $nodeSource
     *
     * @return string
     */
    protected function getPdfFilename(NodesSources $nodeSource = null)
    {
        if (null !== $nodeSource) {
            return $nodeSource->getNode()->getNodeName() . '.pdf';
        }

        return 'generated_' . date('Ymd') . '.pdf';
    }

    /**
     * @param Request $request
     * @param NodesSources|null $nodeSource
     *
     * @return Response
     */
    protected function generatePdf(Request $request, NodesSources $nodeSource = null)
    {
        if (null !== $nodeSource) {
            $facade = $this->getPdfFacade();
            $template = $this->getTwig()->render($this->getPdfTemplate(), $this->assignation);
            //$documentXml and $stylesheetXml are strings contains XML documents, $stylesheetXml is optional

            if ($request->query->has('font')) {
                return new Response($this->getXmlFonts(), Response::HTTP_OK, ['content-type' => 'text/xml']);
            }
            if ($request->query->has('xml')) {
                return new Response($template, Response::HTTP_OK, ['content-type' => 'text/xml']);
            }
            try {
                $stylesheets = [];
                if (file_exists($this->getPdfStylesheetPath())) {
                    $stylesheets = file_get_contents($this->getPdfStylesheetPath());
                }
                $content = $facade->render($template, $stylesheets);
            } catch (RuntimeException $e) {
                if (null !== $e->getPrevious()) {
                    $e = $e->getPrevious();
                }
                $this->get('logger')->error('Can’t output PDF file: ' . $e->getMessage());
                throw $this->createNotFoundException('Can’t output PDF file. (' . $e->getMessage() . ')');
            }

            $response = new Response();
            $response->setStatusCode(Response::HTTP_OK);
            $response->setPublic();
            $response->headers->set('Content-Type', 'application/pdf');
            // do not direct download PDF
            $response->headers->set('Content-Disposition', sprintf('filename="%s"', $this->getPdfFilename($nodeSource)));
            $response->setContent($content);

            return $response;
        }

        throw $this->createNotFoundException();
    }

    /**
     * @return string
     */
    protected function getXmlFonts()
    {
        $assignation['fontsEntities'] = $this->get('em')
            ->getRepository(Font::class)
            ->findBy([]);
        $assignation['fonts'] = [];

        /** @var Font $fontEntity */
        foreach ($assignation['fontsEntities'] as $fontEntity) {
            if (!isset($assignation['fonts'][$fontEntity->getName()])) {
                $assignation['fonts'][$fontEntity->getName()] = [];
            }
            $assignation['fonts'][$fontEntity->getName()][] = $fontEntity;
        }

        $assignation['variants'] = [
            Font::REGULAR => "normal",
            Font::ITALIC => "italic",
            Font::BOLD => "bold",
            Font::BOLD_ITALIC => "bold-italic",
            Font::LIGHT => "light",
            Font::LIGHT_ITALIC => "light-italic",
        ];

        return $this->get('twig.environment')->render(
            $this->getPdfFontsTemplate(),
            $assignation
        );
    }
}

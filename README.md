# Abstract PDF Theme

**Abstract PDF middleware for your Roadiz theme to generate PDF responses out of your contents.**

## Dependency injection

Edit your `app/conf/config.yml` file to register additional PDF theme services.

```yaml
additionalServiceProviders: 
    - \Themes\AbstractPdfTheme\Services\PdfServiceProvider
```

## PdfControllerTrait

### Usage

All you need to do is to create your node-source `Controller` in your theme and use `PdfControllerTrait`. You will be able to override any methods to configure your PDF rendering such as:

- `protected function getPdfTemplate(): string` (Default: `'@AbstractPdfTheme/pdf/base.xml.twig'`) 
- `protected function getPdfStylesheetPath(): string` (Default: `$this->getResourcesFolder() . '/config/pdf_stylesheet.xml'`) 
- `protected function getPdfFilename(NodesSources $nodeSource = null): string` (Default: `'@AbstractPdfTheme/pdf/fonts.xml.twig'`)

```php
<?php
namespace Themes\MyTheme\Controllers;

use Themes\AbstractPdfTheme\Controllers\PdfControllerTrait;
use Themes\MyTheme\MyThemeThemeApp;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageController extends MyThemeThemeApp
{
    use PdfControllerTrait;
    
    /**
     * @param Request $request
     * @param Node|null $node
     * @param Translation|null $translation
     * @return Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null
    ) {
        $this->prepareThemeAssignation($node, $translation);

        if ($request->query->has('_format') && $request->query->get('_format') == 'pdf') {
            return $this->generatePdf($request, $this->nodeSource);
        }

        $response = $this->render('pages/page.html.twig', $this->assignation);

        return $response;
    }

    protected function getPdfTemplate()
    {
        return 'pdf/page.xml.twig';
    }
}
```

### Template examples

`Resources/views/` folder contains useful templates for creating your own PDF. Feel free to include them directly in your theme or duplicated them.

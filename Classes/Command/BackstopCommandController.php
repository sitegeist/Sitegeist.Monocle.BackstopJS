<?php
declare(strict_types=1);

namespace Sitegeist\Monocle\BackstopJS\Command;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Mvc\ActionRequest;
use Sitegeist\Monocle\Service\DummyControllerContextTrait;
use Sitegeist\Monocle\Service\PackageKeyTrait;
use Sitegeist\Monocle\Fusion\FusionService;
use Sitegeist\Monocle\Service\ConfigurationService;
use Neos\Flow\Mvc\Routing\UriBuilder;

class BackstopCommandController extends CommandController
{
    use DummyControllerContextTrait, PackageKeyTrait;

    /**
     * @Flow\Inject
     * @var FusionService
     */
    protected $fusionService;

    /**
     * @Flow\Inject
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @Flow\InjectConfiguration(path="configurationTemplate")
     * @var array
     */
    protected $configurationTemplate;

    /**
     * @Flow\Inject
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\InjectConfiguration(path="scenarioTemplate")
     * @var array
     */
    protected $scenarioTemplate;

    /**
     * @param string $baseUri
     * @param string|null $packageKey
     * @throws \Neos\Flow\Http\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Neos\Domain\Exception
     */
    public function configurationCommand(string $baseUri, ?string $packageKey = null) {

        $backstopJsConfiguration = $this->configurationTemplate;

        $sitePackageKey = $packageKey ?: $this->getDefaultSitePackageKey();

        $fusionAst = $this->fusionService->getMergedFusionObjectTreeForSitePackage($sitePackageKey);
        $styleguideObjects = $this->fusionService->getStyleguideObjectsFromFusionAst($fusionAst);

        // mock action request
        $httpRequest = new ServerRequest('get', new Uri($baseUri));
        $actionRequest = new ActionRequest($httpRequest);
        putenv('FLOW_REWRITEURLS=1');

        // prepare uri builder
        $this->uriBuilder->reset();
        $this->uriBuilder->setRequest($actionRequest);
        $this->uriBuilder->setCreateAbsoluteUri(true);

        $scenarioConfigurations = [];
        foreach ($styleguideObjects as $prototypeName => $styleguideInformations) {
            $scenario = $this->scenarioTemplate;
            $scenario['label'] = $styleguideInformations['title'] ?? $prototypeName;
            $scenario['url'] = $this->uriBuilder->uriFor(
                'index',
                [
                    'prototypeName' => $prototypeName,
                    'sitePackageKey' => $sitePackageKey
                ],
                'preview',
                'Sitegeist.Monocle'
            );
            $scenarioConfigurations[] = $scenario;
        }

        $viewportPresets = $this->configurationService->getSiteConfiguration($sitePackageKey, 'ui.viewportPresets');
        $viewportConfigurations = [];
        foreach ($viewportPresets as $viewportName => $viewportConfiguration) {
            $viewport = [
                'label' => $viewportConfiguration['label'],
                'width' => $viewportConfiguration['width'],
                'height' => $viewportConfiguration['height']
            ];
            $viewportConfigurations[] = $viewport;
        }

        $backstopJsConfiguration['id'] = $sitePackageKey;
        $backstopJsConfiguration['scenarios'] = $scenarioConfigurations;
        $backstopJsConfiguration['viewports'] = $viewportConfigurations;

        $this->outputLine(json_encode($backstopJsConfiguration, JSON_PRETTY_PRINT));
    }
}

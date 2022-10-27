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
use Neos\Utility\Arrays;

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
     * @Flow\Inject
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * Generate a backstopJS configuration file for the given site-package and baseUri
     *
     * @param string|null $baseUri the base uri, if empty the configured baseUri from settings is used
     * @param string|null $packageKey the site-package, if empty the default site package is used
     * @param string|null $report the reports to generate seperated by comma, possible keys are 'browser', 'CI' and 'json'
     * @throws \Neos\Flow\Http\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Neos\Domain\Exception
     */
    public function configurationCommand(string $baseUri = null, ?string $packageKey = null, ?string $report = null) {
        $this->prepareUriBuilder();

        $sitePackageKey = $packageKey ?: $this->getDefaultSitePackageKey();
        $fusionAst = $this->fusionService->getMergedFusionObjectTreeForSitePackage($sitePackageKey);
        $styleguideObjects = $this->fusionService->getStyleguideObjectsFromFusionAst($fusionAst);

        // read configuration and respect package-based overrides
        $baseUri = $baseUri ?: $this->configurationService->getSiteConfiguration($sitePackageKey, 'BackstopJS.baseUri');
        $configurationTemplate = $this->configurationService->getSiteConfiguration($sitePackageKey, 'BackstopJS.configurationTemplate');
        $scenarioTemplate = $this->configurationService->getSiteConfiguration($sitePackageKey, 'BackstopJS.scenarioTemplate');
        $defaultOptIn = $this->configurationService->getSiteConfiguration($sitePackageKey, 'BackstopJS.defaultOptIn');
        $propSetOptIn = $this->configurationService->getSiteConfiguration($sitePackageKey, 'BackstopJS.propSetOptIn');
        $useCaseOptIn = $this->configurationService->getSiteConfiguration($sitePackageKey, 'BackstopJS.useCaseOptIn');

        // apply 'hiddenPrototypeNamePatterns'
        // @todo this should become part of the monocle "getStyleguideObjectsFromFusionAst" method
        $hiddenPrototypeNamePatterns = $this->configurationService->getSiteConfiguration($sitePackageKey, 'hiddenPrototypeNamePatterns');
        if (is_array($hiddenPrototypeNamePatterns)) {
            $alwaysShowPrototypes = $this->configurationService->getSiteConfiguration($sitePackageKey, 'alwaysShowPrototypes');
            foreach ($hiddenPrototypeNamePatterns as $pattern) {
                $styleguideObjects = array_filter(
                    $styleguideObjects,
                    function ($prototypeName) use ($pattern, $alwaysShowPrototypes) {
                        if (in_array($prototypeName, $alwaysShowPrototypes, true)) {
                            return true;
                        }
                        return fnmatch($pattern, $prototypeName) === false;
                    },
                    ARRAY_FILTER_USE_KEY
                );
            }
        }

        $scenarioConfigurations = [];
        foreach ($styleguideObjects as $prototypeName => $styleguideInformations) {
            $enableDefault = $styleguideInformations['options']['backstop']['default'] ?? !$defaultOptIn;
            $enablePropSets = $styleguideInformations['options']['backstop']['propSets'] ?? !$propSetOptIn;
            $enableUseCases = $styleguideInformations['options']['backstop']['useCases'] ?? !$useCaseOptIn;
            if ($enableDefault) {
                $scenarioConfigurations[] = $this->prepareScenario($baseUri, $scenarioTemplate, $sitePackageKey, $prototypeName, $styleguideInformations);
            }
            if ($styleguideInformations['propSets'] && $enablePropSets) {
                foreach ($styleguideInformations['propSets'] as $propSet) {
                    $scenarioConfigurations[] = $this->prepareScenario($baseUri, $scenarioTemplate, $sitePackageKey, $prototypeName, $styleguideInformations, $propSet);
                }
            }
            if ($styleguideInformations['useCases'] && $enableUseCases) {
                foreach ($styleguideInformations['useCases'] as $useCaseConfiguration) {
                    $scenarioConfigurations[] = $this->prepareScenario($baseUri, $scenarioTemplate, $sitePackageKey, $prototypeName, $styleguideInformations, null, $useCaseConfiguration['name']);
                }
            }
        }

        $viewportPresets = $this->configurationService->getSiteConfiguration($sitePackageKey, 'ui.viewportPresets');
        $viewportConfigurations = [];
        foreach ($viewportPresets as $viewportName => $viewportConfiguration) {
            if ($viewportConfiguration && $viewportConfiguration['width'] && $viewportConfiguration['height']) {
                $viewport = [
                    'label' => $viewportConfiguration['label'],
                    'width' => $viewportConfiguration['width'],
                    'height' => $viewportConfiguration['height']
                ];
                $viewportConfigurations[] = $viewport;
            }

        }

        $backstopJsConfiguration = $configurationTemplate;
        $backstopJsConfiguration['id'] = str_replace('.', '_', $sitePackageKey);
        $backstopJsConfiguration['scenarios'] = $scenarioConfigurations;
        $backstopJsConfiguration['viewports'] = $viewportConfigurations;
        if ($report) {
            $backstopJsConfiguration['report'] = explode(',', $report);
        }

        $this->outputLine(json_encode($backstopJsConfiguration, JSON_PRETTY_PRINT));
    }

    /**
     * @param string $baseUri
     */
    protected function prepareUriBuilder(): void
    {
        // mock action request and enable rewriteurl to render
        $httpRequest = new ServerRequest('get', new Uri(""));
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        putenv('FLOW_REWRITEURLS=1');

        // prepare uri builder
        $this->uriBuilder->reset();
        $this->uriBuilder->setRequest($actionRequest);
        $this->uriBuilder->setFormat('html');
        $this->uriBuilder->setCreateAbsoluteUri(true);
    }

    /**
     * @param string $baseUri
     * @param array $scenarioTemplate
     * @param string|null $sitePackageKey
     * @param string $prototypeName
     * @param array $styleguideInformations
     * @param string|null $propSet
     * @param string|null $useCase
     * @return array
     * @throws \Neos\Flow\Http\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    protected function prepareScenario(string $baseUri, array $scenarioTemplate, ?string $sitePackageKey, string $prototypeName, array $styleguideInformations, ?string $propSet = null, ?string $useCase = null): array
    {
        $propSetScenario = $scenarioTemplate;
        $label = $prototypeName . ($propSet ? ':' . $propSet : '') . ($useCase ? ':' . $useCase : '');
        $propSetScenario['label'] = str_replace(['.', ':'], '_', $label);
        $propSetScenario['url'] = $baseUri . $this->uriBuilder->uriFor(
            'index',
            [
                'sitePackageKey' => $sitePackageKey,
                'prototypeName' => $prototypeName,
                'propSet' => $propSet,
                'useCase' => $useCase
            ],
            'preview',
            'Sitegeist.Monocle'
        );

        $scenarioConfiguration = $styleguideInformations['options']['backstop']['scenario'] ?? null;
        if ($scenarioConfiguration && is_array($scenarioConfiguration)) {
            $propSetScenario = Arrays::arrayMergeRecursiveOverrule($propSetScenario, $scenarioConfiguration);
        }
        return $propSetScenario;
    }


}

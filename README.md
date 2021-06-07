# Sitegeist.Monocle.BackstopJS
[BackstopJS](https://garris.github.io/BackstopJS) connector for the Sitegeist.Monocle Styleguide

This package implements the command `./flow backstop:Configuration` to create a BackstopJS configuration file for 
a whole Monocle styleguide to test all prototypes with all propSets in all viewports. The generated configuration 
and scenarios can be adjusted via package setting or via `@styleguide.options.backstop`. 

### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package was generously sponsored
by our customer https://www.cornelsen.de and our employer https://www.sitegeist.de.*

## Installation

Sitegeist.Monocle.BackstopJS is available via packagist run `composer require sitegeist/monocle-backstopjs`.

We use semantic-versioning so every breaking change will increase the major-version number.

## Basic Tutorial 

1. Install BackstopJS: `npm install -g backstopjs` or install BackstopJS as dev depencency of your project
2. Sitegeist.Monocle.BackstopJS: `composer require sitegeist/monocle-backstopjs`
2. Create BackstopJS Configuration: `./flow backstop:configuration --package-key Vendor.Site  --base-uri http://127.0.0.1:8081 > custom-backstop.json` 
3. Start Flow Webserver: `FLOW_CONTEXT=Development/VisualRegressionTesting ./flow server:run`
4. Create Reference Files: `backstop reference --config=custom-backstop.json`
5. Run Test: `backstop test --config=custom-backstop.json`

## CLI Command

The package provides a single cli command that can be that create a backstop js configuration on the
fly for a given site package and baseUri.

```
USAGE:
  ./flow backstop:configuration [<options>]

OPTIONS:
  --base-uri           the base uri, if empty `http://127.0.0.1:8081` is assumed
  --package-key        the site-package, if empty the default site package is used
  --report             the reports to generate seperated by comma, possible keys are 'browser', 'CI' and 'json'
```

## Configuration

The generated backstop configuration is configured via `Settings.yaml` and the `@styleguide.options.backtop`
annotation for fusion prototypes. 

Settings.yaml:
```yaml
Sitegeist:
  Monocle:
    BackstopJS:

      # if enabled only prototypes that have @styleguide.options.backstop.default = true will
      # be included, by default all prototypes from the styleguide are included
      defaultOptIn: false

      # if enabled only propSets from prototypes that have @styleguide.options.backstop.propSets = true
      # will be included, by default all propSets from the styleguide are included
      propSetOptIn: false

      # if enabled only useCases from prototypes that have @styleguide.options.backstop.useCases = true
      # will be included, by default all useCases from the styleguide are included
      useCaseOptIn: false

      # template for the generated backstop.json file
      # the keys 'id','viewports','scenarios' and 'pathes' are
      # replaced by the backstop:configuration command
      # all other keys can be adjusted as needed 
      # See: https://github.com/garris/BackstopJS/blob/master/README.md
      configurationTemplate: ...

      # template for each scenario, the keys 'label' and 'url' are replaced 
      # by the backstop:configuration command, everything else can be adjusted as needed  
      # See: https://github.com/garris/BackstopJS/blob/master/README.md#advanced-scenarios
      scenarioTemplate: ...     
```

All settings can be overwritten for each site-package to adjust to multi site environments

```yaml
Sitegeist:
  Monocle:
    packages:
      'Vendor.Site':
        BackstopJS:
          configurationTemplate:
            paths:
              bitmaps_reference: 'DistributionPackages/Vendor.Site/Test/BackstopJS/References'
              engine_scripts: 'DistributionPackages/Vendor.Site/Test/BackstopJS/EngineScripts'
              bitmaps_test: 'Data/Temporary/BackstopJS/Vendor.Site/Test'
              html_report: 'Data/Temporary/BackstopJS/Vendor.Site/HtmlReport'
              ci_report: 'Data/Temporary/BackstopJS/Vendor.Site/CiReport'
```

Prototype.fusion:
```
prototype(Vendor.Site:Component) < prototype(Neos.Fusion:Component) {
    @styleguide {
        options {
            backstop {

                # enable or disable proptype the default depends on
                # the `itemOptIn` setting
                default = true

                # enable or disable the propSet inclusion, the default
                # depends on `propSetOptIn` settings   
                propSets = true

                # enable or disable the useCases inclusion, the default
                # depends on `useCaseOptIn` settings   
                useCases = true
                
                # configure scenario settings 
                scenario {
                  delay = 2000
                  hoverSelector = '.button'
                }
            }
        }
    }
}
```
### Advanced scenarios 

BackstopJS offers quite a bit of settings to adjust specific scenarios which is documented here https://github.com/garris/BackstopJS#advanced-scenarios. 
While the general scenario template can be adjusted via Settings.yaml the scenario configuration of each prototyoe can
be adjusted by the fusion  annotations `@styleguide.options.backstop.scenario`. All keys defined here there will override 
the generated scenario.

## Common problems and solutions 

### LazyLoading of Images 

If the images in the project use lazy loading it is quite likely that the images are not reliably loaded before the 
screenshot ist taken. This can be mitigated with by disabling the lazy loading in the styleguide. 

The following fusion code that will disable layzyloading for `Sitegeist.Kaleidoscope` and `Sitegeist.Lazybones`
is included via FlowContext `Development/VisualRegressionTesting`.

```
//
// Disable lazy loading via loading="lazy" from Sitegeist.Kaleidoscope
//
prototype(Sitegeist.Kaleidoscope:Image) {
    loading.@process.override = 'eager'
}
prototype(Sitegeist.Kaleidoscope:Picture) {
    loading.@process.override = 'eager'
}

//
// Disable lazy loading via lazysizes.js from Sitegeist.Lazybones
// 
prototype(Sitegeist.Lazybones:Image) {
    lazy.@process.override = false
}
prototype(Sitegeist.Lazybones:Picture) {
    lazy.@process.override = false
}
```
!!! Do not include this code in the regular fusion code for Deveopment or Production !!!


Alternatively you can also use the following options:

1. Configure a delay in the setting `Sitegeist.Monocle.BackstopJS.scenarioTemplate.delay: 3000`
2. Configure a delay for specific prototypes `@styleguide.options.backstop.scenario.delay = 3000`
3. Configure an `onBeforeScript` in the setting `Sitegeist.Monocle.BackstopJS.configurationTemplate.onBeforeScript` 
   see https://github.com/garris/BackstopJS#running-custom-scripts that ensures that responsive images have been loaded.
        
### Cross platform rendering inconsistencies

Since the rendering especially of fonts has slight deviations between different operation systems it is important
to run the tests always in a very similar environment to avoid false errors. BackstopJS come with a `--docker` option
that will execute all tests using a docker container running headless chrome on linux. 

_If the `--docker` option is used make sure to call the `./flow backstop:configuration` command with a `--base-uri` that can be 
reolved from the docker container like in the example below._

```
./flow backstop:configuration --base-uri http://host.docker.internal:8081 > backstop.json && backstop test --config=backstop.json --docker
```

- When using ./flow server run use http instead of https
- The backstop docker-container will likely not know about local hostname to generate the backstiothe http-port of the development nginx container 

## Contribution

We will gladly accept contributions. Please send us pull requests.

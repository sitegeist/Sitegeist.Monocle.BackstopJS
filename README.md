
# Sitegeist.Monocle.BackstopJS

[BackstopJS](https://garris.github.io/BackstopJS) connector for the Sitegeist.Monocle Styleguide

### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored
by our employer http://www.sitegeist.de.*

## Installation

Sitegeist.Monocle.BackstopJS is available via packagist run `composer require sitegeist/monocle-backstopjs`.

We use semantic-versioning so every breaking change will increase the major-version number.

## Basic Tutorial 

1. Install BackstopJS: `npm install -g backstopjs`
2. Sitegeist.Monocle.BackstopJS: `composer require sitegeist/monocle-backstopjs`
2. Create BackstopJS Configuration: `./flow backstop:configuration --package-key Vendor.Site  --base-uri http://127.0.0.1:8081 > custom-backstop.json` 
3. Start Flow webserver: `./flow server:run`
4. Create Reference Files: `backstop reference --config=custom-backstop.json`
5. Run Test: `backstop test --config=custom-backstop.json`

## CLI Command

The package provides a single cli command that can be that create a backstop js configuration on the
fly for a given site package and baseUri.

```
USAGE:
  ./flow backstop:configuration [<options>]

OPTIONS:
  --base-uri           the base uri, if empty a local flow:server run is
                       assumed
  --package-key        the site-package, if empty the default site package is
                       used
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

Prototype.fusion:
```
prototype(Vendor.Site:Component) < prototype(Neos.Fusion:Component) {
    @styleguide {
        options {
            backstop {
                # enable or disable proptype the default depends on
                # the `itemOptIn` setting
                enabled = true
                # enable or disable the propSet inclusion, the default
                # depends on `propSetOptIn` settings   
                propSets = true
            }
        }
    }
}
```

## Contribution

We will gladly accept contributions. Please send us pull requests.

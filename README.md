
# Sitegeist.Monocle.BackstopJS

[BackstopJS](https://garris.github.io/BackstopJS) connector for the Sitegeist.Monocle Styleguide

### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored
by our employer http://www.sitegeist.de.*

## Installation

Sitegeist.Monocle.BackstopJS is available via packagist run `composer require sitegeist/monocle-backstopjs`.

We use semantic-versioning so every breaking change will increase the major-version number.

## Usage 

1. Install BackstopJS && Sitegeist.Monocle.BackstopJS

```
npm install -g backstopjs
composer require sitegeist/monocle-backstopjs
```

2. Create BackstopJS Configuration

```bash
./flow backstop:configuration --package-key Vendor.Sit  --base-uri http://127.0.0.1:8081 > backstop.json 
```

3. Create Reference Files 

```
backstop reference --configuration backstop.json 
```

4. Run Test

```
backstop test --configuration backstop.json 
```


## Contribution

We will gladly accept contributions. Please send us pull requests.

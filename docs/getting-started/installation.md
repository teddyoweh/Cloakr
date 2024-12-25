---
title: Installation
order: 1
---

# Installation

## PHP Archive (PHAR)
We distribute Cloakr as a PHAR archive that contains everytrhing you need in order to use Cloakr. Simply download it from [here]() and make it executable:

```
wget -O cloakr https://link-to-cloakr

chmod +x cloakr

./cloakr
```

Most likely, you want to put the `cloakr.phar` into a directory on your `PATH`, so you can simply call cloakr from any directory. For example:

```
sudo mv cloakr.phar /usr/local/bin/cloakr
```

After that, you are ready to go and can [share your first site](/docs/cloakr/getting-started/sharing-your-first-site).
 
## Via Composer
Cloakr is a PHP application and you can install the client for your local machine as a global composer dependency:

```bash
composer global require beyondcode/cloakr
```

Make sure that your global composer directory is inside of your `PATH` environment variable.
Simply add this directory to your `PATH` in your `~/.bash_profile` (or `~/.bashrc`) like this:

```
export PATH=~/.composer/vendor/bin:$PATH
```

## As a docker container

Cloakr has a `Dockerfile` in the root of the source that you can build and use without any extra effort.

```bash
docker build -t cloakr .
```

Usage:

```bash
docker run cloakr <cloakr command>
```

Examples:

```bash
docker run cloakr share http://192.168.2.100 # share a local site
docker run cloakr serve my-domain.com # start a server
```

Now you're ready to go and can [share your first site](/docs/cloakr/getting-started/sharing-your-first-site).


### Extending Cloakr

By default, Cloakr comes as an executable PHAR file. This allows you to use all Cloakr features out of the box – without any additional setup required.

If you want to modify Cloakr and want to add custom request/response modifiers, you need to clone the GitHub repository instead of the global composer dependency.

You can learn more about the customization of Cloakr in the [extending Cloakr](/docs/cloakr/extending-the-server/subdomain-generator) documentation section.

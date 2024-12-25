---
title: Upgrade from Cloakr v1 
order: 2
---

# Upgrade Guide

Cloakr 2 is now available and comes with a lot of new features and improvements! We worked very hard to make the transition from Cloakr 1.x to 2.x as smooth as possible.

The easiest way to update your existing Cloakr 1 installation to Cloakr 2, is by using composer.

```bash
composer global require beyondcode/cloakr:2.0
```

This will download and install the latest version of Cloakr. Your existing authentication token and configuration file will still be valid after updating to the latest version.

**Important:** Even though the Cloakr 1.x client is backwards compatible with the new Cloakr servers, please upgrade to the latest client as soon as possible, as we will fade out support for the Cloakr 1.x client in the future.

## Upgrading to Cloakr ::pro

Some of the Cloakr features are no longer available on our free server, such as custom subdomains, or the newly added TCP port sharing. 

If you want to upgrade your existing authentication token to Cloakr Pro, please [create a new Cloakr account](/register) where you will receive a fresh authentication token and can then upgrade to Cloakr Pro.
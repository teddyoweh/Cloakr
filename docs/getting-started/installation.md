---
title: Installation
order: 1
---

# Installation
 
Cloakr is a PHP application and you can install the client for your local machine as a global composer dependency:

```bash
composer global require beyondcode/cloakr
```

After that, you are ready to go and can [share your first site](/docs/cloakr/getting-started/sharing-your-first-site).

### Extending Cloakr

By default, Cloakr comes as an executable PHAR file. This allows you to use all Cloakr features out of the box – without any additional setup required.

If you want to modify Cloakr and want to add custom request/response modifiers, you need to clone the GitHub repository instead of the global composer dependency.

You can learn more about the customization of Cloakr in the [extending Cloakr](/docs/cloakr/extending-the-server/subdomain-generator) documentation section.

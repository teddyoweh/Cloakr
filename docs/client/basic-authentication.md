---
title: Basic Authentication
order: 4
---

# Sharing sites with basic authentication

Cloakr allows you to share your local sites with custom basic authentication credentials.

This is useful, if you have a static subdomain that you share with someone else, for example a client, and you want to provide some additional security to it. Before someone can access your shared site, they need to provide the correct credentials.

> **Warning**: You can not add basic authentication to a website that already uses basic authentication.

To share your site with basic authentication, use:

```bash
cloakr --auth="admin:secret"
```

Or if you want to use the explicit sharing:

```bash
cloakr share my-site.test --auth="admin:secret"
```

This will share the local URL my-site.test with the username "admin" and the password "secret".

You can also use the basic authentication parameter in addition to a custom subdomain:

```bash
cloakr share my-site.test --subdomain=site --auth="admin:secret"
```

---
title: Sharing your local sites
order: 1
---

# Sharing local sites

Cloakr allows you to share any kind of HTTP/HTTPS traffic for websites that you can reach on your own computer, with anyone on the internet.

There are multiple ways to share a site with Cloakr. 

## Sharing the current working directory

To share the current working directory with Cloakr, all you need to do is go into the directory and call `cloakr`.

This makes the assumption that you have access to the current working directory name as a domain with the `.test` TLD.  

If you are using Laravel Valet, the configured Valet subdomain will automatically be detected.

If you are using a different domain for your local sites, you can change the default TLD that cloakr uses in the [configuration file](/docs/cloakr/client/configuration).

For example: 

```bash
# Will share a local site "my-site.test" as "my-site.EXPOSE-SERVER"
~/Sites/my-site/ cloakr

# Will share a local site "api.my-site.test" as "api-my-site.EXPOSE-SERVER"
~/Sites/api.my-site/ cloakr
```

If you are using the Cloakr network on the free plan, you get a random subdomain on every connect. You can upgrade to Cloakr Pro and use custom subdomains as if you'd host your own server.

## Sharing a local site explicitly

If your local sites are not available at `foldername.test` you can explicitly share a local URL, without going into a specific folder first. You can do this by using the `cloakr share` command and specify the domain directly.

This is required when sharing sites that have HTTPS locally or Cloakr can't map local directory names to URLs automatically.

```bash
# Will share access to http://192.168.2.100 using a randomly generated subdomain
cloakr share http://192.168.2.100

# Will share access to http://my-local-site.dev using a randomly generated subdomain
cloakr share my-local-site.dev

# Will share access to https://my-local-site.dev using a randomly generated subdomain (note the https)
cloakr share https://my-local-site.dev
```

## Share a local site with a given subdomain

You can also share one of your local sites explicitly and specify the exact subdomain that you want to use when sharing the site. This is very useful if you are testing webhooks and want to use the same webhook configuration and don't update the webhook endpoints on every Cloakr connect. Custom subdomains require an own Cloakr server in your infrastructure or Cloakr Pro.

To specify the subdomain, pass the `--subdomain` option to cloakr:

```bash
cloakr share my-site.test --subdomain=my-site
```

If someone already uses the chosen subdomain on the Cloakr server, you will see an error message and the Cloakr server closes the connection.

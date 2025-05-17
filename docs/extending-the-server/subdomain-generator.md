---
title: Subdomain Generator
order: 1
---

# Subdomain Generator

When a user does not explicitly specify a custom subdomain, the cloakr server takes care of generating a random unique subdomain.

The default generator looks like this:

```php
use Illuminate\Support\Str;
use Cloakr\Client\Contracts\SubdomainGenerator;

class RandomSubdomainGenerator implements SubdomainGenerator
{
    public function generateSubdomain(): string
    {
        return strtolower(Str::random(10));
    }
}
```

It simply generates a random lowercase string.

You can create your own custom subdomain generator class, by implementing the `SubdomainGenerator` interface.

Next you need to specify your custom subdomain generator in your cloakr configuration file:

```php
/*
|--------------------------------------------------------------------------
| Subdomain Generator
|--------------------------------------------------------------------------
|
| This is the subdomain generator that will be used, when no specific
| subdomain was provided. The default implementation simply generates
| a random string for you. Feel free to change this.
|
*/
'subdomain_generator' => \Cloakr\Client\Server\SubdomainGenerator\RandomSubdomainGenerator::class,
```

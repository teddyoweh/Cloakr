---
title: Share your first site
order: 2
---

# Share your first site

Once you have installed Cloakr, you are ready to go and share your local sites.

The easiest way to share your local sites is by going into the folder that you want to share and run `cloakr`:

```bash
cd ~/Sites/my-awesome-project/

cloakr
```

This will connect to the provided server at cloakr.dev and give you a tunnel that you can immediately start using.

To learn more about how you can share your local sites, check out the [sharing local sites](/docs/cloakr/client/sharing) documentation.

## Using the provided server at cloakr.dev

A big advantage of Cloakr over other alternatives such as ngrok, is the ability to host your own server. To make sharing your sites as easy as possible, we provide and host a custom cloakr server on our own - so getting started with cloakr is a breeze.

This server is available free of charge for everyone, but makes use of Cloakr's [authentication token]() authorization method.

Therefore, in order to share your sites for the first time, you will need an authorization token.

You can obtain such a token by singing in to your [Beyond Code account](/login). If you do not yet have an account, you can [sign up and create an account](/register) for free.

## Authenticating with cloakr.dev

To register and use the given credentials, just run the following command:

```bash
cloakr token [YOUR-AUTH-TOKEN]
```

This will register the token globally in your cloakr configuration file, and all following cloakr calls, will automatically use the token to authenticate with the server.
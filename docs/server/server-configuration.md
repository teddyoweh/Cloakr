---
title: Server Configuration
order: 5
---

# Server Configuration

Within the Cloakr admin interface you can configure how you want your specific cloakr server to behave.

Here are the available settings:

![](/img/cloakr_settings.png)

## Authentication

When you start your cloakr server, anyone is able to connect to it by default. If you want to restrict your server only to users that have a valid "authentication token", you can simply check the checkbox. Only registered users / authentication tokens are then able to connect to your server.

> **Note:** This is only a temporary modification for as long as your cloakr server is running. To permanently enable this feature, modify your cloakr config file.

## Message of the day

This message will be shown when a sucessful connection to the cloakr server can be established. You can change it on demand, or modify it permanently in your cloakr configuration file.

## Maximum connection length

You can define how long you want your users connection to your cloakr server to be open at maximum. This time can be configured in minutes. Once the connection exceeds the specified duration, the client connection gets closed automatically.

## Authentication failed

This message will be shown when a user tries to connect with an invalid authentication token. If your users can somehow obtain an authentication token, this is a great place to let them know how to do it.

## Subdomain taken

This message will be shown when a user tries to connect with an already registered subdomain. This could be any user-registered subdomain, as well as the cloakr admin dashboard subdomain.

---
title: Global Server Infrastructure
order: 4
---

# Global Server Infrastructure ::pro

[Cloakr Pro](/get-pro) allows you to choose between multiple Cloakr servers around the world, so that you can use an endpoint closest to you.

To get a list of all the available Cloakr servers, you can run `cloakr servers`

```
$ cloakr servers

+------+---------------------------+------+
| Key  | Region                    | Type |
+------+---------------------------+------+
| eu-1 | EU (Frankfurt)            | Pro  |
| us-1 | US (New York)             | Pro  |
| us-2 | US (San Francisco)        | Pro  |
| ap-1 | Asia Pacific (Singapore)  | Pro  |
| in-1 | India (Bangalore)         | Pro  |
| sa-1 | South America (São Paulo) | Pro  |
| au-1 | Australia (Sydney)        | Pro  |
+------+---------------------------+------+
```

## Changing servers

When you share a local URL, or a local TCP port, you can specify the Cloakr server region, using the `--server` command line option. Pass the server key as the option to connect to this specific server.

```bash
cloakr share my-local-site.test --server=eu-1
```

## Setting a default server

Most of the time you will want to always use the server location that is closest to you for all of your Cloakr commands. You can define the default server that Cloakr should use, by calling the `cloakr default-server` command:

```bash
cloakr default-server us-2
```

Now the next time that you will share a local URL or port, Cloakr is automatically going to connect to the `us-2` server for your.
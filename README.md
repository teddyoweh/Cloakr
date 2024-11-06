# Cloakr

## Usage

## Server

To start the server, call:

```
php cloakr serve
```

## Client

To share local connections with a server, call:

```
php cloakr share somelocalsite.test
```

### Custom subdomain(s) 
You can also define a custom subdomain using the `--subdomain` option:

```
php cloakr share somelocalsite.test --subdomain=custom
```

The subdomain option also allows you to specify multiple, comma-separated subdomains:

```
php cloakr share somelocalsite.test --subdomain=app,admin
```

### Basic authentication 
You can protect your shared URLs using basic authentication, by providing the username and password in the `--auth` option:

```
php cloakr share somelocalsite.test --auth="username:password"
```

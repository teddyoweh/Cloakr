---
title: Admin Interface
order: 3
---

# Admin Interface

The Cloakr server comes with a beautiful admin interface that makes configuring your server a breeze.

The admin interface is available at a specific subdomain on your cloakr server. By default it is called "cloakr", but you can change this in the configuration file:

```
...

/*
|--------------------------------------------------------------------------
| Subdomain
|--------------------------------------------------------------------------
|
| This is the subdomain that your cloakr admin dashboard will be available at.
| The given subdomain will be reserved, so no other tunnel connection can
| request this subdomain for their own connection.
|
*/
'subdomain' => 'cloakr',

...
```

So you can reach the admin interface at http://cloakr.your-domain.com.

## Authentication

Since the cloakr admin interface allows you to change and modify your cloakr server configuration at runtime, access to the admin interface is protected using basic authentication.
You can define which user/password combinations are allowed in the configuration file:

> **Note:** You will need to restart your cloakr server once you change this setting in order for the changes to take effect.

```
...

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
|
| The admin dashboard of cloakr is protected via HTTP basic authentication
| Here you may add the user/password combinations that you want to
| accept as valid logins for the dashboard.
|
*/
'users' => [
    'username' => 'password'
],

...
```

### Users

![](/img/cloakr_users.png)

Here you can list, add and delete all users that you want to be able to connect to your cloakr server. 
The users will be stored in a SQLite database that can be modified in the cloakr configuration file.

You only need to add users to your cloakr server if you have the auth token validation enabled.

### Shared sites

![](/img/cloakr_admin.png)

You can see a list of all connected sites here once you and others start sharing their local sites with your server.
You can see the original client host that was shared, the subdomain that was associated to this and the time and date the site was shared.

The cloakr server can also disconnect a site from the server. Just press on the "Disconnect" button and the client connection will be closed.

### Settings

![](/img/cloakr_settings.png)

Here you can see and modify your Cloakr server settings. All settings that the UI offers can also be manually edited in the cloakr configuration file.

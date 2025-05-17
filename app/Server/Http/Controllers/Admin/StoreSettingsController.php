<?php

namespace App\Server\Http\Controllers\Admin;

use App\Server\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Ratchet\ConnectionInterface;

class StoreSettingsController extends AdminController
{
    /** @var Configuration */
    protected $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        config()->set('cloakr.admin.validate_auth_tokens', (bool) $request->get('validate_auth_tokens'));

        $messages = $request->get('messages');

        config()->set('cloakr.admin.messages.invalid_auth_token', Arr::get($messages, 'invalid_auth_token'));

        config()->set('cloakr.admin.messages.subdomain_taken', Arr::get($messages, 'subdomain_taken'));

        config()->set('cloakr.admin.maximum_connection_length', $request->get('maximum_connection_length'));

        config()->set('cloakr.admin.messages.message_of_the_day', Arr::get($messages, 'message_of_the_day'));

        config()->set('cloakr.admin.messages.custom_subdomain_unauthorized', Arr::get($messages, 'custom_subdomain_unauthorized'));

        config()->set('cloakr.admin.messages.no_free_tcp_port_available', Arr::get($messages, 'no_free_tcp_port_available'));

        config()->set('cloakr.admin.messages.tcp_port_sharing_unauthorized', Arr::get($messages, 'tcp_port_sharing_unauthorized'));

        config()->set('cloakr.admin.messages.tcp_port_sharing_disabled', Arr::get($messages, 'tcp_port_sharing_disabled'));

        $httpConnection->send(
            respond_json([
                'configuration' => $this->configuration,
            ])
        );
    }
}

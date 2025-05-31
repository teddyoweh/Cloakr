<?php

namespace Cloakr\Client\Logger\Plugins;

use Exception;

class StripePlugin extends BasePlugin
{

    public function getTitle(): string
    {
        return 'Stripe';
    }

    public function matchesRequest(): bool
    {
        $request = $this->loggedRequest->getRequest();
        $headers = $request->getHeaders();

        return
            $headers->has('User-Agent') &&
            $headers->has('stripe-signature') &&
            $request->getHeader('User-Agent') &&
            str_starts_with($request->getHeader('User-Agent')->getFieldValue(), "Stripe/1.0");
    }

    public function getPluginData(): PluginData
    {
        try {
            $content = json_decode($this->loggedRequest->getRequest()->getContent(), true);
            $eventType = $content['type'];
            $details = collect($content)->except(['type'])->toArray();
        } catch (\Throwable $e) {
            return PluginData::error($this->getTitle(), $e);
        }

        return PluginData::make()
            ->setPlugin($this->getTitle())
            ->setLabel($eventType)
            ->setDetails($details);
    }
}

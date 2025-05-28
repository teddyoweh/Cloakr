<?php

namespace Cloakr\Client\Logger\Plugins;

use Exception;

class PaddleBillingPlugin extends BasePlugin
{

    public function getTitle(): string
    {
        return 'Paddle Billing';
    }

    public function matchesRequest(): bool
    {
        $request = $this->loggedRequest->getRequest();
        $headers = $request->getHeaders();

        return
            $headers->has('User-Agent') &&
            $headers->has('paddle-signature') &&
            $headers->has('paddle-version') &&
            $request->getHeader('User-Agent') &&
            $request->getHeader('User-Agent')->getFieldValue() === "Paddle";
    }

    public function getPluginData(): PluginData
    {
        try {
            $content = json_decode($this->loggedRequest->getRequest()->getContent(), true);
            $eventType = $content['event_type'];
            $details = collect($content)->except(['event_type'])->toArray();
        } catch (\Throwable $e) {
            return PluginData::error($this->getTitle(), $e);
        }

        return PluginData::make()
            ->setPlugin($this->getTitle())
            ->setLabel($eventType)
            ->setDetails($details);
    }
}

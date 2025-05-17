<?php

namespace App\Logger\Plugins;

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
            $request->getHeader('User-Agent')->getFieldValue() === "Paddle" &&
            $headers->has('paddle-signature') &&
            $headers->has('paddle-version');
    }

    public function getPluginData(): PluginData
    {
        try {
            $content = json_decode($this->loggedRequest->getRequest()->getContent(), true);
            $eventType = $content['event_type'];
            $details = [
                'event_id' => $content['event_id'],
                'notification_id' => $content['notification_id'],
                // TODO
            ];
        } catch (\Throwable $e) {
            return PluginData::error($this->getTitle(), $e);
        }

        return PluginData::make()
            ->setPlugin($this->getTitle())
            ->setUiLabel($eventType)
            ->setCliLabel($eventType)
            ->setDetails($details);
    }
}

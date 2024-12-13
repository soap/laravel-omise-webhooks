<?php

namespace Soap\OmiseWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class OmiseWebhooksProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        return ! WebhookCall::where('name', 'omise')->where('payload->id', $request->get('id'))->exists();
    }
}

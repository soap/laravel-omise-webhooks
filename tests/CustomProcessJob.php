<?php

namespace Soap\OmiseWebhooks\Tests;

use Soap\OmiseWebhooks\ProcessOmiseWebhooksJob;

class CustomProcessJob extends ProcessOmiseWebhooksJob
{
    public function handle()
    {
        cache()->put('customprocessjob', $this->webhookCall);

        parent::handle();
    }
}

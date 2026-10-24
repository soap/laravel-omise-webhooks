<?php

use Soap\OmiseWebhooks\OmiseWebhooksProfile;
use Soap\OmiseWebhooks\ProcessOmiseWebhooksJob;
use Spatie\WebhookClient\Models\WebhookCall;

// config for Soap/OmiseWebhooks
return [
    /*
     * The webhook secret (base64-encoded) shown in the Omise dashboard.
     * It is used to verify the `Omise-Signature` header of incoming webhooks.
     * See https://docs.omise.co/api-webhooks#webhook-signature-verification
     */
    'signing_secret' => env('OMISE_WEBHOOK_SECRET'),

    /*
     * You can define a default job that should be run for all other Omise event type
     * without a job defined in next configuration.
     * You may leave it empty to store the job in database but without processing it.
     */
    'default_job' => '',

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Omise event type (called key for Omise) with the `.` replaced by a `_`.
     *
     * You can find a list of Omise webhook types here:
     * https://docs.opn.ooo/api-webhooks#supported-events.
     */
    'jobs' => [
        // 'card_destroy' => \App\Jobs\OmiseWebhooks\HandleCardDestroy::class,
        // 'card_update' => \App\Jobs\OmiseWebhooks\HandleCardUpdate::class,

        // 'charge_capture' => \App\Jobs\OmiseWebhooks\HandleChargeCapture::class,
        // 'charge_complete' => \App\Jobs\OmiseWebhooks\HandleChargeComplete::class,
        // 'charge_create' => \App\Jobs\OmiseWebhooks\HandleChargeCreate::class,
        // 'charge_expire' => \App\Jobs\OmiseWebhooks\HandleChargeExpire::class,
        // 'charge_reverse' => \App\Jobs\OmiseWebhooks\HandleChargeReverse::class,
        // 'charge_update' => \App\Jobs\OmiseWebhooks\HandleChargeUpdate::class,

        // 'customer_create' => \App\Jobs\OmiseWebhooks\HandleCustomerCreate::class,
        // 'customer_update' => \App\Jobs\OmiseWebhooks\HandleCustomerUpdate::class,
        // 'customer_destroy' => \App\Jobs\OmiseWebhooks\HandleCustomerDestroy::class,
        // 'customer_update_card' => \App\Jobs\OmiseWebhooks\HandleCustomerUpdateCard::class,

        // 'dispute_create' => \App\Jobs\OmiseWebhooks\HandleDisputeCreate::class,
        // 'dispute_update' => \App\Jobs\OmiseWebhooks\HandleDisputeUpdate::class,
        // 'dispute_destroy' => \App\Jobs\OmiseWebhooks\HandleDisputeDestroy::class,
        // 'dispute_activate' => \App\Jobs\OmiseWebhooks\HandleDisputeActivate::class,
        // 'dispute_deactivate' => \App\Jobs\OmiseWebhooks\HandleDisputeDeactivate::class,
        // 'dispute_verify' => \App\Jobs\OmiseWebhooks\HandleDisputeVerify::class,

        // 'refund_create' => \App\Jobs\OmiseWebhooks\HandleRefundCreate::class,

        // 'schedule_create' => \App\Jobs\OmiseWebhooks\HandleScheduleCreate::class,
        // 'schedule_destroy' => \App\Jobs\OmiseWebhooks\HandleScheduleDestroy::class,
        // 'schedule_expire' => \App\Jobs\OmiseWebhooks\HandleScheduleExpire::class,
        // 'schedule_expiring' => \App\Jobs\OmiseWebhooks\HandleScheduleExpiring::class,
        // 'schedule_suspend' => \App\Jobs\OmiseWebhooks\HandleScheduleSuspend::class,

        // 'transfer_create' => \App\Jobs\OmiseWebhooks\HandleTransferCreate::class,
        // 'transfer_update' => \App\Jobs\OmiseWebhooks\HandleTransferUpdate::class,
        // 'transfer_destroy' => \App\Jobs\OmiseWebhooks\HandleTransferDestroy::class,
        // 'transfer_fail' => \App\Jobs\OmiseWebhooks\HandleTransferFail::class,
        // 'transfer_pay' => \App\Jobs\OmiseWebhooks\HandleTransferPay::class,
        // 'transfer_send' => \App\Jobs\OmiseWebhooks\HandleTransferSend::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\WebhookClient\Models\WebhookCall.
     */
    'model' => WebhookCall::class,

    /**
     * This class determines if the webhook call should be stored and processed.
     */
    'profile' => OmiseWebhooksProfile::class,

    /*
     * The job that fires the events and dispatches your jobs for a stored webhook call.
     * The class should equal or extend Soap\OmiseWebhooks\ProcessOmiseWebhooksJob.
     */
    'process_webhook_job' => ProcessOmiseWebhooksJob::class,

    /*
     * Specify a connection and or a queue to process the webhooks
     */
    'connection' => env('OMISE_WEBHOOK_CONNECTION'),
    'queue' => env('OMISE_WEBHOOK_QUEUE'),

    /*
     * When disabled, the package will not verify if the signature is valid.
     * This can be handy in local environments.
     */
    'verify_signature' => env('OMISE_SIGNATURE_VERIFICATION', false),

    /*
     * The maximum age (in seconds) of the `Omise-Signature-Timestamp` header,
     * used to protect against replay attacks. Set to 0 to disable this check.
     */
    'signature_tolerance' => env('OMISE_SIGNATURE_TOLERANCE', 300),

    /*
     * When `verify_ip` is enabled, this setting determines if the signature should be
     * validated using request 's IP address.
     */
    'verify_ip' => env('OMISE_IP_VERIFICATION', false),
];

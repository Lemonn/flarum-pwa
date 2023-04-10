<?php

/*
 * This file is part of askvortsov/flarum-pwa
 *
 *  Copyright (c) 2021 Alexander Skvortsov.
 *
 *  For detailed copyright and license information, please view the
 *  LICENSE file that was distributed with this source code.
 */

namespace Askvortsov\FlarumPWA\Api\Controller;

use Askvortsov\FlarumPWA\Api\Serializer\PushSubscriptionSerializer;
use Askvortsov\FlarumPWA\PushSubscription;
use Carbon\Carbon;
use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class AddPushSubscriptionController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = PushSubscriptionSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
    ];

    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $data = Arr::get($request->getParsedBody(), 'subscription', []);

        if (!($endpoint = Arr::get($data, 'endpoint'))) {
            throw new InvalidParameterException('Endpoint must be provided');
        }
        //TODO The amount of subscriptions allowed per user should be settable by an admin
        //TODO The list of approved push-URLs should be definable in the admin interface
        //TODO The user should be present whit a list of existing push subscriptions. Including the option
        // to delete no longer used ones.

        $existing = PushSubscription::where('endpoint', $endpoint);
        if ($existing->first()) {
            return $existing->first();
        } else if ($existing->count() >= 5) {
            //TODO kick out oldest subscription, or present the user whit an option to delete unused ones
            //TODO correct error handling
            return null;
        }

        //TODO replace whit array form Database. Right now this contains all relevant push services I know. Maybe
        // missing some chinese ones (Huawei?)
        $allowed_push_hosts = array("fcm.googleapis.com", "web.push.apple.com", "updates.push.services.mozilla.com");
        $allowed = false;
        foreach ($allowed_push_hosts as $i => $value) {
            if (parse_url($endpoint, PHP_URL_HOST) == $value) {
                $allowed = true;
            }
        }
        if (!$allowed) {
            //TODO correct error handling
            return null;
        } else {
            $subscription = new PushSubscription();

            $subscription->user_id = $actor->id;
            $subscription->endpoint = $endpoint;
            $subscription->expires_at = isset($data['expirationTime']) ? Carbon::parse($data['expirationTime']) : null;
            $subscription->vapid_public_key = $this->settings->get('askvortsov-pwa.vapid.public');
            $subscription->keys = isset($data['keys']) ? json_encode($data['keys']) : null;
            $subscription->save();
            return $subscription;
        }
    }
}

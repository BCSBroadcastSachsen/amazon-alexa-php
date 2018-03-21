<?php

namespace MaxBeckers\AmazonAlexa\Request;

use MaxBeckers\AmazonAlexa\Exception\MissingRequestDataException;
use MaxBeckers\AmazonAlexa\Exception\MissingRequiredHeaderException;
use MaxBeckers\AmazonAlexa\Request\Request\AbstractRequest;
use MaxBeckers\AmazonAlexa\Request\Request\AudioPlayer\PlaybackFailedRequest;
use MaxBeckers\AmazonAlexa\Request\Request\AudioPlayer\PlaybackFinishedRequest;
use MaxBeckers\AmazonAlexa\Request\Request\AudioPlayer\PlaybackNearlyFinishedRequest;
use MaxBeckers\AmazonAlexa\Request\Request\AudioPlayer\PlaybackStartedRequest;
use MaxBeckers\AmazonAlexa\Request\Request\AudioPlayer\PlaybackStoppedRequest;
use MaxBeckers\AmazonAlexa\Request\Request\Display\ElementSelectedRequest;
use MaxBeckers\AmazonAlexa\Request\Request\PlaybackController\NextCommandIssued;
use MaxBeckers\AmazonAlexa\Request\Request\PlaybackController\PauseCommandIssued;
use MaxBeckers\AmazonAlexa\Request\Request\PlaybackController\PlayCommandIssued;
use MaxBeckers\AmazonAlexa\Request\Request\PlaybackController\PreviousCommandIssued;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\SessionEndedRequest;
use MaxBeckers\AmazonAlexa\Request\Request\System\ExceptionEncounteredRequest;

/**
 * @author Maximilian Beckers <beckers.maximilian@gmail.com>
 */
class Request
{
    /**
     * List of all supported amazon request types.
     */
    const REQUEST_TYPES = [
        // Standard types
        IntentRequest::TYPE                 => IntentRequest::class,
        LaunchRequest::TYPE                 => LaunchRequest::class,
        SessionEndedRequest::TYPE           => SessionEndedRequest::class,
        // AudioPlayer types
        PlaybackStartedRequest::TYPE        => PlaybackStartedRequest::class,
        PlaybackNearlyFinishedRequest::TYPE => PlaybackNearlyFinishedRequest::class,
        PlaybackFinishedRequest::TYPE       => PlaybackFinishedRequest::class,
        PlaybackStoppedRequest::TYPE        => PlaybackStoppedRequest::class,
        PlaybackFailedRequest::TYPE         => PlaybackFailedRequest::class,
        // PlaybackController types
        NextCommandIssued::TYPE             => NextCommandIssued::class,
        PauseCommandIssued::TYPE            => PauseCommandIssued::class,
        PlayCommandIssued::TYPE             => PlayCommandIssued::class,
        PreviousCommandIssued::TYPE         => PreviousCommandIssued::class,
        // System types
        ExceptionEncounteredRequest::TYPE   => ExceptionEncounteredRequest::class,
        // Display types
        ElementSelectedRequest::TYPE        => ElementSelectedRequest::class,
    ];

    /**
     * @var string|null
     */
    public $version;

    /**
     * @var Session|null
     */
    public $session;

    /**
     * @var Context|null
     */
    public $context;

    /**
     * @var AbstractRequest|null
     */
    public $request;

    /**
     * @var string
     */
    public $amazonRequestBody;

    /**
     * @var string
     */
    public $signatureCertChainUrl;

    /**
     * @var string
     */
    public $signature;

    /**
     * @param string $amazonRequestBody
     * @param string $signatureCertChainUrl
     * @param string $signature
     *
     * @throws MissingRequestDataException
     * @throws MissingRequiredHeaderException
     *
     * @return Request
     */
    public static function fromAmazonRequest(string $amazonRequestBody, string $signatureCertChainUrl, string $signature): self
    {
        $request = new self();

        $request->signatureCertChainUrl = $signatureCertChainUrl;
        $request->signature             = $signature;
        $request->amazonRequestBody     = $amazonRequestBody;
        $amazonRequest                  = json_decode($amazonRequestBody, true);

        $request->version = isset($amazonRequest['version']) ? $amazonRequest['version'] : null;
        $request->session = isset($amazonRequest['session']) ? Session::fromAmazonRequest($amazonRequest['session']) : null;
        $request->context = isset($amazonRequest['context']) ? Context::fromAmazonRequest($amazonRequest['context']) : null;

        if (isset($amazonRequest['request']['type']) && isset(self::REQUEST_TYPES[$amazonRequest['request']['type']])) {
            $request->request = (self::REQUEST_TYPES[$amazonRequest['request']['type']])::fromAmazonRequest($amazonRequest['request']);
        } else {
            throw new MissingRequestDataException();
        }

        if ($request->request->validateSignature()) {
            if (!$request->signatureCertChainUrl || !$request->signature) {
                throw new MissingRequiredHeaderException();
            }
        }

        return $request;
    }

    /**
    * @return string|null
    */
    public function getApplicationId()
    {
        return $this->context->system->application->applicationId ?? null;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->context->system->user ?? null;
    }

    /**
     * @return Device|null
     */
    public function getDevice()
    {
        return $this->context->system->device ?? null;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public function getSessionAttribute($key, $default = null)
    {
        return $this->session->attributes[$key] ?? $default;
    }

}

<?php

namespace Askvortsov\FlarumPWA\Forum\Controller;

use Askvortsov\FlarumPWA\PWATrait;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebManifestController implements RequestHandlerInterface
{
    use PWATrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings, Application $app)
    {
        $this->settings = $settings;
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->buildManifest());
    }
}

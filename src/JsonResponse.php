<?php

namespace Drupal\bootstrap;

use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JsonResponse.
 */
class JsonResponse extends Response {

  /**
   * The decoded JSON array.
   *
   * @var array
   */
  protected $json;

  /**
   * {@inheritdoc}
   */
  public function __construct($content = '', $status = 200, array $headers = []) {
    parent::__construct($content, $status, $headers);
    $this->json = Json::decode($content ?: '[]') ?: [];
  }

  /**
   * Creates a new JsonResponse object from a Symfony Response object.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   A Symfony Response object.
   *
   * @return \Drupal\bootstrap\JsonResponse
   *   A JsonResponse object.
   */
  public static function createFromResponse(Response $response) {
    return new static($response->getContent(), $response->getStatusCode(), $response->headers->all());
  }

  /**
   * Retrieves the JSON array.
   *
   * @return array
   *   The JSON array.
   */
  public function getJson(): array {
    return $this->json;
  }

}

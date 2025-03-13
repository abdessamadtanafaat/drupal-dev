<?php

namespace Drupal\module_sprint_6\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

class ArticleController extends ControllerBase
{
  public function getArticles(): JsonResponse {
    $node_ids = [10, 223, 45]; // Hardcoded node IDs

    // Query to get nodes of type 'article' with specific IDs
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('nid', $node_ids, 'IN')
      ->accessCheck(TRUE);

    $result = $query->execute();

    $articles = [];
    if (!empty($result)) {
      $nodes = Node::loadMultiple($result);
      foreach ($nodes as $node) {
        $articles[] = [
          'nid' => (int) $node->id(),
          'title' => (string) $node->getTitle(),
        ];
      }
    }

    // Create a CacheableJsonResponse
    $response = new CacheableJsonResponse($articles);

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->setCacheMaxAge(60);  // Cache for 1 minute

    // Add cache tags for each node (based on their nid)
    foreach ($nodes as $node) {
      $cache_metadata->addCacheTags(['node:' . $node->id()]);
    }
    //dump($cache_metadata);
    $response->addCacheableDependency($cache_metadata);

    // Return the response
    return $response;
  }
}

<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    protected $client;

    public function __construct() {
        $hosts = config('services.elasticsearch.hosts');  // Ensure this returns a string
        $this->client = ClientBuilder::create()->setHosts([$hosts])->build();
    }

    public function search($params) {
        $query = [
            'bool' => [
                'must' => [],
                'filter' => []
            ]
        ];

        // Keyword search
        if (!empty($params['keyword'])) {
            $query['bool']['must'][] = [
                'multi_match' => [
                    'query' => $params['keyword'],
                    'fields' => ['title^3', 'sub_title^2', 'content', 'author.username']
                ]
            ];
        }

        // Category filter
        if (!empty($params['categoryList'])) {
            $query['bool']['filter'][] = [
                'terms' => [
                    'category' => $params['categoryList']
                ]
            ];
        }

        // Tag filter
        if (!empty($params['tagList'])) {
            $query['bool']['filter'][] = [
                'terms' => [
                    'tags' => $params['tagList']
                ]
            ];
        }

        // Content type filter
        if (!empty($params['contentType'])) {
            $query['bool']['filter'][] = [
                'term' => [
                    'type' => $params['contentType']
                ]
            ];
        }

        // Build the final query array
        $searchParams = [
            'index' => 'quanthub-articles',
            'body' => [
                'query' => $query,
                'sort' => $this->buildSortParams($params)
            ]
        ];

        $response = $this->client->search($searchParams);

        // Process the results
        $results = [];
        foreach ($response['hits']['hits'] as $hit) {
            $results[] = [
                'id' => $hit['_id'],
                'score' => $hit['_score'],
                'source' => $hit['_source'] // This contains the actual data of the document
            ];
        }

        return $results;
    }

    /**
     * Indexes an article in Elasticsearch.
     */
    public function indexArticle($articleData) {
        Log::info("Indexing article with ID: {$articleData['id']}");
        Log::info("Author data: " . json_encode($articleData['author']));
        return $this->client->index([
            'index' => 'quanthub-articles',
            'id' => $articleData['id'],
            'body' => [
                'author' => $articleData['author'],
                'title' => $articleData['title'],
                'sub_title' => $articleData['sub_title'],
                'content' => $articleData['content'],
                'type' => $articleData['type'],
                'status' => $articleData['status'],
                'category' => $articleData['category'],
                'tags' => $articleData['tags'],
                'publish_date' => $articleData['publish_date'],
                'cover_image_link' => $articleData['cover_image_link'],
                'attachment_link' => $articleData['attachment_link'],
                'created_by' => $articleData['created_by'],
                'updated_by' => $articleData['updated_by']
            ]
        ]);
    }


    private function buildSortParams($params) {
        $sort = [];
        if (!empty($params['sortStrategy']) && $params['sortDirection'] !== 'none') {
            $direction = $params['sortDirection'] ?? 'desc'; // Default to descending
            switch ($params['sortStrategy']) {
                case 'publish_date':
                    $sort = ['publish_date' => ['order' => $direction]];
                    break;
                case 'update_date':
                    $sort = ['updated_at' => ['order' => $direction]];
                    break;
                case 'recommended':
                    // 'recommended' is a score or similar
                    $sort = ['recommendation_score' => ['order' => $direction]];
                    break;
            }
        }
        return $sort;
    }

    /**
     * Creates an index with mappings tailored for the 'articles' index.
     */
    public function createArticleIndex() {
        $params = [
            'index' => 'quanthub-articles',
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 1
                ],
                'mappings' => [
                    'properties' => [
                        'author' => [
                            'type' => 'object',  // Defining 'author' as an object
                            'properties' => [
                                'id' => [
                                    'type' => 'keyword'
                                ],
                                'username' => [
                                    'type' => 'text'
                                ],
                                'email' => [
                                    'type' => 'keyword'
                                ],
                                'role' => [
                                    'type' => 'keyword',
                                    'index' => false
                                ]
                            ]
                        ],
                        'title' => [
                            'type' => 'text'
                        ],
                        'sub_title' => [
                            'type' => 'text'
                        ],
                        'content' => [
                            'type' => 'text'
                        ],
                        'category' => [
                            'type' => 'keyword'
                        ],
                        'tags' => [
                            'type' => 'keyword'
                        ],
                        'type' => [
                            'type' => 'keyword',
                        ],
                        'status' => [
                            'type' => 'keyword',
                        ],
                        'publish_date' => [
                            'type' => 'date',
                            'format' => 'strict_date_optional_time||epoch_millis'
                        ],
                        'cover_image_link' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'attachment_link' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'created_by' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'updated_by' => [
                            'type' => 'keyword',
                            'index' => false
                        ]
                    ]
                ]
            ]
        ];

        // Delete the index if it already exists
        if ($this->client->indices()->exists(['index' => 'quanthub-articles'])) {
            $this->client->indices()->delete(['index' => 'quanthub-articles']);
        }

        // Create the new index with the defined settings and mappings
        $this->client->indices()->create($params);
    }

    /**
     * delete article document by id
     */
    public function deleteArticleById($index, $id) {
        try {
            $response = $this->client->delete([
                'index' => $index,
                'id' => $id
            ]);
            return $response;  // You might want to return a more user-friendly message or result
        } catch (\Exception $e) {
            // Handle other possible exceptions
            return ['error' => 'Error deleting document', 'message' => $e->getMessage()];
        }
    }

}

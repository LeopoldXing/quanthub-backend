<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Exception;
use Http\Promise\Promise;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    protected Client $client;

    /**
     * @throws AuthenticationException
     */
    public function __construct() {
        $hosts = config('services.elasticsearch.hosts');  // Ensure this returns a string
        $this->client = ClientBuilder::create()->setHosts([$hosts])->build();
    }

    /**
     * search article according to conditions
     *
     * @param $params
     * @return array
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function conditionalSearch($params): array {
        $query = [
            'bool' => [
                'must' => [],
                'filter' => []
            ]
        ];

        // Keyword search
        if (!empty($params['keyword']) && strlen($params['keyword']) > 0) {
            $query['bool']['must'][] = [
                'multi_match' => [
                    'query' => $params['keyword'],
                    'fields' => ['title^3', 'sub_title^2', 'content', 'author.username', 'tags']
                ]
            ];
        }

        // Category filter
        if (!empty($params['categoryList'])) {
            $query['bool']['filter'][] = [
                'terms' => [
                    'category.keyword' => $params['categoryList']
                ]
            ];
        }

        // Tag filter
        if (!empty($params['tagList'])) {
            $query['bool']['filter'][] = [
                'terms' => [
                    'tags.keyword' => $params['tagList']
                ]
            ];
        }

        // Content type filter
        if (!empty($params['type']) && $params['type'] !== 'all') {
            $query['bool']['filter'][] = [
                'term' => [
                    'type' => $params['type']
                ]
            ];
        }

        // Draft filter
        if (isset($params['isDraft'])) {
            $query['bool']['filter'][] = [
                'term' => [
                    'is_draft' => $params['isDraft']
                ]
            ];
        }

        // Determine the indices to search in
        $indices = [];
        if (isset($params['type'])) {
            if ($params['type'] === 'article') {
                $indices[] = 'quanthub-articles';
            } elseif ($params['type'] === 'announcement') {
                $indices[] = 'quanthub-announcements';
            } elseif ($params['type'] === 'all') {
                $indices = ['quanthub-articles', 'quanthub-announcements'];
            }
        }

        // Build the final query array
        $searchParams = [
            'index' => implode(',', $indices),
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

        Log::info("查询结果：", ['result' => $results]);
        return $results;
    }


    /**
     * construct search param of sorting
     *
     * @param $params
     * @return array|array[]
     */
    private function buildSortParams($params): array {
        $sort = [];
        if (!empty($params['sortStrategy']) && $params['sortDirection'] !== 'none') {
            $direction = $params['sortDirection'] ?? 'desc';
            switch ($params['sortStrategy']) {
                case 'publish_date':
                    $sort = ['publish_date' => ['order' => $direction]];
                    break;
                case 'update_date':
                    $sort = ['updated_at' => ['order' => $direction]];
                    break;
                case 'recommended':
                    // 'recommended' is the sorting mode of score
                    $sort = ['recommendation_score' => ['order' => $direction]];
                    break;
            }
        }
        return $sort;
    }

    /**
     * construct the param for articles creation
     *
     * @param $articleData
     * @return array
     */
    private function constructParamsForCreateArticles($articleData): array {
        return [
            'index' => $articleData['index'],
            'id' => $articleData['id'],
            'body' => [
                'author' => $articleData['author'],
                'title' => $articleData['title'],
                'sub_title' => $articleData['sub_title'],
                'content' => $articleData['content'],
                'type' => $articleData['type'],
                'is_draft' => $articleData['is_draft'],
                'status' => $articleData['status'],
                'category' => $articleData['category'],
                'tags' => $articleData['tags'],
                'publish_date' => $articleData['publish_date'],
                'cover_image_link' => $articleData['cover_image_link'],
                'attachment_link' => $articleData['attachment_link'],
                'attachment_name' => $articleData['attachment_name'],
                'created_by' => $articleData['created_by'],
                'updated_by' => $articleData['updated_by'],
                'updated_at' => now()->toIso8601String()
            ]
        ];
    }

    /**
     * construct the param for articles creation
     *
     * @param $articleData
     * @return array
     */
    private function constructParamsForUpdateArticles($articleData): array {
        return [
            'index' => $articleData['index'],
            'id' => $articleData['id'],
            'body' => [
                'doc' => array_filter([
                    'author' => $articleData['author'],
                    'title' => $articleData['title'],
                    'sub_title' => $articleData['sub_title'],
                    'content' => $articleData['content'],
                    'type' => $articleData['type'],
                    'is_draft' => $articleData['is_draft'],
                    'status' => $articleData['status'],
                    'category' => $articleData['category'],
                    'tags' => $articleData['tags'],
                    'publish_date' => $articleData['publish_date'],
                    'cover_image_link' => $articleData['cover_image_link'] ?? null,
                    'attachment_link' => $articleData['attachment_link'] ?? null,
                    'attachment_name' => $articleData['attachment_name'] ?? null,
                    'created_by' => $articleData['created_by'],
                    'updated_by' => $articleData['updated_by'],
                    'updated_at' => now()->toIso8601String()
                ], function ($value) {
                    return !is_null($value);
                })
            ]
        ];
    }

    /**
     * create new document in "quanthub-articles" index
     *
     * @param $articleData
     * @return Elasticsearch|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function createArticleDoc($articleData): Elasticsearch|Promise {
        Log::info("Indexing article with ID: {$articleData['id']}");
        Log::info("Author data: " . json_encode($articleData['author']));
        return $this->client->index($this->constructParamsForCreateArticles($articleData));
    }

    /**
     * update existing document in "quanthub-articles" index
     *
     * @param $articleData
     * @return Elasticsearch|Promise
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function updateArticleDoc($articleData): Elasticsearch|Promise {
        return $this->client->update($this->constructParamsForUpdateArticles($articleData));
    }

    /**
     * delete document in "quanthub-articles" index
     *
     * @param $index
     * @param $id
     * @return array|Elasticsearch|Promise
     */
    public function deleteArticleById($index, $id): Elasticsearch|Promise|array {
        try {
            return $this->client->delete([
                'index' => $index,
                'id' => $id
            ]);
        } catch (Exception $e) {
            return ['error' => 'Error deleting document', 'message' => $e->getMessage()];
        }
    }

    /**
     * create mapping for index "quanthub-articles"
     *
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function createArticleIndex(): void {
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
                            'type' => 'object',
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
                            'type' => 'keyword'
                        ],
                        'is_draft' => [
                            'type' => 'boolean'
                        ],
                        'status' => [
                            'type' => 'keyword'
                        ],
                        'cover_image_link' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'attachment_link' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'attachment_name' => [
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
                        ],
                        'publish_date' => [
                            'type' => 'date',
                            'format' => 'strict_date_optional_time||epoch_millis'
                        ],
                        'updated_at' => [
                            'type' => 'date',
                            'format' => 'strict_date_optional_time||epoch_millis'
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
     * create mapping for index "quanthub-announcements"
     *
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function createAnnouncementIndex(): void {
        $params = [
            'index' => 'quanthub-announcements',
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 1
                ],
                'mappings' => [
                    'properties' => [
                        'author' => [
                            'type' => 'object',
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
                        'is_draft' => [
                            'type' => 'boolean'
                        ],
                        'status' => [
                            'type' => 'keyword',
                        ],
                        'cover_image_link' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'attachment_link' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'attachment_name' => [
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
                        ],
                        'publish_date' => [
                            'type' => 'date',
                            'format' => 'strict_date_optional_time||epoch_millis'
                        ],
                        'updated_at' => [
                            'type' => 'date',
                            'format' => 'strict_date_optional_time||epoch_millis'
                        ]
                    ]
                ]
            ]
        ];

        // Delete the index if it already exists
        if ($this->client->indices()->exists(['index' => 'quanthub-announcements'])) {
            $this->client->indices()->delete(['index' => 'quanthub-announcements']);
        }

        // Create the new index with the defined settings and mappings
        $this->client->indices()->create($params);
    }


    /**
     * create mapping for index "quanthub-logs"
     *
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    /*public function createLogIndex(): void {
        $params = [
            'index' => 'quanthub-logs',
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
                        'cover_image_link' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'attachment_link' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'attachment_name' => [
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
                        ],
                        'publish_date' => [
                            'type' => 'date',
                            'format' => 'strict_date_optional_time||epoch_millis'
                        ],
                        'updated_at' => [
                            'type' => 'date',
                            'format' => 'strict_date_optional_time||epoch_millis'
                        ]
                    ]
                ]
            ]
        ];

        // Delete the index if it already exists
        if ($this->client->indices()->exists(['index' => 'quanthub-logs'])) {
            $this->client->indices()->delete(['index' => 'quanthub-logs']);
        }

        // Create the new index with the defined settings and mappings
        $this->client->indices()->create($params);
    }*/
}

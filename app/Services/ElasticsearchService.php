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

    /**
     * search article according to conditions
     *
     * @param $params
     * @return array
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function conditionalSearch($params) {
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
        if (!empty($params['type'])) {
            $query['bool']['filter'][] = [
                'term' => [
                    'type' => $params['type']
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
     * construct search param of sorting
     *
     * @param $params
     * @return array|array[]
     */
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
    private function constructParamsForCreateArticles($articleData) {
        return [
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
        ];
    }

    /**
     * construct the param for articles creation
     *
     * @param $articleData
     * @return array
     */
    private function constructParamsForUpdateArticles($articleData) {
        return [
            'index' => 'quanthub-articles',
            'id' => $articleData['id'],
            'body' => [
                'doc' => [
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
            ]
        ];
    }

    /**
     * create new document in "quanthub-articles" index
     *
     * @param $articleData
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function createArticleDoc($articleData) {
        Log::info("Indexing article with ID: {$articleData['id']}");
        Log::info("Author data: " . json_encode($articleData['author']));
        return $this->client->index($this->constructParamsForCreateArticles($articleData));
    }

    /**
     * update existing document in "quanthub-articles" index
     *
     * @param $articleData
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function updateArticleDoc($articleData) {
        return $this->client->update($this->constructParamsForUpdateArticles($articleData));
    }

    /**
     * delete document in "quanthub-articles" index
     *
     * @param $index
     * @param $id
     * @return array|\Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
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

    /**
     * create mapping for index "quanthub-articles"
     *
     * @return void
     * @throws \Elastic\Elasticsearch\Exception\ClientResponseException
     * @throws \Elastic\Elasticsearch\Exception\MissingParameterException
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
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
}

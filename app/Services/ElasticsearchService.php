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
                        'type' => [
                            'type' => 'keyword',
                            'index' => false
                        ],
                        'status' => [
                            'type' => 'keyword',
                            'index' => false
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
                'publish_date' => $articleData['publish_date'],
                'cover_image_link' => $articleData['cover_image_link'],
                'attachment_link' => $articleData['attachment_link'],
                'created_by' => $articleData['created_by'],
                'updated_by' => $articleData['updated_by']
            ]
        ]);
    }
}

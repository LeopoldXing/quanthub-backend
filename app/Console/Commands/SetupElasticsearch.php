<?php

namespace App\Console\Commands;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Console\Command;
use App\Services\ElasticsearchService;

class SetupElasticsearch extends Command
{
    protected $signature = 'setup:elasticsearch';
    protected $description = 'Setup Elasticsearch indices with proper mappings';

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function handle(): void {
        $elasticsearchService = new ElasticsearchService();
        $elasticsearchService->createArticleIndex();
        $this->info('Elasticsearch indices have been set up successfully.');
    }
}

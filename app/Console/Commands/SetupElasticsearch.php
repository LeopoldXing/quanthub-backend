<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ElasticsearchService;

class SetupElasticsearch extends Command
{
    protected $signature = 'setup:elasticsearch';
    protected $description = 'Setup Elasticsearch indices with proper mappings';

    public function handle() {
        $elasticsearchService = new ElasticsearchService();
        $elasticsearchService->createArticleIndex();
        $this->info('Elasticsearch indices have been set up successfully.');
    }
}

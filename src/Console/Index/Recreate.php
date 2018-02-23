<?php

namespace Sleimanx2\Plastic\Console\Index;

use Carbon\Carbon;
use Elasticsearch\Client;
use Illuminate\Console\Command;
use Sleimanx2\Plastic\Facades\Plastic;

class Recreate extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'plastic:recreate
                            {--database= : Database connection to use instead of the default one }
                            {--index= : Index to populate instead of the default one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreates an index';

    /**
     * Gets the client.
     *
     * @return Client
     */
    public function client()
    {
        return Plastic::getClient();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Gets the default index name
        $index = $this->index();

        // Recreates the index
        if ($this->recreateIndex($index)) {
            $this->info('Index successfully recreated.');
        } else {
            $this->error('Failed to recreate index.');
        }
    }

    /**
     * Gets the index to recreate.
     *
     * @return array|string
     */
    protected function index()
    {
        return $this->option('index') ?? Plastic::getDefaultIndex();
    }

    /**
     * Checks if index already exists
     *
     * @param $index
     * @return bool
     */
    protected function indexExists($index)
    {
        return $this->client()->indices()->exists(['index' => $index]);
    }

    /**
     * Recreates the given index with an alias (except if an index without alias already exists).
     *
     * @param string $index
     * @return bool
     */
    protected function recreateIndex($index)
    {
        $this->line('Recreating index « '.$index.' » ...');

        // Checks if the index already exists
        $indexAlreadyExists = $this->indexExists($index);

        // Gets the indexes that points on the current index
        $aliasedIndexes = $indexAlreadyExists ? $this->findIndexesByAlias($index) : [];
        if (!$indexAlreadyExists) {
            $this->line('Index does not exist yet.');
        } elseif (empty($aliasedIndexes)) {
            $this->line('Index already exists and is not an alias.');
        } else {
            $this->line('Index already exists as an alias of « '.implode(', ', $aliasedIndexes).' ».');
        }

        // Deletes the current index if exists and not aliased
        if ($indexAlreadyExists && empty($aliasedIndexes)) {
            $this->deleteIndexes([$index]);
        }

        // Generates a unique name for the new index
        $newIndex = $this->uniqueIndexName($index);

        // Creates the index
        $this->createIndex($newIndex, $this->indexConfig($index));

        // Populates the index
        if (!$this->populateIndex($newIndex, $index)) {

            // Deletes the new index as the populate has failed
            $this->deleteIndexes([$newIndex]);

            return false;
        }


        // Deletes the new index as the populate has failed
        $this->deleteIndexes([$newIndex]);
        return true;

        // Removes the alias from the old indexes
        if (!empty($aliasedIndexes)) {
            $this->removeAliasOnIndexes($index, $aliasedIndexes);
        }

        // Sets the alias on the new index
        $this->addAliasOnIndexes($index, [$newIndex]);

        // Deletes the old indexes
        if (!empty($aliasedIndexes)) {
            $this->deleteIndexes($aliasedIndexes);
        }

        $this->refreshIndexes([$newIndex]);

        return true;
    }

    /**
     * Populates the index.
     *
     * @param string $index
     * @param string $indexAlias
     * @return int
     */
    protected function populateIndex($index, $indexAlias)
    {
        $statusCode = $this->call('plastic:populate', [
            '--index' => $index,
            '--index-alias' => $indexAlias,
            '--mappings' => true,
            '--database' => $this->option('database'),
        ]);

        dump($statusCode);

        return $statusCode === 0;
    }

    /**
     * Creates an index.
     *
     * @param string $index The name of the index
     * @param array $config The configuration of the index
     */
    protected function createIndex($index, array $config)
    {
        $this->line('Creating index « '.$index.' » ...');

        $this->client()->indices()->create([
            'index' => $index,
            'body' => $config,
        ]);
    }

    /**
     * Removes an alias from the indexes.
     *
     * @param $alias
     * @param $indexes
     */
    protected function removeAliasOnIndexes($alias, $indexes)
    {
        $this->line(count($indexes) > 1
            ? 'Removing alias from indexes « '.implode(' », « ', $indexes).' » ...'
            : 'Removing alias from index « '.reset($indexes).' » ...'
        );

        try {
            $this->client()->indices()->updateAliases([
                'body' => [
                    'actions' => [
                        [
                            'remove' => [
                                'indices' => $indexes,
                                'alias' => $alias,
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $this->warn($this->readableException($e));
        }
    }

    /**
     * Adds an alias to the indexes.
     *
     * @param $alias
     * @param $indexes
     */
    protected function addAliasOnIndexes($alias, $indexes)
    {
        $this->line(count($indexes) > 1
            ? 'Adding alias to indexes « '.implode(' », « ', $indexes).' » ...'
            : 'Adding alias to index « '.reset($indexes).' » ...'
        );

        try {
            $this->client()->indices()->updateAliases([
                'body' => [
                    'actions' => [
                        [
                            'add' => [
                                'indices' => $indexes,
                                'alias' => $alias,
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $this->warn($this->readableException($e));
        }
    }

    /**
     * Deletes the given indexes.
     *
     * @param array $indexes
     */
    protected function deleteIndexes(array $indexes)
    {
        $this->line(count($indexes) > 1
            ? 'Deleting indexes « '.implode(' », « ', $indexes).' »'
            : 'Deleting index « '.reset($indexes).' »'
        );

        try {
            $this->client()->indices()->delete(['index' => $indexes]);
        } catch (\Exception $e) {
            $this->warn($this->readableException($e));
        }
    }

    /**
     * Refreshes the indexes.
     *
     * @param $indexes
     */
    protected function refreshIndexes($indexes)
    {
        $this->line(count($indexes) > 1
            ? 'Refreshing indexes « '.implode(' », « ', $indexes).' » ...'
            : 'Refreshing index « '.reset($indexes).' » ...'
        );

        $this->client()->indices()->refresh(['index' => $indexes]);
    }

    /**
     * Gets the models to index for the given index.
     *
     * @param $index
     * @return array
     */
    protected function models($index)
    {
        return collect(config('plastic.populate.models'))->get($index, []);
    }
    /**
     * Gets the chunk size.
     *
     * @return int
     */
    protected function chunkSize()
    {
        return (int) config('plastic.populate.chunk_size');
    }

    /**
     * Finds all the indexes linked to the specified alias.
     *
     * @param string $alias
     * @return array
     */
    protected function findIndexesByAlias($alias)
    {
        $indexes = [];

        // Searches for indices with the specified alias
        $aliases = $this->client()->indices()->getAliases();
        foreach ($aliases as $index => $aliasMapping) {
            if (array_key_exists($alias, $aliasMapping['aliases'])) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    /**
     * Gets the configuration of the index.
     *
     * @param $index
     * @return mixed
     */
    protected function indexConfig($index)
    {
        return array_get(config('plastic.indexes', []), $index);
    }

    /**
     * Generates a unique index name with the given prefix.
     *
     * @param string $prefix
     * @return string
     */
    protected function uniqueIndexName($prefix = 'default')
    {
        return $prefix.'_'.Carbon::now()->format('Y-m-d_H-i-s');
    }

    /**
     * If the exception message is a json string from elastic search, returns it
     * as a human readable string (basically, prettified json).
     * Otherwise, it just returns $e->getMessage().
     *
     * @param \Exception $e
     * @return string
     */
    protected function readableException(\Exception $e)
    {
        $message = $e->getMessage();

        $messageJson = json_decode($message, true);
        if ($messageJson !== null && json_last_error() === JSON_ERROR_NONE) {
            return json_encode($messageJson, JSON_PRETTY_PRINT);
        }

        return $message;
    }
}

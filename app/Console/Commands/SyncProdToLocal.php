<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class SyncProdToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync-prod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all data from Supabase Production to Local MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Setting up Production connection (Supabase)...");

        // Parse commented out credentials from .env
        $env = file_get_contents(base_path('.env'));
        preg_match('/# DB_HOST=(.*)/', $env, $host);
        preg_match('/# DB_PORT=(.*)/', $env, $port);
        preg_match('/# DB_DATABASE=(.*)/', $env, $database);
        preg_match('/# DB_USERNAME=(.*)/', $env, $username);
        preg_match('/# DB_PASSWORD=(.*)/', $env, $password);

        if (empty($host[1]) || empty($password[1])) {
            $this->error("Could not find Supabase credentials commented out in .env!");
            return;
        }

        Config::set('database.connections.prod', [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => trim($host[1]),
            'port' => trim($port[1]),
            'database' => trim($database[1]),
            'username' => trim($username[1]),
            'password' => trim($password[1]),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);

        try {
            DB::connection('prod')->getPdo();
            $this->info("Connected to Production PostgreSQL successfully!");
        } catch (\Exception $e) {
            $this->error("Failed to connect to Production: " . $e->getMessage());
            return;
        }

        // List of tables to sync (order matters slightly, but we disable FK checks anyway)
        $tables = [
            'users',
            'categories',
            'products',
            'product_images',
            'product_similarities',
            'product_combos',
            'cart_items',
            'orders',
            'order_items',
            'reviews',
            'inventory_logs',
            'vouchers',
            'contact_messages',
            'chat_conversations',
            'chat_messages',
        ];

        // Disable foreign key checks for local MySQL
        Schema::connection('mysql')->disableForeignKeyConstraints();

        foreach ($tables as $table) {
            $this->info("Syncing table: {$table} ...");

            // Truncate local table
            DB::connection('mysql')->table($table)->truncate();

            // Fetch from production in chunks to avoid memory issues
            DB::connection('prod')->table($table)->orderBy('id')->chunk(500, function ($rows) use ($table) {
                $data = $rows->map(function ($row) {
                    return (array) $row;
                })->toArray();
                
                DB::connection('mysql')->table($table)->insert($data);
            });
            
            $count = DB::connection('mysql')->table($table)->count();
            $this->line(" -> {$count} rows synced.");
        }

        // Enable foreign key checks for local MySQL
        Schema::connection('mysql')->enableForeignKeyConstraints();

        $this->info("Database sync completed successfully!");
    }
}

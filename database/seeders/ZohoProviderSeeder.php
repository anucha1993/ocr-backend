<?php

namespace Database\Seeders;

use App\Models\ApiEndpoint;
use App\Models\ApiProvider;
use Illuminate\Database\Seeder;

class ZohoProviderSeeder extends Seeder
{
    public function run(): void
    {
        $provider = ApiProvider::updateOrCreate(
            ['slug' => 'zoho-crm'],
            [
                'name' => 'Zoho CRM',
                'base_url' => 'https://www.zohoapis.com',
                'token_url' => 'https://accounts.zoho.com/oauth/v2/token',
                'is_active' => true,
            ]
        );

        $endpoints = [
            [
                'name' => 'Get Leads',
                'method' => 'GET',
                'endpoint' => '/crm/v2/Foreign_Data',
                'description' => 'Retrieve all Foreign_Data records',
            ],
            [
                'name' => 'Get Lead By ID',
                'method' => 'GET',
                'endpoint' => '/crm/v2/Foreign_Data/{id}',
                'description' => 'Retrieve a specific Foreign_Data record',
            ],
            [
                'name' => 'Create Lead',
                'method' => 'POST',
                'endpoint' => '/crm/v2/Foreign_Data',
                'description' => 'Create new Foreign_Data record',
                'default_body' => ['data' => [new \stdClass()]],
            ],
            [
                'name' => 'Update Lead',
                'method' => 'PUT',
                'endpoint' => '/crm/v2/Foreign_Data/{id}',
                'description' => 'Update existing Foreign_Data record',
                'default_body' => ['data' => [new \stdClass()]],
            ],
            [
                'name' => 'Delete Lead',
                'method' => 'DELETE',
                'endpoint' => '/crm/v2/Foreign_Data?ids={id}',
                'description' => 'Delete Foreign_Data record by ID',
            ],
            [
                'name' => 'Get Current User',
                'method' => 'GET',
                'endpoint' => '/crm/v2/users?type=CurrentUser',
                'description' => 'Get authenticated user info (for testing connection)',
            ],
        ];

        foreach ($endpoints as $ep) {
            ApiEndpoint::updateOrCreate(
                ['provider_id' => $provider->id, 'name' => $ep['name']],
                array_merge($ep, [
                    'provider_id' => $provider->id,
                    'is_active' => true,
                ])
            );
        }
    }
}

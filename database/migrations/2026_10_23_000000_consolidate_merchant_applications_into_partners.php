<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const APPLICATION_SOURCE = 'merchant_registration';

    public function up(): void
    {
        if (! Schema::hasTable('partners')) {
            return;
        }

        $this->allowPartnersWithoutUserAccounts();

        Schema::table('partners', function (Blueprint $table) {
            if (! Schema::hasColumn('partners', 'application_source')) {
                $table->string('application_source', 40)->nullable()->index();
            }

            if (! Schema::hasColumn('partners', 'recruitment_status')) {
                $table->string('recruitment_status', 20)->nullable()->index();
            }

            if (! Schema::hasColumn('partners', 'recruitment_review_message')) {
                $table->text('recruitment_review_message')->nullable();
            }

            if (! Schema::hasColumn('partners', 'recruitment_submitted_at')) {
                $table->timestamp('recruitment_submitted_at')->nullable()->index();
            }

            if (! Schema::hasColumn('partners', 'owner_name')) {
                $table->string('owner_name')->nullable();
            }

            if (! Schema::hasColumn('partners', 'cuisine')) {
                $table->string('cuisine', 120)->nullable();
            }

            if (! Schema::hasColumn('partners', 'branch_count')) {
                $table->unsignedSmallInteger('branch_count')->default(1);
            }

            if (! Schema::hasColumn('partners', 'services')) {
                $table->json('services')->nullable();
            }

            if (! Schema::hasColumn('partners', 'website')) {
                $table->string('website')->nullable();
            }

            if (! Schema::hasColumn('partners', 'recruitment_reviewed_by')) {
                $table->unsignedBigInteger('recruitment_reviewed_by')->nullable()->index();
            }

            if (! Schema::hasColumn('partners', 'recruitment_reviewed_at')) {
                $table->timestamp('recruitment_reviewed_at')->nullable();
            }
        });

        if (! Schema::hasTable('merchant_applications')) {
            return;
        }

        DB::table('merchant_applications')
            ->orderBy('id')
            ->each(function (object $application) {
                $attributes = [
                    'agent_id' => $application->agent_id ?? null,
                    'restaurant_name' => $application->business_name,
                    'registered_business_name' => $application->registered_business_name,
                    'owner_name' => $application->owner_name,
                    'email' => $application->email,
                    'mobile' => $application->mobile,
                    'telephone' => $application->telephone,
                    'address' => $application->address,
                    'city' => $application->city,
                    'business_structure' => $application->business_structure,
                    'cuisine' => $application->cuisine,
                    'branch_count' => $application->branch_count,
                    'services' => $application->services,
                    'website' => $application->website,
                    'description' => $application->business_description,
                    'percentage' => $application->commission_percentage,
                    'application_source' => self::APPLICATION_SOURCE,
                    'recruitment_status' => $application->status,
                    'recruitment_review_message' => $application->review_message,
                    'recruitment_reviewed_by' => $application->reviewed_by,
                    'recruitment_reviewed_at' => $application->reviewed_at,
                    'recruitment_submitted_at' => $application->created_at,
                    'updated_at' => $application->updated_at,
                ];

                $partner = DB::table('partners')->where('email', $application->email)->first();

                if ($partner) {
                    $updates = collect($attributes)
                        ->except('updated_at')
                        ->filter(function ($value, string $field) use ($partner) {
                            if (str_starts_with($field, 'recruitment_') || $field === 'application_source') {
                                return true;
                            }

                            return blank($partner->$field ?? null) && filled($value);
                        })
                        ->all();

                    DB::table('partners')->where('id', $partner->id)->update($updates);

                    return;
                }

                $slugBase = Str::slug($application->business_name) ?: 'merchant';
                $slug = $slugBase;
                $suffix = 2;

                while (DB::table('partners')->where('slug', $slug)->exists()) {
                    $slug = $slugBase.'-'.$suffix++;
                }

                DB::table('partners')->insert([
                    ...$attributes,
                    'user_id' => null,
                    'slug' => $slug,
                    'search_string' => Str::lower(trim($application->business_name.' '.$application->city)),
                    'active' => false,
                    'account_type_id' => 1,
                    'addup' => true,
                    'created_at' => $application->created_at,
                ]);
            });

        Schema::drop('merchant_applications');
    }

    public function down(): void
    {
        if (! Schema::hasTable('merchant_applications')) {
            Schema::create('merchant_applications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id')->nullable()->index();
                $table->string('business_name');
                $table->string('registered_business_name');
                $table->string('owner_name');
                $table->string('email')->unique();
                $table->string('mobile', 30);
                $table->string('telephone', 30)->nullable();
                $table->text('address');
                $table->string('city', 120);
                $table->string('business_structure', 30);
                $table->string('cuisine', 120)->nullable();
                $table->unsignedSmallInteger('branch_count')->default(1);
                $table->json('services');
                $table->string('website')->nullable();
                $table->text('business_description')->nullable();
                $table->decimal('commission_percentage', 5, 2);
                $table->string('status', 20)->default('pending')->index();
                $table->text('review_message')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('partners')) {
            DB::table('partners')
                ->where('application_source', self::APPLICATION_SOURCE)
                ->orderBy('id')
                ->each(function (object $partner) {
                    DB::table('merchant_applications')->insert([
                        'agent_id' => $partner->agent_id,
                        'business_name' => $partner->restaurant_name,
                        'registered_business_name' => $partner->registered_business_name,
                        'owner_name' => $partner->owner_name,
                        'email' => $partner->email,
                        'mobile' => $partner->mobile,
                        'telephone' => $partner->telephone,
                        'address' => $partner->address,
                        'city' => $partner->city,
                        'business_structure' => $partner->business_structure,
                        'cuisine' => $partner->cuisine,
                        'branch_count' => $partner->branch_count,
                        'services' => $partner->services,
                        'website' => $partner->website,
                        'business_description' => $partner->description,
                        'commission_percentage' => $partner->percentage,
                        'status' => $partner->recruitment_status,
                        'review_message' => $partner->recruitment_review_message,
                        'reviewed_by' => $partner->recruitment_reviewed_by,
                        'reviewed_at' => $partner->recruitment_reviewed_at,
                        'created_at' => $partner->recruitment_submitted_at ?? $partner->created_at,
                        'updated_at' => $partner->updated_at,
                    ]);
                });

            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn([
                    'application_source',
                    'recruitment_status',
                    'recruitment_review_message',
                    'recruitment_submitted_at',
                    'owner_name',
                    'cuisine',
                    'branch_count',
                    'services',
                    'website',
                    'recruitment_reviewed_by',
                    'recruitment_reviewed_at',
                ]);
            });
        }
    }

    private function allowPartnersWithoutUserAccounts(): void
    {
        if (! Schema::hasColumn('partners', 'user_id') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'partners')
            ->where('COLUMN_NAME', 'user_id')
            ->first(['COLUMN_TYPE', 'IS_NULLABLE']);

        if (! $column || $column->IS_NULLABLE === 'YES') {
            return;
        }

        DB::statement("ALTER TABLE `partners` MODIFY `user_id` {$column->COLUMN_TYPE} NULL");
    }
};

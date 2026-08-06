<?php

declare(strict_types=1);

namespace Tests\Unit\Quality;

use MigrationRollbackPortabilityAuditor;
use MigrationRollbackPortabilityFinding;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/scripts/quality/audit-migration-rollback-portability.php';

final class MigrationRollbackPortabilityAuditTest extends TestCase
{
    private string $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = sys_get_temp_dir().'/mvhab-migration-rollback-'.bin2hex(random_bytes(6));
        mkdir($this->repository.'/database/migrations', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->repository);
        parent::tearDown();
    }

    public function test_it_detects_a_foreign_key_drop_by_literal_constraint_name(): void
    {
        $this->writeMigration('2026_01_01_000000_unsafe.php', <<<'PHP'
<?php

$table->dropForeign('unsafe_constraint_fk');
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertCount(1, $result->findings);
        $this->assertSame('unsafe_constraint_fk', $result->findings[0]->reference);
        $this->assertSame(3, $result->findings[0]->line);
    }

    public function test_it_detects_an_unreviewed_dynamic_foreign_key_drop(): void
    {
        $this->writeMigration('2026_01_01_000000_dynamic.php', <<<'PHP'
<?php

$table->dropForeign($foreignKey);
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertCount(1, $result->findings);
        $this->assertSame('$foreignKey', $result->findings[0]->reference);
    }

    public function test_it_detects_unreviewed_vendor_specific_schema_metadata(): void
    {
        $this->writeMigration('2026_01_01_000000_metadata.php', <<<'PHP'
<?php

DB::table('information_schema.TABLE_CONSTRAINTS')->exists();
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertCount(1, $result->findings);
        $this->assertSame('information_schema.TABLE_CONSTRAINTS', $result->findings[0]->reference);
    }

    public function test_it_detects_an_unscoped_index_repair_helper(): void
    {
        $this->writeMigration('2026_01_01_000000_index_repair.php', <<<'PHP'
<?php

private function ensureMunicipalityIndex(): void
{
    if (Schema::hasIndex('roles', 'roles_municipality_lookup_index')) {
        return;
    }

    Schema::table('roles', function (Blueprint $table): void {
        $table->index('municipality_id', 'roles_municipality_lookup_index');
    });
}
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertCount(1, $result->findings);
        $this->assertSame('ensureMunicipalityIndex', $result->findings[0]->reference);
    }

    public function test_it_detects_an_indexed_column_drop_without_index_removal(): void
    {
        $this->writeMigration('2026_01_01_000000_indexed_column.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->string('status')->index();
            $table->index(['status', 'user_id'], 'records_status_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertSame(
            [
                'records_status_index',
                'records_status_user_idx',
            ],
            array_map(
                static fn (MigrationRollbackPortabilityFinding $finding): string => $finding->reference,
                $result->findings,
            ),
        );
    }

    public function test_it_detects_index_removal_after_a_table_rebuild_operation(): void
    {
        $this->writeMigration('2026_01_01_000000_rebuild_order.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->foreignId('municipality_id')->nullable()->constrained();
            $table->string('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropForeign(['municipality_id']);
            $table->dropIndex('records_status_index');
            $table->dropColumn(['municipality_id', 'status']);
        });
    }
};
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertContains(
            'records::dropIndex',
            array_map(
                static fn (MigrationRollbackPortabilityFinding $finding): string => $finding->reference,
                $result->findings,
            ),
        );
    }

    public function test_it_accepts_index_removal_before_a_table_rebuild_operation(): void
    {
        $this->writeMigration('2026_01_01_000000_safe_rebuild_order.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->foreignId('municipality_id')->nullable()->constrained();
            $table->string('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropIndex('records_status_index');
        });

        Schema::table('records', function (Blueprint $table): void {
            $table->dropForeign(['municipality_id']);
        });

        Schema::table('records', function (Blueprint $table): void {
            $table->dropColumn(['municipality_id', 'status']);
        });
    }
};
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertSame([], $result->findings);
    }

    public function test_it_accepts_index_removal_before_indexed_columns(): void
    {
        $this->writeMigration('2026_01_01_000000_safe_indexed_column.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->string('status')->index();
            $table->index(['status', 'user_id'], 'records_status_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex('records_status_user_idx');
            $table->dropColumn('status');
        });
    }
};
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertSame([], $result->findings);
    }

    public function test_it_accepts_column_based_and_driver_aware_drops(): void
    {
        $this->writeMigration('2026_01_01_000000_safe.php', <<<'PHP'
<?php

$table->dropForeign(['municipality_id']);
$table->dropForeign($driver === 'sqlite' ? ['user_id'] : 'users_user_id_foreign');

private function ensureMunicipalityIndex(): void
{
    if (
        DB::connection()->getDriverName() === 'sqlite'
        || Schema::hasIndex('roles', 'roles_municipality_lookup_index')
    ) {
        return;
    }

    Schema::table('roles', function (Blueprint $table): void {
        $table->index('municipality_id', 'roles_municipality_lookup_index');
    });
}
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();
        $this->assertSame([], $result->findings);
    }

    public function test_it_detects_a_repair_migration_removing_an_earlier_owned_index(): void
    {
        $this->writeMigration('2026_01_01_000000_create_records_index.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'records_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropIndex('records_status_created_idx');
        });
    }
};
PHP);

        $this->writeMigration('2026_01_02_000000_repair_records_index.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'records_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropIndex('records_status_created_idx');
        });
    }
};
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertContains(
            'records::records_status_created_idx',
            array_map(
                static fn (MigrationRollbackPortabilityFinding $finding): string => $finding->reference,
                $result->findings,
            ),
        );
    }

    public function test_it_accepts_a_repair_migration_that_preserves_an_earlier_owned_index(): void
    {
        $this->writeMigration('2026_01_01_000000_create_records_index.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'records_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->dropIndex('records_status_created_idx');
        });
    }
};
PHP);

        $this->writeMigration('2026_01_02_000000_repair_records_index.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'records_status_created_idx');
        });
    }

    public function down(): void
    {
        // Compatibility repair: the canonical owner removes the index.
    }
};
PHP);

        $result = (new MigrationRollbackPortabilityAuditor($this->repository))->audit();

        $this->assertSame([], $result->findings);
    }

    public function test_current_repository_contains_no_unreviewed_rollback_constructs(): void
    {
        $repository = dirname(__DIR__, 3);
        $result = (new MigrationRollbackPortabilityAuditor($repository))->audit();

        $this->assertSame([], array_map(
            static fn (MigrationRollbackPortabilityFinding $finding): string => sprintf(
                '%s:%d [%s] %s',
                $finding->file,
                $finding->line,
                $finding->reference,
                $finding->message,
            ),
            $result->findings,
        ));
    }

    private function writeMigration(string $name, string $contents): void
    {
        file_put_contents($this->repository.'/database/migrations/'.$name, $contents);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());

                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }
}

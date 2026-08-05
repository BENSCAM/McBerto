<?php

namespace Tests\Feature;

use App\Livewire\System\BugHistory;
use App\Models\BugLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class BugHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_bug_history(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get('/system/bugs')
            ->assertForbidden();
    }

    public function test_manager_can_view_bug_history(): void
    {
        $manager = User::factory()->manager()->create();
        BugLog::record(new RuntimeException('Erreur test historique bugs'));

        $this->actingAs($manager)
            ->get('/system/bugs')
            ->assertOk()
            ->assertSee('Historique des bugs')
            ->assertSee('Erreur test historique bugs');
    }

    public function test_exception_can_be_recorded_for_authenticated_user(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager);

        BugLog::record(new RuntimeException('Erreur critique caisse'));

        $this->assertDatabaseHas('bug_logs', [
            'user_id' => $manager->id,
            'exception_class' => RuntimeException::class,
            'message' => 'Erreur critique caisse',
        ]);
    }

    public function test_manager_can_resolve_and_reopen_bug(): void
    {
        $manager = User::factory()->manager()->create();
        BugLog::record(new RuntimeException('Erreur à résoudre'));
        $bug = BugLog::firstOrFail();

        Livewire::actingAs($manager)
            ->test(BugHistory::class)
            ->call('selectBug', $bug->id)
            ->set('resolutionNote', 'Correction déployée')
            ->call('resolveBug', $bug->id);

        $this->assertNotNull($bug->fresh()->resolved_at);
        $this->assertSame($manager->id, $bug->fresh()->resolved_by);

        Livewire::actingAs($manager)
            ->test(BugHistory::class)
            ->call('reopenBug', $bug->id);

        $this->assertNull($bug->fresh()->resolved_at);
    }
}

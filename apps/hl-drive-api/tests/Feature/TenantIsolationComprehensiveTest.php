<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LedgerEntry;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected TenantResolver $resolver;
    protected Tenant $t1, $t2, $t3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(TenantResolver::class);

        $this->t1 = Tenant::factory()->create(['name' => 'T1']);
        $this->t2 = Tenant::factory()->create(['name' => 'T2']);
        $this->t3 = Tenant::factory()->create(['name' => 'T3']);
    }

    protected function tearDown(): void
    {
        $this->resolver->clear();
        parent::tearDown();
    }

    public function test_clients_isolated_by_tenant(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        $c1 = Client::create(['tenant_id' => $this->t1->id, 'name' => 'C1']);
        $c2 = Client::create(['tenant_id' => $this->t1->id, 'name' => 'C2']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $c3 = Client::create(['tenant_id' => $this->t2->id, 'name' => 'C3']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t3->id);
        $c4 = Client::create(['tenant_id' => $this->t3->id, 'name' => 'C4']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $all = Client::all();
        $this->assertCount(2, $all);
        $this->assertTrue($all->pluck('id')->contains($c1->id));
        $this->assertTrue($all->pluck('id')->contains($c2->id));
        $this->assertFalse($all->pluck('id')->contains($c3->id));
        $this->assertFalse($all->pluck('id')->contains($c4->id));

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $all = Client::all();
        $this->assertCount(1, $all);
        $this->assertTrue($all->pluck('id')->contains($c3->id));

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t3->id);
        $all = Client::all();
        $this->assertCount(1, $all);
        $this->assertTrue($all->pluck('id')->contains($c4->id));
    }

    public function test_wallet_relationships_isolated(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        $c1 = Client::create(['tenant_id' => $this->t1->id, 'name' => 'C']);
        $w1 = Wallet::create(['tenant_id' => $this->t1->id, 'client_id' => $c1->id, 'name' => 'W1', 'currency_code' => 'USD']);
        $w2 = Wallet::create(['tenant_id' => $this->t1->id, 'client_id' => $c1->id, 'name' => 'W2', 'currency_code' => 'EUR']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $c2 = Client::create(['tenant_id' => $this->t2->id, 'name' => 'C']);
        $w3 = Wallet::create(['tenant_id' => $this->t2->id, 'client_id' => $c2->id, 'name' => 'W', 'currency_code' => 'GBP']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $loaded = Client::with('wallets')->find($c1->id);
        $this->assertNotNull($loaded);
        $this->assertCount(2, $loaded->wallets);
        $this->assertTrue($loaded->wallets->pluck('id')->contains($w1->id));
        $this->assertTrue($loaded->wallets->pluck('id')->contains($w2->id));
        $this->assertFalse($loaded->wallets->pluck('id')->contains($w3->id));

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $loaded = Client::with('wallets')->find($c2->id);
        $this->assertCount(1, $loaded->wallets);
        $this->assertTrue($loaded->wallets->pluck('id')->contains($w3->id));
    }

    public function test_ledger_entries_isolated(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        $c = Client::create(['tenant_id' => $this->t1->id, 'name' => 'C']);
        $w = Wallet::create(['tenant_id' => $this->t1->id, 'client_id' => $c->id, 'name' => 'W', 'currency_code' => 'USD']);
        $e1 = LedgerEntry::create(['tenant_id' => $this->t1->id, 'wallet_id' => $w->id, 'hours' => 10, 'title' => 'E1']);
        $e2 = LedgerEntry::create(['tenant_id' => $this->t1->id, 'wallet_id' => $w->id, 'hours' => 20, 'title' => 'E2']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $c2 = Client::create(['tenant_id' => $this->t2->id, 'name' => 'C']);
        $w2 = Wallet::create(['tenant_id' => $this->t2->id, 'client_id' => $c2->id, 'name' => 'W', 'currency_code' => 'USD']);
        $e3 = LedgerEntry::create(['tenant_id' => $this->t2->id, 'wallet_id' => $w2->id, 'hours' => 30, 'title' => 'E3']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $all = LedgerEntry::all();
        $this->assertCount(2, $all);
        $this->assertTrue($all->pluck('id')->contains($e1->id));
        $this->assertTrue($all->pluck('id')->contains($e2->id));
        $this->assertFalse($all->pluck('id')->contains($e3->id));

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $all = LedgerEntry::all();
        $this->assertCount(1, $all);
        $this->assertTrue($all->pluck('id')->contains($e3->id));

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $wallet = Wallet::with('entries')->find($w->id);
        $this->assertCount(2, $wallet->entries);
    }

    public function test_where_clauses_respect_scope(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        Client::create(['tenant_id' => $this->t1->id, 'name' => 'Active']);
        Client::create(['tenant_id' => $this->t1->id, 'name' => 'Inactive']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        Client::create(['tenant_id' => $this->t2->id, 'name' => 'Active']);
        Client::create(['tenant_id' => $this->t2->id, 'name' => 'Active']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $active = Client::where('name', 'Active')->get();
        $this->assertCount(1, $active);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $active = Client::where('name', 'Active')->get();
        $this->assertCount(2, $active);
    }

    public function test_update_respects_scope(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        Client::create(['tenant_id' => $this->t1->id, 'name' => 'A']);
        Client::create(['tenant_id' => $this->t1->id, 'name' => 'B']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        Client::create(['tenant_id' => $this->t2->id, 'name' => 'C']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $count = Client::where('name', 'A')->update(['name' => 'Updated']);
        $this->assertEquals(1, $count);

        $all = Client::where('name', 'Updated')->get();
        $this->assertCount(1, $all);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $all = Client::all();
        $this->assertCount(1, $all);
        $this->assertEquals('C', $all->first()->name);
    }

    public function test_delete_respects_scope(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        $c1 = Client::create(['tenant_id' => $this->t1->id, 'name' => 'A']);
        $c2 = Client::create(['tenant_id' => $this->t1->id, 'name' => 'B']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $c3 = Client::create(['tenant_id' => $this->t2->id, 'name' => 'C']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $count = Client::where('name', 'A')->delete();
        $this->assertEquals(1, $count);

        $remaining = Client::all();
        $this->assertCount(1, $remaining);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $all = Client::all();
        $this->assertCount(1, $all);
        $this->assertTrue($all->pluck('id')->contains($c3->id));
    }

    public function test_aggregates_respect_scope(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        $c = Client::create(['tenant_id' => $this->t1->id, 'name' => 'C']);
        $w = Wallet::create(['tenant_id' => $this->t1->id, 'client_id' => $c->id, 'name' => 'W', 'currency_code' => 'USD']);
        LedgerEntry::create(['tenant_id' => $this->t1->id, 'wallet_id' => $w->id, 'hours' => 10, 'title' => 'E1']);
        LedgerEntry::create(['tenant_id' => $this->t1->id, 'wallet_id' => $w->id, 'hours' => 20, 'title' => 'E2']);
        LedgerEntry::create(['tenant_id' => $this->t1->id, 'wallet_id' => $w->id, 'hours' => 30, 'title' => 'E3']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $c2 = Client::create(['tenant_id' => $this->t2->id, 'name' => 'C']);
        $w2 = Wallet::create(['tenant_id' => $this->t2->id, 'client_id' => $c2->id, 'name' => 'W', 'currency_code' => 'USD']);
        LedgerEntry::create(['tenant_id' => $this->t2->id, 'wallet_id' => $w2->id, 'hours' => 100, 'title' => 'E']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $this->assertEquals(3, LedgerEntry::count());
        $this->assertEquals(60, LedgerEntry::sum('hours'));

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $this->assertEquals(1, LedgerEntry::count());
        $this->assertEquals(100, LedgerEntry::sum('hours'));
    }

    public function test_find_respects_scope(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        $c1 = Client::create(['tenant_id' => $this->t1->id, 'name' => 'C']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $c2 = Client::create(['tenant_id' => $this->t2->id, 'name' => 'C']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $result = Client::find($c2->id);
        $this->assertNull($result);

        $result = Client::find($c1->id);
        $this->assertNotNull($result);
    }

    public function test_first_or_create_respects_scope(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        $c1 = Client::create(['tenant_id' => $this->t1->id, 'name' => 'Shared']);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $c2 = Client::firstOrCreate(['name' => 'Shared']);

        $this->assertNotEquals($c1->id, $c2->id);
        $this->assertEquals($this->t2->id, $c2->tenant_id);
    }

    public function test_pagination_respects_scope(): void
    {
        $this->resolver->setTenantId($this->t1->id);
        for ($i = 0; $i < 5; $i++) {
            Client::create(['tenant_id' => $this->t1->id, 'name' => "C$i"]);
        }

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        for ($i = 0; $i < 3; $i++) {
            Client::create(['tenant_id' => $this->t2->id, 'name' => "X$i"]);
        }

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t1->id);
        $page = Client::paginate(2);
        $this->assertEquals(5, $page->total());
        $this->assertCount(2, $page->items());

        $this->resolver->clear();
        $this->resolver->setTenantId($this->t2->id);
        $page = Client::paginate(2);
        $this->assertEquals(3, $page->total());
        $this->assertCount(2, $page->items());
    }
}

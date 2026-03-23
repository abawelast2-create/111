<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeModelTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
    }

    // ─── findByToken ──────────────────────────────────────────────────────────

    public function test_find_by_token_returns_active_employee(): void
    {
        $employee = Employee::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $found = Employee::findByToken($employee->unique_token);

        $this->assertNotNull($found);
        $this->assertEquals($employee->id, $found->id);
    }

    public function test_find_by_token_returns_null_for_inactive(): void
    {
        $employee = Employee::factory()->inactive()->create([
            'branch_id' => $this->branch->id,
        ]);

        $found = Employee::findByToken($employee->unique_token);

        $this->assertNull($found);
    }

    public function test_find_by_token_returns_null_for_wrong_token(): void
    {
        $this->assertNull(Employee::findByToken(str_repeat('z', 64)));
    }

    public function test_find_by_token_returns_null_for_deleted_employee(): void
    {
        $employee = Employee::factory()->deleted()->create(['branch_id' => $this->branch->id]);

        $found = Employee::findByToken($employee->unique_token);

        $this->assertNull($found);
    }

    // ─── findByPin ────────────────────────────────────────────────────────────

    public function test_find_by_pin_returns_active_employee(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id, 'pin' => '7777']);

        $found = Employee::findByPin('7777');

        $this->assertNotNull($found);
        $this->assertEquals($employee->id, $found->id);
    }

    public function test_find_by_pin_returns_null_for_inactive(): void
    {
        $employee = Employee::factory()->inactive()->create(['branch_id' => $this->branch->id, 'pin' => '8888']);

        $this->assertNull(Employee::findByPin('8888'));
    }

    // ─── generateUniqueToken ─────────────────────────────────────────────────

    public function test_generate_unique_token_returns_64_char_hex(): void
    {
        $token = Employee::generateUniqueToken();

        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function test_generated_tokens_are_unique(): void
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $tokens[] = Employee::generateUniqueToken();
        }
        $this->assertEquals(count($tokens), count(array_unique($tokens)));
    }

    // ─── generateUniquePin ───────────────────────────────────────────────────

    public function test_generate_unique_pin_returns_4_digit_string(): void
    {
        $pin = Employee::generateUniquePin();

        $this->assertEquals(4, strlen($pin));
        $this->assertMatchesRegularExpression('/^\d{4}$/', $pin);
    }

    // ─── isPinExpired ────────────────────────────────────────────────────────

    public function test_pin_not_expired_when_no_expiry_date(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id, 'pin_expires_at' => null]);

        $this->assertFalse($employee->isPinExpired());
    }

    public function test_pin_expired_when_expiry_in_past(): void
    {
        $employee = Employee::factory()->create([
            'branch_id'     => $this->branch->id,
            'pin_expires_at'=> now()->subDay(),
        ]);

        $this->assertTrue($employee->isPinExpired());
    }

    public function test_pin_not_expired_when_expiry_in_future(): void
    {
        $employee = Employee::factory()->create([
            'branch_id'     => $this->branch->id,
            'pin_expires_at'=> now()->addDay(),
        ]);

        $this->assertFalse($employee->isPinExpired());
    }

    // ─── العلاقات ────────────────────────────────────────────────────────────

    public function test_employee_belongs_to_branch(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->assertNotNull($employee->branch);
        $this->assertEquals($this->branch->id, $employee->branch->id);
    }

    public function test_employee_has_many_attendances(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $employee->attendances);
    }

    // ─── scopeActive ─────────────────────────────────────────────────────────

    public function test_active_scope_excludes_inactive_employees(): void
    {
        Employee::factory()->count(3)->create(['branch_id' => $this->branch->id, 'is_active' => true]);
        Employee::factory()->count(2)->inactive()->create(['branch_id' => $this->branch->id]);

        $active = Employee::where('is_active', true)->get();
        $this->assertCount(3, $active);
    }

    // ─── unique_token في الـ Factory ──────────────────────────────────────────

    public function test_factory_generates_64_char_token(): void
    {
        $employee = Employee::factory()->create(['branch_id' => $this->branch->id]);

        $this->assertEquals(64, strlen($employee->unique_token));
    }

    public function test_factory_generates_unique_tokens_for_multiple_employees(): void
    {
        $employees = Employee::factory()->count(5)->create(['branch_id' => $this->branch->id]);

        $tokens = $employees->pluck('unique_token')->toArray();
        $this->assertEquals(5, count(array_unique($tokens)));
    }
}

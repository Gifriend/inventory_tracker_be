<?php

namespace Tests\Unit;

use App\Actions\Loan\ApproveLoanAction;
use App\Actions\Loan\CheckInLoanAction;
use App\Actions\Loan\CheckOutLoanAction;
use App\Actions\Loan\CreateLoanAction;
use App\DTOs\Loan\ApproveLoanData;
use App\DTOs\Loan\CheckInLoanData;
use App\DTOs\Loan\CreateLoanData;
use App\Enums\LoanStatus;
use App\Models\Desk;
use App\Models\Loan;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_approve_checkin_checkout_workflow()
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'user']);
        $aslab = User::factory()->create(['role' => 'aslab']);
        $room = Room::create(['name' => 'Lab A']);
        $desk = Desk::create(['room_id' => $room->id, 'desk_number' => 'D1', 'status' => 'available']);

        $createAction = new CreateLoanAction();
        $approveAction = new ApproveLoanAction();
        $checkInAction = new CheckInLoanAction();
        $checkOutAction = new CheckOutLoanAction();

        $loan = $createAction(new CreateLoanData(
            userId: $user->id,
            documentPath: null,
            startTime: now()->addDay()->toImmutable(),
            endTime: now()->addDays(2)->toImmutable(),
        ));

        $this->assertSame(LoanStatus::PENDING->value, $loan->status);

        $loan = $approveAction(new ApproveLoanData(
            loanId: $loan->id,
            roomId: $room->id,
            deskId: $desk->id,
            approverId: $aslab->id,
        ));

        $this->assertSame(LoanStatus::APPROVED->value, $loan->status);

        $loan = $checkInAction(new CheckInLoanData(
            userId: $user->id,
            roomId: $room->id,
            deskId: $desk->id,
        ));

        $this->assertNotNull($loan->check_in_time);

        $loan = $checkOutAction(new \App\DTOs\Loan\CheckOutLoanData(
            userId: $user->id,
        ));

        $this->assertSame(LoanStatus::COMPLETED->value, $loan->status);
    }
}

<?php

namespace Tests\Unit;

use App\Integrations\LandInvest\Export;
use App\Models\PaymentPlan;
use App\Models\PlanClosing;
use App\Services\FinancialBalanceService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class LandInvestExportTest extends TestCase
{
    public function test_statuses_and_exclusions(): void
    {
        foreach ([
            ['active', 100, null, false, 'under_contract'],
            ['paused', 100, null, false, 'paused'],
            ['active', 0, null, false, 'paid_off'],
            ['closed', 0, 'completed', false, 'sale_completed'],
            ['closed', 100, null, false, 'no_active_contract'],
            ['terminated', 0, 'completed', false, 'no_active_contract'],
            ['defaulted', 0, null, false, 'no_active_contract'],
            ['draft', 0, null, false, 'no_active_contract'],
            ['active', 0, 'completed', true, 'no_active_contract'],
        ] as [$status, $balance, $closing, $deleted, $expected]) {
            $balances = $this->createStub(FinancialBalanceService::class);
            $balances->method('contractBalance')->willReturn($balance);
            $plan = new PaymentPlan;
        $plan->setDateFormat('Y-m-d H:i:s');
            $plan->setRawAttributes(['id'=>12, 'status'=>$status, 'deleted_at'=>$deleted ? '2026-01-01 00:00:00' : null]);
            $closingModel = new PlanClosing;
            $closingModel->setRawAttributes(['status'=>$closing]);
            $plan->setRelation('closing', $closing ? $closingModel : null);
            $this->assertSame($expected, (new Export($balances))->status($plan));
        }
    }

    public function test_csv_preserves_duplicate_apns_leading_zeros_and_quoted_names(): void
    {
        $balances = $this->createStub(FinancialBalanceService::class);
        $balances->method('contractBalance')->willReturn(100);
        $plan = new PaymentPlan;
        $plan->setDateFormat('Y-m-d H:i:s');
        $plan->setRawAttributes(['id'=>12, 'status'=>'active', 'apn'=>'001-02-003a', 'property_county'=>'Test County', 'title'=>'Parcel, north acreage']);
        $plan->setRelation('closing', null);
        $client = (object) ['organization_name'=>"Example, \"Quoted\"\nCompany", 'first_name'=>'', 'last_name'=>''];
        $plan->setRelation('memberships', new Collection([(object) ['role'=>'primary', 'client'=>$client]]));
        $csv = (new Export($balances))->csv([$plan, $plan]);
        $stream = fopen('php://temp', 'w+'); fwrite($stream, $csv); rewind($stream);
        $header = fgetcsv($stream, 0, ',', '"', '');
        $row = fgetcsv($stream, 0, ',', '"', '');
        $this->assertSame(['apn','county','contract_status','plan_reference','primary_client_name','property_name'], $header);
        $this->assertSame(['001-02-003a','Test County','under_contract','12',$client->organization_name,'Parcel, north acreage'], $row);
        $this->assertSame($row, fgetcsv($stream, 0, ',', '"', ''));
        $this->assertFalse(fgetcsv($stream, 0, ',', '"', ''));
        fclose($stream);
    }
}

<?php

namespace Will\Tests\Library;

use Will\Library\Calculate;
use PHPUnit\Framework\TestCase;

class CalculateTest extends TestCase
{
    /**
     * Test for Compute Commission
     */
    public function testComputeCommission()
    {
        $calculate = new Calculate();
        $input = [
            "date"=> "2015-01-01",
            "uid" => 4,
            "entity" => "legal",
            "transactionType" => "cash_out",
            "amount" => 1000.00,
            "currency" => "EUR"
        ];

        $expectedResult = 3.0;
        $result = $calculate->computeCommission($input);
        $this->assertEquals($result, $expectedResult);
    }
}

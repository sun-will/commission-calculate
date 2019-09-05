<?php

namespace Will\Library;

use http\Exception;
use Matmar10\Money\Entity\Currency;
use Matmar10\Money\Entity\Money;
use Matmar10\Money\Entity\CurrencyPair;
use Matmar10\Money\Entity\ExchangeRate;
use Will\Config\ExchangeRateConfig;

class Calculate
{
    const NATURAL_ENTITY = 'natural';
    const LEAGL_ENTITY = 'legal';
    const TRANS_TYPE_CASH_IN = 'cash_in';
    const TRANS_TYPE_CASH_OUT = 'cash_out';
    const COMMISSION_LIMIT = 1000;
    const CASH_IN_COMMISSION_RATE = 0.0003;
    const CASH_IN_LIMIT = 5; // In Euro
    const CASH_OUT_COMMISSION_RATE = 0.003;
    const LEGAL_MIN_OPERATION = 0.5;
    const NUMBER_LIMIT_TRANS = 3;

    public $userCount = array();
    public $lastAccess = array();

    /**
     * @param $data
     * @return float|int
     */
    public function computeCommission(array $data)
    {
        return ($data['transactionType'] == SELF::TRANS_TYPE_CASH_OUT) ?
            $this->cashOut($data) : $this->cashIn($data);
    }

    /**
     * @param $data
     * @return float|int
     */
    public function cashIn(array $data)
    {
        // origin currency commission
        $commision = $data['amount'] * SELF::CASH_IN_COMMISSION_RATE;
        $amountInEuro = $commision;
        $limitInOriginalCurrency = SELF::CASH_IN_COMMISSION_RATE;
        
        if ($data['currency'] !== 'EUR') {
            $amountInEuro = $this->convertToEuro(
                [
                    'amount' => $commision,
                    'currency' => $data['currency'],
                ]
            );

            $limitInOriginalCurrency = $this->convertEuroToOriginalCurrency(
                [
                    'amount' => SELF::CASH_IN_LIMIT,
                    'currency' => $data['currency'],
                ]
            );
        }
        return ($amountInEuro > SELF::CASH_IN_LIMIT)? $limitInOriginalCurrency : $commision;
    }

    /**
     * @param $data
     * @return float|int
     */
    public function cashOut(array $data)
    {
        if ($data['entity'] == SELF::NATURAL_ENTITY) {
            return $this->computeNatural($data);
        } else {
            return $this->computeLegal($data);
        }
    }

    /**
     * @param array $data
     * @return float|int
     */
    public function computeNatural(array $data)
    {
        if ($this->checkIfExcessLimit($data)) {
            $euroToOriginalCurrency = SELF::COMMISSION_LIMIT;
            if ($data['currency'] !== 'EUR') {
                $euroToOriginalCurrency = $this->convertEuroToOriginalCurrency(
                    [
                        'currency' => $data['currency'],
                        'amount' => SELF::COMMISSION_LIMIT,
                    ]
                );
            }
            $excess = $data['amount'] - $euroToOriginalCurrency;
            
            return ($excess < 0) ? 0: $excess * SELF::CASH_OUT_COMMISSION_RATE;
        } else {
            return $data['amount'] * SELF::CASH_OUT_COMMISSION_RATE;
        }
    }

    /**
     * @param string $lastAccessDate
     * @param string $transDate
     * @return bool
     */
    private function checkWithinWeek(string $lastAccessDate, string $transDate): bool
    {
        $firstDay = date('Y-m-d', strtotime('last monday', strtotime($lastAccessDate)));
        $lastDay = date('Y-m-d', strtotime('this sunday', strtotime($lastAccessDate)));
        
        if ($transDate > $firstDay && $transDate < $lastDay) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $data
     * @return bool
     */
    private function checkIfExcessLimit($data): bool
    {
        if (!isset($this->userCount[$data['uid']])) {
            $this->userCount[$data['uid']] = 1;
            $this->lastAccess[$data['uid']] = $data['date'];

            return true;
        } elseif ($this->userCount[$data['uid']] < SELF::NUMBER_LIMIT_TRANS) {
            $lastAccessDate = $this->lastAccess[$data['uid']];
            if ($this->checkWithinWeek($lastAccessDate, $data['date'])) {
                $this->userCount[$data['uid']] = $this->userCount[$data['uid']]+1;
                $this->lastAccess[$data['uid']] = $data['date'];

                return true;
            }
        }
        return false;
    }

    /**
     * @param array $data
     * @return float|int
     */
    public function computeLegal(array $data)
    {
        // origin currency commision
        $commision = $data['amount'] * SELF::CASH_OUT_COMMISSION_RATE;
        $amountInEuro = $commision;
        // convert to euro
        if ($data['currency'] !== 'EUR') {
            $amountInEuro = $this->convertToEuro([
                'amount' => $commision,
                'currency' => $data['currency'],
            ]);
        }
        return ($amountInEuro >= SELF::LEGAL_MIN_OPERATION)? $commision : 0;
    }

    /**
     * @param array $data
     * @return float
     */
    public function convertToEuro(array $data): float
    {
        $originalCurrnecy = new Currency($data['currency'], 2, 2);
        $euro = new Currency('EUR', 2, 2);

        $originalAmount = new Money($originalCurrnecy);
        $originalAmount->setAmountFloat($data['amount']);

        $ExchangeRateConfig = new ExchangeRateConfig();

        $originalToEuro = new ExchangeRate(
            $originalCurrnecy,
            $euro,
            $ExchangeRateConfig->getRate($data['currency'])
        );
        $amount = $originalToEuro->convert($originalAmount);

        return $amount->getAmountFloat();
    }

    /**
     * @param $data
     * @return float
     */
    public function convertEuroToOriginalCurrency($data): float
    {
        $originalCurrnecy = new Currency($data['currency'], 2, 2);
        $euro = new Currency('EUR', 2, 2);

        $euroAmount = new Money($euro);
        $euroAmount->setAmountFloat($data['amount']);
        
        $ExchangeRateConfig = new ExchangeRateConfig();
        $euroToOriginalCurrency = new ExchangeRate($euro, $originalCurrnecy, $ExchangeRateConfig->getRate($data['currency']));
        $amount = $euroToOriginalCurrency->convert($euroAmount);

        return $amount->getAmountFloat();
    }
}

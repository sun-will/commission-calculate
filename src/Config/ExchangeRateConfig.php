<?php

namespace Will\Config;

class ExchangeRateConfig
{
    /**
     * @var array
     */
    public $exchangeRates =
        [
            'USD' => 1.1497,
            'JPY' => 129.53,
        ];

    /**
     * @param $currency
     * @return int
     */
    public function getRate($currency): int
    {
        return $this->exchangeRates[$currency];
    }
}

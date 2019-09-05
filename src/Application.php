<?php

namespace Will;

use Will\Library\CsvLoader;
use Will\Library\Calculate;

class Application
{
    
    /**
     * @var resouce
     */
    public $loader;

    /**
    * @var Calculate
    */
    public $calculate;

    /**
     * Application constructor.
     * @param null $filename
     * @param null $headers
     */
    public function __construct($filename = null, $headers = null)
    {
        $inputFile = '../input.csv';
        $this->loader = new CsvLoader($inputFile);
        $this->calculate = new Calculate();
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        foreach ($this->loader->getItems() as $item) {
            $data = $this->prepare($item);
            if (!$data) {
                continue;
            }
            echo $this->calculate->computeCommission($data);
            echo "\r\n";
        }
    }

    /**
     * @param $item
     * @return array
     */
    private function prepare($item)
    {
        $data = array(
            'date' => $item['Date'],
            'uid' => $item['UID'],
            'entity' => $item['Entity'],
            'transactionType' => $item['Transaction Type'],
            'amount' => $item['Amount'],
            'currency' => $item['Currency'],
        );
        return $data;
    }
}

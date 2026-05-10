<?php

namespace Apps\Tms\Packages\Btcextractor;

use System\Base\BasePackage;

class Btcextractor extends BasePackage
{
    //protected $modelToUse = ::class;

    protected $packageName = 'btcextractor';

    public $btcextractor;

    public function getBtcextractorById($id)
    {
        $btcextractor = $this->getById($id);

        if ($btcextractor) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }

    public function addBtcextractor($data)
    {
        //
    }

    public function updateBtcextractor($data)
    {
        $btcextractor = $this->getById($id);

        if ($btcextractor) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }

    public function removeBtcextractor($data)
    {
        $btcextractor = $this->getById($id);

        if ($btcextractor) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }
}
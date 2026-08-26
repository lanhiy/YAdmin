<?php

declare(strict_types=1);

namespace App\System\Logic\Business;

use App\Model\CalibrationCert;

/**
 * 校准证书逻辑，CRUD 全部复用 ProductDocLogic.
 */
class CalibrationCertLogic extends ProductDocLogic
{
    protected function modelClass(): string
    {
        return CalibrationCert::class;
    }

    protected function noField(): string
    {
        return 'cert_no';
    }

    protected function docName(): string
    {
        return '校准证书';
    }
}

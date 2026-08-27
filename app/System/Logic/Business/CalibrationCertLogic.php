<?php

declare(strict_types=1);

namespace App\System\Logic\Business;

use App\Model\ProductCertificate;

/**
 * 校准证书逻辑，CRUD 全部复用 ProductDocLogic.
 */
class CalibrationCertLogic extends ProductDocLogic
{
    protected function modelClass(): string
    {
        return ProductCertificate::class;
    }

    protected function certificateType(): int
    {
        return ProductCertificate::TYPE_CALIBRATION_CERT;
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

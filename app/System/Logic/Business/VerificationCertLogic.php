<?php

declare(strict_types=1);

namespace App\System\Logic\Business;

use App\Model\VerificationCert;

/**
 * 检定证书逻辑，CRUD 全部复用 ProductDocLogic.
 */
class VerificationCertLogic extends ProductDocLogic
{
    protected function modelClass(): string
    {
        return VerificationCert::class;
    }

    protected function noField(): string
    {
        return 'cert_no';
    }

    protected function docName(): string
    {
        return '检定证书';
    }
}

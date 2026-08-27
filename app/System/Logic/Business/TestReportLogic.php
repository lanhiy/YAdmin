<?php

declare(strict_types=1);

namespace App\System\Logic\Business;

use App\Model\ProductCertificate;

/**
 * 测试报告逻辑，CRUD 全部复用 ProductDocLogic.
 */
class TestReportLogic extends ProductDocLogic
{
    protected function modelClass(): string
    {
        return ProductCertificate::class;
    }

    protected function certificateType(): int
    {
        return ProductCertificate::TYPE_TEST_REPORT;
    }

    protected function noField(): string
    {
        return 'report_no';
    }

    protected function docName(): string
    {
        return '测试报告';
    }
}

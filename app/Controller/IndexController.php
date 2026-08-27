<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Exception\BusinessException;
use App\Model\Product;
use App\Model\ProductCertificate;

class IndexController extends AbstractController
{
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    /**
     * 公开查询证书结果。
     *
     * 不同类型证书使用统一的公开字段，所有字段均以字符串返回；数据库中的
     * 缺失值统一输出为空字符串，避免前端处理 null、数字等其他类型。
     *
     * @return array<string, string>
     */
    public function certificate(string $token): array
    {
        $token = strtolower(trim($token));
        if (preg_match('/^[a-f0-9]{32}$/', $token) !== 1) {
            throw new BusinessException('证书链接无效');
        }

        $certificate = ProductCertificate::query()->where('public_token', $token)->first();
        if ($certificate === null) {
            throw new BusinessException('证书不存在或链接无效');
        }

        $product = Product::query()->find((int) $certificate->product_id);
        $type = (int) $certificate->certificate_type;

        $unitName = match ($type) {
            ProductCertificate::TYPE_TEST_REPORT,
            ProductCertificate::TYPE_CALIBRATION_CERT => $certificate->client_name,
            ProductCertificate::TYPE_VERIFICATION_CERT => $certificate->submit_unit,
            default => '',
        };
        $checkDate = match ($type) {
            ProductCertificate::TYPE_TEST_REPORT => $certificate->test_date,
            ProductCertificate::TYPE_VERIFICATION_CERT => $certificate->verify_date,
            ProductCertificate::TYPE_CALIBRATION_CERT => $certificate->calibrate_date,
            default => '',
        };
        $validUntil = $type === ProductCertificate::TYPE_VERIFICATION_CERT
            ? $certificate->valid_until
            : '';

        return [
            'certificate_no' => $this->stringValue($certificate->certificate_no),
            'unit_name' => $this->stringValue($unitName),
            'instrument_name' => $this->stringValue($product?->instrument_name),
            'model' => $this->stringValue($product?->model),
            'instrument_no' => $this->stringValue($product?->instrument_no),
            'manufacturer' => $this->stringValue($product?->manufacturer),
            'check_date' => $this->stringValue($checkDate),
            'valid_until' => $this->stringValue($validUntil),
            // 当前模型没有独立的校验单位字段，保留固定字段并返回空字符串。
            'check_unit' => '',
        ];
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}

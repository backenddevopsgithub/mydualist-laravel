<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Submissions\SendSubmissionOtpRequest;
use App\Http\Requests\Submissions\VerifySubmissionOtpRequest;
use App\Services\WhatsAppOtpService;
use Illuminate\Http\JsonResponse;

class SubmissionOtpController extends ApiController
{
    public function send(SendSubmissionOtpRequest $request, WhatsAppOtpService $otp): JsonResponse
    {
        $data = $request->validated();

        $otp->send($data['whatsapp_country_code'], $data['whatsapp_phone']);

        return $this->success([
            'expires_in' => $otp->otpTtlSeconds(),
            'otp_length' => $otp->otpLength(),
        ], 'OTP sent.');
    }

    public function verify(VerifySubmissionOtpRequest $request, WhatsAppOtpService $otp): JsonResponse
    {
        $data = $request->validated();

        $result = $otp->verify(
            $data['whatsapp_country_code'],
            $data['whatsapp_phone'],
            $data['otp'],
        );

        return $this->success([
            'verification_token' => $result['token'],
            'phone' => $result['phone'],
        ], 'Phone number verified successfully!');
    }
}

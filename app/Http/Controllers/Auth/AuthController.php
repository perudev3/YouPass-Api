<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\OtpCode;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:8|max:20'
        ]);

        $phone = $request->phone;

        // Generar código de 6 dígitos
        $code = rand(100000, 999999);

        // Invalidar códigos anteriores
        OtpCode::where('phone', $phone)
            ->where('used', false)
            ->update(['used' => true]);

        // Guardar nuevo código
        OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
            'used' => false,
        ]);

        // Simular SMS (LOG)
        Log::info("OTP enviado al número {$phone}: {$code}");

        return response()->json([
            'message' => 'Código enviado correctamente'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|min:6|max:6'
        ]);

        $otp = OtpCode::where('phone', $request->phone)
            ->where('code', $request->code)
            ->where('used', false)
            ->first();

        if (!$otp) {
            return response()->json([
                'message' => 'Código inválido'
            ], 422);
        }

        if ($otp->expires_at->isPast()) {
            return response()->json([
                'message' => 'Código expirado'
            ], 422);
        }

        // Marcar OTP como usado
        $otp->update(['used' => true]);

        // Buscar o crear usuario
        $user = User::firstOrCreate(
            ['phone' => $request->phone],
            [
                'is_verified' => true,
                'phone_verified_at' => now(),
            ]
        );

        // Generar token
        $token = $user->createToken('auth_token')->plainTextToken;

        // ¿Debe completar perfil?
        $needsProfile = is_null($user->name);

        return response()->json([
            'message' => 'Autenticado correctamente',
            'token' => $token,
            'needs_profile' => $needsProfile,
            'user' => $user,
        ]);
    }

}

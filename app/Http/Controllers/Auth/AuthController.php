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

    private function sendWhatsApp($phone, $message)
    {
        $token = config('services.factiliza.token');
        $instance = config('services.factiliza.instance');

        $url = "https://apiwsp.factiliza.com/v1/message/sendtext/{$instance}";

        $data = [
            'number' => $phone,
            'text' => $message
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            Log::error("Error enviando WhatsApp a {$phone}: {$err}");
            return false;
        }

        Log::info("WhatsApp enviado a {$phone}: {$response}");
        return true;
    }

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

        // Enviar por WhatsApp
        $message = "Tu código de verificación es: {$code}";
        $this->sendWhatsApp($phone, $message);


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
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->accessToken;


        // ¿Debe completar perfil?
        $needsProfile = is_null($user->name);

        return response()->json([
            'message' => 'Autenticado correctamente',
            'token' => $token,
            'needs_profile' => $needsProfile,
            'user' => $user,
        ]);
    }

    public function registerProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }

        if (!is_null($user->name)) {
            return response()->json([
                'message' => 'El perfil ya fue completado'
            ], 400);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string',
            'instagram' => 'nullable|string|max:255',
        ]);

        $user->update($request->only([
            'name',
            'email',
            'birth_date',
            'gender',
            'instagram',
        ]));

        return response()->json([
            'message' => 'Perfil completado correctamente',
            'user' => $user
        ]);
    }


    public function MeProfile(Request $request) {
        return response()->json([
            'user' => $request->user()
        ]);
    }



}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\OtpCode;
use App\User;
use App\Ticket;
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


    public function MeProfile(Request $request)
    {
        $user = $request->user();

        $roles = $user->eventRoles()->get();
        $firstRole = $roles->first();
        $primaryRole = $firstRole ? $firstRole->role : null;

        // 👇 solo eventos para scanners
        $scannerEvents = [];

        if (in_array($primaryRole, ['scanner_puerta', 'scanner_barra'])) {
            $scannerEvents = \App\EventUserRole::with('event')
                ->where('user_id', $user->id)
                ->whereIn('role', ['scanner_puerta', 'scanner_barra'])
                ->get()
                ->pluck('event')
                ->unique('id')
                ->values();
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date,
                'gender' => $user->gender,
                'instagram' => $user->instagram,
                'is_verified' => $user->is_verified,
                'mood_partty' => $user->mood_partty,

                // 🔥 rol principal seguro
                'role' => $primaryRole ?? 'cliente'
            ],

            // 🔥 SOLO para scanners
            'events' => $scannerEvents
        ]);
    }

    public function myScannerEvents(Request $request)
    {
        $user = $request->user();

        // Solo scanners
        if (!in_array($user->role, ['scanner_puerta', 'scanner_barra'])) {
            return response()->json([]);
        }

        $events = EventUserRole::with('event')
            ->where('user_id', $user->id)
            ->get()
            ->pluck('event')
            ->unique('id')
            ->values();

        return response()->json($events);
    }

    public function MoodPartty(Request $request) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }

        $tiene_entradas = Ticket::where('user_id', $user->id)->exists();

        if ($tiene_entradas) {
            $user->update($request->only(['mood_partty']));

            return response()->json([
                'message' => 'Modo Fiesta Activado',
                'status' => 'success'
            ]);
        } else {
            return response()->json([
                'message' => 'Compra entradas de algún evento para activar Modo Fiesta',
                'status' => 'error'
            ], 403);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
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
            'message' => 'Perfil actualizado correctamente',
            'user' => $user
        ]);
    }


    public function myRole(Request $request)
    {
         $user = $request->user();

        $role = \App\EventUserRole::where('user_id', $user->id)->first();
        
        return response()->json([
            'role' => $role ? $role->role : 'cliente'
        ]);
    }

}

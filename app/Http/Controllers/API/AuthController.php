<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    private function buildAuthPayload(User $user, string $token): array
    {
        return [
            'id'              => $user->id,
            'token'           => $token,
            'name'            => $user->name ?? '',
            'email'           => $user->email,
            'username'        => $user->username,
            'provider'        => $user->provider,
            'role'            => $user->getRoleNames()->first(),
            'status'          => $user->status,
            'provider_status' => $user->provider_status ?? null,
        ];
    }

    public function socialSignin(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'provider' => 'required|in:google,apple',
                'id_token' => 'required|string',
                'name' => 'nullable|string|max:100',
                'avatar' => 'nullable|string|max:255',
            ],
            [
                'provider.required' => 'Provider is required',
                'provider.in' => 'Provider must be google or apple',
                'id_token.required' => 'ID token is required',
            ]
        );

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $provider = $request->provider;
        $tokenVerification = $this->verifySocialIdToken($provider, $request->id_token);

        if (! $tokenVerification['valid']) {
            return $this->error([], $tokenVerification['message'], 401);
        }

        $claims = $tokenVerification['claims'];
        $providerId = (string) ($claims['sub'] ?? '');

        if ($providerId === '') {
            return $this->error([], 'Invalid id_token: missing subject', 401);
        }

        $verifiedEmail = isset($claims['email']) ? Str::lower((string) $claims['email']) : null;
        $verifiedName = $request->name ?? ($claims['name'] ?? null);
        $verifiedAvatar = $request->avatar ?? ($claims['picture'] ?? null);

        $user = User::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (! $user && $verifiedEmail) {
            $user = User::where('email', $verifiedEmail)->first();
            if ($user) {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'name' => $verifiedName ?? $user->name,
                    'avatar' => $verifiedAvatar ?? $user->avatar,
                    'last_login_at' => now(),
                ]);
            }
        }

        if (! $user) {
            $socialEmail = $verifiedEmail;

            if (! $socialEmail) {
                $socialEmail = $provider . '_' . Str::lower(Str::random(12)) . '@social.local';
            }

            $user = User::create([
                'name' => $verifiedName,
                'email' => $socialEmail,
                'password' => Hash::make(Str::random(32)),
                'avatar' => $verifiedAvatar ?? 'user.png',
                'provider' => $provider,
                'provider_id' => $providerId,
                'status' => 'active',
                'last_login_at' => now(),
            ]);

            $user->assignRole('user');
        }

        if ($user->status !== 'active') {
            return $this->error([], 'Your account is not active', 403);
        }

        $user->update([
            'last_login_at' => now(),
        ]);

        $token = Auth::guard('api')->login($user);

        if (!$token) {
            return $this->error([], 'Unable to generate login token', 500);
        }

        return $this->success($this->buildAuthPayload($user, $token), 'Social login successful', 200);
    }

    public function guestSignin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $guestProviderId = $request->device_id ?: (string) Str::uuid();

        $user = User::where('provider', 'guest')
            ->where('provider_id', $guestProviderId)
            ->first();

        if (!$user) {
            $email = 'guest_' . Str::lower(Str::random(12)) . '@guest.local';

            $user = User::create([
                'name' => $request->name ?: 'Guest User',
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'provider' => 'guest',
                'provider_id' => $guestProviderId,
                'status' => 'active',
                'last_login_at' => now(),
            ]);

            $user->assignRole('user');
        }

        $user->update([
            'last_login_at' => now(),
        ]);

        $token = Auth::guard('api')->login($user);

        if (!$token) {
            return $this->error([], 'Unable to generate login token', 500);
        }

        return $this->success($this->buildAuthPayload($user, $token), 'Guest login successful', 200);
    }

    public function storeFcmToken(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Error in Validation', 422);
        }

        $user = Auth::guard('api')->user();

        // Check if device exists
        $existing = $user->fcmTokens()->where('device_id', $request->device_id)->first();

        if ($existing) {
            $existing->update(['token' => $request->token]);
        } else {
            $user->fcmTokens()->create([
                'device_id' => $request->device_id,
                'token' => $request->token,
            ]);
        }

        $response = [
            'device_id' => $user->fcmTokens()->where('device_id', $request->device_id)->first()->device_id,
            'token' =>  $user->fcmTokens()->where('device_id', $request->device_id)->first()->token,
        ];

        return $this->success($response, 'FCM token stored successfully', 200);
    }

    public function deleteFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Error in Validation', 422);
        }

        $user = Auth::guard('api')->user();

        $user->fcmTokens()->where('device_id', $request->device_id)->delete();

        return $this->success([], 'FCM token deleted successfully', 200);
    }

    public function deleteUser(Request $request)
    {
        // Get authenticated user from JWT token
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        // Validation: only require password if user is email-based and password is provided
        $validator = Validator::make(
            $request->all(),
            [
                'password' => 'nullable|string|min:6',
                'reason'   => 'nullable|string|max:255',
            ],
            [
                'password.min' => 'Password must be at least 6 characters',
            ]
        );

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // For email-based users, verify password if provided for extra security
        if ($user->provider === 'email' && $request->filled('password')) {
            if (!Hash::check($request->password, $user->password)) {
                return $this->error([], 'Invalid password', 401);
            }
        }

        // Store deletion reason and delete user
        $user->reason = $request->reason ?? null;
        $user->save();

        // Soft delete or hard delete based on your preference
        $user->delete();

        return $this->success([], 'User deleted successfully', 200);
    }

    public function logout()
    {
        try {
            // Get token from request
            $token = JWTAuth::getToken();

            if (!$token) {
                return $this->error([], 'Token not provided', 401);
            }

            // Invalidate token
            JWTAuth::invalidate($token);

            return $this->success([], 'Successfully logged out', 200);
        } catch (JWTException $e) {
            return $this->error([], 'Failed to logout. ' . $e->getMessage(), 500);
        }
    }












    private function verifySocialIdToken(string $provider, string $idToken): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            return ['valid' => false, 'message' => 'Invalid id_token format', 'claims' => []];
        }

        $header = $this->decodeJwtPart($parts[0]);
        $claims = $this->decodeJwtPart($parts[1]);
        $signature = $this->base64UrlDecode($parts[2]);

        if (! $header || ! $claims || $signature === null) {
            return ['valid' => false, 'message' => 'Invalid id_token payload', 'claims' => []];
        }

        if (($header['alg'] ?? null) !== 'RS256') {
            return ['valid' => false, 'message' => 'Unsupported token algorithm', 'claims' => []];
        }

        $publicKeyPem = $this->resolveProviderPublicKey($provider, $header, $claims);

        if (! $publicKeyPem) {
            return ['valid' => false, 'message' => 'Unable to resolve provider public key', 'claims' => []];
        }

        $isValid = openssl_verify($parts[0] . '.' . $parts[1], $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);

        if ($isValid !== 1) {
            return ['valid' => false, 'message' => 'Invalid token signature', 'claims' => []];
        }

        $now = time();

        if (isset($claims['exp']) && $now >= (int) $claims['exp']) {
            return ['valid' => false, 'message' => 'id_token has expired', 'claims' => []];
        }

        if (isset($claims['nbf']) && $now < (int) $claims['nbf']) {
            return ['valid' => false, 'message' => 'id_token is not valid yet', 'claims' => []];
        }

        if (empty($claims['sub'])) {
            return ['valid' => false, 'message' => 'id_token subject is missing', 'claims' => []];
        }

        if ($provider === 'google') {
            $validationError = $this->validateGoogleClaims($claims);
            if ($validationError) {
                return ['valid' => false, 'message' => $validationError, 'claims' => []];
            }
        }

        if ($provider === 'apple') {
            $validationError = $this->validateAppleClaims($claims);
            if ($validationError) {
                return ['valid' => false, 'message' => $validationError, 'claims' => []];
            }
        }

        return ['valid' => true, 'message' => null, 'claims' => $claims];
    }

    private function resolveProviderPublicKey(string $provider, array $header, array $claims): ?string
    {
        $kid = $header['kid'] ?? null;

        if (! $kid) {
            return null;
        }

        if ($provider === 'google') {
            $iss = (string) ($claims['iss'] ?? '');

            if (Str::startsWith($iss, 'https://securetoken.google.com/')) {
                $url = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
            } else {
                $url = 'https://www.googleapis.com/oauth2/v1/certs';
            }

            $cacheKey = 'social_keys_google_' . md5($url);
            $certs = Cache::get($cacheKey);

            if (! is_array($certs) || ! isset($certs[$kid])) {
                $freshCerts = $this->fetchJsonFromUrl($url);

                if (is_array($freshCerts) && ! empty($freshCerts)) {
                    Cache::put($cacheKey, $freshCerts, now()->addHours(12));
                    $certs = $freshCerts;
                }
            }

            return is_array($certs) && isset($certs[$kid]) ? (string) $certs[$kid] : null;
        }

        if ($provider === 'apple') {
            $cacheKey = 'social_keys_apple';
            $jwks = Cache::get($cacheKey);

            if (! is_array($jwks) || empty($jwks)) {
                $freshJwks = $this->fetchJsonFromUrl('https://appleid.apple.com/auth/keys', 'keys');

                if (is_array($freshJwks) && ! empty($freshJwks)) {
                    Cache::put($cacheKey, $freshJwks, now()->addHours(12));
                    $jwks = $freshJwks;
                }
            }

            if (! is_array($jwks) || empty($jwks)) {
                return null;
            }

            foreach ($jwks as $jwk) {
                if (($jwk['kid'] ?? null) === $kid && ($jwk['kty'] ?? null) === 'RSA') {
                    return $this->jwkToPem($jwk);
                }
            }
        }

        return null;
    }

    private function fetchJsonFromUrl(string $url, ?string $jsonPath = null): array|null
    {
        try {
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->retry(2, 250)
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $data = $jsonPath ? $response->json($jsonPath) : $response->json();

            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function validateGoogleClaims(array $claims): ?string
    {
        $iss = (string) ($claims['iss'] ?? '');
        $aud = $claims['aud'] ?? null;

        if (is_array($aud)) {
            $aud = $aud[0] ?? null;
        }

        if (
            ! in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true)
            && ! Str::startsWith($iss, 'https://securetoken.google.com/')
        ) {
            return 'Invalid Google token issuer';
        }

        $googleClientId = env('GOOGLE_AUTH_CLIENT_ID');
        $firebaseProjectId = env('GOOGLE_FIREBASE_PROJECT_ID');

        if (Str::startsWith($iss, 'https://securetoken.google.com/')) {
            if ($firebaseProjectId && (string) $aud !== $firebaseProjectId) {
                return 'Google token audience mismatch';
            }

            if ($firebaseProjectId && $iss !== 'https://securetoken.google.com/' . $firebaseProjectId) {
                return 'Google token issuer mismatch';
            }
        } else {
            if ($googleClientId && (string) $aud !== $googleClientId) {
                return 'Google token audience mismatch';
            }
        }

        return null;
    }

    private function validateAppleClaims(array $claims): ?string
    {
        $iss = (string) ($claims['iss'] ?? '');
        $aud = $claims['aud'] ?? null;

        if (is_array($aud)) {
            $aud = $aud[0] ?? null;
        }

        if ($iss !== 'https://appleid.apple.com') {
            return 'Invalid Apple token issuer';
        }

        $appleClientId = env('APPLE_AUTH_CLIENT_ID');

        if ($appleClientId && (string) $aud !== $appleClientId) {
            return 'Apple token audience mismatch';
        }

        return null;
    }

    private function decodeJwtPart(string $part): ?array
    {
        $decoded = $this->base64UrlDecode($part);

        if ($decoded === null) {
            return null;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload) ? $payload : null;
    }

    private function base64UrlDecode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    private function jwkToPem(array $jwk): ?string
    {
        if (! isset($jwk['n'], $jwk['e'])) {
            return null;
        }

        $modulus = $this->base64UrlDecode((string) $jwk['n']);
        $exponent = $this->base64UrlDecode((string) $jwk['e']);

        if ($modulus === null || $exponent === null) {
            return null;
        }

        $modulusInteger = $this->asn1EncodeInteger($modulus);
        $exponentInteger = $this->asn1EncodeInteger($exponent);
        $rsaPublicKey = $this->asn1EncodeSequence($modulusInteger . $exponentInteger);

        $algorithmIdentifier = hex2bin('300d06092a864886f70d0101010500');
        if ($algorithmIdentifier === false) {
            return null;
        }

        $subjectPublicKey = "\x03" . $this->asn1EncodeLength(strlen($rsaPublicKey) + 1) . "\x00" . $rsaPublicKey;
        $subjectPublicKeyInfo = $this->asn1EncodeSequence($algorithmIdentifier . $subjectPublicKey);

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private function asn1EncodeInteger(string $value): string
    {
        if (ord($value[0]) > 0x7f) {
            $value = "\x00" . $value;
        }

        return "\x02" . $this->asn1EncodeLength(strlen($value)) . $value;
    }

    private function asn1EncodeSequence(string $value): string
    {
        return "\x30" . $this->asn1EncodeLength(strlen($value)) . $value;
    }

    private function asn1EncodeLength(int $length): string
    {
        if ($length <= 0x7f) {
            return chr($length);
        }

        $temp = '';
        while ($length > 0) {
            $temp = chr($length & 0xff) . $temp;
            $length >>= 8;
        }

        return chr(0x80 | strlen($temp)) . $temp;
    }
}

<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use App\Http\Controllers\Controller;
use App\Notifications\OtpNotification;
use App\Exceptions\VerifyEmailException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        $token = $this->attemptLogin($request);
        if ($token) {
            $isOtp = GeneralSetting::where('key', 'email_otp_verified')->value('value') == 1;
            if ($isOtp) {
                $this->sendOtp();
                $otpExpirationTime = getOtpExpirationTime();
                return response()->json([
                    'status' => 'otp_required',
                    '__token' => $token,
                    'message' => 'OTP verification is required.',
                    'otp_expiration_time' => $otpExpirationTime,
                ]);
            }
            return $this->sendLoginResponse($request);
        }

        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => [
                'email' => ['These credentials do not match our records.'],
            ],
        ], 422);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $token = $this->guard()->attempt($this->credentials($request));

        if (!$token) {
            return false;
        }

        $user = $this->guard()->user();
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return false;
        }

        $this->guard()->setToken($token);

        return $token;
    }

    private function sendOtp()
    {
        $user = $this->guard()->user();
        $otp = rand(100000, 999999);

        // Get OTP expiration time from settings (default to 2 minutes if not set)
        $otpExpirationTime = getOtpExpirationTime();

        $user->update(['otp_code' => $otp, 'otp_expires_at' => now()->addMinutes($otpExpirationTime)]);
        $user->notify(new OtpNotification($otp, $otpExpirationTime));
    }

    public function verifyOtp(Request $request)
    {
        // Validate the OTP
        $request->validate([
            'otp_number' => 'required|numeric',
        ]);

        $this->guard()->setToken($request->token_number);

        // Get the authenticated user
        $user = $this->guard()->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated.',
            ]);
        }

        // Check if OTP is valid and not expired
        if ($user->otp_code !== $request->otp_number || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP.',
            ]);
        }

        // OTP is valid, clear OTP fields
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Send login response with the JWT token
        return $this->sendLoginResponse($request);
    }

    /**
     * Resend a new OTP to the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendOtp(Request $request)
    {
        $this->guard()->setToken($request->token_number);
        $user = $this->guard()->user();
        $this->sendOtp();

        $otpExpirationTime = getOtpExpirationTime();
        return response()->json([
            'status' => 'otp_resent',
            'message' => 'A new OTP has been sent to your email.',
            'otp_expiration_time' => $otpExpirationTime,
        ]);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
   protected function sendLoginResponse(Request $request)
{
    $this->clearLoginAttempts($request);

    $token = (string) $this->guard()->getToken();
    $expiration = $this->guard()->getPayload()->get('exp');

    $user = $this->guard()->user()->load('roles', 'employee');

    return response()->json([
        'token' => $token,
        'token_type' => 'bearer',
        'expires_in' => $expiration - time(),
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('slug'),
            'employee' => $user->employee ? [
                'id' => $user->employee->id,
                'designation' => $user->employee->designation
            ] : null
        ]
    ]);
}

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $user = $this->guard()->user();
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            throw VerifyEmailException::forUser($user);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();
    }
}

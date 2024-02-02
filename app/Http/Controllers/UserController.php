<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\User;
use App\Media;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Notifications\ResetPassword;
use DB;
use Mail;
use Carbon\Carbon;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | UserController
    |--------------------------------------------------------------------------
    |
    | This controller handles the manipualtion of user
    |
    */

    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Shows profile of logged in user
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfile()
    {
        $user_id = request()->session()->get('user.id');
        $user = User::where('id', $user_id)->with(['media'])->first();
        $config_languages = config('constants.langs');
        $languages = [];
        foreach ($config_languages as $key => $value) {
            $languages[$key] = $value['full_name'];
        }

        return view('user.profile', compact('user', 'languages'));
    }

    /**
     * updates user profile
     *
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        //Disable in demo
        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $user_id = $request->session()->get('user.id');
            $input = $request->only(['surname', 'first_name', 'last_name', 'email', 'language', 'marital_status',
                'blood_group', 'contact_number', 'fb_link', 'twitter_link', 'social_media_1',
                'social_media_2', 'permanent_address', 'current_address',
                'guardian_name', 'custom_field_1', 'custom_field_2',
                'custom_field_3', 'custom_field_4', 'id_proof_name', 'id_proof_number', 'gender', 'family_number', 'alt_number', ]);

            if (! empty($request->input('dob'))) {
                $input['dob'] = $this->moduleUtil->uf_date($request->input('dob'));
            }
            if (! empty($request->input('bank_details'))) {
                $input['bank_details'] = json_encode($request->input('bank_details'));
            }

            $user = User::find($user_id);
            $user->update($input);

            Media::uploadMedia($user->business_id, $user, request(), 'profile_photo', true);

            //update session
            $input['id'] = $user_id;
            $business_id = request()->session()->get('user.business_id');
            $input['business_id'] = $business_id;
            session()->put('user', $input);

            $output = ['success' => 1,
                'msg' => __('lang_v1.profile_updated_successfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('user/profile')->with('status', $output);
    }

    /**
     * updates user password
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        //Disable in demo
        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $user_id = $request->session()->get('user.id');
            $user = User::where('id', $user_id)->first();

            if (Hash::check($request->input('current_password'), $user->password)) {
                $user->password = Hash::make($request->input('new_password'));
                $user->save();
                $output = ['success' => 1,
                    'msg' => __('lang_v1.password_updated_successfully'),
                ];
            } else {
                $output = ['success' => 0,
                    'msg' => __('lang_v1.u_have_entered_wrong_password'),
                ];
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('user/profile')->with('status', $output);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required'],
            'password' => 'required|min:4',
        ]);

        $credetanils = [
            'username' => $request->username,
            'password' => $request->password
        ];

        if (Auth::attempt($credetanils)) {
            $user = Auth::user();
            $token = $user->createToken('Token name');

            $user_data = [];
            $user_data_key = ['id', 'surname', 'first_name', 'last_name', 'username', 'email', 'dob'];
    
            foreach($user_data_key as $key) {
                $user_data[$key] = $user->{$key};
            }

            return response()->json([
                'accessToken' => $token->accessToken,
                'user' => $user_data
            ]);

        } else {
            return response()->json("Credential not much", 401);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'surname' => 'max:10',
            'first_name' => 'required|max:255',
            'last_name' => 'sometimes|nullable|max:255',
            'email' => 'sometimes|nullable|email|unique:users|max:255',
            'username' => 'required|min:4|max:255|unique:users',
            'password' => 'required|min:4|max:255',
        ]);
        $business_id = env('BUSINESS_ID', 1);

        //Create owner.
        $customer_details = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password', 'language']);

        $customer_details['language'] = empty($customer_details['language']) ? config('app.locale') : $customer_details['language'];

        $customer_details['user_type'] = 'customer';
        $customer_details['business_id'] = $business_id;

        $user = User::create_user($customer_details);
        // $user->assignRole('Rider');
        $token = $user->createToken('Token name');

        return response()->json([
            'accessToken' => $token->accessToken,
            'user' => $user
        ]);
    }

    /**
     * Shows profile of logged in user
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfileApi()
    {
        $user_id = Auth::id();
        $user = User::where('id', $user_id)->with(['media'])->first();

        $user_data = [];
        $user_data_key = ['id', 'surname', 'first_name', 'last_name', 'username', 'email', 'dob'];

        foreach($user_data_key as $key) {
            $user_data[$key] = $user->{$key};
        }

        // $user_data['bank_details'] = json_decode($user->bank_details);

        // $config_languages = config('constants.langs');
        // $languages = [];
        // foreach ($config_languages as $key => $value) {
        //     $languages[$key] = $value['full_name'];
        // }

        return response()->json([
            'user' => $user_data,
            // 'languages' => $languages,
        ]);
    }

    /**
     * updates user profile
     *
     * @return \Illuminate\Http\Response
     */
    public function updateProfileApi(Request $request)
    {
        try {
            $user_id = Auth::id();
            
            $input = $request->only(['surname', 'first_name', 'last_name', 'email', 'language']);

            if (! empty($request->input('dob'))) {
                $input['dob'] = $this->moduleUtil->uf_date($request->input('dob'));
            }

            if (! empty($request->input('bank_details'))) {
                $input['bank_details'] = json_encode($request->input('bank_details'));
            }

            if (! empty($request->input('password'))) {
                $input['password'] = Hash::make($request->input('password'));
            }

            $user = User::find($user_id);
            $user->update($input);

            Media::uploadMedia($user->business_id, $user, request(), 'profile_photo', true);

            $output = ['success' => 1,
                'msg' => __('lang_v1.profile_updated_successfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $this->respond($output);
    }

    public function forgotPasswordApi(Request $request)
    {
        $request->validate([
            'email' => "required|email|exists:users,email",
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {            
            $num_token = random_int(100000, 999999);
            $token = md5($num_token);
            $expire = (int) config('auth.passwords.users.expire');

            DB::table(config('auth.passwords.users.table'))
                ->where('email', $user->email)
                ->where('created_at', '<', Carbon::now()->subMinute($expire))
                ->delete();

            DB::table(config('auth.passwords.users.table'))->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);

            \Log::info("Email Verify Token: " . $num_token);
            try {
                Mail::send(new PasswordResetMail($user, $num_token));
            } catch (\Exception $ex) {
                \Log::error('Conform Mail: ' . $ex->getMessage());
            }

            return response(['message' => trans(Password::RESET_LINK_SENT), 'status' => true], 200);
        }

        return response(['message' => trans(Password::INVALID_USER), 'status' => false], 200);
    }

    public function verifyResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $email = $request->get('email');
        $code = $request->get('code');

        $user = User::where('email', $email)->first();

        if ($user) {
            $expire = (int) config('auth.passwords.users.expire');

            $password_reset = DB::table(config('auth.passwords.users.table'))
                ->where('email', $email)
                ->where('token', md5($code))
                ->where('created_at', '>=', Carbon::now()->subMinute($expire))
                ->first();

            if ($password_reset) {
                if ($user->is_email_verified != 1) {
                    $user->uhash = generateRandomNumber(6);
                    $user->is_email_verified = 1;
                    $user->email_verified_at = Carbon::now()->subMinute($expire);
                }

                $user->security_code_at = Carbon::now();
                $user->setRememberToken(Str::random(60));
                $user->save();

                return $this->respondWithSuccess([
                    'email' => $user->email,
                    'token' => $user->getRememberToken(),
                ]);
            }
        }

        return $this->respondWithNotFound();
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $email = $request->get('email');
        $token = $request->get('token');
        $password = $request->get('password');
        $expire = (int) config('auth.passwords.users.expire');

        $user = User::where('email', $email)
            ->where('remember_token', $token)
            ->where('security_code_at', '>=', Carbon::now()->subMinute($expire))
            ->first();

        if ($user) {
            $user->password = $password;
            $user->security_code_at = Carbon::now()->subMinute($expire);
            $user->setRememberToken(Str::random(60));
            $user->save();

            // Delete password reset token form password_reset table
            DB::table(config('auth.passwords.users.table'))
                ->where('email', $user->email)
                ->delete();

            event(new PasswordReset($user));

            try {
                Mail::send(new InfoPasswordChangedMail($user));
            } catch (\Extension $ex) {
                Log::channel('email')->error('Info Password Changed: ' . $ex->getMessage());
            }

            return $this->respondWithSuccess();
        }

        return $this->respondWithNotFound();
    }

    public function emailVerification(EmailVerificationRequest $request)
    {
        $email = $request->get('email');
        $code = md5($request->get('code'));
        $expire = (int) config('auth.passwords.users.expire');

        $user = User::where('email', $email)
            ->where('uhash', $code)
            ->where('is_email_verified', 0)
            ->where('security_code_at', '>=', Carbon::now()->subMinute($expire))
            ->first();

        if ($user) {
            $new_code = generateRandomNumber(6);

            $user->uhash = md5($new_code);
            $user->is_email_verified = 1;
            $user->email_verified_at = Carbon::now()->subMinute($expire);
            $user->save();

            // Send welcome mail to register user, after register
            try {
                Mail::send(new WelcomeMail($user->id));
            } catch (\Exception $ex) {
                Log::channel('email')->error('Welcome Mail: ' . $ex->getMessage());
            }

            return $this->respondWithSuccess();
        }

        return $this->respondWithNotFound([], "Confirmation code doesn't match.");
    }

    public function resendVerificationCode(ResedVerificationCodeRequest $request)
    {
        $email = $request->get('email');

        $user = User::where('email', $email)
            ->first();

        if ($user) {
            // Send email verification main to register user, after register
            try {
                Mail::send(new EmailConformMail($user->id));
            } catch (\Exception $ex) {
                Log::channel('email')->error('Conform Mail: ' . $ex->getMessage());
            }

            return $this->respondWithSuccess();
        }

        return $this->respondWithNotFound([], "Email doesn't match.");
    }
}

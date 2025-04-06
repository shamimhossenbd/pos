<?php

namespace App\Http\Controllers;

use App\Helper\JWTToken;
use App\Mail\OTPMail;
use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use PhpParser\Node\Stmt\TryCatch;

use function Laravel\Prompts\error;

class UserController extends Controller
{
    public function LoginPage(Request $request)
    {
        return Inertia::render('LoginPage');
    } //end method

    public function RegistrationPage(Request $request)
    {
        return Inertia::render('RegistrationPage');
    } //end method

    public function SendOTPPage(Request $request)
    {
        return Inertia::render('SendOTPPage');
    } //end method

    public function VerifyOTPPage(Request $request)
    {
        return Inertia::render('VerifyOTPPage');
    } //end method

    public function ResetPasswordPage(Request $request)
    {
        return Inertia::render('ResetPasswordPage');
    } //end method



    function UserRegistration(Request $request)
    {
        // dd($request->all());
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
            ]);

            User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'mobile' => $request->input('mobile'),
            ]);
            // return response()->json([
            //     'status' => 'success',
            //     'message' => 'User Registration Successfully'
            // ], 200);

            $data=['message'=>'User Registration Successfully','status'=>true,'error'=>''];
            return redirect('/login')->with($data);
        } catch (Exception $e) {
            // return response()->json([
            //     'status' => 'failed',
            //     'message' => $e->getMessage()
            // ], 200);
            $data=['message'=>'Something went wrong','status'=>false,'error'=>$e->getMessage()];
            return redirect('/registration')->with($data);
        }
    }




     function UserLogin(Request $request)
    {
        $count = User::where('email', '=', $request->input('email'))
            ->where('password', '=', $request->input('password'))
            ->select('id')->first();

        if ($count !== null) {
            // User Login-> JWT Token Issue
            // $token =JWTToken::CreateToken($request->input('email'), $count->id);
            // return response()->json([
            //     'status' => 'success',
            //     'message' => 'User Login Successful',
            //     'token' => $token
            // ], 200)->cookie('token', $token, time() + 60 * 24 * 30);
            $email = $request->input('email');
            $user_id = $count->id;

            $request->session()->put('email', $email);
            $request->session()->put('user_id', $user_id);

            $data = ['message' => 'User login successfully', 'status' => true, 'error' => ''];
            return redirect('/DashboardPage')->with($data);
        } else {
            // return response()->json([
            //     'status' => 'failed',
            //     'message' => 'unauthorized'
            // ], 200);
            $data = ['message' => 'Something went wrong', 'status' => false, 'error' => ''];
            return redirect('/registration')->with($data);
        }
    }
    public function DashboardPage(Request $request){
        $user_id = request()->header('id');

        $product = Product::where('user_id', $user_id)->count();
        $category = Category::where('user_id', $user_id)->count();
        $customer = Customer::where('user_id', $user_id)->count();
        $invoice = Invoice::where('user_id', $user_id)->count();
        $total = Invoice::where('user_id', $user_id)->sum('total');
        $vat = Invoice::where('user_id', $user_id)->sum('vat');
        $payable = Invoice::where('user_id', $user_id)->sum('payable');
        $discount = Invoice::where('user_id', $user_id)->sum('discount');

        $data = [
            'product' => $product,
            'category' => $category,
            'customer' => $customer,
            'invoice' => $invoice,
            'total' => round($total),
            'vat' => round($vat),
            'payable' => round($payable),
            'discount' => $discount
        ];


        // $user = $request->header('email');

        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'user login successful',
        //     'user' => $user
        // ], 200);
        return Inertia::render('DashboardPage',[
            'list'=>$data,
        ]);
    }


    function UserLogout(Request $request){

        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'User Logout Successful'
        // ], 200)->cookie('token', '', -1);
        $request->session()->forget('email');
        $request->session()->forget('user_id');
        return redirect('/login')->with(['message'=>'User Logout Successful','status'=>true,'error'=>'']);

    }


    function SendOTPCode(Request $request)
    {

        $email = $request->input('email');
        $otp = rand(1000, 9999);
        $count = User::where('email', '=', $email)->count();

        if ($count == 1) {
            // OTP Email Address
            // Mail::to($email)->send(new OTPMail($otp));
            // OTO Code Table Update
            User::where('email', '=', $email)->update(['otp' => $otp]);
            $request->session()->put('email', $email);

            // return response()->json([
            //     'status' => 'success',
            //     'message' => ' 4 Digit OTPCode has been send to your email !'
            // ], 200);
            $data=['message'=> " 4 Digit {$otp} OTP send successfully",'status'=> true,'error'=> ''];
            return redirect('/verify-otp')->with($data);
        } else {
            // return response()->json([
            //     'status' => 'failed',
            //     'message' => 'unauthorized'
            // ]);
            $data=['message'=>'Something went wrong','status'=>false,'error'=>''];
            return redirect('/send-otp')->with($data);
        }
    }




    function VerifyOTP(Request $request)
    {
        // $email = $request->input('email');
        $email = $request->session()->get('email');
        $otp = $request->input('otp');

        $count = User::where('email', $email)->where('otp', $otp)->count();

        if ($count == 1) {
            User::where('email', $email)->update(['otp' => 0]);

            // $token = JWTToken::CreateTokenForSetPassword($request->input('email'));

            $request->session()->put('otp_verify', 'yes');

            // return response()->json([
            //     'status' => 'success',
            //     'message' => 'OTP verification successfully',
            // ],200)->cookie('token', $token, 60 * 24 * 30);

            $data = ["message" => "OTP verification successfully", "status" => true, "error" => ''];
            return redirect('/reset-password')->with($data);
        } else {
            // return response()->json([
            //     'status' => 'fail',
            //     'message' => 'unauthorized'
            // ]);
            $data = ['message' => 'unauthorized', 'status' => false, 'error' => ''];
            return redirect('/login')->with($data);
        }
    }



     function ResetPassword(Request $request)
    {
        try {
            // $email = $request->header('email');
            $email = $request->session()->get('email', 'default');
            $password = $request->input('password');

            $otp_verify = $request->session()->get('otp_verify', 'default');
            if ($otp_verify === 'yes') {
                User::where('email', $email)->update(['password' => $password]);
                $request->session()->flush();

                $data = ['message' => 'Password reset successfully', 'status' => true, 'error' => ''];
                return redirect('/login')->with($data);
            } else {
                $data = ['message' => 'Request fail', 'status' => false, 'error' => ''];
                return redirect('/reset-password')->with($data);
            }
            // return response()->json([
            //     'status' => 'success',
            //     'message' => 'Password reset successfully',
            // ],200);

        } catch (Exception $e) {
            // return response()->json([
            //     'status' => 'fail',
            //     'message' => 'somthing went wrong'
            // ]);
            $data = ['message' => $e->getMessage(), 'status' => false, 'error' => ''];
            return redirect('/reset-password')->with($data);
        }
    } //end method




    public function ProfilePage(Request $request)
    {
        $email = request()->header('email');

        $user = User::where('email', $email)->first();
        return Inertia::render('ProfilePage', ['user' => $user]);
    } //end method

    public function UserUpdate(Request $request)
    {
        $email = request()->header('email');
        User::where('email', $email)->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
        ]);
        $data = ['message' => 'Profile update successfully', 'status' => true, 'error' => ''];
        return redirect()->back()->with($data);
    }
}

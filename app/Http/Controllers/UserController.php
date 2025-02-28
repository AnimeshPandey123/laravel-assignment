<?php

namespace App\Http\Controllers;

use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class UserController extends Controller
{
    public function index(Request $request){

        try{
            $users = User::all();
            return response()->json([
                'data' => $users
            ]);  
        }catch(\Exception $exception){
            return response()->json([
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function store(Request $request) : JsonResponse {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);

        $password = Hash::make($request->get('password'));
        $userReq = $request->only('name', 'email');
        $userReq['password'] =  $password;

        $user = User::create($userReq);

        return response()->json([
            'message' => 'User Registered Successfully.'
        ]);
    }
}

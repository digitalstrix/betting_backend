<?php

namespace App\Http\Controllers;

use App\Models\Sitesetting;
use App\Models\Transaction;
use App\Models\Collection as ModelsCollection;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Dirape\Token\Token;
use App\Models\GameTransaction;
use App\Models\Withdrawl;

class AdminController extends Controller
{
    public function register(Request $request)
    {
        $rules =array(
            "name" => "required",
            "email" => "required|email|unique:users",
            "mobile" => "required|unique:users",
            "password" => "required|min:6",
            "user_type" => "required|in:user,s_admin,s_master,master",
            "token"=> "required"
                );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
            if(!User::where('usertoken',$request->token)->first()){
                return response(["status" =>"failed", "message"=>"User is not created Token is not valid"], 401);
            }
            $creator = User::where('usertoken',$request->token)->first();
            $user = new User();
            $user->name=$request->name;
            $user->email=$request->email;
            $user->user_type=$request->user_type;
            $user->mobile=$request->mobile;
            $user->created_by = $creator->id;
            $user->user_coin = 0;
            $user->usertoken = (new Token())->Unique('users', 'usertoken', 60);
            $user->password=Hash::make($request->password);
            $result= $user->save();
            if ($result) {
                $token = $user->createToken('my-app-token')->plainTextToken;
                $response = [
                    'message' => 'User created successfully',
                'user' => $user,
                'bearer-token' => $token,
            ];
                return response($response, 201);
            } else {
                return response(["status" =>"failed", "message"=>"User is not created"], 401);
            }
        }
    }
    public function login(Request $request)
    {
        $rules =array(
            "email" => "required|email",
            "password" => "required|min:6",
            "user_type" => "required|in:user,s_admin,s_master,master",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
            if(!User::where('email',$request->email)->where('user_type',$request->user_type)->first()){
                return response(["status" =>"failed", "message"=>"User is not Registered or Invaild User Type"], 401);
            }
            $user = User::where('email',$request->email)->where('user_type',$request->user_type)->first();
            if(!Hash::check($request->password, $user->password)){
                return response(["status" =>"failed", "message"=>"Incorrect Password"], 401);
            }
            else{
            if ($user){
                $token = $user->createToken('my-app-token')->plainTextToken;
                $response = [
                'user' => $user,
                'bearer-token' => $token,
                "message"=>"User is Logged IN"
            ];
                return response($response, 200);
            }
            }
        }
    }
    public function updateUser(Request $request)
        {
        $rules =array(
            "token" => "required",
            "email" => "required|email",
            "user_type" => "in:user,s_admin,s_master,master",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(User::where('usertoken',$request->token)->where('user_type','user')->first()){
                return response(["status" =>"failed", "message"=>"User is not allowed to change this"], 401);
            }
            if(!User::where('email',$request->email)->first())
            {
                return response(["status" =>"failed", "message"=>"User is not Registered"], 401);
            }
            $user = User::where('email',$request->email)->first();
            }
if(isset($request->password)){
                $user->password = Hash::make($request->password);
            }
if (isset($request->name)) {
    $user->name = $request->name;
}
if (isset($request->mobile)) {
    $user->mobile = $request->mobile;
}
if (isset($request->user_type)) {
    $user->user_type = $request->user_type;
}
            $result= $user->save();
            if($result){
                $response = [
                'status' => true,
                "message" => "User changed successfully",
                "updated-User" => $user
             ];
                return response($response, 200);
            }
        }

        public function addCoin(Request $request)
        {
        $rules =array(
            "token" => "required",
            "email" => "required",
            "amount" => "required",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('usertoken',$request->token)->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            $user = User::where('email',$request->email)->first();
            $vendor = User::where('usertoken',$request->token)->first();
if ($vendor->user_type=='s_master') {
    if ($vendor->user_coin < $request->amount) {
        return response(["status" =>"error", "message"=>"Coin Balance is low."], 422);
    }
}
if ($vendor->user_type=='master') {
    if ($vendor->user_coin < $request->amount){
        return response(["status" =>"error", "message"=>"Coin Balance is low."], 422);
    }
}
            }
            $balance = new Transaction();
            $balance->from_id = $vendor->id;
            $balance->to_id = $user->id;
            $balance->amount = $request->amount;
            $balance->save();
            if($balance->save()){
                $user->user_coin = $balance->amount + $user->user_coin;
                $vendor->user_coin = $vendor->user_coin- $balance->amount;
            }
            if($user->save()&&$vendor->save()){
                $response = [
                'status' => true,
                "message" => "Balance Sucessfully Added",
             ];
                return response($response, 200);
            }
        }

        public function siteSetting(Request $request)
        {
        $rules =array(
            "token" => "required",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('usertoken',$request->token)->where('user_type','s_admin')->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            }
            $balance = Sitesetting::where('id','1')->first();
            $balance->site_name = $request->site_name;
if ($request->hasFile('favicon')) {
    $file = $request->file('favicon')->store('public/site');
    $balance->favicon = $file;
}
if ($request->hasFile('logo')) {
    $file = $request->file('logo')->store('public/site');
    $balance->logo = $file;
}
$balance->save();
            if($balance->save()){
                $response = [
                'status' => true,
                "message" => "Site Added Sucessfully Added",
             ];
                return response($response, 200);
            }
        }

        public function getUser(Request $request)
        {
        $rules =array(
            "token" => "required",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('usertoken',$request->token)->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            }
            $user = User::where('usertoken',$request->token)->first();
            $transaction = Transaction::where('from_id',$user->id)->get();
            $transactions = Transaction::where('to_id',$user->id)->get();
            $games = GameTransaction::where('user_id',$user->id)->get();
            
            if(true){
                $response = [
                'status' => true,
                "message" => "Site Added Sucessfully Added",
                "user"=> $user,
                "coin_sent_transactions" => $transaction,
                "coin_recieved_transactions" => $transactions,
                "games" => $games,

             ];
                return response($response, 200);
            }
        }
        
        
    public function Bet(Request $request)
        {
        $rules =array(
            "token" => "required",
            "game_id" => "required",
            "amount" => "",
            "multiply_value" => "required",
            "status" => "required",
            "team_id" => "required"
            // "update" => "required"
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('usertoken',$request->token)->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            }
            $user = User::where('usertoken',$request->token)->first();
            // if(GameTransaction::where('user_id',$user->id)->where('game_id',$request->game_id)->first())
            // {
            //     if($request->amount>$user->user_coin){
            //         return response(["status" =>"error", "message"=>"You dont have enough money to buy a bet", ], 401);
            //     }
            //     $game = GameTransaction::where('user_id',$user->id)->where('game_id',$request->game_id)->first();
            //     if($request->update==0){
            //          $game->amount = $request->amount+ $game->amount;
            //          $user->user_coin = $user->user_coin-$request->amount;
            //          $user->save();
            //     }
            // }
            // else{
                $game = new GameTransaction;
                $game->amount = $request->amount;
                if($request->amount>$user->user_coin){
                    return response(["status" =>"error", "message"=>"You dont have enough money to buy a bet", ], 401);
                }
                $user->user_coin = $user->user_coin-$request->amount;
                $user->save();
            // }
            $game->user_id = $user->id;
            $game->game_key = $user->game_key;
            $game->team_id = $request->team_id;
            $game->game_id = $request->game_id;
            $game->multiply_value = $request->multiply_value;
            $game->status = $request->status;
            $game->reward_amount = $request->amount*$request->multiply_value;
            $game->save();

            if(!(ModelsCollection::where('game_id',$request->game_id)->first())){
                $collection = new ModelsCollection();
                $collection->game_id = $request->game_id;
                $collection->game_type = $request->game_type;
                $collection->save();
            }

            if(true){
                $response = [
                'status' => true,
                "message" => "Bet Sucessfully Added",
                "game"=> $game,
             ];
                return response($response, 200);
            }
        }
        public function Cron(Request $request)
        {
        $data = ModelsCollection::where('is_end','0')->get();
        foreach($data as $row){
            $game_id = $row->game_id;
            $url = "https://betfair-sportsbook.p.rapidapi.com/markets-by-match?matchid=".$game_id;
    
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'X-RapidAPI-Key: 290fd5be0fmsh6cbd06c612060b1p174658jsn61b406721b77',
                    'X-RapidAPI-Host: betfair-sportsbook.p.rapidapi.com'
                ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                // echo $response;
                $res = json_decode($response, true);
                if(isset($res[0])){
                    $temp = ModelsCollection::where('game_id',$game_id)->first();
                    $temp->data = $response;
                    $temp->count = $temp->count +1;
                    $temp->save();
                     
                }else{
                    $temp2 = GameTransaction::where('game_id',$game_id)->where('is_completed',0)->get();
                    foreach($temp2 as $t){
                        // if(($t->reward_amount>0)&&($t->is_withdrawl==0)){
                        //     $temp3 = User::where('id',$t->user_id)->first();
                        //     $temp3->user_coin = $temp3->user_coin + $t->reward_amount;
                        //     $temp3->save();
                        //     $t->is_withdrawl = '1';
                        // }
                        $t->is_completed = '1';
                        $t->status = 'completed';
                        $t->save();
                    }
                    $temp = ModelsCollection::where('game_id',$game_id)->first();
                    $temp->is_end = 1;
                    $temp->save();
                }
        }  
    }
    public function getMatch(Request $request)
        {
        $rules =array(
            "token" => "required",
            "game_id" => "required",
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('usertoken',$request->token)->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            }
            if(!ModelsCollection::where('game_id',$request->game_id)->first()){
                return response(["status" =>"failed", "message"=>"Invaild Match Id"], 401);
            }
            $match  = ModelsCollection::where('game_id',$request->game_id)->first();
            if($match->is_end==1){
                $s = "Completed";
            }
            else{
                $s = "Running";
            }
            
            if(true){
                $response = [
                'status' => true,
                "message" => "Match Details Fetched Success!",
                "server-api-hit-count"=> $match->count,
                "match-status" => $s,
                "game_id" => $match->game_id,
                "game_data" => $match->data,
             ];
                return response($response, 200);
            }
        } 
    
         public function addgame(Request $request)
        {
        $rules =array(
            "token" => "required",
            "game_id" => "required"
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('usertoken',$request->token)->where('user_type','s_admin')->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            }
           
$game  = new Game();
$game->game_id = $request->game_id;
$game->save();
            if($game->save()){
                $response = [
                'status' => true,
                "message" => "Game Added Sucessfully Added",
             ];
                return response($response, 200);
            }
        }
        
        
        public function earnamount(Request $request)
        {
        $rules =array(
            "token" => "required",
            "id" => "required",
            "amount" => "required"
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('usertoken',$request->token)->where('user_type','user')->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            }
            $t = GameTransaction::where('id',$request->id)->where('is_completed',1)->first();
                        if(($request->amount>0)&&($t->is_withdrawl==0)){
                            $temp3 = User::where('id',$t->user_id)->first();
                            $temp3->user_coin = $temp3->user_coin + $request->amount;
                            $temp3->save();
                            $t->is_withdrawl = '1';
                            $t->save();
                        }
                        
            if($t->save()){
                $response = [
                'status' => true,
                "message" => "Reward Added is Wallet",
             ];
                return response($response, 200);
            }
        }
        
    public function addwithdrawl(Request $request)
        {
        $rules =array(
            "token" => "required",
            "amount" => "required"
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            if(!User::where('usertoken',$request->token)->where('user_type','user')->first())
            {
                return response(["status" =>"failed", "message"=>"Invaild User Self Token"], 401);
            }
            }
            $temp = User::where('usertoken',$request->token)->where('user_type','user')->first();
            if($temp->user_coin>=$request->amount&&$request->amount>0){
                $temp->user_coin = $temp->user_coin - $request->amount;
                $temp->save();
                $temp2 = new Withdrawl();
                $temp2->user_id = $temp->id;
                $temp2->amount = $request->amount;
                
                $temp2->save();
            }else{
                 $response = [
                'status' => false,
                "message" => "Enter Amount Less than or Equal to Availiable Balance",
             ];
                 return response($response, 400);
            }
            if($temp->save()){
                $response = [
                'status' => true,
                "message" => "Request is Added to Admin",
             ];
                return response($response, 200);
            }
        }
        
        
    public function getgame(Request $request)
        {
        $rules =array(
        );
        $validator= Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else{
            
            }
            ;
            $games = Game::get();
            
            if(true){
                $response = [
                'status' => true,
                "games" => $games,

             ];
                return response($response, 200);
            }
        }
        
}
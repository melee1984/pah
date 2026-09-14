<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Partners;
use Auth;
use App\Mail\MerchantResetMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\RestaurantEnrollmentDocument;

use Carbon\Carbon;
use Str;
use DB;
use URL;

class MerchantController extends Controller
{
    public function updateOnlineStatus(Partners $partner, Request $request) {

		  $data['message'] = "Unable to process your request. Please refresh your page and again.";

   		if ($partner) {
   			$partner->store_open = $partner->store_open?false:true;
   			$partner->save();

   			$data['store_online'] = $partner->store_open;
        $data['message'] = "Successfully updated store availability";
   			$data['status'] = 1;
   		}
   		else {
   			$data['status'] = 0;
   		}

   		return response()->json($data, 200);

   	}

    public function updateStatus(Partners $partner, Request $request) {
      if ($partner->agent_id) {
        return response()->json(['status' => 0, 'message' => 'Use the restaurant application review to change this account status.'], 422);
      }
      if (! $partner->active && $partner->missingEnrollmentDocuments()) {
        return response()->json(['status' => 0, 'message' => 'Cannot activate this restaurant. Required enrollment documents are missing.'], 422);
      }
      
      $data = array();

      $data['message'] = "Unable to process your request. Please refresh your page and again.";

       if ($partner) {
         $partner->active = $partner->active?false:true;
         $partner->save();

         $data['message'] = "Successfully updated status";
         $data['status'] = 1;
       }
       else {
         $data['status'] = 0;
       }

       return response()->json($data, 200);
      }

      public function verify(Partners $partner, Request $request) {
        if ($partner->agent_id) {
          return response()->json(['status' => 0, 'message' => 'Use the restaurant application review to change verification status.'], 422);
        }
        if (! $partner->verified_at && $partner->missingEnrollmentDocuments()) {
          return response()->json(['status' => 0, 'message' => 'Cannot verify this restaurant. Required enrollment documents are missing.'], 422);
        }
      
        $data = array();

        $data['message'] = "Unable to process your request. Please refresh your page and again.";

         if ($partner) {
           if ($partner->verified_at=="") {
              $partner->verified_at = now(); 
              $partner->verified_by = Auth::User()->id;
              $partner->save();

              $data['message'] = "Successfully verified account";
              $data['status'] = 1;
           }
           else {
              $partner->verified_at = null; 
              $partner->verified_by = Auth::User()->id;
              $partner->save();

              $data['message'] = "Successfully verified account";
              $data['status'] = 1;
           }
         }
         else {
           $data['status'] = 0;
         }

         return response()->json($data, 200);
    }

    public function viewEnrollmentDocument(Partners $partner, RestaurantEnrollmentDocument $document)
    {
        abort_unless($document->partner_id === $partner->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path, $document->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    public function enrollmentDocuments(Partners $partner)
    {
        return response()->json([
            'documents' => $partner->enrollmentDocuments()->get()->map(fn ($document) => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'original_name' => $document->original_name,
                'url' => route('dashboard.merchant.documents.show', [$partner, $document]),
            ]),
            'missing' => $partner->missingEnrollmentDocuments(),
        ]);
    }

    public function preorder(Partners $partner, Request $request) {
      
        $data = array();

        $data['message'] = "Unable to process your request. Please refresh your page and again.";


         if ($partner) {
              $partner->is_pre_order = $partner->is_pre_order?0:1;
              $partner->save();

              $data['message'] = "Successfully update account";
              $data['status'] = 1;
         }
         else {
           $data['status'] = 0;
         }

         return response()->json($data, 200);
    }

    public function coomrate(Partners $partner, Request $request) {
      
        $data = array();

        $data['message'] = "Unable to process your request. Please refresh your page and again.";


         if ($partner) {
              $partner->pahatud_comm_discount = $partner->pahatud_comm_discount?0:1;
              $partner->save();

              $data['message'] = "Successfully update account";
              $data['status'] = 1;
         }
         else {
           $data['status'] = 0;
         }

         return response()->json($data, 200);
    }

    public function addup(Partners $partner, Request $request)
    {
        $partner->addup = ! $partner->addup;
        $partner->save();

        return response()->json([
            'message' => 'Successfully updated Add-Up Settings',
            'status' => 1,
        ], 200);
    }

    public function passwordSend(Partners $partner, Request $request) {
      
        $data = array();

        $data['message'] = "Unable to process your request. Please refresh your page and again.";

         if ($partner) {

              $user = $partner->partner_user;
              $token = Str::random(60);

               //create a new token to be sent to the user. 
              DB::table('password_resets')->insert([
                  'email' => $user->email,
                  'token' => $token, //change 60 to any length you want
                  'created_at' => Carbon::now()
              ]);

              $data = array('company_name' => $partner->restaurant_name, 
                  'resetURL' =>  URL::to('merchant/reset-password/'.$token),
                  'email' => $user->email);

              $when = Carbon::now()->addMinutes(1);

              $sentEmail = Mail::to($user->email)
                    ->later($when, new MerchantResetMail($data));
                    
              
              // return new MerchantResetMail($data);  
                    
              $sentEmail = true;

              //
              $partner->send_welcome_message = 1;
              $partner->save();

              $data['message'] = "Successfully generated email";
              $data['status'] = 1;
         }
         else {
           $data['status'] = 0;
         }

         return response()->json($data, 200);
    }

    

    
}

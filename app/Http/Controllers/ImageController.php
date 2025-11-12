<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

use Image;

class ImageController extends Controller
{
  
  /**
   * Image Caching for Logo and Banner 
   * @param  Request $request [description]
   * @return [type]           [description]
   */
  public function image(Request $request) 
  {
  	$src = @$request->input('name');
  	$size = @$request->input('s');
  	$for = @$request->input('for');

  	$cacheimage = Image::cache(function($image) use ($src, $size, $for) {

        if ($for == "logo") {
          return $image->make("uploads/user/".$src)->resize(350, 350, function ($constraint) {
                $constraint->aspectRatio();
            });    
        }
        else if($for=="banner") {
             return $image->make("uploads/user/".$src)->resize(250, 250, function ($constraint) {
                $constraint->aspectRatio();
            }); 
        }
        else {

           if ($size == 'thumb') {
           return $image->make("uploads/".$src)->resize(350, 350, function ($constraint) {
                $constraint->aspectRatio();
            });    
          }
          elseif ($size == 'banner') {
            return $image->make("uploads/".$src)->resize(500, 250, function ($constraint) {
                $constraint->aspectRatio();
            }); 
          }
          elseif ($size == 'orig') {
           return $image->make('uploads/'.$src)->resize(500, 500, function ($constraint) {
                $constraint->aspectRatio();
            }); 
          }
      }

    }, 36000, false); 

  	return Response::make($cacheimage, 200, array('Content-Type' => 'image/jpeg'));

  }

  public function imageResize(Request $request) {

      $src = @$request->input('name');
      $for = @$request->input('for');
      $width = @$request->input('w');
      $height = @$request->input('h');

      if ($for == 'logo') {

          $cacheimage = Image::cache(function($image) use ($src, $width, $height) {
            if ($height == "") {
                return $image->make("uploads/user/".$src)->resize($width, null, function ($constraint) {
                      $constraint->aspectRatio();
                      $constraint->upsize();
                });
            }
            else {
                return $image->make("uploads/user/".$src)
                    ->resize($width, $height);    
            }
            
          }, 36000, false); 
      }
      elseif ($for == 'thumb') {

          $cacheimage = Image::cache(function($image) use ($src, $width, $height) {
            
            if ($height == "") {
                return $image->make("uploads/".$src)->resize($width, null, function ($constraint) {
                      $constraint->aspectRatio();
                      $constraint->upsize();
                });
            }
            else {
              
                return $image->make("uploads/".$src)
                    ->resize($width, $height);    
            }
            
          }, 36000, false); 
      }
      elseif ($for == 'banner') {


          $cacheimage = Image::cache(function($image) use ($src, $width, $height) {
        
            if ($height == "") {
              return $image->make("uploads/user/".$src)->resizeCanvas($width, null, 'center', false); 
            }
            else {
               return $image->make("uploads/user/".$src)->resize($width, $height, function($constraint) {
                    $constraint->aspectRatio();
               }); 
   
            }

          }, 36000, false); 

      }

    return Response::make($cacheimage, 200, array('Content-Type' => 'image/jpeg'));

  }

}

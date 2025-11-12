<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Category;
use Str;

class CategoryController extends Controller
{
    //
    public function getList() 
    {	
    	$data = array();

		$categories = Category::whereUserId(Auth::User()->id)
						->with('parent')
    					->orderby('name','asc')
    					->orderby('parent_category_id','asc')
    					->paginate(50);

    	$data['categories'] = $categories;
    	$data['parent_category'] = Category::whereNull('parent_category_id')->whereUserId(Auth::User()->id)->get();

    	return response()->json($data, 200);
    }

    public function updateStatus(Category $category, Request $request) {

    	$data = array();

    	$category->active = $category->active ? 0 : 1;
		$status = $category->save();	

		if ($status) {
			$data['message'] = "Successfully updated status";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}

    	return response()->json($data, 200);

    }

    public function store(Request $request) {

    	$validatedData = $request->validate([
			'name' => 'required|max:75',
	    ]);

		$status = Category::create([
			'user_id'	=> Auth::User()->id,
	    	'name' => $request->input('name'),
	    	'parent_category_id' => $request->input('parent_category_id')?$request->input('parent_category_id'):null,
	    	'identifier' => Str::slug($request->input('name')),
	    	'active'	=> $request->input('active')?1:0,
		]);
	   
		if ($status) {
			$data['message'] = "Successfully added new category";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);

    }	
    public function update(Category $category, Request $request) {

    	$validatedData = $request->validate([
			'name' => 'required|max:75',
	    ]);

		$category->identifier = Str::slug($request->input('name'));
    	$category->parent_category_id = $request->input('parent_category_id');
    	$category->name = $request->input('name');
    	$category->active = $request->input('active');
    	$status = $category->save();

		if ($status) {
			$data['message'] = "Successfully updated category";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
		
		return response()->json($data, 200);

    }	

    public function destroy(Category $category) {

    	$data = array();
    	$cat = Category::find($category->id);
    	$status = $cat->delete();

    	if ($status) {
			$data['message'] = "Successfully delete category";
			$data['status'] = 1;
		}
		else {
			$data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
			$data['status'] = 0;	
		}
	
		return response()->json($data, 200);
	}

}

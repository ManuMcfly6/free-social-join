<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use \App\Models\Post;

class PostsController extends Controller
{
    public function __construct(){

        $this->middleware('auth');
    }

    public function index(){

        //pluck find the specific key in array ,that you passed a return the values

        $users=auth()->user()->following()->pluck('profiles.user_id');
        
        $posts=null;
        if(count($users)>0){
             $posts=Post::WhereIn('user_id',$users)->with('user')->latest()->paginate(5);
            
             //In case you not follow any 
        }else{
           $posts=Post::Where('user_id','>',0)->with('user')->latest()->paginate(5);
        }

        
        


        return view('posts.index',compact('posts'));
    }
    public function create(){

        return view('posts.create');
    }
    public function store(){

        $data=request()->validate([
            'caption'=>'required',
            'image'=>['required','image'],
        ]);
            
            $imagePath="uploads/" . request('image')->hashName();
           $image=Image::make(request('image'))->fit(1200,1200)->encode('jpg');

           Storage::disk('s3')->put($imagePath,(string)$image);
            

        auth()->user()->posts()->create([
            'caption'=>$data['caption'],
            'image'=>$imagePath,
            ]);

        return redirect('profile/'. auth()->user()->id);
        
    }
    public function show(Post $post){

        

        $follows=(auth()->user())? auth()->user()->following->contains($post->user->id):false;


        return view("posts.show",compact('post','follows'));
    }

    public function destroy(Post $post){

         
        //remove img from s3
        Storage::disk('s3')->delete($post->image);
        $postToDelete=Post::find($post->id);

        $postToDelete->delete();

        return redirect('/profile/' .  auth()->user()->id);

    }
}

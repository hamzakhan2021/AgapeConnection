<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Marker;
use App\TrainingFile;
use App\User;
use App\UserProfile;
use Couchbase\UserSettings;
use Exception;
use File;
use DB;
Use App\Like;
Use App\Comment;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $user = Auth::user();
        $response = [
            'response'   => 0,
            'message'    => 'No record found'
        ];
       if($user) {
           $usersAll = User::with('userGallery')->get();
           if($usersAll) {
               $response = [
                   'response'   => 1,
                   'message'    => 'All Users',
                   'data'       => $usersAll
               ];
           }
       }
       return response($response , 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                  => 'string|max:255',
            'email'                 => 'required|string|email|max:255',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
            'date_of_birth'         => 'required',
        ]);

        if ($validator->fails())
        {
            return response([
                'response'  => 0,
                'message'    =>$validator->errors()->all()], 422);
        }
        $user = Auth::user();
        $updateUser = User::where('id',$user->id)->first();
        if(!is_null($updateUser)) {
            $updateUser = User::find($user->id);
            $updateUser->name           = $request->name ?  $request->name : $user->name;
            $updateUser->email          = $request->email ? $request->email :$user->email;
            $updateUser->password       = $request->password ? $request->password : $user->password;
            $updateUser->date_of_birth  = $request->date_of_birth ? $request->date_of_birth : $user->date_of_birth;
            $updateUser->save();
            $response = [
                'response'      => 1,
                'message'       => 'User Successfully Updated',
                'data'          => $updateUser
            ];
        } else {
            $response = [
                'response'      => 1,
                'message'       => 'User does not exist',
                'data'          => null
            ];
        }

        return response($response, 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function getLikes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'picture_id'                  => 'string|max:255',
            'like'                        => 'required|boolean',
        ]);

        if ($validator->fails())
        {
            $error = implode(',', $validator->errors()->all());
            return response([
                'response'   => 0,
                'message'    => $error], 200);
        }
        $user = Auth::user();
        if(!is_null($user)) {
            $userPicture = UserProfile::where('id',$request->picture_id)->first();
            if(!is_null($userPicture)) {
                $like = Like::create([
                    'user_id'    =>    $user->id,
                    'picture_id' =>    $userPicture->id,
                    'status'     =>    $request->like    
                ]);
                $totalLikes = Like::where('user_id',$user->id)->get()->count();            
                $response = [
                    'response'      => 1,
                    'message'       => 'Picture Likes added Successfully',
                    'total_likes'   =>  isset($totalLikes) ? $totalLikes : 0,
                ];
            } else {
                $response = [
                    'response'      => 0,
                    'message'       => 'Picture not exist',
                ];
            }
            
        } else {
            $response = [
                'response'      => 0,
                'message'       => 'User not exist',
            ];
        }

        return response($response, 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function getComments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'picture_id'                  => 'required|max:255',
            'comment'                     => 'required|string',
        ]);

        if ($validator->fails())
        {
            $error = implode(',', $validator->errors()->all());
            return response([
                'response'   => 0,
                'message'    => $error], 200);
        }
        $user = Auth::user();
        if(!is_null($user)) {
            $userPicture = UserProfile::where('id',$request->picture_id)->first();
            if(!is_null($userPicture)) {
                $like = Comment::create([
                    'user_id'    =>    $user->id,
                    'picture_id' =>    $userPicture->id,
                    'comment'    =>    $request->comment    
                ]);
                $totalLikes = Comment::where('user_id',$user->id)->get()->count();          
                $response = [
                    'response'          => 1,
                    'message'           => 'Picture Comments added Successfully',
                    'total_comments'    => isset($totalLikes) ? $totalLikes : 0,
                ];
            } else {
                $response = [
                    'response'      => 0,
                    'message'       => 'Picture not exist',
                ];
            }
            
        } else {
            $response = [
                'response'      => 0,
                'message'       => 'User not exist',
            ];
        }

        return response($response, 200);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function uploadGalleryImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'    => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails())
        {
            return response([
                'response'  => 0,
                'message'    =>$validator->errors()->all()], 422);
        }

        $user = Auth::user();
        if ($user) {
            $file       = $request->file('file');
            $extension  = $file->getClientOriginalExtension();
            $uniqueId   = uniqid();
            $fileName   = "{$user->id}_{$uniqueId}";
            $imageFile  = "{$fileName}".'.'.$extension;
            $storage    = \Storage::disk('public');
            $filePath   = "/images/".$imageFile;

            if($storage->put($filePath, file_get_contents($file), 'public'))
            {
                UserProfile::create([
                    'user_id' => $user->id,
                    'image'   => "/storage{$filePath}"
                ]);
                $this->responseData = [
                    'response'  => 1,
                    'message'   => "File uploaded successfully",
                    'data'      => ['fileName' => "/storage{$filePath}"],
                ];
            } else {
                $this->responseData = [
                    'response' => 0,
                    'message'  => "File not uploaded",
                        ];
            }
        } else {
            $this->responseData = [
                'response' => 0,
                'message'  => "User not exist",
            ];
        }
        return response()->json($this->responseData, 200);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function searchByName(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
        ]);

        if ($validator->fails())
        {
            return response([
                'response'  => 0,
                'message'    =>$validator->errors()->all()], 422);
        }

        $name = $request->get('name');
        $usersData = User::with('userGallery')->where('name', 'LIKE', "%{$name}%")->get()->toArray();
        if($usersData != []){
            $response = [
                'response'  => 1,
                'message'  => 'User found',
                'data'      => $usersData
            ];

        } else {
           $response = [
               'response'  => 0,
                'message'  => 'User not found'
           ];
        }

        return response($response, 200);
    }



    public function searchByLatLng(Request $request)
    {   
        $lat = $request->get('lat');
        $lng = $request->get('lng');
        $distance = 50;

        $query = Marker::getByDistance($lat, $lng, $distance);

        if(empty($query)) {
            return 0;
        }

        $ids = [];

        //Extract the id's
        foreach($query as $q)
        {
            array_push($ids, $q->id);
        }

        // Get the listings that match the returned ids
        $results = User::whereIn( 'id', $ids)->get();
        $response = [
            'response' => 1,
            'message'  => 'User found',
            'data'     => $results
        ];


        return response($response, 200);
    }

    public function removeFile(Request $request) {
        $params = $request->input('params');
        if($params['file']){
            $userId = Auth::user()->id;
            $cleanFileName = _clean_string($params['file']);
            $this->tempDir = 'storage/'.$userId.'/temp';
            $this->filesDir = 'storage/'.$userId.'/files';
            $this->slidesDir = 'storage/'.$userId.'/slides';
            $this->removeSlides($cleanFileName, $this->tempDir);
            if($params['fileId'] and $params['trainingId']){
                TrainingFile::where(['training_id' => $params['trainingId'], 'id' => $params['fileId']])->delete();
                File::delete($this->filesDir.'/'.$cleanFileName);
                $this->removeSlides($cleanFileName, $this->slidesDir);
            }
            File::delete($this->tempDir.'/'.$cleanFileName);
            $this->removeSlides($cleanFileName, $this->tempDir);
            $this->responseData['response'] = 1;
            $this->responseData['message'] = "File deleted from ". $this->tempDir.'/'.$cleanFileName;
            $this->responseData['data'] = '';
            return $this->successResponse();
        }
    }

    function cleanDirectory() {
        $fileSystem = new Filesystem;
        $fileSystem->cleanDirectory('storage/files/tmp/');
        $this->responseData['response'] = 1;
        $this->responseData['message'] = "Directory Cleaned";
        $this->responseData['data'] = '';
        return $this->successResponse();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function uploadProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'    => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails())
        {
            return response([
                'response'  => 0,
                'message'    =>$validator->errors()->all()], 422);
        }

        $user = Auth::user();
        if ($user) {
            $file       = $request->file('file');
            $extension  = $file->getClientOriginalExtension();
            $uniqueId   = uniqid();
            $fileName   = "{$user->id}_{$user->name}_{$uniqueId}";
            $imageFile  = "{$fileName}".'.'.$extension;
            $storage    = \Storage::disk('public');
            $filePath   = "/images/".$imageFile;

            if($storage->put($filePath, file_get_contents($file), 'public'))
            {
//                UserProfile::create([
//                    'user_id' => $user->id,
//                    'image'   => "/storage{$filePath}"
//                ]);
                $userProfileImage = User::find($user->id);
                if ($userProfileImage) {
                    $userProfileImage->profile_photo = "/storage/app/public{$filePath}" ?? null;
                    $userProfileImage->save();
                }
                $this->responseData = [
                    'response'  => 1,
                    'message'   => "File uploaded successfully",
                    'data'      => ['fileName' => "/storage/app/public{$filePath}"],
                ];
            } else {
                $this->responseData = [
                    'response' => 0,
                    'message'  => "File not uploaded",
                ];
            }
        } else {
            $this->responseData = [
                'response' => 0,
                'message'  => "User not exist",
            ];
        }
        return response()->json($this->responseData, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $response = [
            'response'   => 0,
            'message'    => 'No record found'
        ];
        if(!is_null($id)){
            $user = Auth::user();
            if($user){
                $usersAll = User::with('userGallery')->where('id',$id)->first();
                if($usersAll){
                    $response = [
                        'response'   => 1,
                        'message'    => 'User found',
                        'data'       => $usersAll
                    ];
                }
            }
        }
        return response($response , 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

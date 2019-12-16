<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\TrainingFile;
use App\User;
use Exception;
use File;
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
        //
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
            'email'                 => 'required|string|email|max:255|unique:users',
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

    public function uploadImage(){
        $ecib       = Ecib::find($id);
        $file       = $request->file('file');
        $extension  = $file->getClientOriginalExtension();
        $uniqueId = uniqid();
        $fileName   = "{$ecib->cnic}_{$ecib->unique_key}_{$uniqueId}";
        $pdfFile   = "{$fileName}".'.'.$extension;
        $textFile   = "{$fileName}".'.txt';
        $storage = \Storage::disk('public');
        $filePath = "/ecib/".$pdfFile;

        if($storage->put($filePath, file_get_contents($file), 'public')){
            $this->responseData['data'] = [
                'fileName' => "/storage{$filePath}",
                'credit_details' => [],
                'file_cnic' => ''
            ];
            $storedPdfFile =  storage_path()."/app/public".$filePath;
            $textFile =   storage_path()."/app/public/ecib/".$textFile;
            if(file_exists($storedPdfFile)){
                shell_exec("/usr/bin/pdftotext -layout -eol unix $storedPdfFile $textFile");
                if(file_exists($textFile)){
                    $content = file_get_contents($textFile);
                    $this->responseData['data']['credit_details'] = $this->getCreditDetails($content);
                    $this->responseData['data']['file_cnic'] = $this->getNewCnic($content);
                }
            }
            $this->responseData['message'] = "File uploaded successfully";
        }else {
            $this->responseData['response'] = 0;
        }
        return response()->json($this->responseData, $this->httpCode);
    }

    public function uploadFile(Request $request) {
        if($request->file('file')){
            $userId = \Auth::user()->id;
            $this->tempDir = 'storage/'.$userId.'/temp';
            $file = $request->file('file');
            try {
                if(!file_exists($this->tempDir)) {
                    $this->createDirectories();
                }
                $AllowedFiles = ['pdf', 'mp4', 'png', 'jpg', 'jpeg'];
                if(!in_array($file->getClientOriginalExtension(), $AllowedFiles)){
                    throw new Exception('only pdf, mp4, jpg files are allowed');
                }
                $cleanFileName = _clean_string($file->getClientOriginalName());
                if( $file->move($this->tempDir, $cleanFileName) ) {
                    if(in_array($file->getClientOriginalExtension(), ['pdf'])){
                        $toImage = $this->pdfToImage($cleanFileName);
                        if($toImage['response'] == 1) {
                            File::delete($this->tempDir.'/'.$cleanFileName);
                            $this->responseData['response'] = 1;
                            $this->responseData['message'] = "slides created";
                            $this->responseData['data'] = $toImage['data'];
                        }
                    }
                    return $this->successResponse();
                }
            } catch(Exception $e) {
                $data['response'] = 0;
                $data['message'] = $e->getMessage();
                return $data;
            }
        }
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
        //
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

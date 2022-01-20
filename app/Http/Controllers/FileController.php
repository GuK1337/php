<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileRequest;
use App\Models\Folder;
use App\Models\File;
use App\Models\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\Exception;
use PhpParser\Node\Scalar\String_;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use function React\Promise\all;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request, String $folderId)
    {

        $folder = Folder::findOrFail($folderId);
        if(!in_array(Auth::id(), json_decode($folder['users'])) and $folder['users'] != null){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        $files = File::where([
            ['folder_id','=', $folder['id']],
        ])->get();
        $result = [];

        $files = File::where([
            ['folder_id','=', $folder['id']],
        ])->get()->toArray();

        $folders = Folder::where([
            ['parent','=', $folder['id']],
        ])->get()->toArray();

        foreach ($files as $file){
            $users = null;

            if($file['users'] != null){
                $users = [];
                $userList = json_decode($file['users']);
                foreach ($userList as $userId){
                    $userData = User::findOrFail($userId);
                    $users[] = [
                        'fullname'=>$userData['name'],
                        'email'=>$userData['email'],
                        'type'=>$userData['id'] == $file['author'] ? 'author' : 'co-author'
                    ];
                }
            }
            $result[] = [
                'type'=> 'file',
                'file_id'=> $file['id'],
                'name'=> $file['name'],
                'url' => URL::to('/folders/'. $folder['id'].'/files/'.$file['id']),
                'accesses'=>$users == null ? 'all' : $users,
            ];
        }

        foreach ($folders as $childFodler){
            $users = null;
            if($childFodler['users'] != null){
                $users = [];
                $userList = json_decode($childFodler['users']);
                foreach ($userList as $userId){
                    $userData = User::findOrFail($userId);
                    $users[] = [
                        'fullname'=>$userData['name'],
                        'email'=>$userData['email'],
                        'type'=>$userData['id'] == $file['author'] ? 'author' : 'co-author'
                    ];
                }
            }
            $result[] = [
                'type'=> 'folder',
                'file_id'=> $childFodler['id'],
                'name'=> $childFodler['name'],
                'url' => URL::to('/folders/'.$childFodler['id']),
                'accesses'=>$users == null ? 'all' : $users,
            ];
        }

        return response()->json($result);
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(FileRequest $request, String $folderId)
    {
        $request->validate([
            'data' => 'required|file',
        ]);
        $value = $request->validated();
        $value['author'] = Auth::id();
        if (in_array('users', $value)){
            $value['users'] = json_encode(array_merge([$request->user()->id], $value['users']));
        }
        else{
            $value['users'] = null;
        }

        $files = $request->file('data');
        $folder = Folder::findOrFail($folderId);
        if(!in_array(Auth::id(), json_decode($folder['users'])) and $folder['users'] != null){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        if(!is_array($files)){
            $files = [$files];
        }
        $createdFiles = [];
        foreach ($files as $file){
            $name = $file->getClientOriginalName();
            try{

                $fileName = $file->store(null, ['disk'=>'public']);
                $cratedFile = File::create([
                    'name' =>  $this->getFileName($name, $folder['id']),
                    'folder_id' => $folder['id'],
                    'path' => $fileName,
                    'users'=> $value['users'],
                    'author' => $value['author'],
                ]);
                $createdFiles[] = [
                    'success' => true,
                    'message' => 'Success',
                    'folder_id' => $cratedFile['folder_id'],
                    'name'=>  $cratedFile['name'],
                    'file_id'=>$cratedFile['id'],
                    'author' => $value['author'],
                ];
            }
            catch(Exception|FileNotFoundException $ex) {
                $createdFiles[] = [
                    'success' => false,
                    'message' => 'Error',
                ];
            }

        }
        return response() -> json($createdFiles);
    }

    /**
     * Display the specified resource.
     *
     * @param File $file
     * @return BinaryFileResponse|JsonResponse
     */
    public function show(Request $request, String $folderId, String $fileId): BinaryFileResponse | JsonResponse
    {
        $folder = Folder::findOrFail($folderId);
        $files = File::where([
            ['folder_id','=', $folder['id']],
            ['id','=',  $fileId],
        ])->get()->toArray();
        if(count($files) == 0){
            return response()->json([
                'success' => false,
                'message' => 'Error',
            ]);
        }
        $file = $files[0];
        if(!in_array(Auth::id(), json_decode($file['users'])) and $file['users'] != null){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if (Storage::disk('public')->exists($file['path'])) {
            $filePath = storage_path('app\public\\'.$file['path']);
            $ext = pathinfo( $file['name'], PATHINFO_EXTENSION);
            $headers = ['Content-Type: application/'.$ext];
            return response()->download($filePath, $file['name'], $headers);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Error',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param File $file
     * @return JsonResponse
     */
    public function edit(Request $request, String $folderId, String $fileId)
    {
        $request->validate([
            'name' => 'string',
            'id' => 'int',
        ]);
        $value = $request->all();
         $file = File::findOrFail($fileId);
         if (array_key_exists('folder_id', $value)){
             $file['folder_id'] = $folderId;
         }
         if(array_key_exists('name', $value) and strlen($value['name'] > 0)){

             $file['name'] = $this->getFileName($value['name'], $file['folder_id']);
         }
         $file->save();
        return response()->json([
            'success' => true,
            'message' => 'Success',
        ]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param File $file
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param File $file
     * @return JsonResponse
     */
    public function destroy(Request $request, String $folderId, String $fileId)
    {
        $folder = Folder::findOrFail($folderId);
        $files = File::where([
            ['folder_id','=', $folder['id']],
            ['id','=',  $fileId],
        ])->get();
        if(count($files) == 0){
            return response()->json([
                'success' => false,
                'message' => 'Error',
            ]);
        }
        $file = $files[0];
        if(!in_array(Auth::id(), json_decode($file['users'])) and $file['users'] != null){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        if($file->delete()){
            if (Storage::disk('public')->exists($file['path'])) {
                Storage::disk('public')->delete($file['path']);
            }
            return response()->json([
                'success' => true,
                'message' => 'Success'
            ], 200);

        }
        else{
            return response()->json([
                'success' => false,
                'message' => 'Error'
            ], 200);
        }

    }

    public function getFileName(String $oldName, string $parent): string
    {
        $anotherFolders = File::where('folder_id', $parent)->get()->toArray();
        $newName = $oldName;
        if (!empty($anotherFolders)){
            $hasSameName = false;
            foreach ($anotherFolders as $value){
                if ($newName == $value['name']){
                    $hasSameName = true;
                }
            }
            while ($hasSameName){
                $hasSameName = false;
                Log::info('old: '.$newName);
                $nameWithoutExt = pathinfo( $newName, PATHINFO_FILENAME);
                $ext = pathinfo( $newName, PATHINFO_EXTENSION);
                $numberStr = preg_match('/\(\d+\)/', $nameWithoutExt, $str);
                if($numberStr == 1){
                    preg_match('/\d+/',$str[0], $number);

                    $val = (int)$number[0] + 1;
                    Log::info('val: '.$number[0]);
                    $newName = substr($newName,0,strlen($nameWithoutExt) - strlen($str[0])).'('.$val.').'.$ext;
                }
                else{
                    $newName = $newName.'(1).'.$ext;
                }
                Log::info('new: '.$newName);
                foreach ($anotherFolders as $value){
                    if ($newName == $value['name']){
                        $hasSameName = true;
                    }
                }
            }
        }
        return  $newName;
    }

    public function addUser(Request $request, String $folderId, String $fileId)
    {
        $request->validate([
            'email'=>'email',
            'all'=>'true',
        ]);
        $value = $request-> all();
        $folder = Folder::findOrFail($folderId);
        $files = File::where([
            ['folder_id','=', $folder['id']],
            ['id','=',  $fileId],
        ])->get();
        if(count($files->toArray()) == 0){
            return response()->json([
                'success' => false,
                'message' => 'Error',
            ]);
        }
        $file = $files[0];
        if(Auth::id() != $file['author']){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        if(array_key_exists('all', $value) and $value['all'] == true){
            $file['users'] == null;
            $file->save();
        }
        else{
            if(array_key_exists('email', $value)){
                $userList = json_decode($file['users']);
                $user = User::where('email',$value['email'])->get()[0];
                if($userList == null){
                    $userList = [Auth::id()];
                }
                if(!in_array($user['id'], $userList)){
                    $userList[] = $user['id'];
                    $file['users'] = json_encode($userList);
                    $file->save();
                }
            }
        }
        $users = [];
        $userList = json_decode($file['users']);
        foreach ($userList as $userId){
            $userData = User::findOrFail($userId);
            $users[] = [
                'fullname'=>$userData['name'],
                'email'=>$userData['email'],
                'type'=>$userData['id'] == $file['author'] ? 'author' : 'co-author'
            ];
        }
        return response()->json($users);
    }


    public function removeUser(Request $request, String $folderId, String $fileId)
    {
        $request->validate([
            'email'=>'email',
            'all'=>'true',
        ]);
        $value = $request-> all();
        $folder = Folder::findOrFail($folderId);
        $files = File::where([
            ['folder_id','=', $folder['id']],
            ['id','=',  $fileId],
        ])->get();
        if(count($files->toArray()) == 0){
            return response()->json([
                'success' => false,
                'message' => 'Error',
            ]);
        }
        $file = $files[0];
        if(Auth::id() != $file['author']){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        else{
            if(array_key_exists('email', $value)){
                $userList = json_decode($file['users']);
                $user = User::where('email',$value['email'])->get()[0];

                if( $userList != null and in_array($user['id'], $userList,) and $file['author'] != $user['id']){

                    array_splice($userList, array_search($user['id'],$userList), 1);
                    $file['users'] = json_encode($userList);
                    $file->save();
                }
            }
        }
        $users = [];
        $userList = json_decode($file['users']);
        foreach ($userList as $userId){
            $userData = User::findOrFail($userId);
            $users[] = [
                'fullname'=>$userData['name'],
                'email'=>$userData['email'],
                'type'=>$userData['id'] == $file['author'] ? 'author' : 'co-author'
            ];
        }
        return response()->json($users);
    }
}

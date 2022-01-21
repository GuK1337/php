<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Models\File;
use App\Models\Folder;
use App\Http\Requests\FolderRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Nette\Utils\Json;
use PhpParser\Node\Scalar\String_;

class FolderController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $folders = Folder::all()->toArray();
        $userFolders = [];
        foreach ($folders as $value){
            if (in_array(Auth::id(), json_decode($value['users']))){
                $userFolders[] = $value;
            }

        }
        return response()->json($userFolders);
    }
    public function store(FolderRequest $request)
    {
        $value = $request->validated();
        if (!in_array('users', $value)){
            $value['users'] = json_encode([$request->user()->id]);
        }
        else{
            $value['users'] = json_encode(array_merge([$request->user()->id], $value['users']));
        }
        $value['id'] = $this->generateRandomString(10);
        $value['author'] = Auth::id();
        $value['name'] = $this->getFolderName($value['name'],$value['id'],  !array_key_exists('parent_id', $value) || $value['parent_id'] == 0 ? null : $value['parent_id']);
        $folder = Folder::create($value);
        return response()-> json([
            'success' => true,
            'message' => 'Success',
            'folder_id' => $value['id'],
        ]);
    }
    public function show(Folder $folder)
    {
        $folder = Folder::findOrFail($folder['id']);
        if(in_array(Auth::id(), json_decode($folder['users']))){
            $folders = Folder::where('parent', $folder['id'])->get();
            $files = File::where('folder_id', $folder['id'])->get();
            $result = [];

            foreach ($files as $file){
                $result[] = [
                    'type'=> 'file',
                    'file_id'=> $file['id'],
                    'name' => $file['name'],
                    'url' => URL::to('/folders/'. $file['folder_id'].'/files/'.$file['id']),
                    'accesses'=> $this->getUsers($file['users'], $file['author']),
                ];
            }
            foreach ($folders as $folder){
                $result[] = [
                    'type'=> 'file',
                    'file_id'=> $folder['id'],
                    'name' => $folder['name'],
                    'url' => URL::to('/folders/'. $folder['id']),
                    'accesses'=> $this->getUsers($folder['users'], $folder['author']),
                ];
            }
            return response()->json($result);
        }
        return response()->json([
            'success' => false,
            'message' => 'Access denied'
        ], 403);
    }

    public function update(Request $request, String $id)
    {
        $folder = Folder::findOrFail($id);
        if(!in_array(Auth::id(), json_decode($folder['users'])) and $folder['users'] != null){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $folder->fill($request->only(['name','parent']));
        $folder->fill([
            'name' => $this->getFolderName($folder['name'], $folder['id'], $request['parent']),
        ]);
        $folder->save();
        return response()->json([
            'success' => true,
            'message' => 'success'
        ]);
    }

    public function destroy(Request $request,$id)
    {
        $folder = Folder::findOrFail($id);
        if(!in_array(Auth::id(), json_decode($folder['users'])) and $folder['users'] != null){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        if($folder->delete())
            return response()->json([
                'success' => true,
                'message' => 'Success'
            ], 200);
    }

    public function getFolderName(String $oldName,String $id, string|null $parent){
        $anotherFolders = Folder::where('parent', $parent)->get()->toArray();
        $newName = $oldName;
        if (!empty($anotherFolders)){
            $hasSameName = false;
            foreach ($anotherFolders as $value){
                if ($oldName == $value['name'] AND $id != $value['id']){
                    $hasSameName = true;
                }
            }

            while ($hasSameName){
                $hasSameName = false;
                $numberStr = preg_match('/\(\d+\)$/',$newName, $str);
                if($numberStr == 1){
                    preg_match('/\d+/',$str[0], $number);
                    $val = (int)$number[0] + 1;
                    $newName = substr($newName,0,strlen($newName) - strlen($str[0])).'('.$val.')';
                }
                else{
                    $newName = $newName.'(1)';
                }
                foreach ($anotherFolders as $value){
                    if ($newName == $value['name'] && $value['id'] != $id){
                        $hasSameName = true;
                        Log::debug($newName);
                        Log::debug($value['name']);

                    }
                }
            }
        }
        return  $newName;
    }

    public function addUser(Request $request, String $folderId)
    {
        $request->validate([
            'email'=>'email',
            'all'=>'true',
        ]);
        $value = $request-> all();
        $folder = Folder::findOrFail($folderId);
        if(Auth::id() != $folder['author']){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        if(array_key_exists('all', $value) and $value['all'] == true){
            $folder['users'] == null;
            $folder->save();
        }
        else{
            if(array_key_exists('email', $value)){
                $userList = json_decode($folder['users']);
                $user = User::where('email',$value['email'])->get()[0];
                if($userList == null){
                    $userList = [Auth::id()];
                }
                if(!in_array($user['id'], $userList)){
                    $userList[] = $user['id'];
                    $folder['users'] = json_encode($userList);
                    $folder->save();
                }
            }
        }
        $users = [];
        $userList = json_decode($folder['users']);
        foreach ($userList as $userId){
            $userData = User::findOrFail($userId);
            $users[] = [
                'fullname'=>$userData['first_name'].' '.$userData['last_name'],
                'email'=>$userData['email'],
                'type'=>$userData['id'] == $folder['author'] ? 'author' : 'co-author'
            ];
        }
        return response()->json($users);
    }


    public function removeUser(Request $request, String $folderId, String $fileId)
    {
        $request->validate([
            'email'=>'email',
        ]);
        $value = $request-> all();
        $folder = Folder::findOrFail($folderId);
        if(Auth::id() != $folder['author']){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        else{
            if(array_key_exists('email', $value)){
                $userList = json_decode($folder['users']);
                $user = User::where('email',$value['email'])->get()[0];

                if( $userList != null and in_array($user['id'], $userList,) and $folder['author'] != $user['id']){
                    array_splice($userList, array_search($user['id'],$userList), 1);
                    $folder['users'] = json_encode($userList);
                    $folder->save();
                }
            }
        }

        return response()->json($this->getUsers($folder['users'], $folder['author']));
    }

    /**
     * Display all user files
     *
     * @return JsonResponse
     */
    public function getAllUserFiles(Request $request){
        $folders = Folder::where('author', Auth::id())->get();
        $files = File::where('author', Auth::id())->get();
        $result = [];

        foreach ($files as $file){
            $result[] = [
                'type'=> 'file',
                'file_id'=> $file['id'],
                'name' => $file['name'],
                'url' => URL::to('/folders/'. $file['folder_id'].'/files/'.$file['id']),
                'accesses'=> $this->getUsers($file['users'], $file['author']),
            ];
        }
        foreach ($folders as $folder){
            $result[] = [
                'type'=> 'file',
                'file_id'=> $folder['id'],
                'name' => $folder['name'],
                'url' => URL::to('/folders/'. $folder['id']),
                'accesses'=> $this->getUsers($folder['users'], $folder['author']),
            ];
        }

        return response()->json($result);
    }

    /**
     * Display all user files
     *
     * @return JsonResponse
     */
    public function getAllFiles(Request $request){
        $folders = Folder::all()->all();
        $files = File::all()->all();
        $result = [];

        foreach ($files as $file){
            Log::debug(Auth::id());
            Log::debug($file['users']);
            Log::debug(in_array(Auth::id(), (array)json_encode($file['users'])) ? 'true' : 'false');
           if($file['users']!= null and in_array(strval(Auth::id()), json_decode($file['users']))){
               $result[] = [
                   'type'=> 'file',
                   'file_id'=> $file['id'],
                   'name' => $file['name'],
                   'url' => URL::to('/folders/'. $file['folder_id'].'/files/'.$file['id']),
                   'accesses'=> $this->getUsers($file['users'], $file['author']),
               ];
           }
        }
        foreach ($folders as $folder){
            if(in_array(Auth::id().'', (array)json_encode($folder['users']))) {
                $result[] = [
                    'type' => 'file',
                    'file_id' => $folder['id'],
                    'name' => $folder['name'],
                    'url' => URL::to('/folders/' . $folder['id']),
                    'accesses' => $this->getUsers($folder['users'], $folder['author']),
                ];
            }
        }

        return response()->json($result);
    }

    /**
     * Display all user files
     *
     * @return array
     */
    private function getUsers(String|null $userList, int|null $author)
    {
        $users = [];

        if($userList!=null){
            $userList = json_decode($userList);
            foreach ($userList as $userId){
                $userData = User::findOrFail($userId);
                $users[] = [
                    'fullname'=>$userData['first_name'].' '.$userData['last_name'],
                    'email'=>$userData['email'],
                    'type'=>$userData['id'] == $author ? 'author' : 'co-author'
                ];
            }
        }
        return $users;
    }

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

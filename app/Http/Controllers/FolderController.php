<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Models\Folder;
use App\Http\Requests\FolderRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Utils\Json;
use PhpParser\Node\Scalar\String_;

class FolderController extends BaseController
{
    public function index()
    {
        $folders = Folder::all()->toArray();
        $userFolders = [];
        foreach ($folders as $value){
            if (in_array(Auth::id(), json_decode($value['users']))){
                $userFolders[] = $value;
            }

        }
        return  $userFolders;
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

        $folder = Folder::create($value);
        return $this::sendResponse([
            'success' => true,
            'message' => 'Success',
            'folder_id' => $folder['id']
        ],'OK');
    }
    public function show(Folder $folder)
    {
        $folder = Folder::findOrFail($folder['id']);
        if(in_array(Auth::id(), json_decode($folder['users']))){
            return response()->json([
                'success' => true,
                'folder' => $folder,
                'message' => null
            ], 200);
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

    public function getFolderName(String $oldName,String $id, string $parent){
        $anotherFolders = Folder::where('parent', $parent)->get()->toArray();
        $newName = $oldName;
        if (!empty($anotherFolders)){
            $hasSameName = false;
            foreach ($anotherFolders as $value){
                if ($oldName == $value['name'] AND $id != $value['id']){
                    $hasSameName = true;
                }
            }
            if ($hasSameName){
                $numberStr = substr($oldName, -3);
                if(preg_match('/\(\d+\)/', $numberStr) == 1){
                    preg_match('/\d+/',$numberStr, $number);
                    $val = (int)$number[1] + 1;
                    $newName = substr($oldName,0,strlen($oldName) - 3).'('.$val.')';
                }
                else{
                    $newName = $oldName.'(1)';
                }
            }

            while ($hasSameName){
                $hasSameName = false;
                $numberStr = substr($oldName, -3);
                if(preg_match('/\(\d+\)/', $numberStr) == 1){
                    preg_match('/\d+/',$numberStr, $number);

                    $val = (int)$number[0] + 1;
                    $newName = substr($newName,0,strlen($oldName) - 3).'('.$val.')';
                }
                else{
                    $newName = $newName.'(1)';
                }
                foreach ($anotherFolders as $value){
                    if ($newName == $value['name']){
                        $hasSameName = true;
                    }
                }
            }
        }
        return  $newName;
    }
}

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
        return Folder::all();
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
//            return response()->json([
//                'success' => true,
//                'folder' => $folder,
//                'message' => null
//            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'Access denied'
        ], 403);
    }

    public function update(Request $request, String $id): Folder
    {
//        $folder = Folder::findOrFail($id);
//        if(!in_array(Auth::id(), json_decode($folder['users']))){
//            return response()->json([
//                'success' => false,
//                'message' => 'Access denied'
//            ], 403);
//        }
        $user = User::where('email', $request->email)->first();
        $folders = Folder::where('parent_id', '2');
        return Folder::where('parent_id', '2')->all;

//        if(Folder::where('parent_id', $request['parent']) )
//
//        $folder->fill($request->except(['folder_id']));
//        $folder->save();
//        return response()->json($folder);
    }

    public function destroy(Folder $folder,$id)
    {
        $folder = Folder::findOrFail($id);
        if($folder->delete()) return response(null, 204);
    }
}

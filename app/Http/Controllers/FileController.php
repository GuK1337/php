<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileRequest;
use App\Models\Folder;
use App\Models\File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


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
    public function store(FileRequest $request)
    {
        $request->validate([
            'data' => 'required|file',
            'folder_id' => 'required|string'
        ]);
        $value = $request->validated();
        if (!in_array('users', $value)){
            $value['users'] = json_encode([$request->user()->id]);
        }
        else{
            $value['users'] = json_encode(array_merge([$request->user()->id], $value['users']));
        }
        $files = $request->file('data');
        $folder = Folder::findOrFail($request['folder_id']);
        if(!is_array($files)){
            $files = [$files];
        }
        $createdFiles = [];
        foreach ($files as $file){
            $name = $file->getClientOriginalName();
            try{

                $fileName = $file->store(null, ['public'=>'local']);
                $cratedFile = File::create([
                    'name' =>  $this->getFileName($name, $folder['id']),
                    'folder_id' => $folder['id'],
                    'path' => $fileName,
                ]);
                $createdFiles[] = [
                    'success' => true,
                    'message' => 'Success',
                    'folder_id' => $cratedFile['folder_id'],
                    'name'=>  $cratedFile['name'],
                    'file_id'=>$cratedFile['id']
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
    public function show(File $file): BinaryFileResponse | JsonResponse
    {
        $folder = Folder::findOrFail($file['folder_id']);
        File::where('folder_id', $parent)->get()->toArray();
        if(!in_array(Auth::id(), json_decode($folder['users']))){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        if (Storage::disk('public')->exists($file['path'])) {
            $filePath = public_path($file['path']);
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
     * @return \Illuminate\Http\Response
     */
    public function edit(File $file)
    {
        //
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
     * @return \Illuminate\Http\Response
     */
    public function destroy(File $file)
    {
        if(!in_array(Auth::id(), json_decode($file['users']))){
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        if($file->delete())
            return response()->json([
                'success' => true,
                'message' => 'Success'
            ], 200);
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
                $numberStr = substr($nameWithoutExt, -3);
                if(preg_match('/\(\d+\)/', $numberStr) == 1){
                    preg_match('/\d+/',$numberStr, $number);

                    $val = (int)$number[0] + 1;
                    Log::info('val: '.$number[0]);
                    $newName = substr($newName,0,strlen($nameWithoutExt) - 3).'('.$val.').'.$ext;
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
}

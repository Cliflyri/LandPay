<?php
namespace App\Services;
use App\Models\SharedDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class SharedDocumentStorageService{
 public function store(UploadedFile $file):array{$extension=match($file->getMimeType()){'application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx',default=>throw new \InvalidArgumentException('Unsupported document type.')};$path=$file->storeAs('shared-documents',Str::uuid().'.'.$extension,'local');return ['disk'=>'local','path'=>$path,'name'=>$file->getClientOriginalName(),'mime'=>$file->getMimeType(),'size'=>Storage::disk('local')->size($path)];}
 public function delete(SharedDocument $document):bool{$disk=Storage::disk($document->disk);return !$disk->exists($document->path)||$disk->delete($document->path);}
}

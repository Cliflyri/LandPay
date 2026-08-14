<?php
namespace App\Services;
use App\Models\SecureMessage;
use App\Models\SharedDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
class SecureMessageFileService{
 public function __construct(private readonly SharedDocumentStorageService $documents){}
 public function attach(SecureMessage $message,array $files,array $saveIndexes,array $documentIds,array $context):void{
  $stored=[];
  try{
   if($documentIds)$message->documents()->syncWithoutDetaching($documentIds);
   foreach(array_values($files) as $index=>$file){
    if(in_array((string)$index,$saveIndexes,true)||in_array($index,$saveIndexes,true)){
     $data=$this->documents->store($file);$stored[]=$data;
     $document=SharedDocument::query()->create($data+['client_id'=>$context['client_id'],'payment_plan_id'=>$context['payment_plan_id']??null,'uploaded_by_user_id'=>$context['user_id']??null,'uploaded_by_client_id'=>$context['uploader_client_id']??null,'category'=>$context['category']??'general','visible_to_client'=>true]);
     $message->documents()->attach($document->id);
    }else{
     $data=$this->storeAttachment($file);$stored[]=$data;
     $message->attachments()->create($data);
    }
   }
  }catch(Throwable $e){foreach($stored as $file){Storage::disk($file['disk'])->delete($file['path']);}throw $e;}
 }
 public function deleteAttachmentFile(object $attachment):bool{$disk=Storage::disk($attachment->disk);return !$disk->exists($attachment->path)||$disk->delete($attachment->path);}
 private function storeAttachment(UploadedFile $file):array{$extension=match($file->getMimeType()){'application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx',default=>throw new \InvalidArgumentException('Unsupported message file type.')};$path=$file->storeAs('secure-message-attachments',Str::uuid().'.'.$extension,'local');return ['disk'=>'local','path'=>$path,'name'=>$file->getClientOriginalName(),'mime'=>$file->getMimeType(),'size'=>Storage::disk('local')->size($path)];}
}

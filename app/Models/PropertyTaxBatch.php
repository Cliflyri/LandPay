<?php
namespace App\Models;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PropertyTaxBatch extends Model {
 use HasPublicUuid;
 protected $guarded=['id','uuid'];
 protected function casts():array{return ['issue_date'=>'date','due_date'=>'date','email_clients'=>'boolean','confirmed_at'=>'datetime'];}
 public function rows():HasMany{return $this->hasMany(PropertyTaxBatchRow::class)->orderBy('row_number');}
}

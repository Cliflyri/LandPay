<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PropertyTaxBatchRow extends Model {
 protected $guarded=['id'];
 protected function casts():array{return ['email_sent_at'=>'datetime'];}
 public function batch():BelongsTo{return $this->belongsTo(PropertyTaxBatch::class,'property_tax_batch_id');}
 public function paymentPlan():BelongsTo{return $this->belongsTo(PaymentPlan::class)->withTrashed();}
 public function invoice():BelongsTo{return $this->belongsTo(Invoice::class);}
 public function existingInvoice():BelongsTo{return $this->belongsTo(Invoice::class,'existing_invoice_id');}
}

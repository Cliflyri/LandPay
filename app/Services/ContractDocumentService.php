<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ContractDocument;
use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

class ContractDocumentService
{
    /** @param UploadedFile[] $templates */
    public function generate(array $templates, array $values, PaymentPlan $plan, Client $client, User $actor): array
    {
        $created = [];
        Storage::disk('local')->makeDirectory('contract-documents');

        try {
            foreach ($templates as $template) {
                $uuid = (string) Str::uuid();
                $path = "contract-documents/{$uuid}.docx";
                $processor = new TemplateProcessor($template->getRealPath());
                $processor->setValues($values);
                $processor->saveAs(Storage::disk('local')->path($path));

                $clientName = $client->organization_name ?: trim($client->first_name.' '.$client->last_name);
                $base = pathinfo($template->getClientOriginalName(), PATHINFO_FILENAME);
                $name = Str::limit(trim($clientName.' - '.$plan->apn.' - '.$base), 220, '').'.docx';
                $created[] = ContractDocument::query()->create([
                    'payment_plan_id' => $plan->id,
                    'client_id' => $client->id,
                    'created_by_user_id' => $actor->id,
                    'name' => $name,
                    'template_name' => $template->getClientOriginalName(),
                    'disk' => 'local',
                    'path' => $path,
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'size' => Storage::disk('local')->size($path),
                    'expires_at' => now()->addDays(30),
                ]);
            }
        } catch (\Throwable $exception) {
            foreach ($created as $document) {
                Storage::disk($document->disk)->delete($document->path);
                $document->delete();
            }
            throw $exception;
        }

        return $created;
    }

    public function delete(ContractDocument $document): void
    {
        if ($document->deleted_at) return;
        Storage::disk($document->disk)->delete($document->path);
        $document->update(['deleted_at' => now()]);
    }

    public function purgeExpired(): int
    {
        $count = 0;
        ContractDocument::query()
            ->whereNull('deleted_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($documents) use (&$count): void {
                foreach ($documents as $document) {
                    $this->delete($document);
                    $count++;
                }
            });

        return $count;
    }
}

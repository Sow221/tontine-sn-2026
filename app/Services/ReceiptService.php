<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ReceiptService
{
    private ?ImageManager $manager = null;

    private string $fontPath;

    public function __construct()
    {
        try {
            $this->manager = new ImageManager(['driver' => 'gd']);
        } catch (\Throwable $e) {
            Log::warning('ReceiptService: GD not available', ['error' => $e->getMessage()]);
        }

        $candidates = [
            __DIR__.'/../../vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf',
            storage_path('app'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'Inter-Regular.ttf'),
            'C:\Windows\Fonts\arial.ttf',
        ];

        $this->fontPath = $candidates[0];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $this->fontPath = $path;
                break;
            }
        }
    }

    public function generatePaymentReceipt(string $userName, int $amount, string $tontineName, string $date): string
    {
        if (! $this->manager) {
            return '';
        }

        $montant = number_format($amount, 0, ',', ' ');

        $img = $this->manager->canvas(520, 340, '#ffffff');

        $img->rectangle(0, 0, 519, 60, function ($draw) {
            $draw->background('#1a73e8');
        });

        $img->text('TontineSN', 260, 38, function ($font) {
            $font->file($this->fontPath);
            $font->size(22);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        $img->text('Reçu de paiement', 260, 95, function ($font) {
            $font->file($this->fontPath);
            $font->size(16);
            $font->color('#333333');
            $font->align('center');
            $font->valign('middle');
        });

        $img->rectangle(80, 110, 440, 112, function ($draw) {
            $draw->background('#dddddd');
        });

        $lines = [
            ['label' => 'Membre', 'value' => $userName],
            ['label' => 'Tontine', 'value' => $tontineName],
            ['label' => 'Montant', 'value' => $montant.' FCFA'],
            ['label' => 'Date', 'value' => $date],
            ['label' => 'Statut', 'value' => 'Confirmé'],
        ];

        $y = 140;
        foreach ($lines as $line) {
            $img->text($line['label'].' :', 50, $y, function ($font) {
                $font->file($this->fontPath);
                $font->size(14);
                $font->color('#888888');
                $font->align('left');
                $font->valign('middle');
            });

            $img->text($line['value'], 310, $y, function ($font) use ($line) {
                $font->file($this->fontPath);
                $font->size(14);
                $font->color($line['label'] === 'Statut' ? '#2e7d32' : '#333333');
                $font->align('right');
                $font->valign('middle');
            });

            $y += 32;
        }

        $img->rectangle(80, $y + 10, 440, $y + 12, function ($draw) {
            $draw->background('#dddddd');
        });

        $img->text('Merci pour votre ponctualité !', 260, 305, function ($font) {
            $font->file($this->fontPath);
            $font->size(12);
            $font->color('#999999');
            $font->align('center');
            $font->valign('middle');
        });

        $filename = 'receipts/receipt_'.uniqid().'.png';
        Storage::disk('public')->put($filename, $img->encode('png'));

        return Storage::disk('public')->path($filename);
    }

    public function getSignaturePath(): string
    {
        $filename = 'signature.png';

        if (Storage::disk('public')->exists($filename)) {
            return Storage::disk('public')->path($filename);
        }

        if (! $this->manager) {
            return '';
        }

        $img = $this->manager->canvas(300, 60, '#1a73e8');

        $img->rectangle(0, 0, 299, 59, function ($draw) {
            $draw->background('#1a73e8');
        });

        $img->text('TontineSN', 150, 22, function ($font) {
            $font->file($this->fontPath);
            $font->size(16);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        $img->text("L'épargne collective, enfin numérique", 150, 42, function ($font) {
            $font->file($this->fontPath);
            $font->size(9);
            $font->color('#c8e6ff');
            $font->align('center');
            $font->valign('middle');
        });

        Storage::disk('public')->put($filename, $img->encode('png'));

        return Storage::disk('public')->path($filename);
    }
}

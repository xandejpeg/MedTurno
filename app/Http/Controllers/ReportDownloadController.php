<?php

namespace App\Http\Controllers;

use App\Services\PdfReportGenerator;
use App\Services\PresentationGenerator;
use Illuminate\Http\Request;

class ReportDownloadController extends Controller
{
    public function __construct(
        private PdfReportGenerator $pdf,
        private PresentationGenerator $ppt,
    ) {}

    public function download(Request $request, string $type, string $format)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $path = match ([$type, $format]) {
            ['roadmap', 'pdf'] => $this->pdf->roadmap(),
            ['roadmap', 'pptx'] => $this->ppt->roadmap(),
            ['financeiro', 'pdf'] => $this->pdf->financeiro(),
            ['financeiro', 'pptx'] => $this->ppt->financeiro(),
            default => abort(404),
        };

        return response()->download($path)->deleteFileAfterSend();
    }
}

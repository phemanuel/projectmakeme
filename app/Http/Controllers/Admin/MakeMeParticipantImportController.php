<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class MakeMeParticipantImportController extends Controller
{
    private const HEADERS = ['first_name','surname','email','mobile_no','skill_category'];
    private const MAX_ROWS = 500;

    public function template()
    {
        $sheetBook = new Spreadsheet();
        $sheet = $sheetBook->getActiveSheet();
        $sheet->setTitle('Participants');
        $sheet->fromArray([self::HEADERS], null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:E1')->getFill()->setFillType('solid')->getStartColor()->setRGB('17171B');
        foreach (range('A','E') as $column) $sheet->getColumnDimension($column)->setWidth($column === 'C' ? 36 : 24);
        $sheet->getStyle('D2:D501')->getNumberFormat()->setFormatCode('@');
        $instructions = $sheetBook->createSheet();
        $instructions->setTitle('Instructions');
        $instructions->fromArray([
            ['Do not change the header order or names.'],
            ['Put participant rows on the Participants sheet.'],
            ['Valid skill_category values: ' . implode(', ', array_keys(MakeMeParticipantController::SKILLS))],
            ['Email must be unique among MakeMe Live participants.'],
            ['No NYFEW account is created or referenced.'],
            ['Participants need activation emails before login.'],
            ['Maximum 500 entries per import.'],
            ['Set phone numbers as text so leading zeros survive.'],
        ], null, 'A1');
        $instructions->getColumnDimension('A')->setWidth(115);
        $writer = new Xlsx($sheetBook);
        return response()->streamDownload(function () use ($writer, $sheetBook) {
            $writer->save('php://output');
            $sheetBook->disconnectWorksheets();
        }, 'makeme-participant-import-template.xlsx', ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function preview(Request $request)
    {
        $request->validate(['file'=>['required','file','max:2048','mimes:xlsx,csv,txt']]);
        $this->clearStage($request);
        $path = $request->file('file')->store('makeme-imports');
        try {
            $analysis = $this->analyse($this->readRows(Storage::path($path)));
            $token = Str::random(48);
            $request->session()->put('makeme_import', [
                'path'=>$path, 'token'=>$token, 'admin_id'=>$request->user()->id, 'created'=>time(),
            ]);
            return view('admin.makeme.participants.import-preview', compact('analysis','token'));
        } catch (Throwable $e) {
            Storage::delete($path);
            if ($e instanceof ValidationException) throw $e;
            report($e);
            return back()->withErrors(['file'=>'File could not be read. Please use the provided template.']);
        }
    }

    public function confirm(Request $request)
    {
        $request->validate(['token'=>['required','string','size:48']]);
        $stage = $request->session()->get('makeme_import');
        abort_unless($stage && hash_equals($stage['token'], $request->input('token'))
            && (int) $stage['admin_id'] === (int) $request->user()->id
            && time() - $stage['created'] <= 1200 && Storage::exists($stage['path']),
            419, 'Import preview has expired. Upload again.');
        try {
            $analysis = $this->analyse($this->readRows(Storage::path($stage['path'])));
            if ($analysis['errors']) {
                throw ValidationException::withMessages(['file'=>'Spreadsheet changed or contains invalid/duplicate records. Upload again.']);
            }
            DB::transaction(function () use ($analysis) {
                foreach ($analysis['rows'] as $row) {
                    $participant = MakeMeParticipant::create([
                        'first_name'=>$row['first_name'], 'surname'=>$row['surname'],
                        'email'=>$row['email'], 'mobile_no'=>$row['mobile_no'],
                        'skill_category'=>$row['skill_category'],
                        'status'=>'active', 'approved_at'=>now(), 'password'=>null,
                    ]);
                    $participant->participant_number = 'MKL-' . str_pad((string) $participant->id, 5, '0', STR_PAD_LEFT);
                    $participant->save();

                    MakeMeActivityLogger::record(
                        'participants',
                        'imported',
                        'Participant import completed',
                        null,
                        [
                            'imported_count' => $importedCount,
                            'skipped_count' => $skippedCount,
                        ]
                    );
                }
            });
            $this->clearStage($request);
            return redirect()->route('admin.makeme.participants.index')
                ->with('success', $analysis['count'].' participants imported. Send account activation invitations when ready.');
        } catch (ValidationException $e) {
            $this->clearStage($request);
            throw $e;
        } catch (Throwable $e) {
            $this->clearStage($request);
            report($e);
            return redirect()->route('admin.makeme.participants.index')
                ->withErrors(['file'=>'Import was cancelled and no partial records were saved. Check the log and spreadsheet.']);
        }
    }

    private function readRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        try {
            $sheet = $book->getSheet(0);
            $end = $sheet->getHighestDataRow();
            if ($end > self::MAX_ROWS + 1) throw ValidationException::withMessages(['file'=>'Maximum 500 rows allowed.']);
            $headers = [];
            foreach (range('A','E') as $column) {
                $headers[] = strtolower(trim((string) $sheet->getCell($column.'1')->getValue()));
            }
            if ($headers !== self::HEADERS) throw ValidationException::withMessages(['file'=>'Headers do not match the downloaded template.']);
            $rows = [];
            for ($line = 2; $line <= $end; $line++) {
                $cells = [];
                foreach (range('A','E') as $column) {
                    $cell = $sheet->getCell($column.$line);
                    if ($cell->isFormula()) throw ValidationException::withMessages(['file'=>"Formulas are not allowed (row {$line})."]);
                    $cells[] = trim((string) ($cell->getValue() ?? ''));
                }
                if (count(array_filter($cells, fn ($v)=>$v!=='')) === 0) continue;
                $rows[] = array_combine(self::HEADERS, $cells) + ['line'=>$line];
            }
            if (!$rows) throw ValidationException::withMessages(['file'=>'No participant rows found.']);
            return $rows;
        } finally { $book->disconnectWorksheets(); }
    }

    private function analyse(array $rows): array
    {
        $emails = array_map(fn ($r)=>strtolower(trim($r['email'])), $rows);
        $existing = MakeMeParticipant::whereIn('email',array_unique($emails))->pluck('email')->map(fn ($e)=>strtolower($e))->flip();
        $seen = []; $errors = []; $valid = [];
        foreach ($rows as $row) {
            $email = strtolower($row['email']); $issues = [];
            if (!$row['first_name'] || mb_strlen($row['first_name']) > 100) $issues[] = 'Invalid first name';
            if (!$row['surname'] || mb_strlen($row['surname']) > 100) $issues[] = 'Invalid surname';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email)>255) $issues[] = 'Invalid email';
            if (!preg_match('/^[+0-9() .-]{7,25}$/', $row['mobile_no'])) $issues[] = 'Invalid phone';
            if (!isset(MakeMeParticipantController::SKILLS[$row['skill_category']])) $issues[] = 'Invalid skill category';
            if ($existing->has($email)) $issues[] = 'Already exists in MakeMe Live';
            if (isset($seen[$email])) $issues[] = 'Duplicate email in sheet at row '.$seen[$email];
            $seen[$email] = $row['line'];
            if ($issues) $errors[] = ['line'=>$row['line'],'email'=>$email,'message'=>implode('; ', $issues)];
            else { $row['email'] = $email; $valid[] = $row; }
        }
        return ['rows'=>$valid, 'errors'=>$errors,'count'=>count($rows)];
    }

    private function clearStage(Request $request): void
    {
        $stage = $request->session()->pull('makeme_import');
        if ($stage && !empty($stage['path'])) Storage::delete($stage['path']);
    }
}

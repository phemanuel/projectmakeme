<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeParticipant;
use App\Services\MakeMeActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class MakeMeParticipantImportController extends Controller
{
    private const LEGACY_HEADERS = [
        'first_name', 'surname', 'email', 'mobile_no', 'skill_category',
    ];

    private const HEADERS = [
        'first_name', 'surname', 'other_name', 'email', 'mobile_no',
        'skill_category', 'status', 'business_name', 'profession', 'age',
        'city', 'state_of_origin', 'alternative_email', 'instagram_handle',
        'tiktok_handle', 'facebook_handle', 'x_handle', 'linkedin_handle',
    ];

    private const MAX_ROWS = 500;

    private const OPTIONAL_LENGTHS = [
        'other_name' => 100,
        'business_name' => 180,
        'profession' => 150,
        'city' => 120,
        'state_of_origin' => 120,
        'instagram_handle' => 150,
        'tiktok_handle' => 150,
        'facebook_handle' => 255,
        'x_handle' => 255,
        'linkedin_handle' => 255,
    ];

    public function template()
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Participants');
        $sheet->fromArray([self::HEADERS], null, 'A1');
        $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADERS));
        $headerRange = 'A1:' . $lastColumn . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('F1D17A');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('17171B');
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($headerRange);

        foreach (self::HEADERS as $index => $field) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getColumnDimension($column)->setWidth(in_array($field, ['email', 'alternative_email'], true) ? 36 : 24);
            // Never let Excel silently drop leading zeros from telephone numbers.
            if ($field === 'mobile_no') {
                $sheet->getStyle("{$column}2:{$column}501")
                    ->getNumberFormat()->setFormatCode('@');
            }
        }

        $instructions = $book->createSheet();
        $instructions->setTitle('Instructions');
        $instructions->fromArray([
            ['Project MakeMe Live — Participant Import'],
            ['Use the Participants worksheet. Keep header names AND their order unchanged.'],
            ['Required: first_name, surname, email, mobile_no, skill_category.'],
            ['All other columns are optional; leave unknown values empty.'],
            ['status: active or inactive. Blank status defaults to active.'],
            ['Valid skill_category values: ' . implode(', ', array_keys(MakeMeParticipantController::SKILLS))],
            ['Each email must be unique among MakeMe Live participants and within the uploaded sheet.'],
            ['Maximum 500 participant rows per import.'],
            ['Set phone numbers as text to retain leading zeros.'],
            ['age must be a whole number from 1 to 120 (when provided).'],
            ['Do not include formulas or contestant photos in this sheet.'],
            ['The importer does not create NYFEW user accounts. Invitations are sent separately.'],
            ['Legacy five-column templates are still accepted.'],
        ], null, 'A1');
        $instructions->getColumnDimension('A')->setWidth(115);
        $instructions->getStyle('A1')->getFont()->setBold(true);

        $writer = new Xlsx($book);
        return response()->streamDownload(function () use ($writer, $book) {
            try {
                $writer->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'makeme-participant-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:2048', 'mimes:xlsx,csv,txt']]);
        $this->clearStage($request);
        $path = $request->file('file')->store('makeme-imports');

        try {
            $analysis = $this->analyse($this->readRows(Storage::path($path)));
            $token = Str::random(48);
            $request->session()->put('makeme_import', [
                'path' => $path,
                'token' => $token,
                'admin_id' => auth('web')->id(),
                'created' => time(),
            ]);
            return view('admin.makeme.participants.import-preview', compact('analysis', 'token'));
        } catch (Throwable $e) {
            Storage::delete($path);
            if ($e instanceof ValidationException) {
                throw $e;
            }
            report($e);
            return back()->withErrors(['file' => 'File could not be read. Please use the provided template.']);
        }
    }

    public function confirm(Request $request)
    {
        $request->validate(['token' => ['required', 'string', 'size:48']]);
        $stage = $request->session()->get('makeme_import');
        abort_unless(
            is_array($stage)
                && isset($stage['token'], $stage['admin_id'], $stage['created'], $stage['path'])
                && hash_equals((string) $stage['token'], (string) $request->input('token'))
                && (int) $stage['admin_id'] === (int) auth('web')->id()
                && time() - (int) $stage['created'] <= 1200
                && Storage::exists($stage['path']),
            419,
            'Import preview has expired. Upload again.'
        );

        try {
            $analysis = $this->analyse($this->readRows(Storage::path($stage['path'])));
            if ($analysis['errors']) {
                throw ValidationException::withMessages([
                    'file' => 'The spreadsheet has invalid or duplicate records. Correct it and upload again.',
                ]);
            }

            DB::transaction(function () use ($analysis) {
                // Guard against a simultaneous import creating the same email since preview.
                // The email unique index must remain in place as the final concurrency safeguard.
                $emails = array_column($analysis['rows'], 'email');
                if (MakeMeParticipant::query()->whereIn('email', $emails)->exists()) {
                    throw ValidationException::withMessages([
                        'file' => 'A participant email already exists. Upload again to review duplicates.',
                    ]);
                }

                foreach ($analysis['rows'] as $row) {
                    $status = $row['status'] ?? 'active';
                    $data = [
                        'first_name' => $row['first_name'],
                        'surname' => $row['surname'],
                        'email' => $row['email'],
                        'mobile_no' => $row['mobile_no'],
                        'skill_category' => $row['skill_category'],
                        'status' => $status,
                        'approved_at' => $status === 'active' ? now() : null,
                        'password' => null,
                    ];
                    foreach (array_keys(self::OPTIONAL_LENGTHS) as $field) {
                        $data[$field] = $row[$field] ?? null;
                    }
                    $data['age'] = $row['age'] ?? null;
                    $data['alternative_email'] = $row['alternative_email'] ?? null;

                    $participant = MakeMeParticipant::create($data);
                    $participant->participant_number = 'MKL-' . str_pad((string) $participant->getKey(), 5, '0', STR_PAD_LEFT);
                    $participant->save();
                }

                MakeMeActivityLogger::record(
                    'participants',
                    'imported',
                    'Participant import completed',
                    null,
                    [
                        'imported_count' => count($analysis['rows']),
                        'skipped_count' => 0,
                        'format' => 'excel_or_csv',
                    ]
                );
            }, 3);

            $this->clearStage($request);
            return redirect()->route('admin.makeme.participants.index')
                ->with('success', count($analysis['rows']) . ' participants imported. Send activation invitations when ready.');
        } catch (ValidationException $e) {
            $this->clearStage($request);
            throw $e;
        } catch (Throwable $e) {
            $this->clearStage($request);
            report($e);
            return redirect()->route('admin.makeme.participants.index')
                ->withErrors(['file' => 'Import failed. No partial records were saved. Check the log and spreadsheet.']);
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
            if ($end > self::MAX_ROWS + 1) {
                throw ValidationException::withMessages(['file' => 'Maximum 500 rows allowed.']);
            }

            $lastColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            if ($lastColumnIndex > count(self::HEADERS)) {
                throw ValidationException::withMessages(['file' => 'Unexpected columns found. Use the downloaded template.']);
            }
            $headers = [];
            for ($index = 1; $index <= $lastColumnIndex; $index++) {
                $letter = Coordinate::stringFromColumnIndex($index);
                $headers[] = strtolower(trim((string) $sheet->getCell($letter . '1')->getValue()));
            }

            // Keep support for previously downloaded 5-column files.
            if ($headers !== self::HEADERS && $headers !== self::LEGACY_HEADERS) {
                throw ValidationException::withMessages(['file' => 'Headers do not match the downloaded template.']);
            }

            $rows = [];
            for ($line = 2; $line <= $end; $line++) {
                $cells = [];
                foreach ($headers as $index => $field) {
                    $letter = Coordinate::stringFromColumnIndex($index + 1);
                    $cell = $sheet->getCell($letter . $line);
                    if ($cell->isFormula()) {
                        throw ValidationException::withMessages(['file' => "Formulas are not allowed (row {$line})."]);
                    }
                    // Use formatted display for phone numbers so Excel numeric cells can
                    // retain their displayed leading zeros when a number format exists.
                    $value = $field === 'mobile_no'
                        ? $cell->getFormattedValue()
                        : $cell->getValue();
                    $cells[] = trim((string) ($value ?? ''));
                }
                if (!array_filter($cells, static fn ($value) => $value !== '')) {
                    continue;
                }
                $rows[] = array_combine($headers, $cells) + ['line' => $line];
            }
            if (!$rows) {
                throw ValidationException::withMessages(['file' => 'No participant rows found.']);
            }
            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private function analyse(array $rows): array
    {
        $emails = array_map(static fn ($r) => strtolower(trim($r['email'] ?? '')), $rows);
        $existing = MakeMeParticipant::query()->whereIn('email', array_unique($emails))
            ->pluck('email')->map(static fn ($email) => strtolower($email))->flip();

        $seen = [];
        $errors = [];
        $valid = [];

        foreach ($rows as $row) {
            // Convert empty optional spreadsheet cells into null values.
            foreach (self::HEADERS as $field) {
                if (array_key_exists($field, $row) && $row[$field] === '') {
                    $row[$field] = null;
                }
            }
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $issues = [];

            if (empty($row['first_name']) || mb_strlen((string) $row['first_name']) > 100) $issues[] = 'Invalid first name';
            if (empty($row['surname']) || mb_strlen((string) $row['surname']) > 100) $issues[] = 'Invalid surname';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) $issues[] = 'Invalid email';
            if (!preg_match('/^[+0-9() .-]{7,25}$/', (string) ($row['mobile_no'] ?? ''))) $issues[] = 'Invalid phone';
            if (!isset(MakeMeParticipantController::SKILLS[$row['skill_category'] ?? ''])) $issues[] = 'Invalid skill category';

            foreach (self::OPTIONAL_LENGTHS as $field => $maximum) {
                if (isset($row[$field]) && mb_strlen((string) $row[$field]) > $maximum) {
                    $issues[] = "{$field} exceeds {$maximum} characters";
                }
            }

            if (isset($row['age']) && (!ctype_digit((string) $row['age']) || (int) $row['age'] < 1 || (int) $row['age'] > 120)) {
                $issues[] = 'Age must be a whole number between 1 and 120';
            }
            if (isset($row['alternative_email']) && (
                mb_strlen((string) $row['alternative_email']) > 255
                || !filter_var($row['alternative_email'], FILTER_VALIDATE_EMAIL)
            )) {
                $issues[] = 'Invalid alternative email';
            }
            $status = strtolower(trim((string) ($row['status'] ?? 'active')));
            if (!in_array($status, ['active', 'inactive'], true)) {
                $issues[] = 'Invalid status (use active or inactive)';
            }
            $row['status'] = $status;

            if ($existing->has($email)) $issues[] = 'Already exists in MakeMe Live';
            if (isset($seen[$email])) $issues[] = 'Duplicate email in sheet at row ' . $seen[$email];
            $seen[$email] = $row['line'];

            if ($issues) {
                $errors[] = ['line' => $row['line'], 'email' => $email, 'message' => implode('; ', $issues)];
            } else {
                $row['email'] = $email;
                $valid[] = $row;
            }
        }

        return ['rows' => $valid, 'errors' => $errors, 'count' => count($rows)];
    }

    private function clearStage(Request $request): void
    {
        $stage = $request->session()->pull('makeme_import');
        if (is_array($stage) && !empty($stage['path'])) {
            Storage::delete($stage['path']);
        }
    }
}

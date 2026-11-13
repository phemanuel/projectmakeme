@extends('admin.makeme.layouts.app')
@section('title', 'Preview participant import')
@section('content')
<section class="page-intro"><div><div class="intro-kicker">SHOW OPERATIONS / PARTICIPANTS</div><h1>Preview Import<span class="gold-period">.</span></h1><p>Review the rows before confirming.</p></div><a class="btn btn-outline" href="{{ route('admin.makeme.participants.index') }}">← Back</a></section>
<div class="stat-grid"><article class="stat-card"><span class="stat-label">ROWS</span><div class="stat-value">{{ $analysis['count'] }}</div></article><article class="stat-card"><span class="stat-label">VALID</span><div class="stat-value">{{ count($analysis['rows']) }}</div></article><article class="stat-card"><span class="stat-label">ERRORS</span><div class="stat-value">{{ count($analysis['errors']) }}</div></article></div>
<section class="panel" style="padding:24px;margin-top:24px">
@if(count($analysis['errors']))
<div class="notice error">Fix the following rows. No records will be saved yet.</div>
<div class="table-scroll"><table class="participant-table"><thead><tr><th>ROW</th><th>EMAIL</th><th>ISSUE</th></tr></thead><tbody>@foreach($analysis['errors'] as $error)<tr><td>{{ $error['line'] }}</td><td>{{ $error['email'] }}</td><td>{{ $error['message'] }}</td></tr>@endforeach</tbody></table></div>
<a href="{{ route('admin.makeme.participants.index') }}" class="btn btn-gold">Correct and upload again</a>
@else
<div class="notice success">All rows are valid. These profiles will be created independently of NYFEW user accounts. Activation emails are sent separately.</div>
<div class="table-scroll"><table class="participant-table"><thead><tr><th>ROW</th><th>NAME</th><th>EMAIL</th><th>PHONE</th><th>SKILL</th></tr></thead><tbody>@foreach(array_slice($analysis['rows'], 0, 40) as $row)<tr><td>{{ $row['line'] }}</td><td>{{ $row['first_name'] }} {{ $row['surname'] }}</td><td>{{ $row['email'] }}</td><td>{{ $row['mobile_no'] }}</td><td>{{ \App\Http\Controllers\Admin\MakeMeParticipantController::SKILLS[$row['skill_category']] }}</td></tr>@endforeach</tbody></table></div>
@if(count($analysis['rows'])>40)<p>Showing first 40 rows.</p>@endif
<form method="POST" action="{{ route('admin.makeme.participants.import.confirm') }}" style="padding:18px 0">@csrf<input type="hidden" name="token" value="{{ $token }}"><button type="submit" class="btn btn-gold">Confirm and import {{ $analysis['count'] }} participants</button></form>
@endif
</section>
@endsection

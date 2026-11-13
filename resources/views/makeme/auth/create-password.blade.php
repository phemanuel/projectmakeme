
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Create Password | Project MakeMe Live</title>

    <link rel="icon"
          href="{{ asset('images/makeme-favicon.png') }}">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #0B0B0D;
            color: #FAF8F2;
            font-family: Arial, sans-serif;
            padding: 24px;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: #17171B;
            border: 1px solid rgba(214,173,88,.3);
            border-radius: 20px;
            padding: 35px;
        }

        .logo {
            display: block;
            max-width: 160px;
            margin: 0 auto 25px;
        }

        h1 {
            color: #F5DE94;
            text-align: center;
            font-size: 25px;
        }

        .subtitle {
            color: #BDBDBD;
            text-align: center;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 14px;
            background: #242429;
            color: white;
            border: 1px solid #454545;
            border-radius: 9px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #D6AD58;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 15px;
            background: #D6AD58;
            color: #111;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #F5DE94; }

        .error {
            background: rgba(220,53,69,.12);
            color: #FF9A9A;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

<div class="auth-card">

    <img src="{{ asset('images/nyfew-logo.jpeg') }}"
         alt="NYFEW Project MakeMe Live"
         class="logo">

    <h1>Create Your Password</h1>

    <p class="subtitle">
        Welcome, {{ $participant->first_name }}!
        Secure your Project MakeMe Live participant account.
    </p>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST"
          action="{{ route('makeme.invitation.store') }}">

        @csrf

        <input type="hidden" name="token"
               value="{{ $token }}">

        <input type="hidden" name="email"
               value="{{ $email }}">

        <label for="password">Create Password</label>
        <input type="password"
               id="password"
               name="password"
               minlength="8"
               autocomplete="new-password"
               required>

        <label for="password_confirmation">
            Confirm Password
        </label>
        <input type="password"
               id="password_confirmation"
               name="password_confirmation"
               minlength="8"
               autocomplete="new-password"
               required>

        <button type="submit">
            Activate My Account
        </button>

    </form>

</div>

</body>
</html>

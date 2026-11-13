<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B0B0D">
    <title>Sign In | NYFEW Project MakeMe Live</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/makeme-signin.css') }}">
</head>
<body>
    <main class="signin-shell">
        <section class="show-panel" aria-label="Project MakeMe Live welcome">
            <div class="show-panel__glow"></div>
            <div class="show-panel__top">
                <!-- <span class="eyebrow"><span class="live-dot"></span> NYFEW ORIGINAL PRODUCTION</span> -->
                <span class="season-tag">PROJECT MAKEME LIVE</span>
            </div>
            <div class="show-panel__center">
                <img src="{{ asset('images/nyfew-logo.jpeg') }}" alt="NYFEW Project MakeMe Reality TV Show logo" class="show-logo">
                <div class="show-panel__copy">
                    <p class="eyebrow eyebrow--gold">THE CREATIVE EXPERIENCE</p>
                    <h1>Every talent<br>deserves <em>a stage.</em></h1>
                    <p>Welcome to the home of creators, makers and bold ideas. Your next chapter starts here.</p>
                </div>
            </div>
            <div class="show-panel__bottom">
                <span>FASHION</span><span class="point">✦</span><span>BEAUTY</span><span class="point">✦</span><span>PHOTOGRAPHY</span><span class="point">✦</span><span>CRAFT</span>✦</span><span>MODELLING</span>
            </div>
        </section>

        <section class="form-panel">
            <div class="form-wrap">
                <div class="mobile-brand">
                    <span class="brand-mark">NYFEW</span><span class="mobile-brand__name">PROJECT MAKEME LIVE</span>
                </div>
                <div class="form-heading">
                    <!-- <span class="eyebrow eyebrow--gold">YOUR SHOW PORTAL</span> -->
                    <h2>Welcome back<span class="gold-period">.</span></h2>
                    <p>Sign in to access your MakeMe Live experience.</p>
                </div>

                @if(session('success'))
                    <div class="alert alert--success" role="status">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert--error" role="alert">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert--error" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('makeme.login.submit') }}" class="login-form">
                    @csrf
                    <div class="form-field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="you@example.com" required autofocus>
                    </div>
                    <div class="form-field">
                        <div class="label-row">
                            <label for="password">Password</label>
                            <a href="{{ route('password.request') }}" class="subtle-link">Forgot password?</a>
                        </div>
                        <div class="password-wrap">
                            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                            <button class="password-toggle" type="button" id="passwordToggle" aria-label="Show password" aria-pressed="false">Show</button>
                        </div>
                    </div>
                    <label class="remember-row"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}><span>Keep me signed in</span></label>
                    <button type="submit" class="submit-btn">Enter MakeMe Live <span aria-hidden="true">↗</span></button>
                </form>

                <div class="login-note">
                    <span class="login-note__icon" aria-hidden="true">✦</span>
                    <p>Already registered on NYFEW? Use the same email and password. Portal access is for approved participants and administrators.</p>
                </div>
                <p class="back-link"><a href="{{ route('home') }}">← Back to NYFEW website</a></p>
            </div>
            <footer class="form-footer">© {{ date('Y') }} NYFEW. Project MakeMe Live.</footer>
        </section>
    </main>
    <script>
        (() => {
            const toggle = document.getElementById('passwordToggle');
            const input = document.getElementById('password');
            toggle.addEventListener('click', () => {
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                toggle.textContent = visible ? 'Show' : 'Hide';
                toggle.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
                toggle.setAttribute('aria-pressed', String(!visible));
            });
        })();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login | BrightStar LMS</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100">
        <div class="mx-auto flex min-h-screen max-w-5xl items-center justify-center px-4 py-12">
            <div class="grid w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl lg:grid-cols-2">
                <div class="hidden bg-gradient-to-br from-blue-900 via-blue-800 to-sky-600 p-10 text-white lg:block">
                    <p class="text-sm uppercase tracking-[0.24em] text-blue-100">BrightStar Academy</p>
                    <h1 class="mt-8 text-3xl font-bold">Welcome back.</h1>
                    <p class="mt-4 max-w-sm text-blue-100">Access the school portal for classes, attendance, results, and communication.</p>
                </div>
                <div class="p-8 sm:p-10">
                    <h2 class="text-2xl font-bold text-slate-900">Sign in</h2>
                    <p class="mt-2 text-sm text-slate-500">Use your email and password to continue.</p>

                    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                        @csrf

                        @if ($errors->any())
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                                {{ $errors->first('email') ?: 'We could not sign you in. Check your email and password and try again.' }}
                            </div>
                        @endif

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                            <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200" />
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <label class="inline-flex items-center gap-2 text-slate-600">
                                <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Remember me
                            </label>
                            <a href="{{ route('password.request') }}" class="font-medium text-blue-700">Forgot password?</a>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            Sign in
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>

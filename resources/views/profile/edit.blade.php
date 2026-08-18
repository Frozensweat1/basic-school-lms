<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile settings</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 p-4 text-slate-900 sm:p-8">
    <main class="mx-auto max-w-xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <a href="{{ route('lms.dashboard') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Back to dashboard</a>
        <h1 class="mt-5 text-2xl font-bold">Profile settings</h1>
        <p class="mt-1 text-sm text-slate-600">Update your account details.</p>
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')
            <div><label for="name" class="block text-sm font-medium">Name</label><input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"></div>
            <div><label for="email" class="block text-sm font-medium">Email</label><input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600"></div>
            <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Save changes</button>
        </form>
    </main>
</body>
</html>

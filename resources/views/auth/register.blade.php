<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Register — {{ config('app.name', 'Desk') }}</title>
        <x-corner-boot />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex h-screen flex-col bg-canvas font-sans text-ink antialiased">
        <header class="flex h-16 shrink-0 items-center justify-between border-b border-line bg-card px-6">
            <a href="{{ url('/') }}" class="text-lg font-semibold tracking-wide">Desk</a>
            <a href="{{ route('login') }}" class="rounded-xl border border-line bg-canvas px-4 py-2 text-sm font-semibold">Log in</a>
        </header>

        <main class="mx-auto flex min-h-0 w-full max-w-3xl flex-1 flex-col px-4 py-4">
            <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-line bg-card" data-arrow-pane>
                <div class="border-b border-line px-6 py-4">
                    <h1 class="text-2xl font-semibold">Register</h1>
                    <p class="mt-1 text-sm text-ink/70">Send your details. The account stays closed until someone approves it and gives you a role.</p>
                </div>
                <button type="button" class="side-arrow w-full border-b border-line bg-soft" data-scroll-up hidden aria-label="Form above">
                    <svg viewBox="0 0 16 16" class="mx-auto h-4 w-4" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </button>
                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="arrow-scroll min-h-0 flex-1 px-6 py-5" data-arrow-scroll>
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm font-semibold">Name
                            <input name="name" value="{{ old('name') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                            @error('name')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-1 text-sm font-semibold">Name in Urdu
                            <input name="name_ur" value="{{ old('name_ur') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                            @error('name_ur')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-1 text-sm font-semibold sm:col-span-2">Email
                            <input name="email" type="email" value="{{ old('email') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                            @error('email')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-1 text-sm font-semibold">Address
                            <textarea name="address" rows="3" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">{{ old('address') }}</textarea>
                            @error('address')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-1 text-sm font-semibold">Address in Urdu
                            <textarea name="address_ur" rows="3" required class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">{{ old('address_ur') }}</textarea>
                            @error('address_ur')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-1 text-sm font-semibold">Password
                            <input name="password" type="password" required minlength="8" autocomplete="new-password" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                            @error('password')<span class="font-normal text-red-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-1 text-sm font-semibold">Confirm password
                            <input name="password_confirmation" type="password" required autocomplete="new-password" class="rounded-xl border border-line bg-canvas px-4 py-3 font-normal">
                        </label>
                    </div>

                    <fieldset class="mt-6 grid gap-3">
                        <legend class="text-sm font-semibold">Phone</legend>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <label class="grid gap-1 text-sm">Contact person
                                <input name="phones[0][contact_person]" value="{{ old('phones.0.contact_person') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            </label>
                            <label class="grid gap-1 text-sm">Contact person in Urdu
                                <input name="phones[0][contact_person_ur]" value="{{ old('phones.0.contact_person_ur') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            </label>
                            <label class="grid gap-1 text-sm">Phone number
                                <input name="phones[0][phone_number]" value="{{ old('phones.0.phone_number') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            </label>
                        </div>
                        @error('phones.*.phone_number')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                    </fieldset>

                    <fieldset class="mt-6 grid gap-3">
                        <legend class="text-sm font-semibold">Bank</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="grid gap-1 text-sm">Bank
                                <select name="banks[0][bank_name]" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                                    <option value="">Choose a bank</option>
                                    @foreach (\App\Support\PakistaniBanks::names() as $bank)
                                        <option value="{{ $bank }}" @selected(old('banks.0.bank_name') === $bank)>{{ $bank }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm">Account number
                                <input name="banks[0][account_number]" value="{{ old('banks.0.account_number') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            </label>
                            <label class="grid gap-1 text-sm">Account title
                                <input name="banks[0][account_title]" value="{{ old('banks.0.account_title') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            </label>
                            <label class="grid gap-1 text-sm">Account title in Urdu
                                <input name="banks[0][account_title_ur]" value="{{ old('banks.0.account_title_ur') }}" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            </label>
                        </div>
                        @error('banks.*.bank_name')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                    </fieldset>

                    <fieldset class="mt-6 grid gap-3">
                        <legend class="text-sm font-semibold">Identity</legend>
                        <label class="grid gap-1 text-sm">Your photo
                            <input name="profile_photo" type="file" accept="image/*" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            @error('profile_photo')<span class="text-red-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-1 text-sm">Identity card, front
                            <input name="cnic_front" type="file" accept="image/*" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            @error('cnic_front')<span class="text-red-700">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-1 text-sm">Identity card, back
                            <input name="cnic_back" type="file" accept="image/*" required class="rounded-xl border border-line bg-canvas px-4 py-3">
                            @error('cnic_back')<span class="text-red-700">{{ $message }}</span>@enderror
                        </label>
                    </fieldset>

                    <label class="mt-6 flex items-start gap-2 text-sm">
                        <input type="checkbox" name="terms" value="1" required class="mt-1" @checked(old('terms'))>
                        I confirm these details and the identity card are mine.
                    </label>
                    @error('terms')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror

                    <button type="submit" class="mt-6 rounded-xl bg-primary px-5 py-3 text-sm font-semibold">Send for approval</button>
                </form>
                <button type="button" class="side-arrow w-full border-t border-line bg-soft" data-scroll-down hidden aria-label="Form below">
                    <svg viewBox="0 0 16 16" class="mx-auto h-4 w-4" aria-hidden="true"><path d="M3.5 5.5 8 10l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </button>
            </section>
        </main>
    </body>
</html>

<x-layouts.shell title="Home" :user="$user">
    <h1 class="text-4xl font-semibold">Welcome, {{ $user->name }}.</h1>
    <p class="mt-3 max-w-xl text-lg leading-8">Trading, the store, and business sit in this one desk.</p>

    <section class="mt-10 grid gap-4 lg:grid-cols-3">
        <article id="trading" class="rounded-2xl border border-line bg-card px-6 py-8">
            <h2 class="text-xl font-semibold">Trading</h2>
            <p class="mt-3 leading-7 text-ink/80">Rates, orders, and the ledger.</p>
        </article>
        <article id="store" class="rounded-2xl border border-line bg-soft px-6 py-8">
            <h2 class="text-xl font-semibold">Store</h2>
            <p class="mt-3 leading-7 text-ink/80">The online shop.</p>
        </article>
        <article id="business" class="rounded-2xl border border-line bg-card px-6 py-8">
            <h2 class="text-xl font-semibold">Business</h2>
            <p class="mt-3 leading-7 text-ink/80">The business books.</p>
        </article>
    </section>
</x-layouts.shell>
